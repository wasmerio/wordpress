<?php

define('APP_PATH', dirname(__FILE__));

$preload_patterns = [
    'wp-load.php',
    'wp-includes/http.php',
    'wp-includes/class-http.php',
    'wp-includes/class-wp-http-response.php',
    'wp-includes/class-wp-http-requests-hooks.php',
    'wp-includes/class-wp-http-proxy.php',
    'wp-includes/class-wp-http-requests-response.php',
    'wp-includes/class-wp-http-cookie.php',
    'wp-includes/class-wp-query.php',
    'wp-includes/class-wp-tax-query.php',
    'wp-includes/class-wp-user.php',
    'wp-includes/class-wp-post.php',
    'wp-includes/class-wp-roles.php',
    'wp-includes/class-wp-role.php',

    'wp-includes/taxonomy.php',
    'wp-includes/post.php',
    'wp-includes/user.php',
    'wp-includes/pluggable.php',
    'wp-includes/rest-api.php',
    'wp-includes/kses.php',
    'wp-includes/capabilities.php',
    'wp-includes/comment.php',
    'wp-includes/query.php',
    'wp-includes/shortcodes.php',
    'wp-includes/theme.php',
    'wp-includes/post-template.php',
    'wp-includes/post-thumbnail-template.php',
    'wp-includes/media.php',
    'wp-includes/date.php',
    'wp-includes/author-template.php',

    "wp-includes/**/*.php",
    "wp-includes/**/**/*.php",
    "wp-includes/**/**/**/*.php",
    "wp-includes/**/**/**/**/*.php",
];

foreach ($preload_patterns as $pattern) {
    $files = glob(APP_PATH . $pattern);

    foreach ($files as $file) {
        opcache_compile_file($file);
    }
}

echo "Preloading complete\n";