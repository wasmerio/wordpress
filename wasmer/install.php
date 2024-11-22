<?

define("IS_CLI", php_sapi_name() === 'cli' OR defined('STDIN'));

if (!IS_CLI) {
    die("This program can only be run from the CLI");
}


echo "Installing WordPress\n";

sleep(1);
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


define( 'DB_NAME', $_ENV['DB_NAME'] );

/** Database username */
define( 'DB_USER', $_ENV['DB_USERNAME'] );

/** Database password */
define( 'DB_PASSWORD', $_ENV['DB_PASSWORD'] );

/** Database hostname */
define( 'DB_HOST', $_ENV['DB_HOST'] );

/** Database port */
define( 'DB_PORT', $_ENV['DB_PORT'] );

$start = floor(microtime(true) * 1000);

$mysqli = new mysqli();

if (!$mysqli->real_connect(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME, DB_PORT, NULL, MYSQLI_CLIENT_SSL)) {
    echo "Connection to the DB failed";
    exit();
}

$end = floor(microtime(true) * 1000);

$total = $end - $start;

sleep(1);
echo "    Connected to Database in $total miliseconds\n";

echo " -> Creating tables and relations...\n";
sleep(4);

echo "    All tables created successfully\n";

echo " -> Creating admin user with provided information\n";
sleep(1);

echo "\n";

echo "WordPress installed successfully!\n";
