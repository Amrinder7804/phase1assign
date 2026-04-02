<?php

function phase1_legal_back_button() {
  if ( ! is_page( array( 'privacy-policy', 'terms-and-conditions', 3, 66 ) ) ) return;
  echo '<a href="' . esc_url( wc_get_page_permalink( 'myaccount' ) ) . '" class="phase1-back-btn" aria-label="Back to Account">&#8592; Back to Account</a>';
}
add_action( 'wp_body_open', 'phase1_legal_back_button' );

function phase1_load_styles() {
  $style_path = get_stylesheet_directory() . '/style.css';
  $style_version = file_exists($style_path) ? filemtime($style_path) : wp_get_theme()->get('Version');

  wp_enqueue_style(
      'phase1-google-fonts',
      'https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400;0,700;0,900;1,400;1,700&family=Lato:ital,wght@0,400;0,700;1,400&family=Poppins:wght@400;500;600;700&display=swap',
      array(),
      null
  );

  wp_enqueue_style(
      'phase1-style',
      get_stylesheet_uri(),
      array('phase1-google-fonts'),
      $style_version
  );
}
add_action('wp_enqueue_scripts', 'phase1_load_styles');

function phase1_theme_setup() {

add_theme_support('title-tag');

add_theme_support('post-thumbnails');

add_theme_support('custom-logo', array(
    'height'      => 120,
    'width'       => 120,
    'flex-height' => true,
    'flex-width'  => true,
));

add_theme_support('wp-block-styles');
add_theme_support('responsive-embeds');
add_theme_support('editor-styles');

add_theme_support('woocommerce');

add_theme_support('wc-product-gallery-zoom');
add_theme_support('wc-product-gallery-lightbox');
add_theme_support('wc-product-gallery-slider');
}
add_action('after_setup_theme', 'phase1_theme_setup');

function phase1_enable_gutenberg_for_products($can_edit, $post_type) {
if ($post_type === 'product') {
    return true;
}

return $can_edit;
}
add_filter('use_block_editor_for_post_type', 'phase1_enable_gutenberg_for_products', 10, 2);

function phase1_content_width() {
$GLOBALS['content_width'] = apply_filters('phase1_content_width', 1200);
}
add_action('after_setup_theme', 'phase1_content_width', 0);

function phase1_cart_quantity_autoupdate_script() {
if (!is_cart()) {
    return;
}
?>
<script>
document.addEventListener('DOMContentLoaded', function () {
    var cartForm = document.querySelector('form.woocommerce-cart-form');
    if (!cartForm) {
        return;
    }

    var updateButton = cartForm.querySelector('button[name="update_cart"]');
    var updateTimer;

    cartForm.addEventListener('change', function (event) {
        if (!event.target.matches('input.qty')) {
            return;
        }

        if (updateButton) {
            updateButton.disabled = false;
            window.clearTimeout(updateTimer);
            updateTimer = window.setTimeout(function () {
                updateButton.click();
            }, 250);
        }
    });
});
</script>
<?php
}
add_action('wp_footer', 'phase1_cart_quantity_autoupdate_script', 30);

function phase1_single_product_quantity_controls_script() {
if (!is_product()) {
    return;
}
?>
<script>
document.addEventListener('DOMContentLoaded', function () {
    var forms = document.querySelectorAll('form.cart');

    forms.forEach(function (form) {
        var quantityWrap = form.querySelector('.quantity');
        var qtyInput = quantityWrap ? quantityWrap.querySelector('input.qty') : null;

        if (!quantityWrap || !qtyInput || quantityWrap.querySelector('.phase1-qty-control')) {
            return;
        }

        var minusBtn = document.createElement('button');
        minusBtn.type = 'button';
        minusBtn.className = 'phase1-qty-control phase1-qty-minus';
        minusBtn.setAttribute('aria-label', 'Decrease quantity');
        minusBtn.textContent = '-';

        var plusBtn = document.createElement('button');
        plusBtn.type = 'button';
        plusBtn.className = 'phase1-qty-control phase1-qty-plus';
        plusBtn.setAttribute('aria-label', 'Increase quantity');
        plusBtn.textContent = '+';

        quantityWrap.insertBefore(minusBtn, qtyInput);
        quantityWrap.appendChild(plusBtn);

        function getStep() {
            var step = parseFloat(qtyInput.step);
            return Number.isFinite(step) && step > 0 ? step : 1;
        }

        function getMin() {
            var min = parseFloat(qtyInput.min);
            return Number.isFinite(min) ? min : 1;
        }

        function getMax() {
            var max = parseFloat(qtyInput.max);
            return Number.isFinite(max) ? max : Infinity;
        }

        function updateQty(direction) {
            var current = parseFloat(qtyInput.value);

            if (!Number.isFinite(current)) {
                current = getMin();
            }

            var next = current + (direction * getStep());
            next = Math.max(getMin(), Math.min(getMax(), next));
            qtyInput.value = String(next);
            qtyInput.dispatchEvent(new Event('change', { bubbles: true }));
        }

        minusBtn.addEventListener('click', function () { updateQty(-1); });
        plusBtn.addEventListener('click', function () { updateQty(1); });
    });
});
</script>
<?php
}
add_action('wp_footer', 'phase1_single_product_quantity_controls_script', 31);

function phase1_get_cart_link_markup() {
    $count = 0;

    if (function_exists('WC') && WC()->cart) {
        $count = (int) WC()->cart->get_cart_contents_count();
    }

    $cart_url = function_exists('wc_get_cart_url') ? wc_get_cart_url() : home_url('/cart');

    return sprintf(
        '<p class="phase1-cart-link-wrap"><a class="phase1-cart-link" href="%1$s" aria-label="Go to cart">🛒<span class="phase1-cart-count">%2$d</span></a></p>',
        esc_url($cart_url),
        absint($count)
    );
}

function phase1_cart_link_shortcode() {
    return phase1_get_cart_link_markup();
}
add_shortcode('phase1_cart_link', 'phase1_cart_link_shortcode');

function phase1_refresh_cart_link_fragment($fragments) {
    $fragments['.phase1-cart-link-wrap'] = phase1_get_cart_link_markup();
    return $fragments;
}
add_filter('woocommerce_add_to_cart_fragments', 'phase1_refresh_cart_link_fragment');

function phase1_enqueue_cart_fragments_script() {
    if (!function_exists('WC')) {
        return;
    }

    if (wp_script_is('wc-cart-fragments', 'registered')) {
        wp_enqueue_script('wc-cart-fragments');
    }
}
add_action('wp_enqueue_scripts', 'phase1_enqueue_cart_fragments_script', 20);

function phase1_cart_layout_open() {
    if (!is_cart()) return;
    echo '<div class="phase1-cart-layout">';
}
add_action('woocommerce_before_cart', 'phase1_cart_layout_open', 5);

function phase1_cart_layout_close() {
    if (!is_cart()) return;
    echo '</div>';
}
add_action('woocommerce_after_cart', 'phase1_cart_layout_close', 20);

function phase1_terms_page_fallback_redirect() {
    if (!is_404()) {
        return;
    }

    global $wp;

    $requested_path = isset($wp->request) ? trim((string) $wp->request, '/') : '';

    if ($requested_path !== 'terms-and-conditions') {
        return;
    }

    $terms_page = get_page_by_path('terms-and-conditions', OBJECT, 'page');
    $terms_page_id = $terms_page ? (int) $terms_page->ID : 0;

    if ($terms_page_id <= 0) {
        $terms_page_id = (int) get_option('woocommerce_terms_page_id');
    }

    if ($terms_page_id <= 0) {
        return;
    }

    $terms_url = get_permalink($terms_page_id);

    if (!$terms_url) {
        return;
    }

    wp_safe_redirect($terms_url, 301);
    exit;
}
add_action('template_redirect', 'phase1_terms_page_fallback_redirect', 1);

function phase1_account_menu_add_legal_links($items) {
    $new_items = array();

    foreach ($items as $endpoint => $label) {
        $new_items[$endpoint] = $label;

        if ($endpoint === 'edit-account') {
            $new_items['privacy-policy-link'] = 'Privacy Policy';
            $new_items['terms-and-conditions-link'] = 'Terms & Conditions';
        }
    }

    if (!isset($new_items['privacy-policy-link'])) {
        $new_items['privacy-policy-link'] = 'Privacy Policy';
    }

    if (!isset($new_items['terms-and-conditions-link'])) {
        $new_items['terms-and-conditions-link'] = 'Terms & Conditions';
    }

    return $new_items;
}
add_filter('woocommerce_account_menu_items', 'phase1_account_menu_add_legal_links', 20);

function phase1_account_menu_legal_link_urls($url, $endpoint) {
    if ($endpoint === 'privacy-policy-link') {
        return home_url('/privacy-policy');
    }

    if ($endpoint === 'terms-and-conditions-link') {
        return home_url('/terms-and-conditions');
    }

    return $url;
}
add_filter('woocommerce_get_endpoint_url', 'phase1_account_menu_legal_link_urls', 10, 2);
