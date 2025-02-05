<?php
/**
 * Plugin Name: Disable Auto Updates
 * Description: Disables automatic theme, core, and plugin updates.
 */

// Disable automatic core updates
add_filter('automatic_updater_disabled', '__return_true');

// Disable automatic theme updates
add_filter('auto_update_theme', '__return_false');

// Disable automatic plugin updates
add_filter('auto_update_plugin', '__return_false');

if (defined('WP_CLI') && WP_CLI) {
    include_once __DIR__ . '/wasmer/class-wasmer-aio-install-command.php';
}

require_once __DIR__ . '/wasmer/wasmer.php';

// require_once __DIR__ . '/hostinger/hostinger.php';