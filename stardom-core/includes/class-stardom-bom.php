<?php
defined('ABSPATH') || exit;
/**
 * Immutable draft BOM snapshots owned by a project author.
 * Initial manual line intake only; drawing takeoff adapter is not implemented.
 */
final class Stardom_BOM {
    public static function register_types(): void {
        register_post_type('stardom_bom', array(
            'label' => 'Stardom BOM',
            'public' => false,
            'show_ui' => false,
            'show_in_rest' => false,
            'supports' => array('title'),
        ));
    }
    public static function register_routes(): void {
        register_rest_route('stardom/v1', '/projects/(?P<project_id>\\d+)/bom', array(
            array('methods' => 'GET', 'permission_callback' => array(__CLASS__, 'owner'), 'callback' => array(__CLASS__, 'list_versions')),
            array('methods' => 'POST', 'permission_callback' => array(__CLASS__, 'owner'), 'callback' => array(__CLASS__, 'create_version')),
        ));
    }
    public static function owner(WP_REST_Request $r): bool {
        $p = get_post((int) $r['project_id']);
        return is_user_logged_in() && $p && $p->post_type === 'stardom_project'
            && (int) $p->post_author === get_current_user_id();
    }
    public static function list_versions(WP_REST_Request $r): WP_REST_Response {
        $items = get_posts(array('post_type' => 'stardom_bom', 'post_status' => 'private', 'author' => get_current_user_id(), 'meta_key' => '_stardom_project_id', 'meta_value' => (int) $r['project_id'], 'posts_per_page' => 100, 'orderby' => 'date', 'order' => 'ASC'));
        $out = array();
        foreach ($items as $p) {
            $out[] = array('id' => (int) $p->ID, 'version' => (int) get_post_meta($p->ID, '_stardom_bom_version', true), 'created' => $p->post_date);
        }
        return rest_ensure_response($out);
    }
    public static function create_version(WP_REST_Request $r) {
        $lines = $r->get_param('lines');
        if (!is_array($lines) || count($lines) < 1 || count($lines) > 200) {
            return new WP_Error('invalid_bom', 'Provide 1 to 200 BOM lines.', array('status' => 400));
        }
        $clean = array();
        foreach ($lines as $line) {
            if (!is_array($line)) return new WP_Error('invalid_line', 'Invalid line.', array('status' => 400));
            $sku = isset($line['sku']) ? sanitize_text_field($line['sku']) : '';
            $qty = isset($line['quantity']) ? filter_var($line['quantity'], FILTER_VALIDATE_FLOAT) : false;
            $unit = isset($line['unit']) ? sanitize_key($line['unit']) : '';
            if (!$sku || strlen($sku) > 80 || $qty === false || !is_finite((float) $qty) || $qty <= 0 || !in_array($unit, array('ea','m','m2','m3','kg'), true)) {
                return new WP_Error('invalid_line', 'SKU, positive quantity and supported unit required.', array('status' => 400));
            }
            $clean[] = array('sku' => $sku, 'quantity' => (float) $qty, 'unit' => $unit);
        }
        $pid = (int) $r['project_id'];
        $lock = 'stardom_bom_lock_' . $pid;
        if (!wp_cache_add($lock, 1, 'stardom', 30)) return new WP_Error('busy', 'Please retry.', array('status' => 409));
        try {
            $existing = get_posts(array('post_type' => 'stardom_bom', 'post_status' => 'private', 'meta_key' => '_stardom_project_id', 'meta_value' => $pid, 'posts_per_page' => -1, 'fields' => 'ids'));
            $version = count($existing) + 1;
            $id = wp_insert_post(array('post_type' => 'stardom_bom', 'post_title' => 'BOM ' . $pid . ' Rev ' . $version, 'post_status' => 'private', 'post_author' => get_current_user_id()), true);
            if (is_wp_error($id)) return $id;
            update_post_meta($id, '_stardom_project_id', $pid);
            update_post_meta($id, '_stardom_bom_version', $version);
            update_post_meta($id, '_stardom_bom_lines', wp_json_encode($clean));
            return new WP_REST_Response(array('id' => (int) $id, 'version' => $version), 201);
        } finally {
            wp_cache_delete($lock, 'stardom');
        }
    }
}
