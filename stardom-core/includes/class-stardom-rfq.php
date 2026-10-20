<?php
defined('ABSPATH') || exit;

/** Draft RFQ intake. No supplier invitation or email dispatch in this release. */
final class Stardom_RFQ {
    public static function register_type(): void {
        register_post_type('stardom_rfq', array(
            'label' => 'Stardom RFQs', 'public' => false, 'show_ui' => false,
            'show_in_rest' => false, 'supports' => array('title'),
        ));
    }
    public static function register_routes(): void {
        register_rest_route('stardom/v1', '/projects/(?P<project_id>\\d+)/rfqs', array(
            array('methods' => 'GET', 'permission_callback' => array(__CLASS__, 'can_manage'),
                'callback' => array(__CLASS__, 'list_drafts')),
            array('methods' => 'POST', 'permission_callback' => array(__CLASS__, 'can_manage'),
                'callback' => array(__CLASS__, 'create_draft')),
        ));
    }
    public static function can_manage(WP_REST_Request $request): bool {
        if (!is_user_logged_in()) return false;
        $project = get_post((int) $request['project_id']);
        return $project && $project->post_type === 'stardom_project'
            && (int) $project->post_author === get_current_user_id();
    }
    private static function validated_bom(int $project_id, int $bom_id, int $user_id) {
        $bom = get_post($bom_id);
        if (!$bom || $bom->post_type !== 'stardom_bom'
            || $bom->post_status !== 'private'
            || (int) $bom->post_author !== $user_id
            || (int) get_post_meta($bom_id, '_stardom_project_id', true) !== $project_id) {
            return new WP_Error('invalid_bom', 'BOM does not belong to this project.', array('status' => 400));
        }
        return $bom;
    }
    public static function list_drafts(WP_REST_Request $request): WP_REST_Response {
        $project_id = (int) $request['project_id'];
        $posts = get_posts(array(
            'post_type' => 'stardom_rfq', 'post_status' => 'private',
            'author' => get_current_user_id(), 'meta_key' => '_stardom_project_id',
            'meta_value' => $project_id, 'posts_per_page' => 100,
        ));
        $items = array();
        foreach ($posts as $post) {
            $items[] = array(
                'id' => (int) $post->ID,
                'bom_id' => (int) get_post_meta($post->ID, '_stardom_bom_id', true),
                'status' => 'draft',
            );
        }
        return rest_ensure_response($items);
    }
    public static function create_draft(WP_REST_Request $request) {
        $project_id = (int) $request['project_id'];
        $bom_id = absint($request->get_param('bom_id'));
        $bom = self::validated_bom($project_id, $bom_id, get_current_user_id());
        if (is_wp_error($bom)) return $bom;
        $id = wp_insert_post(array(
            'post_type' => 'stardom_rfq',
            'post_title' => 'Draft RFQ for BOM ' . $bom_id,
            'post_status' => 'private',
            'post_author' => get_current_user_id(),
        ), true);
        if (is_wp_error($id)) return $id;
        update_post_meta($id, '_stardom_project_id', $project_id);
        update_post_meta($id, '_stardom_bom_id', $bom_id);
        update_post_meta($id, '_stardom_rfq_status', 'draft');
        return new WP_REST_Response(array('id' => (int) $id, 'status' => 'draft'), 201);
    }
}
