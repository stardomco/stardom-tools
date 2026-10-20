<?php
defined('ABSPATH') || exit;
/**
 * Purchase order drafts. Approval and production release are intentionally separate.
 */
final class Stardom_PO {
    public static function register_type(): void {
        register_post_type('stardom_po', array(
            'label' => 'Stardom Purchase Orders', 'public' => false,
            'show_ui' => false, 'show_in_rest' => false, 'supports' => array('title'),
        ));
    }
    public static function register_routes(): void {
        register_rest_route('stardom/v1', '/admin/quotes/(?P<quote_id>\\d+)/purchase-order', array(
            'methods' => 'POST',
            'permission_callback' => function () { return current_user_can('manage_options'); },
            'callback' => array(__CLASS__, 'create_draft'),
        ));
    }
    public static function create_draft(WP_REST_Request $r) {
        $quote_id = absint($r['quote_id']);
        $quote = get_post($quote_id);
        if (!$quote || $quote->post_type !== 'stardom_quote' || $quote->post_status !== 'private') {
            return new WP_Error('invalid_quote', 'Quote not found.', array('status' => 404));
        }
        if (get_post_meta($quote_id, '_stardom_quote_status', true) !== 'approved') {
            return new WP_Error('not_approved', 'Quote must be approved before PO drafting.', array('status' => 409));
        }
        $existing = get_posts(array('post_type' => 'stardom_po', 'post_status' => 'private',
            'meta_key' => '_stardom_quote_id', 'meta_value' => $quote_id, 'posts_per_page' => 1, 'fields' => 'ids'));
        if ($existing) return rest_ensure_response(array('id' => (int) $existing[0], 'status' => 'draft'));
        $id = wp_insert_post(array('post_type' => 'stardom_po',
            'post_title' => 'Draft PO for Quote ' . $quote_id,
            'post_status' => 'private', 'post_author' => get_current_user_id()), true);
        if (is_wp_error($id)) return $id;
        update_post_meta($id, '_stardom_quote_id', $quote_id);
        update_post_meta($id, '_stardom_supplier_user_id', (int) $quote->post_author);
        update_post_meta($id, '_stardom_po_status', 'draft');
        update_post_meta($id, '_stardom_currency', get_post_meta($quote_id, '_stardom_currency', true));
        update_post_meta($id, '_stardom_total_price', get_post_meta($quote_id, '_stardom_total_price', true));
        return new WP_REST_Response(array('id' => (int) $id, 'status' => 'draft', 'production_released' => false), 201);
    }
}
