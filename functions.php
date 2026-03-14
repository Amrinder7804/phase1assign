<?php

function phase1_load_styles() {
wp_enqueue_style(
    'phase1-style',
    get_stylesheet_uri(),
    array(),
    wp_get_theme()->get('Version')
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
