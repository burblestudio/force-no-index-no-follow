<?php
/*
Plugin Name: Force No-Index No-Follow
Plugin URI: http://burblestudio.com
Description: Forces noindex, nofollow meta tags on all pages regardless of WordPress settings
Version: 1.1
Author: Burble Studio
Author URI: http://brickslibrary.burblestudio.com
License: GPL2
*/

// Add noindex, nofollow meta tag to head
function force_noindex_nofollow() {
    echo '<meta name="robots" content="noindex, nofollow">' . "\n";
}
add_action('wp_head', 'force_noindex_nofollow', 1);

// Add X-Robots-Tag HTTP header
function add_noindex_nofollow_header() {
    header("X-Robots-Tag: noindex, nofollow", true);
}
add_action('send_headers', 'add_noindex_nofollow_header');

// Override WordPress' robots.txt output
function custom_robots_txt($output, $public) {
    $output = "User-agent: *\n";
    $output .= "Disallow: /\n";
    return $output;
}
add_filter('robots_txt', 'custom_robots_txt', 10, 2);

// Disable WordPress' default indexing controls
function remove_default_robots_meta() {
    remove_action('wp_head', 'noindex', 1);
    remove_action('wp_head', 'wp_no_robots');
}
add_action('init', 'remove_default_robots_meta');

// Add permanently dismissible admin notice
function noindex_admin_notice() {
    $user_id = get_current_user_id();
    if (!get_user_meta($user_id, 'noindex_notice_dismissed')) {
        echo '<div class="notice notice-warning is-dismissible" id="noindex-notice">';
        echo '<p><strong>Force No-Index No-Follow</strong> plugin is active. All pages are set to noindex, nofollow regardless of WordPress settings.</p>';
        echo '</div>';
        echo '<script>
            jQuery(document).on("click", "#noindex-notice .notice-dismiss", function() {
                jQuery.post(ajaxurl, {
                    action: "dismiss_noindex_notice"
                });
            });
        </script>';
    }
}
add_action('admin_notices', 'noindex_admin_notice');

// Handle the dismissal of the admin notice
function dismiss_noindex_notice() {
    $user_id = get_current_user_id();
    add_user_meta($user_id, 'noindex_notice_dismissed', 'true', true);
}
add_action('wp_ajax_dismiss_noindex_notice', 'dismiss_noindex_notice');

// Prevent unchecking "Discourage search engines" option
function prevent_search_engine_indexing($value) {
    return 0; // Always keep the site discouraged from search engine indexing
}
add_filter('pre_update_option_blog_public', 'prevent_search_engine_indexing');

// Add non-dismissible notice when trying to enable search engine indexing
function search_engine_indexing_notice() {
    $screen = get_current_screen();
    if ($screen->id === 'options-reading' && isset($_POST['blog_public'])) {
        echo '<div class="notice notice-error">';
        echo '<p>This site is blocked from SERPs using the "Force No-Index No-Follow" plugin. If you wish to make the site visible to search engines, please <a href="' . wp_nonce_url(admin_url('plugins.php?action=deactivate&plugin=' . plugin_basename(__FILE__)), 'deactivate-plugin_' . plugin_basename(__FILE__)) . '">disable this plugin</a>.</p>';
        echo '</div>';
    }
}
add_action('admin_notices', 'search_engine_indexing_notice');

// Ensure "Discourage search engines" option is always checked
function force_discourage_search_engines() {
    update_option('blog_public', 0);
}
add_action('admin_init', 'force_discourage_search_engines');