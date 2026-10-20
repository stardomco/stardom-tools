<?php
defined('ABSPATH') || exit;

/**
 * Supplier access to explicitly assigned RFQs only.
 * Assignments are controlled by administrators; no self-service claims.
 */
final class Stardom_Supplier_RFQ {
    public static function register_routes(): void {
        register_rest_route('stardom/v1', '/supplier/rfqs', array(
            'methods' => 'GET',
            'permission_callback' => array(__CLASS__, 'is_supplier'),
            'callback' => array(__CLASS__, 'assigned'),
        ));
        register_rest_route('stardom/v1', '/rfqs/(?P<rfq_id>\\d+)/assign', array(
            'methods' => 'POST',
            'permission_callback' => function () { return current_user_can('manage_options'); },
            'callback' => array(__CLASS__, 'assign'),
            'args' => array(
                'supplier_user_id' => array('required' => true, 'type' => 'integer', 'minimum' => 1),
            ),
        ));
    }
    public static function is_supplier(): bool {
        if (!is_user_logged_in()) return false;
        $user = wp_get_current_user();
        return in_array('stardom_supplier', (array) $user->roles, true)
            && get_user_meta($user->ID, '_stardom_supplier_approved', true) === 'yes';
    }
    public static function assign(WP_REST_Request $r) {
        $rfq = get_post((int) $r['rfq_id']);
        $uid = absint($r->get_param('supplier_user_id'));
        $user = get_user_by('id', $uid);
        if (!$rfq || $rfq->post_type !== 'stardom_rfq' || $rfq->post_status !== 'private') {
            return new WP_Error('invalid_rfq', 'RFQ not found.', array('status' => 404));
        }
        if (!$user || !in_array('stardom_supplier', (array) $user->roles, true)
            || get_user_meta($uid, '_stardom_supplier_approved', true) !== 'yes') {
            return new WP_Error('invalid_supplier', 'Approved supplier account required.', array('status' => 400));
        }
        $ids = array_map('absint', (array) get_post_meta($rfq->ID, '_stardom_assigned_supplier_users', true));
        $ids[] = $uid;
        $ids = array_values(array_unique(array_filter($ids)));
        update_post_meta($rfq->ID, '_stardom_assigned_supplier_users', $ids);
        return rest_ensure_response(array('rfq_id' => (int) $rfq->ID, 'supplier_user_id' => $uid, 'assigned' => true));
    }
    public static function assigned(WP_REST_Request $r): WP_REST_Response {
        $uid = get_current_user_id();
        $rfqs = get_posts(array('post_type' => 'stardom_rfq', 'post_status' => 'private', 'posts_per_page' => 100, 'meta_query' => array(
            array('key' => '_stardom_assigned_supplier_users', 'value' => 'i:' . $uid . ';', 'compare' => 'LIKE'),
        )));
        $out = array();
        foreach ($rfqs as $rfq) {
            $ids = array_map('absint', (array) get_post_meta($rfq->ID, '_stardom_assigned_supplier_users', true));
            if (!in_array($uid, $ids, true)) continue;
            $out[] = array('id' => (int) $rfq->ID, 'title' => $rfq->post_title, 'status' => get_post_meta($rfq->ID, '_stardom_rfq_status', true));
        }
        return rest_ensure_response($out);
    }
}
