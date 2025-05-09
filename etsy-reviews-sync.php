<?php
/**
 * Plugin Name: Etsy Reviews Sync
 * Description: Adds an Etsy Product ID field to WooCommerce products.
 * Version: 1.5.6
 * Author: Tarun
 */


// Hook to add the custom meta box in WooCommerce products
add_action('add_meta_boxes', function () {
    add_meta_box(
        'etsy_product_id',
        'Etsy Product ID',
        'ers_render_etsy_product_id_field',
        'product',
        'side',
        'default'
    );
});

// Render the input field in the meta box
function ers_render_etsy_product_id_field($post) {
    $value = get_post_meta($post->ID, '_etsy_product_id', true);
    echo '<label for="etsy_product_id">Enter Etsy Product ID:</label>';
    echo '<input type="text" id="etsy_product_id" name="etsy_product_id" value="' . esc_attr($value) . '" style="width:100%;" />';
}

// Save the custom field when the product is saved
add_action('save_post_product', function ($post_id) {
    if (isset($_POST['etsy_product_id'])) {
        update_post_meta($post_id, '_etsy_product_id', sanitize_text_field($_POST['etsy_product_id']));
    }
});

// Register shortcode: [etsy_reviews]
add_shortcode('etsy_reviews', function () {
    if (!is_product()) return ''; // Only run on product pages

    global $post;
    $etsy_id = get_post_meta($post->ID, '_etsy_product_id', true);
    if (!$etsy_id) return '<div class="etsy-reviews">No Etsy product linked.</div>';

    ob_start();
    ?>
    <div class="etsy-reviews" data-etsy-id="<?php echo esc_attr($etsy_id); ?>">
        <h3>Etsy Reviews</h3>
        <div class="etsy-review-list">
            <!-- Reviews will be inserted here via next step -->
            <p>Loading Etsy reviews for product ID <strong><?php echo esc_html($etsy_id); ?></strong>...</p>
        </div>
    </div>
    <?php
    return ob_get_clean();
});
// Enqueue JavaScript on product pages only
add_action('wp_enqueue_scripts', function () {
    if (is_product()) {
        wp_enqueue_script(
            'etsy-reviews-script',
            plugin_dir_url(__FILE__) . 'assets/js/etsy-reviews.js',
            [],
            '1.0',
            true
        );

        global $post;
        $etsy_id = get_post_meta($post->ID, '_etsy_product_id', true);
        $etsy_api_key = 'uit67os8xe3logub2nofh0x9'; // Use your real Etsy API key here

        wp_localize_script('etsy-reviews-script', 'EtsyReviewsData', [
            'etsyProductId' => $etsy_id,
            'apiKey' => $etsy_api_key,
            'ajaxUrl' => admin_url('admin-ajax.php'),
        ]);
    }
});


add_action('wp_ajax_get_etsy_reviews', 'ers_handle_etsy_reviews_ajax');
add_action('wp_ajax_nopriv_get_etsy_reviews', 'ers_handle_etsy_reviews_ajax');

function ers_handle_etsy_reviews_ajax() {
    $listing_id = sanitize_text_field($_GET['listing_id'] ?? '');
    if (!$listing_id) {
        wp_send_json_error('Missing listing ID');
    }

    $api_key = 'uit67os8xe3logub2nofh0x9'; // Make sure this matches your real key

    $url = "https://openapi.etsy.com/v3/application/listings/{listing_id}/reviews";

    $response = wp_remote_get($url, [
        'headers' => [
            'x-api-key' => $api_key,
        ]
    ]);

    if (is_wp_error($response)) {
        wp_send_json_error('Failed to contact Etsy API');
    }
    

    $body = wp_remote_retrieve_body($response);
    $data = json_decode($body, true);
    wp_send_json_success($data);
}

