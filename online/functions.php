<?php
defined( 'ABSPATH' ) || exit;

/**
 * Enqueue child scripts
 */
if ( ! function_exists( 'minimog_child_enqueue_scripts' ) ) {
	function minimog_child_enqueue_scripts() {
		wp_enqueue_style( 'minimog-child-style', get_stylesheet_directory_uri() . '/style.css' );
	}
}
add_action( 'wp_enqueue_scripts', 'minimog_child_enqueue_scripts', 15 );


function minio_s3_url_domain( $domain, $bucket, $region, $expires, $args ) {
	$current_domain = $_SERVER['HTTP_HOST'];
	return 'gliterin-media.' . $current_domain;
}

add_filter( 'as3cf_aws_s3_url_domain', 'minio_s3_url_domain' , 10, 5 );


// add kosovo on list of countries
add_filter( 'woocommerce_countries',  'snippetpress_add_kosovo' );
 
function snippetpress_add_kosovo( $countries ) {
    $new_countries = array(
        'XK'  => __( 'Kosovo', 'woocommerce' ),
    );
    return array_merge( $countries, $new_countries );
}

add_filter( 'woocommerce_continents', 'snippetpress_add_kosovo_to_continents' );

function snippetpress_add_kosovo_to_continents( $continents ) {
    $continents['EU']['countries'][] = 'XK';
    return $continents;
}

// add kosovo cities
add_filter( 'woocommerce_states', 'kosovo_custom_woocommerce_states' );

function kosovo_custom_woocommerce_states( $states ) {
	
    // start from 20 to prevent overwrite deafult woocommerce cities
    $states['XK']['XK-20'] = 'Artanë';
  	$states['XK']['XK-21'] = 'Deçan';
    $states['XK']['XK-22'] = 'Dragash';
    $states['XK']['XK-23'] = 'Drenas';
    $states['XK']['XK-24'] = 'Fushë Kosovë';
    $states['XK']['XK-25'] = 'Ferizaj';
    $states['XK']['XK-26'] = 'Gjilan';
    $states['XK']['XK-27'] = 'Gjakovë';
    $states['XK']['XK-28'] = 'Graçanicë';
    $states['XK']['XK-29'] = 'Hani I Elezit';
    $states['XK']['XK-30'] = 'Istog';
    $states['XK']['XK-31'] = 'Junik';
    $states['XK']['XK-32'] = 'Kaçanik';
    $states['XK']['XK-33'] = 'Klinë';
    $states['XK']['XK-34'] = 'Kamenicë';
    $states['XK']['XK-35'] = 'Kllokot';
    $states['XK']['XK-36'] = 'Leposaviq';
    $states['XK']['XK-37'] = 'Lipjan';
    $states['XK']['XK-38'] = 'Malishevë';
    $states['XK']['XK-39'] = 'Mamushë';
    $states['XK']['XK-40'] = 'Mitrovica Veriore';
    $states['XK']['XK-41'] = 'Mitrovicë';
    $states['XK']['XK-42'] = 'Novobërdë';
    $states['XK']['XK-43'] = 'Obiliq';
    $states['XK']['XK-44'] = 'Podujevë';
    $states['XK']['XK-45'] = 'Pejë';
    $states['XK']['XK-46'] = 'Prishtinë';
    $states['XK']['XK-47'] = 'Partesh';
    $states['XK']['XK-48'] = 'Prizren';
    $states['XK']['XK-49'] = 'Ranillug';
    $states['XK']['XK-50'] = 'Rahovec';
    $states['XK']['XK-51'] = 'Shtërpcë';
    $states['XK']['XK-52'] = 'Skënderaj';
    $states['XK']['XK-53'] = 'Shtime';
    $states['XK']['XK-54'] = 'Suharekë';
    $states['XK']['XK-55'] = 'Viti';
    $states['XK']['XK-56'] = 'Vushtrri';
    $states['XK']['XK-57'] = 'Zubin Potok';
    $states['XK']['XK-58'] = 'Zveçan';

  asort($states['XK']); // Sort the cities alphabetically

  return $states;
}


 // add tirana cities
add_filter( 'woocommerce_states', 'albania_custom_woocommerce_states' );

function albania_custom_woocommerce_states( $states ) {
	
  // start from AL-20 to prevent overwrite deafult woocommerce cities
    $states['AL']['AL-20'] = 'Kurbin';
    $states['AL']['AL-21'] = 'Kuçovë';
    $states['AL']['AL-22'] = 'Ksamil';
    $states['AL']['AL-23'] = 'Kolonjë';
    $states['AL']['AL-24'] = 'Krumë';
    $states['AL']['AL-25'] = 'Konispol';
    $states['AL']['AL-26'] = 'Krujë';
    $states['AL']['AL-27'] = 'Klos';
    $states['AL']['AL-28'] = 'Kavajë';
    $states['AL']['AL-29'] = 'Laç';
    $states['AL']['AL-30'] = 'Librazhd';
    $states['AL']['AL-31'] = 'Leskovik';
    $states['AL']['AL-32'] = 'Lushnjë';
    $states['AL']['AL-33'] = 'Mamuras';
    $states['AL']['AL-34'] = 'Milot';
    $states['AL']['AL-35'] = 'Mallakastër';
    $states['AL']['AL-36'] = 'Maliq';
    $states['AL']['AL-37'] = 'Malësi e Madhe';
    $states['AL']['AL-38'] = 'Mirditë';
    $states['AL']['AL-39'] = 'Mat';
    $states['AL']['AL-40'] = 'Orikum';
    $states['AL']['AL-41'] = 'Patos';
    $states['AL']['AL-42'] = 'Peqin';
    $states['AL']['AL-43'] = 'Pogradec';
    $states['AL']['AL-44'] = 'Pukë';
    $states['AL']['AL-45'] = 'Përmet';
    $states['AL']['AL-46'] = 'Poliçan';
    $states['AL']['AL-47'] = 'Peshkopi';
    $states['AL']['AL-48'] = 'Fushë Krujë';
    $states['AL']['AL-49'] = 'Prrenjas';
    $states['AL']['AL-50'] = 'Roskovec';
    $states['AL']['AL-51'] = 'Rrogozhinë';
    $states['AL']['AL-52'] = 'Shijak';
    $states['AL']['AL-53'] = 'Maminas';
    $states['AL']['AL-54'] = 'Rrëshen';
    $states['AL']['AL-55'] = 'Skrapar';
    $states['AL']['AL-56'] = 'Sarandë';
    $states['AL']['AL-57'] = 'Sukth';
    $states['AL']['AL-58'] = 'Tepelenë';
    $states['AL']['AL-59'] = 'Tropojë';
    $states['AL']['AL-60'] = 'Vau i Dejës';
    $states['AL']['AL-61'] = 'Vorë';
    $states['AL']['AL-62'] = 'Ura Vajgurore';
	$states['AL']['AL-63'] = 'Bilisht';
	$states['AL']['AL-66'] = 'Tiranë Periferi';

  asort($states['AL']); // Sort the cities alphabetically

  return $states;
}

 // add macedonia cities
add_filter( 'woocommerce_states', 'macedonia_custom_woocommerce_states' );

function macedonia_custom_woocommerce_states( $states ) {
	
  // start from 20 to prevent overwrite deafult woocommerce cities
    $states['MK']['MK-20'] = 'Bogdanca';
    $states['MK']['MK-21'] = 'Brod';
    $states['MK']['MK-22'] = 'Berova';
    $states['MK']['MK-23'] = 'Dellceva';
    $states['MK']['MK-24'] = 'Demir Hisar';
    $states['MK']['MK-25'] = 'Dibra e Madhe';
    $states['MK']['MK-26'] = 'Gostivar';
    $states['MK']['MK-27'] = 'Gjevgjelja';
    $states['MK']['MK-28'] = 'Kamenica';
    $states['MK']['MK-29'] = 'Kercove';
    $states['MK']['MK-30'] = 'Kumanova';
    $states['MK']['MK-31'] = 'Kocani';
    $states['MK']['MK-31'] = 'Kriva Palanka';
    $states['MK']['MK-33'] = 'Kratova';
    $states['MK']['MK-34'] = 'Krusheva';
    $states['MK']['MK-35'] = 'Kavadar';
    $states['MK']['MK-36'] = 'Manastir';
    $states['MK']['MK-37'] = 'Negotina';
    $states['MK']['MK-38'] = 'Oher';
    $states['MK']['MK-39'] = 'Peceva';
    $states['MK']['MK-40'] = 'Prilep';
    $states['MK']['MK-41'] = 'Probishtip';
    $states['MK']['MK-42'] = 'Radovisht';
    $states['MK']['MK-43'] = 'Resnja';
    $states['MK']['MK-44'] = 'Shtip';
    $states['MK']['MK-45'] = 'Shkup';
    $states['MK']['MK-46'] = 'Strumica';
    $states['MK']['MK-47'] = 'Sveti Nikola';
    $states['MK']['MK-48'] = 'Struga';
    $states['MK']['MK-49'] = 'Tetova';
    $states['MK']['MK-50'] = 'Vinica';
    $states['MK']['MK-51'] = 'Veles';
     
  	asort($states['MK']); // Sort the cities alphabetically

  	return $states;
}

/**
 * Change the default country on the checkout for non-existing users only
 */
add_filter( 'default_checkout_billing_state', 'change_default_checkout_country' );
add_filter( 'default_checkout_shipping_state', 'change_default_checkout_country' );
add_filter( 'default_checkout_billing_country', 'change_default_checkout_country' );
add_filter( 'default_checkout_shipping_country', 'change_default_checkout_country' );

function change_default_checkout_country( $default ) {
    if ( ! is_user_logged_in() ) {
        $default = null;
    }
    return $default;
}



// Remove checkout fields
add_filter( 'woocommerce_checkout_fields', 'quadlayers_remove_checkout_fields' );

function quadlayers_remove_checkout_fields( $fields ) {

    // Remove billing fields
    unset( $fields['billing']['billing_postcode'] );
    unset( $fields['billing']['billing_city'] );
    unset( $fields['billing']['billing_last_name'] );

    // Remove shipping fields
    unset( $fields['shipping']['shipping_postcode'] );
    unset( $fields['shipping']['shipping_city'] );
    unset( $fields['shipping']['shipping_last_name'] );

    // Rename first name field
    $fields['billing']['billing_first_name']['label'] = 'Name / Surname';
    $fields['billing']['billing_first_name']['placeholder'] = 'Name / Surname';

    $fields['shipping']['shipping_first_name']['label'] = 'Name / Surname';
    $fields['shipping']['shipping_first_name']['placeholder'] = 'Name / Surname';

    // Rename state field to City
    $fields['billing']['billing_state']['label'] = 'City';
    $fields['billing']['billing_state']['placeholder'] = 'City';

    $fields['shipping']['shipping_state']['label'] = 'City';
    $fields['shipping']['shipping_state']['placeholder'] = 'City';

    return $fields;
}


// out of stock products with strikeout price
add_filter('advanced_woo_discount_rules_do_strikeout_for_out_of_stock_variants', '__return_true');

// Add stock / 15 days preorder to products loop
// woocommerce_shop_loop_item_title
// woocommerce_before_shop_loop_item_title
add_action( 'woocommerce_before_shop_loop_item_title', 'my_stock_badge', 10 );
function my_stock_badge() {
    global $product;

    $my_text = 'Out of stock';

    if ( $product->get_type() == 'variable' ) {

        foreach ( $product->get_available_variations() as $key ) {
            $variation = wc_get_product( $key['variation_id'] );
            $stock = $variation->get_availability();
            $stock_string = $stock['availability'] ? $stock['availability'] : __( 'In stock', 'woocommerce' );
            if (str_contains(strtolower($stock_string), 'in stock') || str_contains(strtolower($stock_string), 'disponibile') || str_contains(strtolower($stock_string), 'ka stok')) {
                $my_text = 'STOCK'; 
                break;
            } elseif (str_contains(strtolower($stock_string), 'backorder')) {
               $my_text = '15 DAYS PREORDER';
            }
        }

        echo '<div class="my-stock-badge">'.$my_text.'</div>';

    } else {

        $stock = $product->get_availability();
        $stock_string = $stock['availability'] ? $stock['availability'] : __( 'In stock', 'woocommerce' );
        if (str_contains(strtolower($stock_string), 'in stock') || str_contains(strtolower($stock_string), 'disponibile') || str_contains(strtolower($stock_string), 'ka stok')) {
            $my_text = 'STOCK';
        } elseif (str_contains(strtolower($stock_string), 'backorder')) {
           $my_text = '15 DAYS PREORDER';
        }
        echo '<div class="my-stock-badge">'.$my_text.'</div>';
    }
 
}


/**
 * Display Backorder Badge on Single Product Page
 */
function display_backorder_badge() {
    global $product;

    if ( $product->is_on_backorder() ) {
        echo '<div class="single-product-badge">15 DAYS PREORDER</div>';
    }
}
add_action( 'woocommerce_single_product_summary', 'display_backorder_badge', 21 );






/* Add fireworks to success order */
// function woocommerce_thankyou_fireworks() {
//     echo '<div class="fireworks"></div>
//         <style>
//             .fireworks {
//                 width: 100%;
//                 height: 100vh;
//                 position: fixed;
//                 top: 0;
//                 left: 0;
//                 z-index: 0;
//             }
//         </style>
//         <!-- jsDelivr  -->
//         <script src="https://cdn.jsdelivr.net/npm/fireworks-js@2.x/dist/index.umd.js"></script>

//         <!-- UNPKG -->
//         <script src="https://unpkg.com/fireworks-js@2.x/dist/index.umd.js"></script>

//         <!-- Usage -->
//         <script>
//           const container = document.querySelector(".fireworks");
//           const fireworks = new Fireworks.default(container);
//           fireworks.start();
//         </script>';
// }
// add_action('woocommerce_thankyou', 'woocommerce_thankyou_fireworks');


/* Success order new template */
/* Success order new template */
function woocommerce_thankyou_new_template() {
    echo '<style>
            article .woocommerce .woocommerce-order p,
            article .woocommerce .woocommerce-order section,
            article .woocommerce .woocommerce-order ul,
            article .woocommerce .woocommerce-order .right-box {
                display: none;
            }
            article .woocommerce .woocommerce-order .left-box {
                flex: 0 0 100%;
                max-width: 100%;
            }
            .daka-success-wrapper {
             display: flex;
             justify-content: center;
             align-items: center;
             flex-direction: column;
             margin: 80px auto;
            }
            .daka-success-icon {
             margin: 0 auto 80px;
            }
            .daka-success-icon svg {
             width: 100px;
             height: 100px;
             fill: #00d084;
            }
            .daka-success-msg {
             text-align: center;
             margin: 0 20px;
            }
            .daka-success-msg h2 {
             font-size: 30px;
             font-weight: 900;
             margin-bottom: 10px;
            }
            article .woocommerce .woocommerce-order .daka-success-msg p {
                display: block;
                line-height: 1.25;
                margin: 20px auto 40px;
            }
            .daka-success-btn a {
             display: inline-block;
             padding: 10px 20px;
             color: #fff;
             text-decoration: none;
             background: rgba(10, 76, 125, 0.92);
             border-radius: 5px;
             transition: all 0.3s ease-in-out;
             margin: 20px auto;
            }
            .daka-success-btn a:hover {
             background: #0A4C7D;
            }
         </style>
         <div class="daka-success-wrapper">
            <div class="daka-success-icon">
             <svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" viewBox="0,0,256,256"><g fill-rule="nonzero" stroke="none" stroke-width="1" stroke-linecap="butt" stroke-linejoin="miter" stroke-miterlimit="10" stroke-dasharray="" stroke-dashoffset="0" font-family="none" font-weight="none" font-size="none" text-anchor="none" style="mix-blend-mode: normal"><g transform="scale(4,4)"><path d="M32,6c-14.359,0 -26,11.641 -26,26c0,14.359 11.641,26 26,26c14.359,0 26,-11.641 26,-26c0,-14.359 -11.641,-26 -26,-26zM29.081,42.748l-10.409,-9.253l2.657,-2.99l7.591,6.747l15.08,-16.252l3.414,3.414z"/></g></g></svg>
            </div>
            <div class="daka-success-msg">
             <h2>Thank you for your order!</h2>
             <p>Your order has been placed and will be processed as soon as possible.</p>
            </div>
            <div class="daka-success-btn">
             <a href="/shop">Continue Shopping</a>
            </div>
         </div>';
}
add_action('woocommerce_thankyou', 'woocommerce_thankyou_new_template');

// Whatsapp Button
/*add_action( 'wp_footer', function() {
    ?> 
    <style type="text/css">
        .whatsapp-wrapper {
            position: fixed;
            bottom: 56px;
            right: 29px;
            border-radius: 50%;
            z-index: 1400;
            align-items: center;
            justify-content: center;
            display: flex;
        }
        .whatsapp-wrapper img {
            width: 55px;
        }
        @media only screen and (max-width: 600px) {
            .whatsapp-wrapper {
                bottom: 70px;
                right: 15px;
            }
        }
    </style>
    <div class="whatsapp-wrapper"><a href="https://wa.me/+355683885286" target="_blank" id="whatsapp-button">
        <img src="https://dakabrand.uk/wp-content/uploads/2024/01/whatsapp-logo.svg">
    </a></div>
    <?php
}, 5 );*/



// GLITERIN AI Button
/*add_action( 'wp_footer', function() {
    ?> 

	<style type="text/css">
        .gliterinai-wrapper {
            position: fixed;
            bottom: 56px;
            right: 29px;
            border-radius: 50%;
            z-index: 1400;
            align-items: center;
            justify-content: center;
            display: flex;
        }
        .gliterinai-wrapper img {
            width: 55px;
        }
        @media only screen and (max-width: 600px) {
            .whatsapp-wrapper {
                bottom: 70px;
                right: 15px;
            }
			.gl-chat-app-icon {
				bottom: 75px;
			}
        }
    </style>
    <div class="gliterinai-wrapper">
		<script src='https://cdn.pnerp.com/webchat/assets/webchat.js' data-widget-id='f6a59037-5e40-40a4-98c9-d283d7fda033'>		    </script>
	</div>
    <?php
}, 5 );*/



function woocommerce_before_shop_loop_item_whatsapp_button() {
    global $product;
    if ($product) {
        $product_name = $product->get_name();
        $product_sku = $product->get_sku();
        $product_url = $product->get_permalink();
        
        // Notice the \n right before "Linku:" — this creates the new line
        $message_text = "INTERESOHEM PER PRODUKTIN: $product_name me SKU: $product_sku \nLinku: $product_url";
        $whatsapp_message = urlencode($message_text);
        
        echo '
        <div class="loop-whatsapp-wrapper">
            <a href="https://wa.me/+355692374317?text=' . $whatsapp_message . '" target="_blank">
                <img src="https://dakaoutlet.com/wp-content/uploads/2023/06/whatsapp-logo.svg" alt="WhatsApp">
            </a>
        </div>';
    }
}
add_action('woocommerce_before_shop_loop_item_title', 'woocommerce_before_shop_loop_item_whatsapp_button');








/*function prevent_backorder_purchase( $purchasable, $product ) {
    if ( $product->is_on_backorder( 1 ) ) {
        $purchasable = false;
    }
    return $purchasable;
}
add_filter( 'woocommerce_is_purchasable', 'prevent_backorder_purchase', 10, 2 );*/

function custom_whatsapp_button() {
    global $product;

    if ( ! $product ) {
        return;
    }

    $product_name      = $product->get_name();
    $product_sku       = $product->get_sku();
    $product_url       = $product->get_permalink();
    
    // Added the new line (\n) and the product link to the message
    $message_text      = "INTERESOHEM PER PRODUKTIN: $product_name me SKU: $product_sku.\nLinku: $product_url";
    $whatsapp_message  = urlencode($message_text);
    
    $whatsapp_url      = 'https://wa.me/355683885286?text=' . $whatsapp_message;

    echo '
    <style>
        :root {
            --whatsapp-green: #25D366;
            --whatsapp-light: #32e676;
            --whatsapp-border: #1eb956;
            --button-radius: 12px;
            --font-button: "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
        }

        a.wtsapp.button.btn-soft-bubble {
            display: inline-flex;
            max-width: 350px;
            align-items: center;
            justify-content: center;
            gap: 12px;
            padding: 0 35px;
            min-height: 50px;
            width: 100%;
            text-decoration: none;
            cursor: pointer;
            box-sizing: border-box;
            margin: 12px 0;

            color: #fff;
            font-family: var(--font-button);
            font-weight: 700;
            font-size: 15px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            line-height: 1;

            background: linear-gradient(180deg, var(--whatsapp-light) 0%, var(--whatsapp-green) 100%);
            border: 1px solid var(--whatsapp-border);
            border-radius: var(--button-radius);

            box-shadow:
                inset 0 2px 0 rgba(255, 255, 255, 0.4),
                0 6px 15px rgba(37, 211, 102, 0.3);

            transition: all 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            animation: pulse-soft 2.5s infinite ease-in-out;
        }

        a.wtsapp.button.btn-soft-bubble:hover {
            color: #fff;
            transform: scale(1.05);
            box-shadow:
                inset 0 2px 0 rgba(255, 255, 255, 0.5),
                0 10px 25px rgba(37, 211, 102, 0.4);
        }

        a.wtsapp.button.btn-soft-bubble:focus {
            color: #fff;
        }

        a.wtsapp.button.btn-soft-bubble:active {
            transform: scale(0.96);
            box-shadow:
                inset 0 2px 5px rgba(0, 0, 0, 0.15),
                0 2px 5px rgba(37, 211, 102, 0.2);
            transition: all 0.1s;
        }

        a.wtsapp.button.btn-soft-bubble svg {
            width: 22px;
            height: 22px;
            fill: currentColor;
            flex-shrink: 0;
            filter: drop-shadow(0 1px 1px rgba(0,0,0,0.1));
        }

        @keyframes pulse-soft {
            0% {
                transform: scale(1);
            }
            50% {
                transform: scale(1.03);
                box-shadow:
                    inset 0 2px 0 rgba(255, 255, 255, 0.4),
                    0 8px 30px rgba(37, 211, 102, 0.5);
            }
            100% {
                transform: scale(1);
            }
        }
    </style>';

    echo '<a href="' . esc_url( $whatsapp_url ) . '" class="wtsapp button alt btn-soft-bubble" target="_blank" rel="noopener noreferrer">
        <svg viewBox="0 0 24 24" aria-hidden="true">
            <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L0 24l6.335-1.662c1.72.937 3.659 1.432 5.631 1.433h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413Z"/>
        </svg>
        <span>Contact Us On WhatsApp</span>
    </a>';
}

function replace_add_to_cart_with_whatsapp() {
    global $product;

    if ( ! $product ) {
        return;
    }

    if ( $product->is_on_backorder() ) {
        remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_add_to_cart', 30 );
        add_action( 'woocommerce_single_product_summary', 'custom_whatsapp_button', 30 );
    }
}
add_action( 'woocommerce_before_single_product', 'replace_add_to_cart_with_whatsapp' );




// IMAGE ZOOM
function add_custom_script_to_footer() {
?>
    <div class="fs-img-wrapper"></div>

    <script type="text/javascript">
        (function() {
            if (jQuery(window).width() < 768) {
                jQuery(document).ready(function() {
                    const fsWrapper = jQuery('.fs-img-wrapper');
                    const imgs = jQuery('.zoom img');
                    imgs.on('click', function() {
                        fsWrapper.html(`<span id="close-fs" class="fal fa-times"></span><img id="pinch-to-zoom" src="${this.getAttribute('src')}">`);
                        fsWrapper.fadeIn();
                        const el = document.querySelector('#pinch-to-zoom');
                        new PinchZoom.default(el, {});
                        jQuery('#close-fs').on('click', function() {
                            fsWrapper.fadeOut();
                        });
                    });
                });
            }
        })();
    </script>
<?php
}
add_action('wp_footer', 'add_custom_script_to_footer');






// Add Google Tag Manager code to the <head> section
function add_google_tag_manager() {
    ?>
    <!-- Google Tag Manager -->
    <script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':
    new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],
    j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src=
    'https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);
    })(window,document,'script','dataLayer','GTM-WRCQP8LW');</script>
    <!-- End Google Tag Manager -->
    <?php
}
add_action('wp_head', 'add_google_tag_manager');

// Add Google Tag Manager code immediately after the opening <body> tag
function add_google_tag_manager_noscript() {
    ?>
    <!-- Google Tag Manager (noscript) -->
    <noscript><iframe src="https://www.googletagmanager.com/ns.html?id=GTM-WRCQP8LW"
    height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>
    <!-- End Google Tag Manager (noscript) -->
    <?php
}
add_action('wp_body_open', 'add_google_tag_manager_noscript');



// Register custom REST API endpoint
add_action('rest_api_init', function () {
    // Define namespace for your custom endpoint
    $namespace = 'custom/v1';

    // Define endpoint route
    $route = '/hello-world';

    // Define endpoint arguments
    $args = array(
        'methods'             => 'GET',
        'callback'            => 'custom_hello_world_callback',
        'permission_callback' => '__return_true', // No permission callback, accessible to all
    );

    // Register the custom endpoint
    register_rest_route($namespace, $route, $args);
});

// Callback function to handle requests to the custom endpoint
function custom_hello_world_callback($request) {
	

    // create wc query to get products and then when loop through them get the attributes 

    $args = array(
        'status' => array( 'draft', 'publish' ),
        'limit' => 0,
        'page' => 0,
        'orderby' => 'date',
        'order' => 'DESC'
    );

    // Create a new product query instance
    $query = new WC_Product_Query($args);

    // Get products based on the query
    $products = $query->get_products();

    echo 'Total products: ' . count($products) . '<br>';

    if (count($products) === 0) {
        echo 'No products found';
        return;
    }

    $affected_rows = 0;

    // Prepare and index each product
    foreach ($products as $product) {
        $product_attribute = $product->get_attributes();

        // loop attributes and find attribute with name pa_rrobat and then update it
        foreach ($product_attribute as $key => $value) {
            if ($value->get_name() === 'pa_rrobat') {
                
                $pa_rrobat = $value;
                $pa_rrobat_options = $pa_rrobat->get_options();

                if (count($pa_rrobat_options) === 0) {

                   add_or_update_product_attribute($product->get_id());
                   $affected_rows++;
                }
                break;
            }
        }

        // print_r($product_attribute);
    }

    echo 'Affected rows: ' . $affected_rows;

    // global $wpdb;

    // $sql = "
    //     SELECT p.ID, p.post_title
    //     FROM {$wpdb->posts} p
    //     INNER JOIN {$wpdb->term_relationships} tr ON p.ID = tr.object_id
    //     INNER JOIN {$wpdb->term_taxonomy} tt ON tr.term_taxonomy_id = tt.term_taxonomy_id
    //     INNER JOIN {$wpdb->terms} t ON tt.term_id = t.term_id
    //     WHERE p.post_type = 'product'
    //     AND p.post_status = 'publish'
    //     AND tt.taxonomy = 'pa_rrobat'
    // ";

    

    // $products = $wpdb->get_results($sql);

    // if (count($products) === 0) {
    //     echo 'No products found';
    //     return;
    // }

    // print_r(count($products));
    // echo '<br>';
    // foreach ($products as $product) {
    //     $wpc = wc_get_product($product->ID);

    //     echo 'ID: ' . $product->ID . ' | Title: ' . $product->post_title . ' | SKU: ' . $wpc->sku . '<br>';
    // }


    // return rest response
    // print_r($products);

    // // Prepare and index each product
    // foreach ($products as $product) {
    //     $product_id = $product->get_id();
    //     add_or_update_product_attribute($product_id);
    // }

    // wp_reset_postdata();

    // echo 'Go Next';
}


function add_or_update_product_attribute($product_id) {
    // retrieve the product
    $product = wc_get_product( $product_id );

    // // get all attributes
    $attributes = $product->get_attributes();

    // // make a copy from the attribute to be updated
    $pa_rrobat = $attributes['pa_rrobat'];

    // // delete pa_rrobat from the attributes
    unset( $attributes['pa_rrobat'] );

    // // set the attributes without pa_rrobat
    $product->set_attributes( $attributes );

    // // save the product
    $product->save();

    // set your new options for pa_rrobat
    $pa_rrobat->set_options( [ 'XS', 'S', 'M', 'L', 'XL', 'XXL' ] );

    // add the WC_Product_Attribute object back again
    $attributes['pa_rrobat'] = $pa_rrobat;

    // set the attributes, now with pa_rrobat in it
    $product->set_attributes( $attributes );

    // save it again
    $product->save();
}






/*
function add_flying_ball_css() {
    echo '
    <style>
        .flying-container {
            position: fixed;
            top: 0;
            left: -200px;
            right: -200px;
            width: calc(100vw + 400px);
            height: 1px;
            z-index: 9999;
            pointer-events: none;
        }

        .flying-ball {
            width: 140px;
            height: 140px;
            animation: flying 60s infinite linear;
            position: absolute;
            top: 40px;
        }

        .flying-ball img {
            width: 100%;
            height: 100%;
            object-fit: contain;
            display: block;
            animation: ballspin 2s infinite linear;
        }

        @keyframes flying {
            0% {
                transform: rotateY(180deg);
                left: -200px;
                top: 40px;
            }
            25% {
                transform: rotateY(180deg);
                left: calc(100vw + 400px);
                top: 100px;
            }
            25.0001% {
                transform: rotateY(0deg);
                left: calc(100vw + 400px);
                top: calc(100vh - 180px);
            }
            50% {
                transform: rotateY(0deg);
                left: -200px;
                top: calc(100vh - 280px);
            }
            50.0001% {
                transform: rotateY(180deg);
                left: -200px;
                top: 40px;
            }
            75% {
                transform: rotateY(180deg);
                left: calc(100vw + 400px);
                top: 100px;
            }
            75.0001% {
                transform: rotateY(0deg);
                left: calc(100vw + 400px);
                top: calc(100vh - 280px);
            }
            100% {
                transform: rotateY(0deg);
                left: -200px;
                top: calc(100vh - 480px);
            }
        }

        @keyframes ballspin {
            0% {
                transform: rotate(0deg);
            }
            100% {
                transform: rotate(360deg);
            }
        }

        @media only screen and (max-width: 600px) {
            .flying-container {
                left: -100px;
                right: -100px;
                width: calc(100vw + 200px);
            }

            .flying-ball {
                width: 75px;
                height: 75px;
                animation: flying 30s infinite linear;
                position: absolute;
                top: 40px;
            }

            .flying-ball img {
                animation: ballspin 1.5s infinite linear;
            }

            @keyframes flying {
                0% {
                    transform: rotateY(180deg);
                    left: -200px;
                    top: 40px;
                }
                25% {
                    transform: rotateY(180deg);
                    left: calc(100vw + 200px);
                    top: 100px;
                }
                25.0001% {
                    transform: rotateY(0deg);
                    left: calc(100vw + 200px);
                    top: calc(100vh - 120px);
                }
                50% {
                    transform: rotateY(0deg);
                    left: -100px;
                    top: calc(100vh - 180px);
                }
                50.0001% {
                    transform: rotateY(180deg);
                    left: -100px;
                    top: 40px;
                }
                75% {
                    transform: rotateY(180deg);
                    left: calc(100vw + 200px);
                    top: 100px;
                }
                75.0001% {
                    transform: rotateY(0deg);
                    left: calc(100vw + 200px);
                    top: calc(100vh - 120px);
                }
                100% {
                    transform: rotateY(0deg);
                    left: -100px;
                    top: calc(100vh - 260px);
                }
            }
        }
    </style>
    ';
}
add_action('wp_head', 'add_flying_ball_css');

function add_flying_ball_html() {
    echo '
    <div class="flying-container">
        <div class="flying-ball">
            <img src="https://dakabrand.uk/wp-content/uploads/2026/06/JD8031_3_HARDWARE_Photography_BackCenterView_transparent.webp" alt="2026 World Cup Ball">
        </div>
    </div>
    ';
}
add_action('wp_body_open', 'add_flying_ball_html');
*/







/*
function add_flying_image_css() {
    echo '
    <style>
        .flying-container {
            position: fixed;
            top: 0;
            left: -200px;
            right: -200px;
            width: calc(100vw + 400px);
            height: 1px;
            z-index: 9999;
        }

        .flying-image {
            width: 100%;
            max-width: 200px;
            animation: flying 60s infinite;
            position: absolute;
            top: 40px;
        }

        @keyframes flying {
            0% {
                transform: rotateY(180deg);
                left: -200px;
                top: 40px;
            }
            25% {
                transform: rotateY(180deg);
                left: calc(100vw + 400px);
                top: 100px;
            }
            25.0001% {
                transform: rotateY(0deg);
                left: calc(100vw + 400px);
                top: calc(100vh - 40px);
            }
            50% {
                transform: rotateY(0deg);
                left: -200px;
                top: calc(100vh - 140px);
            }
            50.0001% {
                transform: rotateY(180deg);
                left: -200px;
                top: 40px;
            }
            75% {
                transform: rotateY(180deg);
                left: calc(100vw + 400px);
                top: 100px;
            }
            75.0001% {
                transform: rotateY(0deg);
                left: calc(100vw + 400px);
                top: calc(100vh - 140px);
            }
            100% {
                transform: rotateY(0deg);
                left: -200px;
                top: calc(100vh - 340px);
            }
        }

        @media only screen and (max-width: 600px) {
            .flying-container {
                left: -100px;
                right: -100px;
                width: calc(100vw + 200px);
            }

            .flying-image {
                width: 100px;
                animation: flying 30s infinite;
                position: absolute;
                top: 40px;
            }

            @keyframes flying {
                0% {
                    transform: rotateY(180deg);
                    left: -200px;
                    top: 40px;
                }
                25% {
                    transform: rotateY(180deg);
                    left: calc(100vw + 200px);
                    top: 100px;
                }
                25.0001% {
                    transform: rotateY(0deg);
                    left: calc(100vw + 200px);
                    top: calc(100vh - 140px);
                }
                50% {
                    transform: rotateY(0deg);
                    left: -100px;
                    top: calc(100vh - 200px);
                }
                50.0001% {
                    transform: rotateY(180deg);
                    left: -100px;
                    top: 40px;
                }
                75% {
                    transform: rotateY(180deg);
                    left: calc(100vw + 200px);
                    top: 100px;
                }
                75.0001% {
                    transform: rotateY(0deg);
                    left: calc(100vw + 200px);
                    top: calc(100vh - 140px);
                }
                100% {
                    transform: rotateY(0deg);
                    left: -100px;
                    top: calc(100vh - 300px);
                }
            }
        }
    </style>
    ';
}
add_action('wp_head', 'add_flying_image_css');

function add_flying_image_html() {
    echo '
    <div class="flying-container">
        <div class="flying-image">
            <img src="https://dakabrand.uk/wp-content/uploads/2024/05/gk1.gif" alt="Son Goku Nimbus">
        </div>
    </div>
    ';
}
add_action('wp_body_open', 'add_flying_image_html');
*/



function my_custom_scripts() {
    // Register the script
    wp_register_script('custom-script', get_template_directory_uri() . '/assets/js/myscript.js', array('jquery'), null, true);

    // Enqueue the script
    wp_enqueue_script('custom-script');
}
add_action('wp_enqueue_scripts', 'my_custom_scripts');




function disable_woocommerce_cart_fragments() {
    wp_dequeue_script('wc-cart-fragments');
    wp_deregister_script('wc-cart-fragments');
}
add_action('wp_enqueue_scripts', 'disable_woocommerce_cart_fragments', 11);













/* fucntion for search page */
function gliterin_ajax_search() {
    // Sanitize and retrieve search query
    $search_query = isset($_GET['sc']) ? sanitize_text_field($_GET['sc']) : '';
    $paged = isset($_GET['pg']) ? absint($_GET['pg']) : 1;
	
	if (strlen($search_query) <= 2){
		echo '<p>Term length must be more than 2 charaters.</p>';
		wp_die();
	}

    // WP Query arguments
    $args = array(
        'post_type'      => 'product',
        'posts_per_page' => 20, // Number of products per page
        's'               => $search_query,
        'paged'           => $paged,
    );

    $query = new WP_Query($args);

    // Start the loop if posts are found
    if ($query->have_posts()) :
        ?>
        <div class="minimog-main-post minimog-grid-wrapper minimog-product group-style-01 style-grid-01"
             data-grid="{&quot;type&quot;:&quot;grid&quot;,&quot;columns&quot;:4,&quot;columnsTabletExtra&quot;:&quot;3&quot;,&quot;columnsMobileExtra&quot;:&quot;2&quot;,&quot;gutter&quot;:&quot;30&quot;,&quot;gutterTabletExtra&quot;:&quot;20&quot;,&quot;gutterMobileExtra&quot;:&quot;16&quot;}"
             style="--grid-columns-desktop: 4; --grid-columns-tablet-extra: 3; --grid-columns-mobile-extra: 2; --grid-gutter-desktop: 30; --grid-gutter-tablet-extra: 20; --grid-gutter-mobile-extra: 16; --grid-real-width: 374px;"
             data-active-columns="2">
            <div class="minimog-grid lazy-grid loaded" style="position: relative; display: grid;">
                <?php
                // Loop through posts
                while ($query->have_posts()) : $query->the_post();
                    $product = wc_get_product(get_the_ID());
					
                    $regular_price = (float) $product->get_price();
                    $sale_price = (float) woo_discount_rules_get_product_discount_price($product);
                    $discount_percentage = ($sale_price && $sale_price < $regular_price) ? round((($regular_price - $sale_price) / $regular_price) * 100) : 0;
                    $image_url = get_the_post_thumbnail_url(get_the_ID(), 'medium');
					$stock_status = $product->get_stock_status();
                    ?>
                    <div class="grid-item product type-product post-<?php echo get_the_ID(); ?> status-publish first instock">
                        <div class="product-wrapper">
                            <div class="product-thumbnail">
                                <div class="product-badges product-badges-label">
                                    <?php if ($discount_percentage > 0) : ?>
                                        <div class="vi-sctv-sale-badge onsale"><span><?php echo $discount_percentage; ?>%</span></div>
                                    <?php endif; ?>
                                    <!---<div class="new"><span>New</span></div>-->
                                </div>
                                <div class="thumbnail">
                                    <a href="<?php the_permalink(); ?>" class="woocommerce-LoopProduct-link woocommerce-loop-product__link">
                                        <div class="product-main-image">
                                            <img fetchpriority="high" src="<?php echo esc_url($image_url); ?>" width="300" height="300" alt="<?php the_title(); ?>" class="product-main-image-img">
                                        </div>
                                    </a>
                                </div>
                            </div>

                            <div class="product-actions">
                                <div class="product-action quick-view-btn style-01 hint--bounce hint--left" data-hint="Quick view" data-pid="<?php echo get_the_ID(); ?>">
                                    <a class="quick-view-icon" href="#">Quick view</a>
                                </div>
                            </div>

                            <div class="product-info">
                                <div class="my-stock-badge">
									<?php 
										if ($stock_status === 'onbackorder') {
											echo 'preorder';
										} elseif ($stock_status === 'instock') {
											echo 'stock';
										} elseif ($stock_status === 'outofstock') {
											echo 'outofstock';
										} else {
											echo $stock_status; // For debugging
										}
									?>
								</div>
                                <div class="loop-whatsapp-wrapper">
                                    <a href="https://wa.me/+355683885286?text=INTERESOHEM+PER+PRODUKTIN%3A+<?php echo urlencode(get_the_title()); ?>+me+SKU%3A+<?php echo $product->get_sku(); ?>" target="_blank">
                                        <img src="https://dakabrand.uk/wp-content/uploads/2024/01/whatsapp-logo.svg" alt="WhatsApp">
                                    </a>
                                </div>
                                <h3 class="woocommerce-loop-product__title post-title-2-rows">
                                    <a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
                                </h3>

                                <div class="price">
                                    <?php if ($sale_price && $sale_price < $regular_price) : ?>
                                        <del><span class="woocommerce-Price-amount amount"><bdi><span class="woocommerce-Price-currencySymbol">€</span><?php echo number_format($regular_price, 2); ?></bdi></span></del><br>
                                        <ins><span class="woocommerce-Price-amount amount"><bdi><span class="woocommerce-Price-currencySymbol">€</span><?php echo number_format($sale_price, 2); ?></bdi></span></ins>
                                    <?php else : ?>
                                        <span class="woocommerce-Price-amount amount"><bdi><span class="woocommerce-Price-currencySymbol">€</span><?php echo number_format($regular_price, 2); ?></bdi></span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endwhile; wp_reset_postdata(); ?>
            </div>

            <?php
            // Pagination
            $total_pages = $query->max_num_pages;
            if ($total_pages > 1) :
				echo '<div class="minimog-grid-pagination">';
				echo '<ul class="page-pagination">';

				// Display "Previous" button if not on the first page
				if ($paged > 1) {
					echo '<li><a class="prev page-numbers" href="https://dakabrand.uk/search/?sc=' . $search_query . '&pg=' . $paged - 1 . '"><span class="far fa-angle-double-left"></span></a></li>';
				}

				// Display first page and ellipsis if needed
				if ($paged > 3) {
					echo '<li><a class="page-numbers" href="https://dakabrand.uk/search/?sc=' . $search_query . '&pg=' . 1 . '">1</a></li>';
					echo '<li><span class="page-numbers dots">…</span></li>';
				}

				// Loop through a range of pages around the current page
				for ($i = max(1, $paged - 2); $i <= min($total_pages, $paged + 2); $i++) {
					if ($i == $paged) {
						// Current page - add "current" class
						echo '<li><span aria-current="page" class="page-numbers current">' . $i . '</span></li>';
					} else {
						// Other pages - links to the pages
						echo '<li><a class="page-numbers" href="https://dakabrand.uk/search/?sc=' . $search_query . '&pg=' . $i . '">' . $i . '</a></li>';
					}
				}

				// Display last page and ellipsis if needed
				if ($paged < $total_pages - 2) {
					echo '<li><span class="page-numbers dots">…</span></li>';
					echo '<li><a class="page-numbers" href="https://dakabrand.uk/search/?sc=' . $search_query . '&pg=' . $total_pages . '">' . $total_pages . '</a></li>';
				}

				// Display "Next" button if not on the last page
				if ($paged < $total_pages) {
					echo '<li><a class="next page-numbers" href="https://dakabrand.uk/search/?sc=' . $search_query . '&pg=' . $paged + 1 . '"><span class="far fa-angle-double-right"></span></a></li>';
				}

				echo '</ul>';
				echo '</div>';
			endif;
            ?>
        </div>
    <?php else :
        echo '<p>No products found.</p>';
    endif;

    wp_die();
}

add_action('wp_ajax_gliterin_search', 'gliterin_ajax_search');
add_action('wp_ajax_nopriv_gliterin_search', 'gliterin_ajax_search');







function woo_discount_rules_get_product_discount_price($product) {
    // global $product;
    $sale_price = $product->get_price(); 

    /**
    * Get the discount price of a product
    * @param $sale_price float|integer
    * @param $product object[wc_get_product($product_id))]|integer
    * @return float|integer
    */
    $sale_price = apply_filters('advanced_woo_discount_rules_get_product_discount_price', $sale_price, $product);

    return $sale_price;
}





/**
 * Add custom data to WooCommerce REST API product response for single product
 */
add_filter('woocommerce_rest_prepare_product_object', 'add_custom_data_to_product_response', 10, 3);

function add_custom_data_to_product_response($response, $product, $request) {
    // Only modify response for single product requests
    if (preg_match('~/wc/v3/products/(?P<id>[\d]+)~', $request->get_route())) {
        // Get the current response data as an array
        $data = $response->get_data();
        
        // Add woo_discount_rule_get_discount_detalis_price_of_product result to the response
        $discount_details = rest_api_woo_discount_rule_get_discount_detalis_price_of_product($product);

        if ($discount_details) {
            $data['discount_details'] = $discount_details;
        } else {
            $data['discount_details'] = (object) [];
        }
       
        $sale_price = $product->get_sale_price();

        $discount_price = rest_api_woo_discount_rules_get_product_discount_price($product);
        
        if($discount_price) {
            $sale_price = $discount_price;
        }

        // Add the discount price to the response
        $data['sale_price'] = (string) $sale_price;

        $brands = getProductBrands($product->get_id());
        $data['brands'] = $brands;
        
        // Set the modified data back to the response
        $response->set_data($data);
    }
    
    return $response;
}

/**
 * Add custom data to WooCommerce REST API product response for product collections
 */
add_filter('woocommerce_rest_prepare_product_object', function ($response, $product, $request) {
    // Only modify response for product collection requests
    if (strpos($request->get_route(), '/wc/v3/products') !== false && empty($request['id'])) {
        $data = $response->get_data();

        // Add discount details
        $discount_details = rest_api_woo_discount_rule_get_discount_detalis_price_of_product($product);
        $data['discount_details'] = $discount_details ? $discount_details : (object) [];

        // Add sale price
        $sale_price = $product->get_sale_price();
        $discount_price = rest_api_woo_discount_rules_get_product_discount_price($product);
        $data['sale_price'] = $discount_price ? (string) $discount_price : (string) $sale_price;

        // Add product brands
        $brands = getProductBrands($product->get_id());
        $data['brands'] = $brands;

        $response->set_data($data);
    }

    return $response;
}, 10, 3);


function getProductBrands($product_id) {
    $product_brand = get_the_terms($product_id, 'product_brand');
    $brands = array();
    if (is_array($product_brand) && !empty($product_brand)) {
        foreach ($product_brand as $term) {
            $brands[] = array(
                'id' => $term->term_id,
                'name' => $term->name,
                'slug' => $term->slug,
            );
        }
    }
    return $brands;
}



 /**
* Get the discount details of a product
* @param false
* @param $product object[wc_get_product($product_id))]|integer
* @param $quantity integer [optional]
* @param $custom_price float|integer [optional]
* @return array|false - Returns false if there is no discount
*/
function rest_api_woo_discount_rule_get_discount_detalis_price_of_product($product) {

    try {
        $discount_details = apply_filters('advanced_woo_discount_rules_get_product_discount_details', false, $product);

        return $discount_details;
    } catch (Exception $e) {
        return false;
    }
}


function rest_api_woo_discount_rules_get_product_discount_price($product) {
    try {
        // global $product;
        $sale_price = $product->get_price(); 

        /**
        * Get the discount price of a product
        * @param $sale_price float|integer
        * @param $product object[wc_get_product($product_id))]|integer
        * @return float|integer
        */
        $sale_price = apply_filters('advanced_woo_discount_rules_get_product_discount_price', $sale_price, $product);

        return $sale_price;
    } catch (Exception $e) {
        return false;
    }
}











function g_el_template($id){
  if (!class_exists('\Elementor\Plugin')) return '';
  return \Elementor\Plugin::instance()->frontend->get_builder_content_for_display((int)$id);
}

add_shortcode('g_women_mega_menu', function () {
  ob_start(); ?>
<style>
    :root{
      --g-menu-border:#e8dddd;
      --g-menu-text:#111;
      --g-menu-underline:#111;
      --g-menu-bg:#fff;
    }

    .g-menu-root *{ box-sizing:border-box; }

    .g-menu-root{
      font-family: system-ui, -apple-system, Segoe UI, Roboto, Helvetica, Arial, sans-serif;
      color:var(--g-menu-text);
      background:var(--g-menu-bg);
		padding: 0 15px 0 0;
    }

    /* anchors the absolute dropdown */
    .g-menu-bar{
      position: relative;
      background:#fff;
      border-top: 1px solid var(--g-menu-border);
      border-bottom: 1px solid var(--g-menu-border);
    }

    .g-menu-inner{
      margin: 0 auto;
      padding: 0;
    }
	
	.g-menu-root ul {
		display: block !important;
	}

    .g-menu-row{
      display:flex;
      align-items:center;
      gap: 44px;
      padding: 0;
      overflow:auto;
      scrollbar-width: thin;
    }

    .g-menu-btn{
      appearance:none;
      background:transparent !important;
      border:0;
      padding: 6px 2px;
      font: inherit;
      font-size: 16px;
      color: var(--g-menu-text);
      cursor:pointer;
      white-space:nowrap;
      position:relative;
      box-shadow: none !important;
      outline-offset: 6px;
    }

    .g-menu-btn.g-menu-active{ font-weight: 700; }
    .g-menu-btn.g-menu-active::after{
      content:"";
      position:absolute;
      left: 50%;
      transform: translateX(-50%);
      bottom: 0;
      width: 100%;
      height: 3px;
      background: var(--g-menu-underline);
      border-radius: 2px;
    }

    .g-menu-btn:hover{ color:#000; }
    .g-menu-btn:focus-visible{
      outline: 2px solid #111;
      border-radius: 8px;
    }

    /* ABSOLUTE full-width dropdown */
    .g-menu-dropdown-wrap{
      position: absolute;
      left: 0;
      right: 0;
      top: calc(100% + 5px);
      z-index: 50;
      background:#fff;
      pointer-events: none;
      overflow:hidden;
    }

    .g-menu-root.g-menu-has-open .g-menu-dropdown-wrap{
      pointer-events: auto;
      border-bottom: 1px solid var(--g-menu-border);
      box-shadow: 0 10px 30px rgba(0,0,0,.08);
    }

    .g-menu-panel{
      max-height: 0;
      opacity: 0;
      overflow:hidden;
      transition: max-height .25s ease, opacity .2s ease;
    }

    .g-menu-panel.g-menu-open{
      max-height: max-content;
      opacity: 1;
    }

    .g-menu-panel-inner{
      margin: 0 auto;
      padding: 16px 18px 18px;
      color: var(--g-menu-text);
    }

    @media (prefers-reduced-motion: reduce){
      .g-menu-panel{ transition:none; }
    }
  </style>
  <div class="g-menu-root" data-g-menu-root>
    <div class="g-menu-bar">
      <div class="g-menu-inner">
        <div class="g-menu-row">
          <button class="g-menu-btn" type="button" aria-expanded="false" data-g-menu-target="g-menu-p-newin-f">New In</button>
          <button class="g-menu-btn" type="button" aria-expanded="false" data-g-menu-target="g-menu-p-clothing-f">Clothing</button>
          <button class="g-menu-btn" type="button" aria-expanded="false" data-g-menu-target="g-menu-p-shoes-f">Shoes</button>
          <button class="g-menu-btn" type="button" aria-expanded="false" data-g-menu-target="g-menu-p-accessories-f">Accessories</button>
          <!--<button class="g-menu-btn" type="button" aria-expanded="false" data-g-menu-target="g-menu-p-brand-f">Brand</button>-->
          <button class="g-menu-btn" type="button" aria-expanded="false" data-g-menu-target="g-menu-p-preorder-f">Preorder</button>
		<a href="/product-category/big-offer-women/?stock_status=instock%3Ainstock%2Coutofstock%3Aoutofstock" target="_self" class="g-menu-btn">BIG OFFER</a>
        </div>
      </div>

      <div class="g-menu-dropdown-wrap">
        <div id="g-menu-p-newin-f" class="g-menu-panel">
          <div class="g-menu-panel-inner"><?php echo g_el_template(474808); ?></div>
        </div>
        <div id="g-menu-p-clothing-f" class="g-menu-panel">
          <div class="g-menu-panel-inner"><?php echo g_el_template(474715); ?></div>
        </div>
        <div id="g-menu-p-shoes-f" class="g-menu-panel">
          <div class="g-menu-panel-inner"><?php echo g_el_template(474772); ?></div>
        </div>
        <div id="g-menu-p-accessories-f" class="g-menu-panel">
          <div class="g-menu-panel-inner"><?php echo g_el_template(474780); ?></div>
        </div>
        <div id="g-menu-p-brand-f" class="g-menu-panel">
          <div class="g-menu-panel-inner"><?php echo g_el_template(474793); ?></div>
        </div>
        <div id="g-menu-p-preorder-f" class="g-menu-panel">
          <div class="g-menu-panel-inner"><?php echo g_el_template(474830); ?></div>
        </div>
      </div>
    </div>
  </div>

  
  <?php return ob_get_clean();
});








add_shortcode('g_man_mega_menu', function () {
  ob_start(); ?>

  <div class="g-menu-root" data-g-menu-root>
    <div class="g-menu-bar">
      <div class="g-menu-inner">
        <div class="g-menu-row">
          <button class="g-menu-btn" type="button" aria-expanded="false" data-g-menu-target="g-menu-p-newin">New In</button>
          <button class="g-menu-btn" type="button" aria-expanded="false" data-g-menu-target="g-menu-p-clothing">Clothing</button>
          <button class="g-menu-btn" type="button" aria-expanded="false" data-g-menu-target="g-menu-p-shoes">Shoes</button>
          <button class="g-menu-btn" type="button" aria-expanded="false" data-g-menu-target="g-menu-p-accessories">Accessories</button>
          <!--<button class="g-menu-btn" type="button" aria-expanded="false" data-g-menu-target="g-menu-p-brand">Brand</button>-->
          <button class="g-menu-btn" type="button" aria-expanded="false" data-g-menu-target="g-menu-p-preorder">Preorder</button>
		<a href="/product-category/big-offer/?stock_status=instock%3Ainstock%2Coutofstock%3Aoutofstock" target="_self" class="g-menu-btn">BIG OFFER</a>
        </div>
      </div>

      <div class="g-menu-dropdown-wrap">
        <div id="g-menu-p-newin" class="g-menu-panel">
          <div class="g-menu-panel-inner"><?php echo g_el_template(474809); ?></div>
        </div>
        <div id="g-menu-p-clothing" class="g-menu-panel">
          <div class="g-menu-panel-inner"><?php echo g_el_template(474707); ?></div>
        </div>
        <div id="g-menu-p-shoes" class="g-menu-panel">
          <div class="g-menu-panel-inner"><?php echo g_el_template(474757); ?></div>
        </div>
        <div id="g-menu-p-accessories" class="g-menu-panel">
          <div class="g-menu-panel-inner"><?php echo g_el_template(474764); ?></div>
        </div>
        <div id="g-menu-p-brand" class="g-menu-panel">
          <div class="g-menu-panel-inner"><?php echo g_el_template(474788); ?></div>
        </div>
        <div id="g-menu-p-preorder" class="g-menu-panel">
          <div class="g-menu-panel-inner"><?php echo g_el_template(474829); ?></div>
        </div>
      </div>
    </div>
  </div>

  <script>
    (function () {
      const roots = Array.from(document.querySelectorAll('[data-g-menu-root]'));
      if (!roots.length) return;

      const apiByRoot = new WeakMap();

      function setPanelOpen(panel, open) {
        if (!panel) return;

        // If currently maxHeight is "none", set it to a pixel value so we can animate
        const isNone = panel.style.maxHeight === 'none';
        if (isNone) panel.style.maxHeight = panel.scrollHeight + 'px';

        if (open) {
          panel.classList.add('g-menu-open');
          // set to scrollHeight to animate open
          panel.style.maxHeight = panel.scrollHeight + 'px';

          const onEnd = (e) => {
            if (e.propertyName !== 'max-height') return;
            panel.removeEventListener('transitionend', onEnd);
            // allow content to grow after opening (Elementor content/images)
            if (panel.classList.contains('g-menu-open')) {
              panel.style.maxHeight = 'none';
            }
          };
          panel.addEventListener('transitionend', onEnd);
        } else {
          panel.classList.remove('g-menu-open');
          // animate close
          panel.style.maxHeight = panel.scrollHeight + 'px';
          // force reflow
          void panel.offsetHeight;
          panel.style.maxHeight = '0px';
        }
      }

      function setupMenu(root) {
        const buttons = Array.from(root.querySelectorAll('.g-menu-btn'));
        const panels  = Array.from(root.querySelectorAll('.g-menu-panel'));

        function closeAll() {
          root.classList.remove('g-menu-has-open');
          buttons.forEach(b => {
            b.classList.remove('g-menu-active');
            b.setAttribute('aria-expanded', 'false');
          });
          panels.forEach(p => setPanelOpen(p, false));
        }

        function openOne(btn) {
          const id = btn.getAttribute('data-g-menu-target');
          const panel = root.querySelector('#' + CSS.escape(id));
          if (!panel) return;

          // close current (within this root only)
          panels.forEach(p => setPanelOpen(p, false));
          buttons.forEach(b => {
            b.classList.toggle('g-menu-active', b === btn);
            b.setAttribute('aria-expanded', b === btn ? 'true' : 'false');
          });

          root.classList.add('g-menu-has-open');
          setPanelOpen(panel, true);
        }

        function toggle(btn) {
          const isOpen = btn.getAttribute('aria-expanded') === 'true';
          if (isOpen) closeAll();
          else openOne(btn);
        }

        // Click handling (scoped)
        root.addEventListener('click', (e) => {
          const btn = e.target.closest('.g-menu-btn');
          if (!btn || !root.contains(btn)) return;
          toggle(btn);
        });

        // Keyboard (Enter / Space)
        root.addEventListener('keydown', (e) => {
          const btn = e.target.closest('.g-menu-btn');
          if (!btn || !root.contains(btn)) return;

          if (e.key === 'Enter' || e.key === ' ') {
            e.preventDefault();
            toggle(btn);
          }
        });

        // Keep open panel height correct on resize
        window.addEventListener('resize', () => {
          const openPanel = root.querySelector('.g-menu-panel.g-menu-open');
          if (openPanel && openPanel.style.maxHeight !== 'none') {
            openPanel.style.maxHeight = openPanel.scrollHeight + 'px';
          }
        });

        return { closeAll };
      }

      roots.forEach(r => apiByRoot.set(r, setupMenu(r)));

      // Click outside closes the menus you clicked outside of
      document.addEventListener('click', (e) => {
        roots.forEach(root => {
          if (!root.contains(e.target)) apiByRoot.get(root)?.closeAll();
        });
      });

      // Escape closes all menus
      document.addEventListener('keydown', (e) => {
        if (e.key !== 'Escape') return;
        roots.forEach(root => apiByRoot.get(root)?.closeAll());
      });
    })();
  </script>

  
  <?php return ob_get_clean();
});


add_action('wp_footer', function () { ?>
  <script>
    document.addEventListener('DOMContentLoaded', function () {
      var manTab = document.getElementById('tab-title-29ea2c3');
      var womenTab = document.getElementById('tab-title-36ece14');

      // CLICK -> redirect
      function goTo(url) {
        return function (e) {
          // stop the tab JS from toggling content
          e.preventDefault();
          e.stopPropagation();
          // in case Minimog uses delegated listeners
          if (e.stopImmediatePropagation) e.stopImmediatePropagation();
          window.location.href = url;
        };
      }

      if (manTab) {
        manTab.style.cursor = 'pointer';
        manTab.addEventListener('click', goTo('/man/'), true);   // capture=true helps beat other handlers
      }

      if (womenTab) {
        womenTab.style.cursor = 'pointer';
        womenTab.addEventListener('click', goTo('/woman/'), true);
      }

      // ---- your existing "active on load" logic ----
      if (manTab) manTab.classList.remove('active');
      if (womenTab) womenTab.classList.remove('active');

      var path = window.location.pathname || "";
      var isMan = /\/(man|men)(\/|$)/i.test(path) || /\/product-category\/big-offer(\/|$)/i.test(path);

      const root = document.querySelector('.minimog-tabs__content');
      if (root) {
        root.querySelectorAll('.tab-content.active').forEach(n => n.classList.remove('active'));
      }

      if (isMan) {
        if (manTab) manTab.classList.add('active');
        const target = root?.querySelector('.tab-content[data-tab="2"]');
        if (target) target.classList.add('active');
      } else {
        if (womenTab) womenTab.classList.add('active');
        const target = root?.querySelector('.tab-content[data-tab="1"]');
        if (target) target.classList.add('active');
      }
    });
  </script>
<?php }, 99);






add_action('wp_footer', 'daka_keep_stock_status_param_script', 100);
function daka_keep_stock_status_param_script() {
    ?>
    <script>
    (function () {
        const DEFAULT_STOCK_STATUS = 'onbackorder:onbackorder';

        function isValidStockStatus(val) {
            return typeof val === 'string' && val.trim() !== '';
        }

        function getCurrentStockStatus() {
            const url = new URL(window.location.href);
            const stockStatus = url.searchParams.get('stock_status');

            if (isValidStockStatus(stockStatus)) {
                return stockStatus;
            }

            return DEFAULT_STOCK_STATUS;
        }

        function ensureCurrentUrlHasStockStatus() {
            const url = new URL(window.location.href);
            const currentValue = url.searchParams.get('stock_status');

            if (!isValidStockStatus(currentValue)) {
                url.searchParams.set('stock_status', DEFAULT_STOCK_STATUS);
                history.replaceState({}, '', url.toString());
            }
        }

        function shouldSkipLink(link) {
            if (!link) return true;

            if (link.closest('.branding__logo')) return true;

            const href = link.getAttribute('href');
            if (!href) return true;

            if (
                href.startsWith('#') ||
                href.startsWith('mailto:') ||
                href.startsWith('tel:') ||
                href.startsWith('javascript:')
            ) {
                return true;
            }

            try {
                const url = new URL(link.href, window.location.origin);

                if (url.origin !== window.location.origin) return true;

                return false;
            } catch (e) {
                return true;
            }
        }

        function updateLinkHref(link, stockStatus) {
            if (shouldSkipLink(link)) return;

            try {
                const url = new URL(link.href, window.location.origin);

                if (!url.searchParams.has('stock_status')) {
                    url.searchParams.set('stock_status', stockStatus);
                    link.href = url.toString();
                }
            } catch (e) {}
        }

        function processRoot(root, stockStatus) {
            if (!root || !root.querySelectorAll) return;

            root.querySelectorAll('a[href]').forEach(function(link) {
                updateLinkHref(link, stockStatus);
            });

            root.querySelectorAll('*').forEach(function(el) {
                if (el.shadowRoot) {
                    processRoot(el.shadowRoot, stockStatus);
                }
            });
        }

        function appendStockStatusToAllLinks() {
            const stockStatus = getCurrentStockStatus();
            processRoot(document, stockStatus);
        }

        function handleLogoClick(e) {
            const path = e.composedPath ? e.composedPath() : [];
            let logoLink = null;

            for (let i = 0; i < path.length; i++) {
                const el = path[i];
                if (el && el.matches && el.matches('.branding__logo a')) {
                    logoLink = el;
                    break;
                }
            }

            if (!logoLink) {
                logoLink = e.target.closest ? e.target.closest('.branding__logo a') : null;
            }

            if (!logoLink) return;

            e.preventDefault();

            try {
                const url = new URL(logoLink.href, window.location.origin);
                url.searchParams.delete('stock_status');
                window.location.href = url.toString();
            } catch (err) {
                window.location.href = logoLink.href;
            }
        }

        function observeRoot(root) {
            if (!root || !root.querySelectorAll) return;

            const observer = new MutationObserver(function() {
                appendStockStatusToAllLinks();
                attachShadowObservers();
            });

            observer.observe(root, {
                childList: true,
                subtree: true
            });
        }

        const observedRoots = new WeakSet();

        function attachShadowObservers() {
            function walk(root) {
                if (!root || !root.querySelectorAll || observedRoots.has(root)) return;

                observedRoots.add(root);
                observeRoot(root);

                root.querySelectorAll('*').forEach(function(el) {
                    if (el.shadowRoot) {
                        walk(el.shadowRoot);
                    }
                });
            }

            walk(document);
        }

        function refreshAll() {
            ensureCurrentUrlHasStockStatus();
            appendStockStatusToAllLinks();
            attachShadowObservers();
        }

        function init() {
            refreshAll();

            document.addEventListener('click', handleLogoClick, true);

            const originalPushState = history.pushState;
            history.pushState = function () {
                originalPushState.apply(this, arguments);
                setTimeout(refreshAll, 50);
            };

            const originalReplaceState = history.replaceState;
            history.replaceState = function () {
                originalReplaceState.apply(this, arguments);
                setTimeout(refreshAll, 50);
            };

            window.addEventListener('popstate', function() {
                setTimeout(refreshAll, 50);
            });

            setInterval(attachShadowObservers, 1000);
        }

        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', init);
        } else {
            init();
        }
    })();
    </script>
    <?php
}



add_filter('wp_terms_checklist_args', function($args, $post_id) {
    add_filter('get_terms', function($terms, $taxonomies) {
        if (!in_array('product_cat', (array)$taxonomies)) {
            return $terms;
        }

        foreach ($terms as $term) {
            if ($term->slug === 'big-offer') {
                $term->name .= ' (Men)';
            }

            if ($term->slug === 'big-offer-women') {
                $term->name .= ' (Women)';
            }
        }

        return $terms;
    }, 10, 2);

    return $args;
}, 10, 2);



// 1. Hide Brands from Products menu.
add_action('admin_menu', function () {
    global $submenu;

    $parent = 'edit.php?post_type=product';

    if (!isset($submenu[$parent])) {
        return;
    }

    foreach ($submenu[$parent] as $key => $item) {

        if (
            isset($item[2]) &&
            (
                strpos($item[2], 'product_brand') !== false ||
                strtolower(wp_strip_all_tags($item[0])) === 'brands'
            )
        ) {
            unset($submenu[$parent][$key]);
        }
    }

}, 9999);


// 2. Hide Brands from product editor.
add_action('add_meta_boxes_product', function () {
    remove_meta_box(
        'product_branddiv',
        'product',
        'side'
    );
}, 999);


// 3. Hide Brands column from Products table.
add_filter('manage_edit-product_columns', function ($columns) {
    unset($columns['product_brand']);
    unset($columns['taxonomy-product_brand']);

    return $columns;
}, 999);


// 4. Hide Brands from Quick Edit.
add_action('admin_head-edit.php', function () {
    $screen = get_current_screen();

    if ($screen && $screen->id === 'edit-product') {
        echo '<style>
            .inline-edit-col .product_brand-checklist,
            .inline-edit-col label:has(select[name="product_brand"]) {
                display: none !important;
            }
        </style>';
    }
});



/*
 * Falling Heart Confetti (infinite, no text)
 * Add to your (child theme) functions.php
 *

add_action('wp_footer', function () {
  if (is_admin()) return;
  echo '<div id="valentine-heart-confetti" aria-hidden="true"></div>';
}, 5);

add_action('wp_head', function () {
  if (is_admin()) return;

  echo '<style id="valentine-heart-confetti-css">
  #valentine-heart-confetti{
    --vh-heart-color: #ff304f;
    --vh-heart-size: 20px;

    position: fixed;
    inset: 0;
    width: 100%;
    height: 100%;
    pointer-events: none;
    overflow: hidden;
    z-index: 999999;
  }

  #valentine-heart-confetti .vh-heart{
    position: absolute;
    top: -60px;
    left: 0;

    --vh-size: var(--vh-heart-size);

    width: var(--vh-size);
    height: var(--vh-size);
    background: var(--vh-heart-color);

    transform: rotate(45deg);
    animation: vh-fall 2.5s linear infinite;

    will-change: transform, top, opacity;
  }

  #valentine-heart-confetti .vh-heart::before,
  #valentine-heart-confetti .vh-heart::after{
    content: "";
    position: absolute;
    width: var(--vh-size);
    height: var(--vh-size);
    background: var(--vh-heart-color);
    border-radius: 50%;
  }

  #valentine-heart-confetti .vh-heart::before{
    top: calc(-1 * var(--vh-size) / 2);
    left: 0;
  }
  #valentine-heart-confetti .vh-heart::after{
    top: 0;
    left: calc(-1 * var(--vh-size) / 2);
  }

  @keyframes vh-fall{
    0%   { top: -60px; opacity: 1; transform: rotate(45deg); }
    100% { top: 110vh; opacity: 0; transform: rotate(90deg); }
  }
  </style>';
});

add_action('wp_footer', function () {
  if (is_admin()) return;

  echo '<script id="valentine-heart-confetti-js">
  (function () {
    if (window.__vhConfettiInit) return;
    window.__vhConfettiInit = true;

    var maxHearts = 40;
    var spawnEvery = 180;

    function createHeart(container) {
      if (container.childElementCount >= maxHearts) {
        container.removeChild(container.firstElementChild);
      }

      var heart = document.createElement("div");
      heart.className = "vh-heart";

      heart.style.left = (Math.random() * 100) + "vw";

      var size = Math.floor(Math.random() * 14) + 12;
      heart.style.setProperty("--vh-size", size + "px");

      var duration = (Math.random() * 2) + 3;
      heart.style.animationDuration = duration + "s";

      heart.style.animationDelay = (Math.random() * 2) + "s";

      container.appendChild(heart);
    }

    function startInfinite() {
      var container = document.getElementById("valentine-heart-confetti");
      if (!container) return;

      for (var i = 0; i < maxHearts; i++) {
        createHeart(container);
      }

      setInterval(function () {
        createHeart(container);
      }, spawnEvery);
    }

    if (document.readyState === "loading") {
      document.addEventListener("DOMContentLoaded", startInfinite);
    } else {
      startInfinite();
    }
  })();
  </script>';
}, 99);

*/
	

	
	// Disable out of stock variations to force theme swatches to cross them out
add_filter( 'woocommerce_variation_is_active', 'daka_disable_out_of_stock_variations', 10, 2 );

function daka_disable_out_of_stock_variations( $is_active, $variation ) {
    if ( ! $variation->is_in_stock() ) {
        return false; // Tells WooCommerce this variation cannot be interacted with
    }
    return $is_active;
}

	
	
	
	
	
?>




