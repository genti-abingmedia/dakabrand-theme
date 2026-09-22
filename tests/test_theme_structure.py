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

    def test_header_is_a_custom_document_component(self) -> None:
        header = self.read("header.php")
        render_api = self.read("inc/render-api.php")

        for forbidden in ("wp_head()", "wp_footer()", "wp_body_open()", "get_header()", "get_footer()"):
            self.assertNotIn(forbidden, "\n".join((header, self.read("footer.php"))))
        for forbidden in ("get_header()", "get_footer()"):
            self.assertNotIn(forbidden, render_api)
        self.assertIn('id="mobile-navigation"', header)
        self.assertIn('class="mobile-navigation__menu"', header)
        self.assertIn("staticbridge_catalog_category_url", header)
        self.assertIn("get_template_part('header')", render_api)
        self.assertIn("get_template_part('footer')", render_api)
        self.assertIn("staticbridge_should_show_admin_toolbar", render_api)
        self.assertIn('return false;', render_api)
        self.assertIn("add_filter('show_admin_bar', '__return_false', 999)", self.read("functions.php"))

    def test_theme_has_static_english_and_albanian_ui(self) -> None:
        functions = self.read("functions.php")
        header = self.read("header.php")
        front_page = self.read("template-parts/views/front-page.php")
        catalogue = self.read("languages/dakabrand-sq_AL.l10n.php")
        source_catalogue = self.read("languages/dakabrand-sq_AL.po")

        self.assertIn("'en_US', 'sq_AL'", functions)
        self.assertIn("staticbridge_language_messages", functions)
        self.assertIn("'languageCatalogues'", functions)
        self.assertNotIn("admin-post.php", functions)
        self.assertNotIn("admin_post_nopriv_staticbridge_set_language", functions)
        self.assertIn("staticbridge_theme_gettext_fallback", functions)
        self.assertIn("'locale'           => staticbridge_requested_locale()", functions)
        self.assertNotIn("languageEndpoint", functions)
        self.assertIn("staticbridge_language_switcher", header)
        self.assertIn("language-switcher--mobile", header)
        self.assertIn("language-switcher--gateway", front_page)
        self.assertIn("'Man' => 'Meshkuj'", catalogue)
        self.assertIn('Language: sq_AL', source_catalogue)

    def test_footer_preserves_custom_shell_and_newsletter_contracts(self) -> None:
        footer = self.read("footer.php")

        for contract in ("data-newsletter-form", "data-newsletter-status"):
            self.assertIn(contract, footer)
        seo_document = self.read("inc/seo-document.php")
        self.assertIn("staticbridge_newsletter_endpoint", seo_document)
        self.assertIn("newsletterEndpoint", seo_document)
        self.assertIn("staticbridge_wpforms_form_id('newsletter')", footer)
        self.assertIn("wpforms_display($newsletter_form_id", footer)
        self.assertNotIn("receive 10% off", footer)

    def test_custom_seo_document_owns_metadata_and_assets(self) -> None:
        seo = self.read("inc/seo-document.php")
        render_api = self.read("inc/render-api.php")

        for contract in ("canonical", "noindex,follow", "application/ld+json", "BreadcrumbList", "AggregateOffer", "staticbridge_render_document_assets"):
            self.assertIn(contract, seo)
        self.assertIn("staticbridge_render_seo_head", render_api)
        self.assertIn("staticbridge_render_document_scripts", render_api)

    def test_catalog_has_server_rendered_product_discovery(self) -> None:
        archive = self.read("template-parts/views/product-archive.php")
        card = self.read("template-parts/components/product-card.php")
        catalog = self.read("assets/js/catalog.js")
        main_css = self.read("assets/css/main.css")
        functions = self.read("functions.php")

        self.assertIn("while (have_posts())", archive)
        self.assertIn("template-parts/components/product-card", archive)
        self.assertIn("paginate_links", archive)
        self.assertIn("has-image-error", card)
        self.assertIn("showNoImage", catalog)
        self.assertIn("addImageFallback", catalog)
        self.assertIn(".catalog-card__image-link.has-image-error::after", main_css)
        self.assertIn(".storefront-product-card__image-link.has-image-error::after", main_css)
        self.assertIn(".product-card__link.has-image-error::after", main_css)
        self.assertIn("advanced_woo_discount_rules_do_strikeout_for_out_of_stock_variants", functions)

    def test_recently_published_products_show_a_new_detail_badge(self) -> None:
        product = self.read("template-parts/views/product.php")
        stylesheet = self.read("assets/css/main.css")

        self.assertIn("get_post_timestamp", product)
        self.assertIn("WEEK_IN_SECONDS", product)
        self.assertIn("product-detail__new", product)
        self.assertIn("product-detail__badges", stylesheet)

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

    def test_checkout_route_uses_a_localstorage_theme_view(self) -> None:
        functions = self.read("functions.php")
        render_api = self.read("inc/render-api.php")
        route = self.read("page-checkout.php")
        view = self.read("template-parts/views/checkout.php")

        self.assertIn("staticbridge_is_checkout_request", functions)
        self.assertIn("staticbridge_checkout_template", functions)
        self.assertIn("staticbridge_allow_empty_local_checkout", functions)
        self.assertIn("get_theme_file_path('/page-checkout.php')", functions)
        self.assertIn("staticbridge_render_document('checkout')", route)
        self.assertIn("'checkout'", render_api)
        for target in (
            'data-checkout-page',
            'data-checkout-form',
            'data-checkout-page-items',
            'data-checkout-page-summary',
            'data-checkout-page-subtotal',
        ):
            self.assertIn(target, view)
        self.assertIn('billing_first_name', view)
        self.assertIn('billing_last_name', view)
        self.assertIn('billing_address_1', view)

    def test_checkout_uses_store_api_payment_methods(self) -> None:
        view = self.read("template-parts/views/checkout.php")
        script = self.read("assets/js/checkout.js")
        gateway = self.read("inc/remittance-gateway.php")

        self.assertIn("data-checkout-payment-methods", view)
        self.assertIn("data-checkout-payment-note", view)
        self.assertNotIn('value="cod"', view)
        self.assertNotIn('checkout_payment_option', view)
        self.assertIn("payment_methods", script)
        self.assertIn("data.payment_method = selectedPaymentMethod()", script)
        self.assertIn("$this->id = 'staticbridge_remittance'", gateway)

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
        self.assertIn("'landing'   => staticbridge_department_page_url('woman')", front_page)
        self.assertIn("'landing'   => staticbridge_department_page_url('man')", front_page)
        self.assertIn("href=\"<?php echo esc_url($section['landing']); ?>\"", front_page)
        self.assertNotIn("WP_Query", front_page)

    def test_theme_uses_a_global_broken_image_fallback(self) -> None:
        script = self.read("assets/js/main.js")
        stylesheet = self.read("assets/css/main.css")

        self.assertIn("enhanceImageFallbacks", script)
        self.assertIn("fallbackApplied", script)
        self.assertIn("fallbackImage", script)
        self.assertIn(".home-gateway__image-link.has-image-error", stylesheet)
        self.assertIn(".man-hero__media.has-image-error", stylesheet)
        self.assertIn(".fashion-mega-menu__feature.has-image-error", stylesheet)

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

    def test_man_page_uses_theme_native_assets_and_api_grids(self) -> None:
        route = self.read("page-man.php")
        view = self.read("template-parts/views/man.php")
        render_api = self.read("inc/render-api.php")
        functions = self.read("functions.php")
        script = self.read("assets/js/main.js")

        self.assertIn("staticbridge_render_document('man')", route)
        self.assertIn("Template Name: Man", route)
        self.assertIn("'man_page'        => 'man'", render_api)
        self.assertIn("staticbridge_is_man_request", functions)
        self.assertIn("$department === get_query_var('staticbridge_view')", functions)
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
        self.assertNotIn("new WP_Query", view)
        self.assertIn("data-catalog-source", view)
        self.assertIn("data-catalog-limit", view)
        self.assertIn("path === '/man'", script)
        self.assertIn("staticbridge_campaign_page_view", render_api)
        self.assertIn("'page-man.php'      => 'man'", render_api)
        self.assertNotIn("elementor", view.lower())
        self.assertNotIn("minimog", view.lower())

    def test_woman_page_uses_theme_native_assets_and_api_grids(self) -> None:
        route = self.read("page-woman.php")
        view = self.read("template-parts/views/woman.php")
        render_api = self.read("inc/render-api.php")
        functions = self.read("functions.php")
        script = self.read("assets/js/main.js")

        self.assertIn("staticbridge_render_document('woman')", route)
        self.assertIn("Template Name: Woman", route)
        self.assertIn("'woman_page'      => 'woman'", render_api)
        self.assertIn("staticbridge_is_woman_request", functions)
        self.assertIn("add_filter('template_include', 'staticbridge_woman_template', 99)", functions)
        self.assertIn('data-static-view="woman"', view)
        for asset in (
            "woman-hero-desktop.jpg",
            "woman-hero-mobile.jpg",
            "woman-editorial-primary.jpg",
            "woman-editorial-secondary.jpg",
        ):
            self.assertTrue((THEME / "assets/images" / asset).is_file())
            self.assertIn(asset, view)
        for heading in ("New In", "Top Deals", "Limited Stock"):
            self.assertIn(heading, view)
        self.assertNotIn("new WP_Query", view)
        self.assertIn("data-catalog-source", view)
        self.assertIn("path === '/woman'", script)
        self.assertIn("'page-woman.php'    => 'woman'", render_api)
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

    def test_checkout_and_account_storefront_contract(self) -> None:
        header = self.read("header.php")
        functions = self.read("functions.php")
        render_api = self.read("inc/render-api.php")
        product = self.read("template-parts/views/product.php")
        checkout = self.read("template-parts/views/checkout.php")
        self.assertNotIn("home_url('/my-account/')", header)
        self.assertNotIn("home_url('/my-account/')", functions)
        self.assertIn("staticbridge_hide_restricted_menu_items", functions)
        self.assertIn("is_page('my-account')", render_api)
        self.assertIn("'page-checkout.php' => 'checkout'", render_api)
        self.assertIn("'checkout' => 'checkout'", render_api)
        self.assertIn("'excluded_paths' => array('/my-account/')", render_api)
        self.assertIn('data-catalog-source', product)
        self.assertNotIn('data-recent-products', product)
        self.assertIn('data-checkout-shipping-rates', checkout)
        self.assertIn('data-checkout-order-total', checkout)
        self.assertIn("'staticbridge-checkout'", functions)

    def test_product_purchase_state_uses_actual_product_stock(self) -> None:
        product = self.read("template-parts/views/product.php")

        self.assertNotIn("$_GET['stock_status']", product)
        self.assertIn("'onbackorder' === $product->get_stock_status()", product)
        self.assertIn("$is_sold_out = !$has_stock && !$preorder", product)
        self.assertIn("if (!$preorder && !$is_sold_out)", product)
        self.assertIn("product-detail__sold-out", product)
        self.assertIn("product-detail__availability--sold-out", product)

    def test_contact_and_policy_pages_share_the_static_render_contract(self) -> None:
        render_api = self.read("inc/render-api.php")
        functions = self.read("functions.php")
        contact = self.read("template-parts/views/contact.php")
        policy = self.read("template-parts/views/policy.php")

        for slug, view in (
            ("contact-us", "contact"),
            ("privacy-policy", "policy"),
            ("terms-conditions", "policy"),
            ("refund_returns", "policy"),
        ):
            self.assertIn(f"'{slug}'", render_api)
            self.assertIn(f"staticbridge_render_document('{view}')", self.read(f"page-{slug}.php"))
        self.assertIn("'contact'", render_api)
        self.assertIn("'policy'", render_api)
        self.assertIn("the_content()", policy)
        self.assertIn("staticbridge_wpforms_form_id('contact')", contact)
        self.assertIn("wpforms_display($contact_form_id", contact)
        self.assertIn("staticbridge_wpforms_contact_form_id", self.read("README.md"))
        self.assertIn("'staticbridge_contact_endpoint'", functions)
        self.assertIn("'endpoint' => (string) apply_filters('staticbridge_contact_endpoint', '')", functions)


if __name__ == "__main__":
    unittest.main()
