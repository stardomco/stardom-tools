<?php
defined('ABSPATH') || exit;

/**
 * Private project intake. Does not expose supplier or legacy plugin data.
 * Every read and write is constrained to the current WordPress user.
 */
final class Stardom_Projects {
    public static function register_post_type_only(): void {
        register_post_type('stardom_project', array(
            'label' => 'Stardom Projects',
            'public' => false,
            'show_ui' => true,
            'show_in_rest' => false,
            'supports' => array('title'),
            'capability_type' => 'post',
            'map_meta_cap' => true,
        ));
    }
    public static function register_routes(): void {
        register_rest_route('stardom/v1', '/projects', array(
            array(
                'methods' => 'GET',
                'permission_callback' => array(__CLASS__, 'authenticated'),
                'callback' => array(__CLASS__, 'list_own'),
            ),
            array(
                'methods' => 'POST',
                'permission_callback' => array(__CLASS__, 'authenticated'),
                'callback' => array(__CLASS__, 'create'),
                'args' => array(
                    'title' => array('required' => true, 'type' => 'string', 'minLength' => 3, 'maxLength' => 120),
                    'project_type' => array('required' => true, 'type' => 'string', 'enum' => array('s60','s70','s120','custom')),
                ),
            ),
        ));
    }
    public static function authenticated(): bool {
        return is_user_logged_in();
    }
    public static function list_own(WP_REST_Request $request): WP_REST_Response {
        $posts = get_posts(array(
            'post_type' => 'stardom_project',
            'post_status' => 'private',
            'author' => get_current_user_id(),
            'posts_per_page' => 50,
            'orderby' => 'date',
            'order' => 'DESC',
        ));
        $items = array();
        foreach ($posts as $post) {
            $items[] = array(
                'id' => (int) $post->ID,
                'title' => get_the_title($post),
                'project_type' => get_post_meta($post->ID, '_stardom_project_type', true),
            );
        }
        return rest_ensure_response($items);
    }
    public static function create(WP_REST_Request $request) {
        $title = sanitize_text_field($request->get_param('title'));
        $type = sanitize_key($request->get_param('project_type'));
        if (mb_strlen($title) < 3 || !in_array($type, array('s60','s70','s120','custom'), true)) {
            return new WP_Error('invalid_project', 'Invalid project details.', array('status' => 400));
        }
        $id = wp_insert_post(array(
            'post_type' => 'stardom_project',
            'post_title' => $title,
            'post_status' => 'private',
            'post_author' => get_current_user_id(),
        ), true);
        if (is_wp_error($id)) {
            return $id;
        }
        update_post_meta($id, '_stardom_project_type', $type);
        return new WP_REST_Response(array('id' => (int) $id, 'status' => 'private'), 201);
    }
}
