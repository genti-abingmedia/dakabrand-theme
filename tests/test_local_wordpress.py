import pathlib
import unittest


ROOT = pathlib.Path(__file__).resolve().parents[1]


class LocalWordPressTests(unittest.TestCase):
    def read(self, relative_path: str) -> str:
        return (ROOT / relative_path).read_text(encoding="utf-8")

    def test_compose_mounts_only_the_theme_into_wordpress(self) -> None:
        compose = self.read("compose.yaml")

        self.assertIn("./theme:/var/www/html/wp-content/themes/dakabrand:ro", compose)
        self.assertIn('"127.0.0.1:${DAKABRAND_LOCAL_PORT:-8080}:80"', compose)
        self.assertIn("WP_ENVIRONMENT_TYPE', 'local'", compose)
        self.assertNotIn("FTP_", compose)

    def test_local_helper_never_calls_the_ftp_helper(self) -> None:
        helper = self.read("scripts/local-wordpress.sh")

        self.assertNotIn("theme_ftp.py", helper)
        self.assertIn("DAKABRAND_CONFIRM_RESET", helper)
        self.assertIn("wp plugin install woocommerce --activate", helper)
        self.assertIn("wp theme activate dakabrand", helper)

    def test_seed_catalog_is_idempotent_by_sku(self) -> None:
        seed = self.read("scripts/local/seed-catalog.php")

        self.assertIn("wc_get_product_id_by_sku", seed)
        self.assertIn("get_term_by('slug'", seed)
        self.assertIn("Local demo catalog is ready", seed)


if __name__ == "__main__":
    unittest.main()
