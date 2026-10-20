<?php
defined('ABSPATH') || exit;
/**
 * Separate PO approval step. The creator cannot approve their own PO.
 */
final class Stardom_PO_Approval {
    public static function register_routes(): void {
        register_rest_route('stardom/v1', '/admin/purchase-orders/(?P<po_id>\\d+)/approve', array(
            'methods' => 'POST',
            'permission_callback' => function () { return current_user_can('manage_options'); },
            'callback' => array(__CLASS__, 'approve'),
        ));
    }
    public static function approve(WP_REST_Request $request) {
        $id = absint($request['po_id']);
        $po = get_post($id);
        if (!$po || $po->post_type !== 'stardom_po' || $po->post_status !== 'private') {
            return new WP_Error('po_not_found', 'Purchase order not found.', array('status' => 404));
        }
        if ((int) $po->post_author === get_current_user_id()) {
            return new WP_Error('separation_of_duties', 'A second administrator must approve this purchase order.', array('status' => 403));
        }
        if (get_post_meta($id, '_stardom_po_status', true) !== 'draft') {
            return new WP_Error('invalid_po_state', 'Only draft purchase orders can be approved.', array('status' => 409));
        }
        $reason = sanitize_textarea_field((string) $request->get_param('reason'));
        if (mb_strlen($reason) < 3 || mb_strlen($reason) > 1000) {
            return new WP_Error('invalid_reason', 'Approval reason must be 3 to 1000 characters.', array('status' => 400));
        }
        if (!update_post_meta($id, '_stardom_po_status', 'approved', 'draft')) {
            return new WP_Error('state_conflict', 'Purchase order status changed; reload.', array('status' => 409));
        }
        add_post_meta($id, '_stardom_po_approval_event', array(
            'approver_user_id' => get_current_user_id(),
            'reason' => $reason,
            'approved_at_gmt' => current_time('mysql', true),
        ));
        return rest_ensure_response(array('po_id' => $id, 'status' => 'approved', 'production_released' => false));
    }
}
