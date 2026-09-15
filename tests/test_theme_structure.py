import pathlib
import unittest


ROOT = pathlib.Path(__file__).resolve().parents[1]
THEME = ROOT / "theme"


class ThemeStructureTests(unittest.TestCase):
    def read(self, relative_path: str) -> str:
        return (THEME / relative_path).read_text(encoding="utf-8")

    def test_bootstrap_is_local_and_enqueued_before_theme_assets(self) -> None:
        functions = self.read("functions.php")

        self.assertTrue((THEME / "assets/vendor/bootstrap/bootstrap.min.css").is_file())
        self.assertTrue((THEME / "assets/vendor/bootstrap/bootstrap.bundle.min.js").is_file())
        self.assertTrue((THEME / "assets/vendor/bootstrap/LICENSE").is_file())
        self.assertIn("STATICBRIDGE_BOOTSTRAP_VERSION', '5.3.8'", functions)
        self.assertIn("array('staticbridge-bootstrap')", functions)
        self.assertNotIn("cdn.jsdelivr.net", functions)

    def test_theme_registers_all_navigation_locations(self) -> None:
        functions = self.read("functions.php")

        for location in ("primary", "footer", "footer_information", "footer_services"):
            self.assertIn(f"'{location}'", functions)

    def test_header_preserves_wordpress_and_cart_contracts(self) -> None:
        header = self.read("header.php")

        for contract in ("wp_head()", "wp_body_open()", "data-cart-link", "data-cart-count"):
            self.assertIn(contract, header)
        self.assertIn('id="mobile-navigation"', header)
        self.assertIn("wp_nav_menu", header)

    def test_footer_preserves_lifecycle_and_newsletter_contracts(self) -> None:
        footer = self.read("footer.php")
        functions = self.read("functions.php")

        for contract in ("wp_footer()", "data-newsletter-form", "data-newsletter-status"):
            self.assertIn(contract, footer)
        self.assertIn("staticbridge_newsletter_endpoint", functions)
        self.assertIn("newsletterEndpoint", functions)

    def test_shell_has_no_minimog_or_elementor_runtime_dependency(self) -> None:
        shell = "\n".join((self.read("header.php"), self.read("footer.php"), self.read("functions.php")))

        self.assertNotIn("minimog", shell.lower())
        self.assertNotIn("elementor", shell.lower())
        self.assertNotIn("font-awesome", shell.lower())


if __name__ == "__main__":
    unittest.main()
