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

// Hook to add admin menu
add_action('admin_menu', 'wasmer_add_admin_menu');
// Hook to add a menu to the admin top bar
add_action('admin_bar_menu', 'wasmer_add_top_bar_menu', 100);

function wasmer_icon() {
    $svg_icon = '<svg width="1em" height="1em" viewBox="0 0 29 36" xmlns="http://www.w3.org/2000/svg"  fill="currentColor" style="vertical-align:middle;"><g clip-path="url(#prefix__clip0_1268_12249)"><path d="M8.908 13.95v.1c0 1.187-.746 1.704-1.662 1.157-.917-.546-1.662-1.952-1.662-3.138v-.1L0 8.636v18.719l14.5 8.645V17.28L8.908 13.95z"></path><path d="M16.158 9.629v.101c0 1.186-.746 1.704-1.662 1.157-.917-.547-1.662-1.952-1.662-3.138v-.101L7.25 4.32v6.697l8.88 5.296v12.023l5.62 3.352V12.97l-5.592-3.34z"></path><path d="M23.408 5.313v.101c0 1.187-.746 1.704-1.662 1.157-.916-.547-1.662-1.952-1.662-3.138v-.1L14.5 0v6.697l8.88 5.296v12.023L29 27.369V8.649l-5.592-3.336z"></path></g><defs><clipPath id="prefix__clip0_1268_12249"><path fill="#fff" d="M0 0h29v36H0z"></path></clipPath></defs></svg>';
    return $svg_icon;
}
function wasmer_base_url() {
    // $graphql_url = getenv('WASMER_GRAPHQL_URL');
    $graphql_url = 'https://registry.wasmer.io/graphql';
    if (!$graphql_url) {
        return 'https://wasmer.io/';
    }
    $host = parse_url($graphql_url, PHP_URL_HOST);
    $host = str_replace('registry.', '', $host);
    
    return "https://$host";
}

function wasmer_app_dashboard_url($app_id) {
    return wasmer_base_url().'/id/'.$app_id;
}

function wasmer_add_top_bar_menu($admin_bar) {
    // Base64-encoded SVG for the custom icon

    // Add the main Wasmer menu
    $admin_bar->add_menu(array(
        'id'    => 'wasmer-top-menu',
        'title' => wasmer_icon() . ' Wasmer', // Display the SVG icon with the menu title
        'href'  => admin_url('admin.php?page=wasmer-dashboard'), // Link to the Wasmer Dashboard
        'meta'  => array(
            'title' => 'Wasmer Dashboard', // Tooltip
            // 'html'  => $svg_icon,         // Custom HTML for icon
        ),
    ));

    // Add a submenu
    $admin_bar->add_menu(array(
        'id'     => 'wasmer-dashboard-submenu',
        'parent' => 'wasmer-top-menu', // Attach to the main Wasmer menu
        'title'  => 'Dashboard',
        'href'   => admin_url('admin.php?page=wasmer-dashboard'),
        'meta'   => array(
            'title' => 'Go to Wasmer Dashboard', // Tooltip
        ),
    ));

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
