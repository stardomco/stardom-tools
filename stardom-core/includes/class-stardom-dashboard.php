<?php
defined('ABSPATH') || exit;
final class Stardom_Dashboard {
    public static function register(): void {
        add_shortcode('stardom_customer_dashboard', array(__CLASS__, 'render'));
        add_action('admin_post_stardom_create_project', array(__CLASS__, 'create_project'));
    }
    public static function render(): string {
        if (!is_user_logged_in()) {
            return '<p>Please sign in to access your projects.</p>' . wp_login_form(array('echo' => false, 'redirect' => home_url('/my-stardom/')));
        }
        $uid = get_current_user_id();
        $projects = get_posts(array('post_type' => 'stardom_project', 'post_status' => 'private', 'author' => $uid, 'posts_per_page' => 50));
        ob_start();
        echo '<section class="stardom-dashboard"><h2>My Projects</h2>';
        if (!$projects) {
            echo '<p>No projects yet. Start your first Stardom project below.</p>';
        } else {
            echo '<ul>';
            foreach ($projects as $p) {
                echo '<li>' . esc_html($p->post_title) . ' — ' . esc_html(strtoupper(get_post_meta($p->ID, '_stardom_project_type', true))) . '</li>';
            }
            echo '</ul>';
        }
        echo '<h3>Start a Project</h3><form method="post" action="' . esc_url(admin_url('admin-post.php')) . '">';
        wp_nonce_field('stardom_create_project', 'stardom_nonce');
        echo '<input type="hidden" name="action" value="stardom_create_project">';
        echo '<p><label>Project name <input type="text" name="project_title" required minlength="3" maxlength="120"></label></p>';
        echo '<p><label>House type <select name="project_type"><option value="s60">S60</option><option value="s70">S70</option><option value="s120">S120</option><option value="custom">Custom</option></select></label></p>';
        echo '<button type="submit">Create Project</button></form></section>';
        return ob_get_clean();
    }
    public static function create_project(): void {
        if (!is_user_logged_in() || !isset($_POST['stardom_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['stardom_nonce'])), 'stardom_create_project')) {
            wp_die('Invalid request', '', array('response' => 403));
        }
        $title = isset($_POST['project_title']) ? sanitize_text_field(wp_unslash($_POST['project_title'])) : '';
        $type = isset($_POST['project_type']) ? sanitize_key(wp_unslash($_POST['project_type'])) : '';
        if (mb_strlen($title) < 3 || mb_strlen($title) > 120 || !in_array($type, array('s60','s70','s120','custom'), true)) {
            wp_die('Invalid project details', '', array('response' => 400));
        }
        $id = wp_insert_post(array('post_type' => 'stardom_project', 'post_title' => $title, 'post_status' => 'private', 'post_author' => get_current_user_id()), true);
        if (is_wp_error($id)) {
            wp_die('Unable to create project', '', array('response' => 500));
        }
        update_post_meta($id, '_stardom_project_type', $type);
        wp_safe_redirect(home_url('/my-stardom/'));
        exit;
    }
}
