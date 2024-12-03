<?php
/**
 * Does a magic login to the WordPress site using the Wasmer API and a one-time token.
 *
 * @package WordPress-Wasmer
 */

if ( !empty($_GET['is_check']) ) {
    http_response_code(200);
    header('Access-Control-Allow-Origin: *');
    echo 'Success!';
    exit();
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
