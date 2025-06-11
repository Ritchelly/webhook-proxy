<?php
/*
Plugin Name: Webhook proxy
Description: Encaminha requisições de um webhook para um endpoint local via ngrok.
Version: 1.0
Author: CoffeeCode
*/
if (!defined('ABSPATH')) exit;

add_action('admin_menu', function () {
    add_options_page(
        'Getnet Webhook Forwarder',
        'Getnet Webhook Forwarder',
        'manage_options',
        'getnet-webhook-forwarder',
        'gwf_render_settings_page'
    );
});

add_action('admin_init', function () {
    register_setting('gwf_settings_group', 'gwf_ngrok_url');
});

function gwf_render_settings_page() {
    ?>
    <div class="wrap">
        <h1>Getnet Webhook Forwarder</h1>
        <form method="post" action="options.php">
            <?php settings_fields('gwf_settings_group'); ?>
            <?php do_settings_sections('gwf_settings_group'); ?>
            <table class="form-table">
                <tr valign="top">
                    <th scope="row">URL do seu ngrok local</th>
                    <td>
                        <input type="url" name="gwf_ngrok_url" value="<?php echo esc_attr(get_option('gwf_ngrok_url')); ?>" size="50" required />
                        <p class="description">Ex: https://abc123.ngrok-free.app</p>
                    </td>
                </tr>
            </table>
            <?php submit_button(); ?>
        </form>
    </div>
    <?php
}

add_action('init', function () {
    if (stripos($_SERVER['REQUEST_URI'], '/wc-api/getnet') !== false) {
        gwf_forward_request();
    }
}, 0);

function gwf_forward_request() {
    $ngrok_url = rtrim(get_option('gwf_ngrok_url'), '/');
    if (!$ngrok_url) return;

    $target_url = $ngrok_url . $_SERVER['REQUEST_URI'];

    $body = file_get_contents('php://input');

    $method = $_SERVER['REQUEST_METHOD'];

    $logger = wc_get_logger();
    $context = ['source' => 'webhook-proxy'];

    $requestData = [
        "URL" => $target_url,
        "Method" => $method,
        "Body" => $body
    ];

    $response = wp_remote_request($target_url, [
        'method'  => $method,
        'body'    => $body,
        'timeout' => 15,
    ]);

    if (is_wp_error($response)) {
        $logger->error("Erro ao redirecionar para ngrok: " . $response->get_error_message(), $context);
         status_header(502);
    } 

    //error_log(print_r($response, true));

    $logBody = [
        "Request" => $requestData,
        "Response" => json_decode(wp_remote_retrieve_body($response))
    ];
    
    $logger->info(json_encode($logBody, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES), $context);

    wp_remote_retrieve_body($response);
}

