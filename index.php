<?php
/**
 * Plugin Name: Custom Post API
 * Description: Custom REST API endpoint for fetching posts.
 * Version: 1.0.0
 * Author: Satyam Regmi
 */

if (!defined('ABSPATH')) {
    exit; 
}

// Custom REST API Endpoint
function cpa_register_api_routes() {
    register_rest_route('custom-api/v1', '/posts/', array(
        'methods'  => 'GET',
        'callback' => 'cpa_get_posts',
        'permission_callback' => '__return_true'
    ));
}
add_action('rest_api_init', 'cpa_register_api_routes');

// Callback Function for Fetching Posts
function cpa_get_posts($request) {
    $params = $request->get_params();
    $count = isset($params['count']) ? intval($params['count']) : 5;
    $category = isset($params['category']) ? sanitize_text_field($params['category']) : '';
    $author = isset($params['author']) ? intval($params['author']) : '';

    $args = [
        'post_type'      => 'post',
        'posts_per_page' => $count,
        'category_name'  => $category,
        'author'         => $author
    ];
    
    $query = new WP_Query($args);
    $posts = [];
    
    if ($query->have_posts()) {
        while ($query->have_posts()) {
            $query->the_post();
            $image_url = get_the_post_thumbnail_url(get_the_ID(), 'full');
            $posts[] = [
                'id'       => get_the_ID(),
                'title'    => get_the_title(),
                'content'  => apply_filters('the_content', get_the_content()),
                'excerpt'  => get_the_excerpt(),
                'author'   => get_the_author(),
                'date'     => get_the_date('Y-m-d H:i:s'),
                'meta'     => get_post_meta(get_the_ID(), '_cpe_custom_field', true),
                'image'    => $image_url ? $image_url : '',
                'link'     => get_permalink()
            ];
        }
        wp_reset_postdata();
    }

    return rest_ensure_response($posts);
}
