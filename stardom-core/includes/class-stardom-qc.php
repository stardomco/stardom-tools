<?php
defined('ABSPATH') || exit;
/**
 * Append-only production evidence notes for released purchase orders.
 * Files, photos and certificates require a separate private upload service.
 */
final class Stardom_QC {
    public static function register_routes(): void {
        register_rest_route('stardom/v1', '/purchase-orders/(?P<po_id>\\d+)/qc-events', array(
            array(
                'methods' => 'GET',
                'permission_callback' => array(__CLASS__, 'authorized'),
                'callback' => array(__CLASS__, 'list_events'),
            ),
            array(
                'methods' => 'POST',
                'permission_callback' => array(__CLASS__, 'authorized'),
                'callback' => array(__CLASS__, 'add_event'),
            ),
        ));
    }
    public static function authorized(WP_REST_Request $r): bool {
        if (!is_user_logged_in() || !current_user_can('manage_options')) return false;
        $po = get_post(absint($r['po_id']));
        return $po && $po->post_type === 'stardom_po' && $po->post_status === 'private';
    }
    public static function list_events(WP_REST_Request $r): WP_REST_Response {
        $events = get_post_meta(absint($r['po_id']), '_stardom_qc_event', false);
        return rest_ensure_response(array_values($events));
    }
    public static function add_event(WP_REST_Request $r) {
        $id = absint($r['po_id']);
        if (get_post_meta($id, '_stardom_po_status', true) !== 'released') {
            return new WP_Error('po_not_released', 'Production release is required.', array('status' => 409));
        }
        $stage = sanitize_key((string) $r->get_param('stage'));
        $result = sanitize_key((string) $r->get_param('result'));
        $notes = sanitize_textarea_field((string) $r->get_param('notes'));
        if (!in_array($stage, array('incoming','in_process','pre_shipment','corrective_action'), true)
            || !in_array($result, array('pass','fail','pending'), true)
            || mb_strlen($notes) < 3 || mb_strlen($notes) > 2000) {
            return new WP_Error('invalid_qc', 'Valid stage, result and notes required.', array('status' => 400));
        }
        $event = array(
            'stage' => $stage,
            'result' => $result,
            'notes' => $notes,
            'recorded_by' => get_current_user_id(),
            'recorded_at_gmt' => current_time('mysql', true),
        );
        add_post_meta($id, '_stardom_qc_event', $event, false);
        return new WP_REST_Response($event, 201);
    }
}
