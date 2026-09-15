#!/usr/bin/env python3
"""Safely inspect, synchronize, and deploy the configured WordPress theme."""

from __future__ import annotations

import argparse
import hashlib
import json
import os
import posixpath
import shutil
import ssl
import sys
import tempfile
import uuid
from contextlib import contextmanager
from dataclasses import dataclass
from datetime import datetime, timezone
from ftplib import FTP_TLS, all_errors, error_perm
from pathlib import Path, PurePosixPath
from typing import BinaryIO, Iterator, Mapping, Sequence


REPOSITORY_ROOT = Path(__file__).resolve().parents[1]
CONFIG_PATH = REPOSITORY_ROOT / ".codex" / "theme-deploy.json"
ENV_PATH = REPOSITORY_ROOT / ".env"
BACKUP_ROOT = REPOSITORY_ROOT / "backups"
TRANSFER_CHUNK_SIZE = 128 * 1024
HEADER_READ_LIMIT = 128 * 1024


class ThemeFtpError(RuntimeError):
    """A safe, user-facing FTP workflow error."""


@dataclass(frozen=True)
class Config:
    local_theme_dir: Path
    remote_theme_dir: str
    theme_slug: str


@dataclass(frozen=True)
class Diff:
    added: tuple[str, ...]
    modified: tuple[str, ...]
    remote_only: tuple[str, ...]

    @property
    def upload_paths(self) -> tuple[str, ...]:
        return self.added + self.modified


def load_dotenv(path: Path) -> dict[str, str]:
    values: dict[str, str] = {}
    if not path.is_file():
        raise ThemeFtpError(f"Missing credentials file: {path}")

    for line_number, raw_line in enumerate(path.read_text(encoding="utf-8").splitlines(), 1):
        line = raw_line.strip()
        if not line or line.startswith("#"):
            continue
        if line.startswith("export "):
            line = line[7:].lstrip()
        if "=" not in line:
            raise ThemeFtpError(f"Invalid .env entry on line {line_number}")
        key, value = line.split("=", 1)
        key = key.strip()
        value = value.strip()
        if len(value) >= 2 and value[0] == value[-1] and value[0] in {"'", '"'}:
            value = value[1:-1]
        values[key] = value
    return values


def load_config(path: Path = CONFIG_PATH) -> Config:
    try:
        raw = json.loads(path.read_text(encoding="utf-8"))
    except (OSError, json.JSONDecodeError) as exc:
        raise ThemeFtpError(f"Unable to read {path}: {exc}") from exc

    local_value = raw.get("local_theme_dir")
    remote_value = raw.get("remote_theme_dir")
    slug = raw.get("theme_slug")
    if not all(isinstance(value, str) and value for value in (local_value, remote_value, slug)):
        raise ThemeFtpError("Theme deploy config is missing required string values")

    local_path = safe_local_path(REPOSITORY_ROOT, local_value)
    remote_path = normalize_remote_root(remote_value)
    if PurePosixPath(remote_path).name != slug:
        raise ThemeFtpError("Configured remote theme directory does not match theme_slug")
    return Config(local_path, remote_path, slug)


def normalize_relative_path(value: str) -> str:
    path = PurePosixPath(value.replace("\\", "/"))
    if path.is_absolute() or not path.parts or any(part in {"", ".", ".."} for part in path.parts):
        raise ThemeFtpError(f"Unsafe relative path: {value!r}")
    return path.as_posix()


def normalize_remote_root(value: str) -> str:
    path = PurePosixPath(value)
    if not path.is_absolute() or any(part == ".." for part in path.parts):
        raise ThemeFtpError(f"Unsafe remote theme path: {value!r}")
    normalized = posixpath.normpath(path.as_posix())
    if normalized in {"/", "/wp-content", "/wp-content/themes"}:
        raise ThemeFtpError("Remote theme path is too broad")
    if not normalized.startswith("/wp-content/themes/"):
        raise ThemeFtpError("Remote path must be inside /wp-content/themes/")
    return normalized


def safe_local_path(root: Path, relative: str | Path) -> Path:
    candidate = (root / relative).resolve()
    resolved_root = root.resolve()
    try:
        candidate.relative_to(resolved_root)
    except ValueError as exc:
        raise ThemeFtpError(f"Local path escapes the repository: {relative}") from exc
    return candidate


def local_target(root: Path, relative: str) -> Path:
    return safe_local_path(root, Path(*PurePosixPath(normalize_relative_path(relative)).parts))


def sha256_bytes(content: bytes) -> str:
    return hashlib.sha256(content).hexdigest()


def sha256_file(path: Path) -> str:
    digest = hashlib.sha256()
    with path.open("rb") as stream:
        while chunk := stream.read(TRANSFER_CHUNK_SIZE):
            digest.update(chunk)
    return digest.hexdigest()


def is_transfer_temporary(path: Path) -> bool:
    return path.name.startswith(".ftp-tmp-") or (
        path.name.startswith(".") and ".upload-" in path.name and path.name.endswith(".tmp")
    )


def local_snapshot(theme_root: Path) -> dict[str, str]:
    if not theme_root.exists():
        return {}
    snapshot: dict[str, str] = {}
    for path in sorted(theme_root.rglob("*")):
        if is_transfer_temporary(path):
            continue
        if path.is_symlink():
            raise ThemeFtpError(f"Symlinks are not allowed in the theme: {path}")
        if path.is_file():
            relative = path.relative_to(theme_root).as_posix()
            snapshot[normalize_relative_path(relative)] = sha256_file(path)
    return snapshot


def classify_snapshots(local: Mapping[str, str], remote: Mapping[str, str]) -> Diff:
    local_paths = set(local)
    remote_paths = set(remote)
    return Diff(
        added=tuple(sorted(local_paths - remote_paths)),
        modified=tuple(sorted(path for path in local_paths & remote_paths if local[path] != remote[path])),
        remote_only=tuple(sorted(remote_paths - local_paths)),
    )


def atomic_write(path: Path, content: bytes) -> None:
    path.parent.mkdir(parents=True, exist_ok=True)
    descriptor, temporary_name = tempfile.mkstemp(prefix=".ftp-tmp-", dir=path.parent)
    temporary = Path(temporary_name)
    try:
        with os.fdopen(descriptor, "wb") as stream:
            stream.write(content)
            stream.flush()
            os.fsync(stream.fileno())
        os.replace(temporary, path)
    finally:
        temporary.unlink(missing_ok=True)


def timestamp() -> str:
    return datetime.now(timezone.utc).strftime("%Y%m%dT%H%M%SZ")


def backup_local(path: Path, theme_root: Path, backup_dir: Path) -> None:
    relative = path.relative_to(theme_root)
    target = safe_local_path(backup_dir, relative)
    target.parent.mkdir(parents=True, exist_ok=True)
    shutil.copy2(path, target)


def parse_wp_headers(content: bytes, wanted: Sequence[str]) -> dict[str, str]:
    text = content[:HEADER_READ_LIMIT].decode("utf-8", errors="replace")
    found: dict[str, str] = {}
    keys = {item.lower(): item for item in wanted}
    for raw_line in text.splitlines():
        stripped = raw_line.strip().lstrip("/*# ").rstrip("*/ ")
        if ":" not in stripped:
            continue
        key, value = stripped.split(":", 1)
        canonical = keys.get(key.strip().lower())
        if canonical and canonical not in found:
            found[canonical] = value.strip()
    return found


class FtpsClient:
    def __init__(self, values: Mapping[str, str]):
        required = ("FTP_SERVER", "FTP_ACCOUNT_NAME", "FTP_PASSWORD")
        missing = [key for key in required if not values.get(key)]
        if missing:
            raise ThemeFtpError(f"Missing .env keys: {', '.join(missing)}")
        try:
            self.port = int(values.get("FTP_PORT", "21"))
        except ValueError as exc:
            raise ThemeFtpError("FTP_PORT must be an integer") from exc
        self.host = values["FTP_SERVER"]
        self.tls_server_name = values.get("FTP_TLS_SERVER_NAME", self.host)
        self.username = values["FTP_ACCOUNT_NAME"]
        self.password = values["FTP_PASSWORD"]
        self.ftp: FTP_TLS | None = None

    def __enter__(self) -> "FtpsClient":
        connection = FTP_TLS(context=ssl.create_default_context())
        # Connect through the certificate hostname when an IP-only FTP endpoint
        # presents a valid certificate for its hosting name.
        connection.connect(self.tls_server_name, self.port, timeout=30)
        connection.login(self.username, self.password)
        connection.prot_p()
        self.ftp = connection
        return self

    def __exit__(self, exc_type, exc, traceback) -> None:
        if self.ftp is None:
            return
        try:
            self.ftp.quit()
        except Exception:
            self.ftp.close()
        finally:
            self.ftp = None

    def _connection(self) -> FTP_TLS:
        if self.ftp is None:
            raise ThemeFtpError("FTPS client is not connected")
        return self.ftp

    def list_entries(self, remote_dir: str) -> list[tuple[str, str]]:
        entries: list[tuple[str, str]] = []
        for name, facts in self._connection().mlsd(remote_dir):
            if name not in {".", ".."}:
                entries.append((facts.get("type", "unknown"), name))
        return sorted(entries, key=lambda item: item[1].lower())

    def list_files(self, remote_root: str) -> list[str]:
        root = normalize_remote_root(remote_root)
        files: list[str] = []

        def walk(directory: str, prefix: str = "") -> None:
            for entry_type, name in self.list_entries(directory):
                relative = normalize_relative_path(posixpath.join(prefix, name))
                remote_path = posixpath.join(directory, name)
                if entry_type == "dir":
                    walk(remote_path, relative)
                elif entry_type == "file":
                    files.append(relative)

        walk(root)
        return sorted(files)

    def read_bytes(self, remote_path: str) -> bytes:
        chunks: list[bytes] = []
        self._connection().retrbinary(f"RETR {remote_path}", chunks.append, blocksize=TRANSFER_CHUNK_SIZE)
        return b"".join(chunks)

    def upload_atomic(self, remote_path: str, source: BinaryIO) -> None:
        directory, filename = posixpath.split(remote_path)
        temporary = posixpath.join(directory, f".{filename}.upload-{uuid.uuid4().hex}.tmp")
        self.ensure_directories(directory)
        self._connection().storbinary(f"STOR {temporary}", source, blocksize=TRANSFER_CHUNK_SIZE)
        try:
            self._connection().rename(temporary, remote_path)
        except Exception as exc:
            raise ThemeFtpError(
                f"Uploaded temporary file but could not rename it to {remote_path}; "
                f"the server was left untouched at the target path"
            ) from exc

    def ensure_directories(self, remote_dir: str) -> None:
        connection = self._connection()
        current = ""
        for part in PurePosixPath(remote_dir).parts:
            if part == "/":
                current = "/"
                continue
            current = posixpath.join(current, part)
            try:
                connection.mkd(current)
            except error_perm as exc:
                if not str(exc).startswith("550"):
                    raise


def remote_snapshot(client: FtpsClient, config: Config) -> tuple[dict[str, str], dict[str, bytes]]:
    hashes: dict[str, str] = {}
    content: dict[str, bytes] = {}
    for relative in client.list_files(config.remote_theme_dir):
        remote_path = posixpath.join(config.remote_theme_dir, relative)
        payload = client.read_bytes(remote_path)
        hashes[relative] = sha256_bytes(payload)
        content[relative] = payload
    return hashes, content


def show_diff(diff: Diff) -> None:
    for marker, paths in (("A", diff.added), ("M", diff.modified), ("R", diff.remote_only)):
        for path in paths:
            print(f"{marker} {path}")
    if not (diff.added or diff.modified or diff.remote_only):
        print("Theme is synchronized; no differences found.")


def command_status(client: FtpsClient, config: Config) -> Diff:
    remote, _ = remote_snapshot(client, config)
    diff = classify_snapshots(local_snapshot(config.local_theme_dir), remote)
    show_diff(diff)
    return diff


def command_pull(client: FtpsClient, config: Config, assume_yes: bool = False) -> None:
    remote, remote_content = remote_snapshot(client, config)
    local = local_snapshot(config.local_theme_dir)
    changed = tuple(sorted(path for path in remote if local.get(path) != remote[path]))
    overwritten = tuple(path for path in changed if path in local)
    if not changed:
        print("Theme is already synchronized; nothing to pull.")
        return

    print(f"Pull will write {len(changed)} remote theme file(s) into {config.local_theme_dir}.")
    if overwritten:
        print(f"Existing local files to back up first: {len(overwritten)}")
    if not assume_yes:
        confirmation = input(f"Type PULL {config.theme_slug} to continue: ").strip()
        if confirmation != f"PULL {config.theme_slug}":
            raise ThemeFtpError("Pull cancelled")

    backup_dir = BACKUP_ROOT / "pull" / timestamp()
    for relative in overwritten:
        backup_local(local_target(config.local_theme_dir, relative), config.local_theme_dir, backup_dir)
    for relative in changed:
        atomic_write(local_target(config.local_theme_dir, relative), remote_content[relative])

    print(f"Pulled {len(changed)} file(s).")
    if overwritten:
        print(f"Local backup: {backup_dir}")


def command_deploy(client: FtpsClient, config: Config) -> None:
    remote, remote_content = remote_snapshot(client, config)
    local = local_snapshot(config.local_theme_dir)
    diff = classify_snapshots(local, remote)
    show_diff(diff)
    if not diff.upload_paths:
        print("Nothing to deploy.")
        return

    print("Only A and M files above will be uploaded. R files will remain untouched.")
    confirmation = input(f"Type DEPLOY {config.theme_slug} to continue: ").strip()
    if confirmation != f"DEPLOY {config.theme_slug}":
        raise ThemeFtpError("Deployment cancelled")

    backup_dir = BACKUP_ROOT / "deploy" / timestamp()
    for relative in diff.modified:
        atomic_write(safe_local_path(backup_dir, relative), remote_content[relative])

    for relative in diff.upload_paths:
        local_path = local_target(config.local_theme_dir, relative)
        remote_path = posixpath.join(config.remote_theme_dir, relative)
        with local_path.open("rb") as stream:
            client.upload_atomic(remote_path, stream)

    print(f"Deployed {len(diff.upload_paths)} file(s).")
    if diff.modified:
        print(f"Remote backup: {backup_dir}")


def read_header_candidate(client: FtpsClient, path: str, fields: Sequence[str]) -> dict[str, str]:
    try:
        return parse_wp_headers(client.read_bytes(path), fields)
    except error_perm:
        return {}


def command_inventory(client: FtpsClient) -> None:
    print("Themes")
    for entry_type, slug in client.list_entries("/wp-content/themes"):
        if entry_type != "dir":
            continue
        headers = read_header_candidate(
            client, posixpath.join("/wp-content/themes", slug, "style.css"), ("Theme Name", "Version")
        )
        label = headers.get("Theme Name", slug)
        version = headers.get("Version", "unknown")
        print(f"  {slug}: {label} (version {version})")

    print("Plugins")
    for entry_type, slug in client.list_entries("/wp-content/plugins"):
        if entry_type == "file" and slug.endswith(".php"):
            candidates = [posixpath.join("/wp-content/plugins", slug)]
        elif entry_type == "dir":
            entries = client.list_entries(posixpath.join("/wp-content/plugins", slug))
            php_files = [name for kind, name in entries if kind == "file" and name.endswith(".php")]
            preferred = f"{slug}.php"
            ordered = ([preferred] if preferred in php_files else []) + [name for name in php_files if name != preferred]
            candidates = [posixpath.join("/wp-content/plugins", slug, name) for name in ordered]
        else:
            continue

        headers: dict[str, str] = {}
        for candidate in candidates:
            headers = read_header_candidate(client, candidate, ("Plugin Name", "Version"))
            if headers.get("Plugin Name"):
                break
        if entry_type == "file" and not headers.get("Plugin Name"):
            continue
        label = headers.get("Plugin Name", slug)
        version = headers.get("Version", "unknown")
        print(f"  {slug}: {label} (version {version})")


@contextmanager
def connected_client() -> Iterator[FtpsClient]:
    values = {**load_dotenv(ENV_PATH), **os.environ}
    with FtpsClient(values) as client:
        yield client


def build_parser() -> argparse.ArgumentParser:
    parser = argparse.ArgumentParser(description=__doc__)
    subcommands = parser.add_subparsers(dest="command", required=True)
    subcommands.add_parser("inventory", help="Read remote theme/plugin names and versions")
    subcommands.add_parser("status", help="Compare the local and remote theme")
    pull_parser = subcommands.add_parser("pull", help="Back up and pull remote theme changes")
    pull_parser.add_argument("--yes", action="store_true", help="Confirm the requested pull non-interactively")
    subcommands.add_parser("deploy", help="Back up and upload local theme changes")
    return parser


def main(argv: Sequence[str] | None = None) -> int:
    args = build_parser().parse_args(argv)
    try:
        config = load_config()
        with connected_client() as client:
            if args.command == "inventory":
                command_inventory(client)
            elif args.command == "status":
                command_status(client, config)
            elif args.command == "pull":
                command_pull(client, config, assume_yes=args.yes)
            elif args.command == "deploy":
                command_deploy(client, config)
        return 0
    except (ThemeFtpError, *all_errors) as exc:
        print(f"error: {exc}", file=sys.stderr)
        return 1


if __name__ == "__main__":
    raise SystemExit(main())
