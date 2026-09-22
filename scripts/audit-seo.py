#!/usr/bin/env python3
"""Check anonymous, rendered SEO output from a local DakaBrand storefront."""

from __future__ import annotations

import json
import re
import sys
from html import unescape
from urllib.parse import urljoin
from urllib.request import urlopen


BASE_URL = (sys.argv[1] if len(sys.argv) > 1 else "http://localhost:8080/").rstrip("/") + "/"


def fetch(path: str) -> str:
    with urlopen(urljoin(BASE_URL, path), timeout=15) as response:  # nosec B310: local/operator URL
        if response.status != 200:
            raise AssertionError(f"{path}: expected HTTP 200, got {response.status}")
        return response.read().decode("utf-8")


def one(html: str, pattern: str, label: str, path: str) -> str:
    matches = re.findall(pattern, html, re.I | re.S)
    if len(matches) != 1:
        raise AssertionError(f"{path}: expected exactly one {label}, got {len(matches)}")
    return unescape(matches[0]).strip()


def inspect(path: str, expected_robots: str) -> str:
    html = fetch(path)
    one(html, r"<title>(.*?)</title>", "title", path)
    one(html, r'<link\s+rel="canonical"\s+href="([^"]+)"', "canonical", path)
    robots = one(html, r'<meta\s+name="robots"\s+content="([^"]+)"', "robots", path)
    if robots != expected_robots:
        raise AssertionError(f"{path}: expected robots {expected_robots!r}, got {robots!r}")
    structured_data = one(html, r'<script\s+type="application/ld\+json">(.*?)</script>', "JSON-LD graph", path)
    json.loads(structured_data)
    return html


def main() -> None:
    inspect("/", "index,follow")
    shop = inspect("/shop/", "index,follow")
    if not re.search(r'class="product-card__link"\s+href="([^"]+)"', shop):
        raise AssertionError("/shop/: initial HTML does not contain a crawlable product link")
    product_path = re.search(r'class="product-card__link"\s+href="([^"]+)"', shop).group(1)
    product = inspect(product_path, "index,follow")
    if '"@type":"Product"' not in product and '"@type": "Product"' not in product:
        raise AssertionError(f"{product_path}: Product JSON-LD is missing")
    inspect("/cart/", "noindex,follow")
    inspect("/checkout/", "noindex,follow")
    inspect("/shop/?sale=1", "noindex,follow")
    print(f"SEO audit passed: {BASE_URL}")


if __name__ == "__main__":
    main()
