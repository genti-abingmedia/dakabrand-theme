import importlib.util
import os
import sys
import tempfile
import unittest
from pathlib import Path
from unittest import mock


MODULE_PATH = Path(__file__).resolve().parents[1] / "scripts" / "theme_ftp.py"
SPEC = importlib.util.spec_from_file_location("theme_ftp", MODULE_PATH)
assert SPEC and SPEC.loader
theme_ftp = importlib.util.module_from_spec(SPEC)
sys.modules[SPEC.name] = theme_ftp
SPEC.loader.exec_module(theme_ftp)


class ThemeFtpSafetyTests(unittest.TestCase):
    def test_status_classification(self):
        diff = theme_ftp.classify_snapshots(
            {"added.css": "1", "changed.php": "local", "same.js": "same"},
            {"changed.php": "remote", "same.js": "same", "remote.php": "2"},
        )
        self.assertEqual(diff.added, ("added.css",))
        self.assertEqual(diff.modified, ("changed.php",))
        self.assertEqual(diff.remote_only, ("remote.php",))
        self.assertEqual(diff.upload_paths, ("added.css", "changed.php"))

    def test_rejects_relative_path_traversal(self):
        for unsafe in ("../wp-config.php", "/etc/passwd", "parts/../../secret"):
            with self.subTest(unsafe=unsafe):
                with self.assertRaises(theme_ftp.ThemeFtpError):
                    theme_ftp.normalize_relative_path(unsafe)

    def test_rejects_broad_or_non_theme_remote_paths(self):
        for unsafe in ("/", "/wp-content", "/wp-content/themes", "/wp-content/plugins/woocommerce"):
            with self.subTest(unsafe=unsafe):
                with self.assertRaises(theme_ftp.ThemeFtpError):
                    theme_ftp.normalize_remote_root(unsafe)

    def test_atomic_write_replaces_complete_file(self):
        with tempfile.TemporaryDirectory() as directory:
            target = Path(directory) / "nested" / "file.php"
            theme_ftp.atomic_write(target, b"complete")
            self.assertEqual(target.read_bytes(), b"complete")
            self.assertEqual(list(target.parent.glob(".ftp-tmp-*")), [])

    def test_interrupted_atomic_replace_preserves_original(self):
        with tempfile.TemporaryDirectory() as directory:
            target = Path(directory) / "file.php"
            target.write_bytes(b"original")
            with mock.patch.object(os, "replace", side_effect=OSError("interrupted")):
                with self.assertRaises(OSError):
                    theme_ftp.atomic_write(target, b"replacement")
            self.assertEqual(target.read_bytes(), b"original")
            self.assertEqual(list(target.parent.glob(".ftp-tmp-*")), [])

    def test_local_snapshot_rejects_symlinks(self):
        with tempfile.TemporaryDirectory() as directory:
            root = Path(directory)
            source = root / "source"
            source.write_text("content", encoding="utf-8")
            (root / "linked").symlink_to(source)
            with self.assertRaises(theme_ftp.ThemeFtpError):
                theme_ftp.local_snapshot(root)

    def test_local_snapshot_excludes_transfer_temporaries(self):
        with tempfile.TemporaryDirectory() as directory:
            root = Path(directory)
            (root / "style.css").write_bytes(b"theme")
            (root / ".ftp-tmp-abcd").write_bytes(b"partial pull")
            (root / ".style.css.upload-abcd.tmp").write_bytes(b"partial deploy")
            self.assertEqual(set(theme_ftp.local_snapshot(root)), {"style.css"})

    def test_header_parser_extracts_only_requested_fields(self):
        headers = theme_ftp.parse_wp_headers(
            b"/*\nPlugin Name: Store\nVersion: 1.2.3\nSecret: hidden\n*/",
            ("Plugin Name", "Version"),
        )
        self.assertEqual(headers, {"Plugin Name": "Store", "Version": "1.2.3"})

    def test_tls_hostname_defaults_to_ftp_server(self):
        client = theme_ftp.FtpsClient(
            {"FTP_SERVER": "ftp.example.test", "FTP_ACCOUNT_NAME": "user", "FTP_PASSWORD": "pass"}
        )
        self.assertEqual(client.tls_server_name, "ftp.example.test")

    def test_tls_hostname_can_be_overridden_for_certificate_validation(self):
        client = theme_ftp.FtpsClient(
            {
                "FTP_SERVER": "192.0.2.1",
                "FTP_TLS_SERVER_NAME": "hosting.example.test",
                "FTP_ACCOUNT_NAME": "user",
                "FTP_PASSWORD": "pass",
            }
        )
        self.assertEqual(client.tls_server_name, "hosting.example.test")


if __name__ == "__main__":
    unittest.main()
