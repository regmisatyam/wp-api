<?php
/**
 * Plugin Name: Custom Post API
 * Description: Custom REST API endpoint for posts.
 * Version: 1.4.0
 * Author: Satyam Regmi
 */

if (!defined('ABSPATH')) {
    exit; 
}

// Allow CORS for all origins
function cpa_allow_cors_headers() {
    header("Access-Control-Allow-Origin: *");
    header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
    header("Access-Control-Allow-Headers: Content-Type, Authorization");
}
add_action('init', 'cpa_allow_cors_headers');

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
    
    if (isset($params['id'])) {
        $post_id = intval($params['id']);
        $post = get_post($post_id);
    } elseif (isset($params['slug'])) {
        $post = get_page_by_path(sanitize_text_field($params['slug']), OBJECT, 'post');
    }

    if (!empty($post)) {
        return rest_ensure_response([
            'id'       => $post->ID,
            'title'    => get_the_title($post),
            'content'  => apply_filters('the_content', $post->post_content),
            'excerpt'  => get_the_excerpt($post),
            'author'   => get_the_author_meta('display_name', $post->post_author),
            'date'     => get_the_date('Y-m-d H:i:s', $post),
            'categories' => wp_get_post_categories($post->ID, ['fields' => 'names']),
            'tags'     => wp_get_post_tags($post->ID, ['fields' => 'names']),
            'meta'     => get_post_meta($post->ID, '_cpe_custom_field', true),
            'image'    => get_the_post_thumbnail_url($post->ID, 'full') ?: '',
            'link'     => get_permalink($post->ID)
        ]);
    }

    $limit = isset($params['limit']) ? intval($params['limit']) : 5;
    $category = isset($params['category']) ? sanitize_text_field($params['category']) : '';
    $tags = isset($params['tags']) ? sanitize_text_field($params['tags']) : '';
    $author = isset($params['author']) ? intval($params['author']) : '';

    $args = [
        'post_type'      => 'post',
        'posts_per_page' => $limit,
        'category_name'  => $category,
        'tag'           => $tags,
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
                'categories' => wp_get_post_categories(get_the_ID(), ['fields' => 'names']),
                'tags'     => wp_get_post_tags(get_the_ID(), ['fields' => 'names']),
                'meta'     => get_post_meta(get_the_ID(), '_cpe_custom_field', true),
                'image'    => $image_url ? $image_url : '',
                'link'     => get_permalink()
            ];
        }
        wp_reset_postdata();
    }

    return rest_ensure_response($posts);
}
