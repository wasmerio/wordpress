<?

function copy_into_dir($source, $dest)
{
    mkdir($dest, 0755, true);
    foreach (
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($source, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST
        ) as $item
    ) {
        $new_path = $dest . DIRECTORY_SEPARATOR . $iterator->getSubPathname();
        if ($item->isDir()) {
            // echo "mkdir $new_path\n";
            mkdir($new_path);
        } else {
            // echo "copy $item -> $new_path\n";
            // if (!copy($item, $new_path)) {
            //     echo "Couldn't copy";
            // }

            // COPY DOESN'T WORK, so we do it manually
            $content = file_get_contents($item);
            file_put_contents($new_path, $content);
            // echo "copied";
        }
    };
}

define("IS_CLI", php_sapi_name() === 'cli' or defined('STDIN'));
define("WP_INSTALL", isset($_ENV["WP_INSTALL"]) ? $_ENV["WP_INSTALL"] === "1" || $_ENV["WP_INSTALL"] === "true" || $_ENV["WP_INSTALL"] === "yes" : false);
define("WP_INSTALL_LANGUAGE", isset($_ENV["WP_INSTALL_LANGUAGE"]) ? $_ENV["WP_INSTALL_LANGUAGE"] : "en_US");
define("WP_INSTALL_TITLE", isset($_ENV["WP_INSTALL_TITLE"]) ? $_ENV["WP_INSTALL_TITLE"] : "Wordpres site");
define("WP_INSTALL_USER", isset($_ENV["WP_INSTALL_USER"]) ? $_ENV["WP_INSTALL_USER"] : "admin");
define("WP_INSTALL_PASSWORD", isset($_ENV["WP_INSTALL_PASSWORD"]) ? $_ENV["WP_INSTALL_PASSWORD"] : "admin");
define("WP_INSTALL_EMAIL", isset($_ENV["WP_INSTALL_EMAIL"]) ? $_ENV["WP_INSTALL_EMAIL"] : "test@wasmer.io");
define("WP_INSTALL_PUBLIC", isset($_ENV["WP_INSTALL_PUBLIC"]) ? $_ENV["WP_INSTALL_PUBLIC"] === "1" : 1);
define("WP_INSTALL_APP_DOMAIN", isset($_ENV["WP_INSTALL_APP_DOMAIN"]) ? $_ENV["WP_INSTALL_APP_DOMAIN"] : "https://wordpressapp.wasmer.dev");

if (!IS_CLI) {
    die("This program can only be run from the CLI\n");
}

if (!WP_INSTALL) {
    die("To run this program, you neet to set the WP_INSTALL env variables (WP_INSTALL=1|true|yes)\n");
}


echo "Installing WordPress\n";

echo " -> Connecting to Database...\n";

if (!isset($_ENV['DB_NAME'])) {
    die("DB_NAME env var is not set. Please set it before running install.\n");
}
if (!isset($_ENV['DB_USERNAME'])) {
    die("DB_USERNAME env var is not set. Please set it before running install.\n");
}
if (!isset($_ENV['DB_PASSWORD'])) {
    die("DB_PASSWORD env var is not set. Please set it before running install.\n");
}
if (!isset($_ENV['DB_HOST'])) {
    die("DB_HOST env var is not set. Please set it before running install.\n");
}
if (!isset($_ENV['DB_PORT'])) {
    die("DB_PORT env var is not set. Please set it before running install.\n");
}

$start = floor(microtime(true) * 1000);

$mysqli = new mysqli();

try {
    if (!$mysqli->real_connect($_ENV['DB_HOST'], $_ENV['DB_USERNAME'], $_ENV['DB_PASSWORD'], $_ENV['DB_NAME'], $_ENV['DB_PORT'], NULL, MYSQLI_CLIENT_SSL)) {
        die("    Connection to the DB failed\n");
    }
} catch (Exception $error) {
    die("    Connection to the DB failed: $error\n");
}

$end = floor(microtime(true) * 1000);

$total = $end - $start;

echo "    Connected to Database in $total miliseconds\n";

echo " -> Checking requirements...\n";

define('WP_INSTALLING', true);
define('WP_HOME', WP_INSTALL_APP_DOMAIN);

/** Load WordPress Bootstrap */
require_once dirname(__DIR__) . '/wp-load.php';

/** Load WordPress Administration Upgrade API */
require_once ABSPATH . 'wp-admin/includes/upgrade.php';

/** Load WordPress Translation Install API */
require_once ABSPATH . 'wp-admin/includes/translation-install.php';

/** Load wpdb */
require_once ABSPATH . WPINC . '/class-wpdb.php';

if (is_blog_installed()) {
    echo "    Warning: WordPress Already Installed.\n";
    echo "    -> You appear to have already installed WordPress. To reinstall please clear your old database tables first.\n";
    echo "Exiting install\n";
    exit(0);
}

/**
 * @global string $wp_version             The WordPress version string.
 * @global string $required_php_version   The required PHP version string.
 * @global string $required_mysql_version The required MySQL version string.
 * @global wpdb   $wpdb                   WordPress database abstraction object.
 */
global $wp_version, $required_php_version, $required_mysql_version, $wpdb;

$php_version   = PHP_VERSION;
$mysql_version = $wpdb->db_version();
$php_compat    = version_compare($php_version, $required_php_version, '>=');
$mysql_compat  = version_compare($mysql_version, $required_mysql_version, '>=') || file_exists(WP_CONTENT_DIR . '/db.php');


$php_update_message = sprintf(
    " Learn more about updating PHP here: %s\n",
    esc_url(wp_get_update_php_url())
);

if (! $mysql_compat && ! $php_compat) {
    $compat = sprintf(
        /* translators: 1: URL to WordPress release notes, 2: WordPress version number, 3: Minimum required PHP version number, 4: Minimum required MySQL version number, 5: Current PHP version number, 6: Current MySQL version number. */
        __('You cannot install because WordPress %2$s requires PHP version %3$s or higher and MySQL version %4$s or higher. You are running PHP version %5$s and MySQL version %6$s.'),
        $version_url,
        $wp_version,
        $required_php_version,
        $required_mysql_version,
        $php_version,
        $mysql_version
    ) . $php_update_message;
} elseif (! $php_compat) {
    $compat = sprintf(
        /* translators: 1: URL to WordPress release notes, 2: WordPress version number, 3: Minimum required PHP version number, 4: Current PHP version number. */
        __('You cannot install because WordPress %2$s requires PHP version %3$s or higher. You are running version %4$s.'),
        $version_url,
        $wp_version,
        $required_php_version,
        $php_version
    ) . $php_update_message;
} elseif (! $mysql_compat) {
    $compat = sprintf(
        /* translators: 1: URL to WordPress release notes, 2: WordPress version number, 3: Minimum required MySQL version number, 4: Current MySQL version number. */
        __('You cannot install because WordPress %2$s requires MySQL version %3$s or higher. You are running version %4$s.'),
        $version_url,
        $wp_version,
        $required_mysql_version,
        $mysql_version
    );
}

if (! $mysql_compat || ! $php_compat) {
    die("Requirements Not Met.\n" . $compat);
}

echo " -> Setting up wp-content...\n";

if (!file_exists(WP_CONTENT_DIR)) {
    echo "    Creating wp-content dir\n";
    mkdir(WP_CONTENT_DIR);
}
if (!file_exists(get_theme_root() . '/twentytwentyfour')) {
    echo "    Setting up theme\n";
    mkdir(get_theme_root(). '/twentytwentyfour', 0777, true);
    // rename(WPMU_PLUGIN_DIR . '/default-themes/twentytwentyfour', get_theme_root() . '/twentytwentyfour');
    copy_into_dir(WPMU_PLUGIN_DIR . '/default-themes/twentytwentyfour', get_theme_root() . '/twentytwentyfour');
    // recursive_copy_install(WPMU_PLUGIN_DIR . '/default-themes/twentytwentyfour', get_theme_root() . '/twentytwentyfour');
}


echo " -> Choosing language...\n";
if (!wp_can_install_language_pack()) {
    echo "    Warning: Can't install the language pack\n";
}

$loaded_language = 'en_US';
if (! empty(WP_INSTALL_LANGUAGE)) {
    if (load_default_textdomain(WP_INSTALL_LANGUAGE)) {
        echo "    Language " . WP_INSTALL_LANGUAGE . " loaded properly\n";
        $loaded_language      = WP_INSTALL_LANGUAGE;
    } else {
        echo "    Language " . WP_INSTALL_LANGUAGE . " not installed. Downloading locally...\n";
        $loaded_language = wp_download_language_pack(WP_INSTALL_LANGUAGE);
        if (load_default_textdomain(WP_INSTALL_LANGUAGE)) {
            $loaded_language      = WP_INSTALL_LANGUAGE;
        }
    }
    $GLOBALS['wp_locale'] = new WP_Locale();
    echo "    Set language to $loaded_language\n";
} else {
    echo "    Using default language";
}


echo " -> Installing Wordpress...\n";


$weblog_title         = WP_INSTALL_TITLE;
$user_name            = WP_INSTALL_USER;
$admin_password       = WP_INSTALL_PASSWORD;
$admin_email          = WP_INSTALL_EMAIL;
$public               = WP_INSTALL_PUBLIC;

// Check email address.
$error = false;
if (empty($user_name)) {
    echo __('Please provide a valid username.');
    $error = true;
} elseif (sanitize_user($user_name, true) !== $user_name) {
    echo __('The username you provided has invalid characters.');
    $error = true;
} elseif (empty($admin_email)) {
    echo __('You must provide an email address.');
    $error = true;
} elseif (! is_email($admin_email)) {
    echo __('Sorry, that is not a valid email address. Email addresses look like <code>username@example.com</code>.');
    $error = true;
}

if ($error) {
    die("\nThere were errors when setting up the installation\n");
}

echo "------------\n";

$result = wp_install($weblog_title, $user_name, $admin_email, $public, '', wp_slash($admin_password), $loaded_language);

if (!$result || !$result["password"]) {
    die("Couldn't install Wordpress\n");
}

echo "\n------------\n    ";

echo _e("Username") . ": " . esc_html(sanitize_user($user_name, true)) . "\n    ";
echo _e("Password") . ": " . (! empty($result['password'])  ? esc_html($result['password']) : "****") . "\n";
echo "    -> " . $result['password_message'];


echo "\n\n";

echo "WordPress installed successfully!\n";
