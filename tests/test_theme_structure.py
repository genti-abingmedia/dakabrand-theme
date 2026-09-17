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

    def test_header_preserves_wordpress_and_navigation_contracts(self) -> None:
        header = self.read("header.php")

        for contract in ("wp_head()", "wp_body_open()"):
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

    def test_cart_drawer_is_shared_and_product_data_supports_it(self) -> None:
        header = self.read("header.php")
        footer = self.read("footer.php")
        functions = self.read("functions.php")
        product_data = self.read("inc/product-data.php")
        card = self.read("template-parts/components/product-card.php")

        self.assertIn('data-cart-open', header)
        self.assertIn('data-cart-drawer', footer)
        self.assertIn('data-cart-items', footer)
        self.assertIn('data-cart-subtotal', footer)
        self.assertIn("home_url('/checkout/')", footer)
        self.assertIn("'staticbridge-cart'", functions)
        self.assertIn("'cartStorageKey'", functions)
        self.assertIn("'options'", product_data)
        self.assertIn("'currency'", product_data)
        self.assertIn('data-staticbridge-product', card)

    def test_cart_route_uses_a_localstorage_theme_view(self) -> None:
        functions = self.read("functions.php")
        render_api = self.read("inc/render-api.php")
        route = self.read("page-cart.php")
        view = self.read("template-parts/views/cart.php")

        self.assertIn("staticbridge_is_cart_request", functions)
        self.assertIn("staticbridge_cart_template", functions)
        self.assertIn("get_theme_file_path('/page-cart.php')", functions)
        self.assertIn("staticbridge_render_document('cart')", route)
        self.assertIn("'cart'", render_api)
        for target in (
            'data-cart-page',
            'data-cart-page-status',
            'data-cart-page-items',
            'data-cart-page-summary',
            'data-cart-page-subtotal',
        ):
            self.assertIn(target, view)
        self.assertIn("home_url('/checkout/')", view)

    def test_shell_has_no_minimog_or_elementor_runtime_dependency(self) -> None:
        shell = "\n".join((self.read("header.php"), self.read("footer.php"), self.read("functions.php")))

        self.assertNotIn("minimog", shell.lower())
        self.assertNotIn("elementor", shell.lower())
        self.assertNotIn("font-awesome", shell.lower())

    def test_front_page_is_an_immersive_gateway(self) -> None:
        functions = self.read("functions.php")
        render_api = self.read("inc/render-api.php")
        front_page = self.read("template-parts/views/front-page.php")

        self.assertIn("staticbridge_is_immersive_front_page", functions)
        self.assertIn("set_query_var('staticbridge_view', $view)", render_api)
        self.assertIn('class="home-gateway"', front_page)
        self.assertIn("home-man.jpg", front_page)
        self.assertIn("home-woman.jpg", front_page)
        self.assertLess(front_page.index("'key'       => 'woman'"), front_page.index("'key'       => 'man'"))
        self.assertLess(front_page.index('href="#home-woman"'), front_page.index('href="#home-man"'))
        self.assertIn('href="#home-woman" aria-current="true"', front_page)
        self.assertIn("'landing'   => '/woman/'", front_page)
        self.assertIn("'landing'   => '/man/'", front_page)
        self.assertIn("home_url($section['landing'])", front_page)
        self.assertNotIn("WP_Query", front_page)

    def test_mobile_app_toolbar_remains_available_on_front_page(self) -> None:
        footer = self.read("footer.php")
        front_page = self.read("template-parts/views/front-page.php")
        script = self.read("assets/js/main.js")

        self.assertRegex(
            footer,
            r'</footer>\s*<\?php endif; \?>\s*<nav class="mobile-tabs',
        )
        for tab in ("home", "women", "shop", "man", "cart"):
            self.assertIn(f'data-mobile-tab="{tab}"', footer)
        self.assertIn("data-cart-link", footer)
        self.assertIn("data-cart-count", footer)
        self.assertIn("data-gateway-switcher", front_page)
        self.assertIn("data-gateway-panel", front_page)
        self.assertIn("enhanceMobileTabs", script)
        self.assertIn("enhanceGatewaySwitcher", script)

    def test_man_page_uses_theme_native_assets_and_product_queries(self) -> None:
        route = self.read("page-man.php")
        view = self.read("template-parts/views/man.php")
        render_api = self.read("inc/render-api.php")
        functions = self.read("functions.php")
        script = self.read("assets/js/main.js")

        self.assertIn("staticbridge_render_document('man')", route)
        self.assertIn("'man_page'        => 'man'", render_api)
        self.assertIn("staticbridge_is_man_request", functions)
        self.assertIn("add_filter('template_include', 'staticbridge_man_template', 99)", functions)
        self.assertIn("add_filter('redirect_canonical', 'staticbridge_man_canonical_redirect')", functions)
        self.assertIn("status_header(200)", functions)
        self.assertIn('data-static-view="man"', view)
        for asset in (
            "man-hero-desktop.jpg",
            "man-hero-mobile.jpg",
            "man-editorial-primary.jpg",
            "man-editorial-secondary.jpg",
        ):
            self.assertTrue((THEME / "assets/images" / asset).is_file())
            self.assertIn(asset, view)
        for heading in ("New In", "Top Deals", "Limited Stock"):
            self.assertIn(heading, view)
        for asset in (
            "man-product-hermes-277.jpg",
            "man-product-hermes-199.jpg",
            "man-product-hermes-53.jpg",
            "man-product-hermes-190.jpg",
            "man-product-lv-550.jpg",
        ):
            self.assertTrue((THEME / "assets/images" / asset).is_file())
            self.assertIn(asset, view)
        self.assertIn("new WP_Query", view)
        self.assertIn("get_template_part('template-parts/components/product-card')", view)
        self.assertIn("data-catalog-fallback", view)
        self.assertIn("path === '/man'", script)
        self.assertNotIn("elementor", view.lower())
        self.assertNotIn("minimog", view.lower())

    def test_woman_page_uses_theme_native_assets_and_product_queries(self) -> None:
        route = self.read("page-woman.php")
        view = self.read("template-parts/views/woman.php")
        render_api = self.read("inc/render-api.php")
        functions = self.read("functions.php")
        script = self.read("assets/js/main.js")

        self.assertIn("staticbridge_render_document('woman')", route)
        self.assertIn("'woman_page'      => 'woman'", render_api)
        self.assertIn("staticbridge_is_woman_request", functions)
        self.assertIn("add_filter('template_include', 'staticbridge_woman_template', 99)", functions)
        self.assertIn('data-static-view="woman"', view)
        for asset in (
            "woman-hero-desktop.jpg",
            "woman-hero-mobile.jpg",
            "woman-editorial-primary.jpg",
            "woman-editorial-secondary.jpg",
            "woman-product-bottega-174.jpg",
            "woman-product-bottega-173.jpg",
            "woman-product-bottega-172.jpg",
            "woman-product-chanel-380.jpg",
            "woman-product-bottega-171.jpg",
        ):
            self.assertTrue((THEME / "assets/images" / asset).is_file())
            self.assertIn(asset, view)
        for heading in ("New In", "Top Deals", "Limited Stock"):
            self.assertIn(heading, view)
        self.assertIn("new WP_Query", view)
        self.assertIn("data-catalog-fallback", view)
        self.assertIn("path === '/woman'", script)
        self.assertNotIn("elementor", view.lower())
        self.assertNotIn("minimog", view.lower())

    def test_product_cards_use_local_jost_and_centered_metadata(self) -> None:
        component = self.read("template-parts/components/product-card.php")
        styles = self.read("assets/css/main.css")
        man_view = self.read("template-parts/views/man.php")
        woman_view = self.read("template-parts/views/woman.php")

        self.assertTrue((THEME / "assets/fonts/jost-latin.woff2").is_file())
        self.assertIn('font-family: "Jost"', styles)
        self.assertIn("product-card__category", component)
        self.assertIn("wc_get_product_category_list", component)
        self.assertIn("text-align: center", styles)
        self.assertNotIn("Shop product", man_view)
        self.assertNotIn("Shop product", woman_view)


if __name__ == "__main__":
    unittest.main()
