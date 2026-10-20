<?php
defined('ABSPATH') || exit;
/** Supplier quotations are private, append-only submissions for assigned RFQs. */
final class Stardom_Quotes {
    public static function register_type(): void {
        register_post_type('stardom_quote', array(
            'label' => 'Stardom Supplier Quotes', 'public' => false,
            'show_ui' => false, 'show_in_rest' => false, 'supports' => array('title'),
        ));
    }
    public static function register_routes(): void {
        register_rest_route('stardom/v1', '/rfqs/(?P<rfq_id>\\d+)/quotes', array(
            'methods' => 'POST',
            'permission_callback' => array(__CLASS__, 'supplier_assigned'),
            'callback' => array(__CLASS__, 'submit'),
        ));
        register_rest_route('stardom/v1', '/admin/rfqs/(?P<rfq_id>\\d+)/quotes', array(
            'methods' => 'GET',
            'permission_callback' => function () { return current_user_can('manage_options'); },
            'callback' => array(__CLASS__, 'admin_list'),
        ));
    }
    public static function supplier_assigned(WP_REST_Request $r): bool {
        if (!Stardom_Supplier_RFQ::is_supplier()) return false;
        $rfq = get_post((int) $r['rfq_id']);
        if (!$rfq || $rfq->post_type !== 'stardom_rfq' || $rfq->post_status !== 'private') return false;
        $ids = array_map('absint', (array) get_post_meta($rfq->ID, '_stardom_assigned_supplier_users', true));
        return in_array(get_current_user_id(), $ids, true);
    }
    public static function submit(WP_REST_Request $r) {
        $currency = strtoupper(sanitize_text_field((string) $r->get_param('currency')));
        $price = $r->get_param('total_price');
        $lead = $r->get_param('lead_days');
        if (!in_array($currency, array('AUD','CNY','USD'), true)
            || !is_numeric($price) || !is_finite((float) $price) || (float) $price <= 0 || (float) $price > 100000000
            || filter_var($lead, FILTER_VALIDATE_INT) === false || (int) $lead < 1 || (int) $lead > 730) {
            return new WP_Error('invalid_quote', 'Valid currency, positive total and lead days required.', array('status' => 400));
        }
        $rfq_id = (int) $r['rfq_id'];
        $id = wp_insert_post(array(
            'post_type' => 'stardom_quote',
            'post_title' => 'Quote for RFQ ' . $rfq_id,
            'post_status' => 'private',
            'post_author' => get_current_user_id(),
        ), true);
        if (is_wp_error($id)) return $id;
        update_post_meta($id, '_stardom_rfq_id', $rfq_id);
        update_post_meta($id, '_stardom_currency', $currency);
        update_post_meta($id, '_stardom_total_price', (float) $price);
        update_post_meta($id, '_stardom_lead_days', (int) $lead);
        update_post_meta($id, '_stardom_quote_status', 'submitted');
        return new WP_REST_Response(array('id' => (int) $id, 'status' => 'submitted'), 201);
    }
    public static function admin_list(WP_REST_Request $r): WP_REST_Response {
        $rfq_id = (int) $r['rfq_id'];
        $posts = get_posts(array('post_type' => 'stardom_quote', 'post_status' => 'private',
            'meta_key' => '_stardom_rfq_id', 'meta_value' => $rfq_id, 'posts_per_page' => 100));
        $out = array();
        foreach ($posts as $p) {
            $out[] = array('id' => (int) $p->ID, 'supplier_user_id' => (int) $p->post_author,
                'currency' => get_post_meta($p->ID, '_stardom_currency', true),
                'total_price' => (float) get_post_meta($p->ID, '_stardom_total_price', true),
                'lead_days' => (int) get_post_meta($p->ID, '_stardom_lead_days', true));
        }
        return rest_ensure_response($out);
    }
}
