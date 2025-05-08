<?php
/**
 * Different actions for the Wasmer API.
 *
 * @package WordPress-Wasmer
 */

if ( !empty($_GET['is_check']) ) {
    wasmer_action_check();
}

// Initialize WordPress
define( 'WP_USE_THEMES', true );

if ( ! isset( $wp_did_header ) ) {
    $wp_did_header = true;
    // Load the WordPress library.
    require_once( dirname( __FILE__ ) . '/wp-load.php' );

    //Workaround to fix deactivating plugins after autologin if NextGEN Gallery plugin is enabled.
    if ( class_exists( 'C_NextGEN_Bootstrap' ) ) {
        define( 'DOING_AJAX', true );
    }

    add_filter( 'option_active_plugins' , function ( $plugins ) {

        return array_filter( $plugins , function ( $item ) {
            return strpos( $item, 'wasmer' ) !== false;
        });
    });

    $action = isset($_GET['action']) ? $_GET['action'] : '';

    switch ($action) {
        case 'liveconfig':
            wasmer_action_liveconfig();
            break;
        case 'magiclogin':
            wasmer_action_magiclogin();
            break;
        case "check":
            wasmer_action_check();
            break;
        default:
            die('Invalid action: '.$action);
    }
}

function wasmer_action_check() {
    http_response_code(200);
    header('Access-Control-Allow-Origin: *');
    echo 'Success!';
    exit();
}


function wasmer_action_liveconfig() {
    http_response_code(200);
    header('Content-Type: application/json');
    $wpdb = $GLOBALS['wpdb'];
    include_once(ABSPATH . 'wp-admin/includes/plugin.php');
    $plugins = get_plugins();
    $themes = wp_get_themes();
    global $update_plugins, $update_themes;
    $update_plugins = get_site_transient( 'update_plugins' );
    $update_themes = get_site_transient( 'update_themes' );
    $update_core = get_site_transient( 'update_core' );
    $plugins = array_map(function($plugin_path, $plugin) {
        global $update_plugins;
        $slug = dirname( $plugin_path);
        if ( '.' === $slug ) {
            $slug = basename( $plugin_path, '.php' );
        }
        $version = isset( $plugin['Version'] ) ? $plugin['Version'] : '';

        $transientData = isset($update_plugins->response[ $plugin_path ]) ? $update_plugins->response[ $plugin_path ] : (isset($update_plugins->no_update[ $plugin_path ]) ? $update_plugins->no_update[ $plugin_path ] : null);
        $pending_update = isset($transientData) ? $transientData->new_version : null;
        return [
            'slug' => isset($transientData->slug) ? $transientData->slug : $slug,
            'icon' => isset($transientData->icons) ? $transientData->icons['1x'] : null,
            'url' => isset($transientData->url) ? $transientData->url : null,
            'name' => $plugin['Name'],
            'version' => $version,
            'description' => $plugin['Description'],
            'is_active' => is_plugin_active($plugin_path),
            'latest_version' => $pending_update,
        ];
    }, array_keys($plugins), $plugins);
    $themes = array_map(function($slug, $theme) {
        global $update_themes;
        $transientData = isset($update_themes->response[ $slug ]) ? $update_themes->response[ $slug ] : (isset($update_themes->no_update[ $slug ]) ? $update_themes->no_update[ $slug ] : null);
        return [
            'slug' => $slug,
            'name' => $theme->name,
            'version' => $theme->version,
            'latest_version' => isset($transientData["new_version"]) ? $transientData["new_version"] : null,
            'is_active' => get_option( 'template' ) == $slug,
        ];
    }, array_keys($themes), $themes);
    $user_count = count_users();
    echo json_encode([
        'liveconfig_version' => '1',
        'wordpress' => [
            'version' => get_bloginfo('version'),
            'latest_version' => isset($update_core->updates[0]->version) ? $update_core->updates[0]->version : null,
            'url' => home_url(),
            'language' => get_locale(),
            'timezone' => date_default_timezone_get(),
            'debug' => WP_DEBUG,
            'debug_log' => WP_DEBUG_LOG,
            'is_main_site' => is_main_site(),
            'plugins' => $plugins,
            'themes' => $themes,
            'users' => [
                'total' => $user_count['total_users'],
                'admins' => isset($user_count['avail_roles']['administrator']) ? $user_count['avail_roles']['administrator'] : 0,
            ],
            'posts' => [
                'count' => wp_count_posts('post')->publish,
            ],
            'pages' => [
                'count' => wp_count_posts('page')->publish,
            ],
        ],
        'php' => [
            'version' => phpversion(),
            'architecture' => PHP_INT_SIZE == 4 ? '32' : '64',
            'memory_limit' => ini_get('memory_limit'),
            'max_execution_time' => ini_get('max_execution_time'),
            'max_input_time' => ini_get('max_input_time'),
            'max_input_vars' => ini_get('max_input_vars'),
        ],
        'mysql' => [
            'version' => $wpdb->db_version(),
            'server' => $wpdb->db_server_info(),
        ],
    ]);
    exit();
}

function wasmer_action_magiclogin() {
    $url = getenv("WASMER_GRAPHQL_URL");
    if (!$url || empty($url)) {
        die('Error while doing Magic Login: The Wasmer GraphQL URL is not set.');
    }
    $authToken = $_GET["magiclogin"];
    $query = <<<'GRAPHQL'
    query ($appid: ID!) {
        viewer {
            email
        }
        node(id: $appid) {
            ... on DeployApp {
                id
            }
        }
    }
    GRAPHQL;
    $variables = [
        "appid" => getenv("WASMER_APP_ID"),
    ];
    $responseData = wasmer_graphql_query($url, $query, $variables, $authToken);
    if (!$responseData) {
        die('Error while doing Magic Login: Error occurred while fetching the data.');
    }
    // Extract node data
    $nodeData = isset($responseData['data']['node']) ? $responseData['data']['node'] : null;
    // Extract node data
    $viewerData = isset($responseData['data']['viewer']) ? $responseData['data']['viewer'] : null;

    if (!$viewerData) {
        die('Error while doing Magic Login: Error occurred while fetching the data (token might be invalid or expired).');
    }
    if (!$nodeData) {
        die('Error while doing Magic Login: Error occurred while fetching the application data.');
    }

    if (!isset($nodeData["id"])) {
        die('Error while doing Magic Login: The provided id is not a valid App Id.');
    }

    $wasmerLoginData = [
        'email' => $viewerData["email"],
        'redirect_location' => 'wasmer',
        'client_id' => '',
        'acting_client_id' => '',
        'callback_url' => '',
    ];

    if ( is_user_logged_in() ) {
        $redirect_page = wasmer_get_login_link( $wasmerLoginData );

        $wasmerLoginData['redirect_page'] = $redirect_page;
        do_action( 'wasmer_autologin_user_logged_in', $wasmerLoginData );

        wasmer_callback( $wasmerLoginData );
        wp_redirect( $redirect_page );

        exit();
    }

    wasmer_auto_login( $wasmerLoginData );

    wp();
    // Load the theme template
    require_once( ABSPATH . WPINC . '/template-loader.php' );

    wasmer_callback( $wasmerLoginData );
}

function wasmer_auto_login( $args ) {
    if ( ! is_user_logged_in() ) {
        $user_id       = wasmer_get_user_id( $args['email'] );
        $user          = get_user_by( 'ID', $user_id );

        $redirect_page = wasmer_get_login_link( $args );
        if ( ! $user ) {
            wasmer_callback( $args );
            wp_redirect( $redirect_page );

            exit();
        }
        $login_username = $user->user_login;
        wp_set_current_user( $user_id, $login_username );
        wp_set_auth_cookie( $user_id );
        do_action( 'wp_login', $login_username, $user );
        // Go to admin area
        $args['redirect_page'] = $redirect_page;
        do_action( 'wasmer_autologin', $args );

        wasmer_callback( $args );
        wp_redirect( $redirect_page );

        exit();
    }
}

function wasmer_get_user_id( $email )
{
    $admins = get_users( [
        'role' => 'administrator',
        'search' => '*' . $email . '*',
        'search_columns' => ['user_email'],
    ] );
    if (isset($admins[0]->ID)) {
        return $admins[0]->ID;
    }

    $admins = get_users( [ 'role' => 'administrator' ] );
    if (isset($admins[0]->ID)) {
        return $admins[0]->ID;
    }

    return null;
}

function wasmer_get_login_link( $args )
{
    $query_args = [
        'platform' => $args['redirect_location'],
    ];

    if (!empty($args['client_id'])) {
        $query_args['client_id'] = $args['client_id'];
    }

    if (!empty($args['acting_client_id'])) {
        $query_args['acting_client_id'] = $args['acting_client_id'];
    }

    return add_query_arg($query_args, admin_url());
}

function wasmer_callback( $args )
{
    if ( empty($args['callback_url']) ) {
        return;
    }

    wp_remote_post( $args['callback_url'], ['body' => $args] );
}

function wasmer_graphql_query($registry, $query, $variables, $authToken = NULL) {
    // Prepare the payload
    $payload = json_encode([
        "query" => $query,
        "variables" => $variables
    ]);
    $authHeader = $authToken ? "Authorization: Bearer $authToken\r\n": NULL;
    // Set up the HTTP context
    $options = [
        'http' => [
            'header'  => implode("\r\n", array_filter([
                "Content-Type: application/json",
                "Accept: application/json",
                $authHeader
            ])),
            'method'  => 'POST',
            'content' => $payload,
        ],
    ];
    $context = stream_context_create($options);

    // Send the request
    $response = file_get_contents($registry, false, $context);

    // Handle errors
    if ($response === FALSE) {
        var_dump($registry);
        var_dump($payload);
        var_dump($options);
        return NULL;
    }

    // Decode the JSON response
    $responseData = json_decode($response, true);
    return $responseData;
}
