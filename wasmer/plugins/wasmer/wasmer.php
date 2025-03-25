<?php
/*
Plugin Name: Wasmer Plugin
Description: Adds a custom menu to WordPress admin with a Wasmer Dashboard submenu.
Version: 1.0
Author: Your Name
*/

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

define("WASMER_APP_ID", getenv('WASMER_APP_ID'));
define("WASMER_PERISHABLE_TIMESTAMP", getenv('WASMER_PERISHABLE_TIMESTAMP'));

// Hook to add admin menu
// add_action('admin_menu', 'wasmer_add_admin_menu');
// Hook to add a menu to the admin top bar
add_action('admin_bar_menu', 'wasmer_add_top_bar_menu', 100);

function wasmer_icon() {
    $svg_icon = '<svg viewBox="0 0 29 34" height="1em" width="1em"  fill="currentColor" style="vertical-align:middle;">
                        <g clip-path="url(#prefix__clip0_1268_12249)">
                            <path d="M0 12.3582C0 10.4725 0 9.52973 0.507307 9.23683C1.01461 8.94394 1.83111 9.41534 3.46411 10.3581L10.784 14.5843C12.417 15.5271 13.2335 15.9985 13.7408 16.8771C14.2481 17.7558 14.2481 18.6986 14.2481 20.5843V29.0364C14.2481 30.9221 14.2481 31.8649 13.7408 32.1578C13.2335 32.4507 12.417 31.9793 10.784 31.0365L3.4641 26.8103C1.83111 25.8675 1.01461 25.3961 0.507307 24.5175C0 23.6388 0 22.696 0 20.8103V12.3582Z"></path>
                            <path fill-rule="evenodd" clip-rule="evenodd" d="M7.46147 5.14203C6.95416 5.43492 6.95416 6.37773 6.95416 8.26335V9.18177L13.9688 13.2317C15.6018 14.1745 16.4183 14.6459 16.9256 15.5246C17.433 16.4032 17.433 17.346 17.433 19.2317V26.7654L17.7382 26.9416C19.3711 27.8845 20.1876 28.3559 20.695 28.063C21.2023 27.7701 21.2023 26.8273 21.2023 24.9416V16.4894C21.2023 14.6038 21.2023 13.661 20.695 12.7823C20.1876 11.9037 19.3711 11.4323 17.7382 10.4895L10.4183 6.26334C8.78527 5.32054 7.96878 4.84914 7.46147 5.14203Z"></path>
                            <path fill-rule="evenodd" clip-rule="evenodd" d="M14.5533 1.05023C14.046 1.34313 14.046 2.28594 14.046 4.17156V5.09003L21.0607 9.13993C22.6937 10.0827 23.5102 10.5541 24.0175 11.4328C24.5248 12.3115 24.5248 13.2543 24.5248 15.1399V22.6736L24.83 22.8499C26.463 23.7927 27.2795 24.2641 27.7868 23.9712C28.2941 23.6783 28.2941 22.7355 28.2941 20.8498V12.3976C28.2941 10.512 28.2941 9.56922 27.7868 8.69054C27.2795 7.81187 26.463 7.34046 24.83 6.39766L17.5101 2.17155C15.8771 1.22874 15.0606 0.757338 14.5533 1.05023Z"></path>
                        </g>
                        <defs>
                            <clipPath id="prefix__clip0_1268_12249">
                                <path fill="#fff" d="M0 0h29v36H0z"></path>
                            </clipPath>
                        </defs>
                    </svg>';
    return $svg_icon;
}
function wasmer_base_url() {
    $graphql_url = getenv('WASMER_GRAPHQL_URL');
    if (!$graphql_url) {
        return 'https://wasmer.io';
    }
    $host = parse_url($graphql_url, PHP_URL_HOST);
    $host = str_replace('registry.', '', $host);

    return "https://$host";
}

function wasmer_app_dashboard_url($app_id) {
    return wasmer_base_url().'/id/'.$app_id;
}

function wasmer_claim_app_url($app_id) {
    return wasmer_base_url().'/apps/claim/'.$app_id;
}

function wasmer_add_top_bar_menu($admin_bar) {
    // Calculate time left based on WASMER_PERISHABLE_TIMESTAMP
    $notification_preview = '';
    $notification_html = '';
    if (WASMER_PERISHABLE_TIMESTAMP) {
        $perishable_time = intval(WASMER_PERISHABLE_TIMESTAMP);
        $current_time = current_time('timestamp');
        $time_left = $perishable_time - $current_time;
        if ($time_left > 0) {
            $time_left_text = '';
            if ($time_left > 86400) { // More than a day
                $days = floor($time_left / 86400);
                $time_left_text = $days . ' day' . ($days > 1 ? 's' : '');
            } elseif ($time_left >= 3600-60) { // More than an hour
                $hours = floor($time_left / 3600);
                $time_left_text = $hours . ' hour' . ($hours > 1 ? 's' : '');
            } elseif ($time_left > 60-10) { // More than a minute
                $minutes = floor($time_left / 60);
                $time_left_text = $minutes . ' minute' . ($minutes > 1 ? 's' : '');
            } else {
                $time_left_text = 'seconds';
            }

            $notification_preview = ' <span class="awaiting-mod" style="display: inline-block; vertical-align: middle; margin: -2px 0 0 2px; padding: 0 5px; min-width: 7px; height: 17px; border-radius: 11px; background-color: #d63638; color: #fff; font-size: 9px; line-height: 17px; text-align: center; z-index: 26;">!</span>';
            $notification_html = ' <span class="awaiting-mod" style="display: inline-block; vertical-align: middle; margin: -2px 0 0 2px; padding: 0 5px; min-width: 7px; height: 17px; border-radius: 11px; background-color: #d63638; color: #fff; font-size: 9px; line-height: 17px; text-align: center; z-index: 26;">App expiring in ' . $time_left_text . '</span>';
        }
    }

    // Add the main Wasmer menu
    $admin_bar->add_menu(array(
        'id'    => 'wasmer-top-menu',
        'title' => wasmer_icon() . ' Wasmer' . $notification_preview, // Display the SVG icon with the menu title and notification
        // 'href'  => admin_url('admin.php?page=wasmer-dashboard'), // Link to the Wasmer Dashboard
        'meta'  => array(
            'title' => 'Wasmer Dashboard', // Tooltip
            // 'html'  => $svg_icon,         // Custom HTML for icon
        ),
    ));

    // Add a submenu
    // $admin_bar->add_menu(array(
    //     'id'     => 'wasmer-dashboard-submenu',
    //     'parent' => 'wasmer-top-menu', // Attach to the main Wasmer menu
    //     'title'  => 'Dashboard',
    //     'href'   => admin_url('admin.php?page=wasmer-dashboard'),
    //     'meta'   => array(
    //         'title' => 'Go to Wasmer Dashboard', // Tooltip
    //     ),
    // ));

    if (WASMER_PERISHABLE_TIMESTAMP) {
        $admin_bar->add_menu(array(
            'id'     => 'wasmer-dashboard-claim',
            'parent' => 'wasmer-top-menu', // Attach to the main Wasmer menu
            'title'  => 'Claim app ' . $notification_html,
            'href'   => wasmer_claim_app_url(WASMER_APP_ID),
            'meta'   => array(
                'title' => 'Claim App to prevent expiration', // Updated tooltip with more information
            ),
        ));
    }

    // Add a submenu
    $admin_bar->add_menu(array(
        'id'     => 'wasmer-dashboard-external',
        'parent' => 'wasmer-top-menu', // Attach to the main Wasmer menu
        'title'  => 'Wasmer Control Panel',
        'href'   => wasmer_app_dashboard_url(WASMER_APP_ID),
        'meta'   => array(
            'title' => 'Go to Wasmer Control Panel', // Tooltip
            'rel' => 'noopener noreferrer',
        ),
    ));
}

// Function to add the menu and submenu
function wasmer_add_admin_menu() {
    global $submenu;

    $svg_icon = 'data:image/svg+xml;base64,' . base64_encode(wasmer_icon());

    add_menu_page(
        'Wasmer Dashboard', // Page title
        'Wasmer',           // Menu title
        'manage_options',   // Capability
        'wasmer-dashboard', // Menu slug
        'wasmer_dashboard_page', // Callback function
        $svg_icon,  // Icon (dashicons or URL to a custom icon)
        0                   // Position in menu
    );

    add_submenu_page(
        'wasmer-dashboard', // Parent slug
        'Dashboard',        // Page title
        'Dashboard',        // Submenu title
        'manage_options',   // Capability
        'wasmer-dashboard', // Menu slug
        'wasmer_dashboard_page' // Callback function
    );


    if (WASMER_APP_ID) {
        $submenu["wasmer-dashboard"][] = array('Wasmer Control Panel', 'manage_options', wasmer_app_dashboard_url(WASMER_APP_ID));
    }

    // Add a submenu linking to Wasmer.io
    // add_submenu_page(
    //     'wasmer-dashboard', // Parent slug
    //     'Visit Wasmer.io',  // Page title
    //     'Visit Wasmer.io', // Custom HTML in the submenu title
    //     'manage_options',   // Capability
    //     'wasmer-external',  // Menu slug
    //     'wasmer_external_link_page' // Callback function (for redirect)
    // );
}

// Callback function for the external link submenu
function wasmer_external_link_page() {
    // Redirect to the Wasmer.io site
    wp_redirect('https://wasmer.io/');
    exit;
}


// Callback function for the dashboard page
function wasmer_dashboard_page() {
    if (!current_user_can('manage_options')) {
        return;
    }
    $svg_icon = wasmer_icon();
    ?>
    <div class="wrap">
        <h1><?= $svg_icon ?> Wasmer Dashboard</h1>
        <p>Welcome to the Wasmer plugin! Customize this dashboard as needed.</p>
    </div>
    <?php
}

// Activation hook
register_activation_hook(__FILE__, 'wasmer_plugin_activate');
function wasmer_plugin_activate() {
    // Code to run on activation, e.g., setting default options.
}

// Deactivation hook
register_deactivation_hook(__FILE__, 'wasmer_plugin_deactivate');
function wasmer_plugin_deactivate() {
    // Code to run on deactivation, e.g., cleaning up options.
}
