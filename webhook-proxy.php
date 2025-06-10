<?php
/*
Plugin Name: Webhook proxy
Description: Encaminha requisições do webhook da para um endpoint local via ngrok.
Version: 1.0
Author: CoffeeCode
*/
if (!defined('ABSPATH')) exit;

// Adiciona página no admin
add_action('admin_menu', function () {
    add_options_page(
        'Webhook proxy',
        'Webhook proxy',
        'manage_options',
        'webhook-proxy',
        'gwf_render_settings_page'
    );
});

// Registra a configuração
add_action('admin_init', function () {
    register_setting('gwf_settings_group', 'gwf_ngrok_url');
});

// Renderiza a interface de configuração
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

// Intercepta a requisição da Getnet
add_action('init', function () {
    // Detecta se a URL é /wc-api/getnet-creditcard
    if (stripos($_SERVER['REQUEST_URI'], '/wc-api/getnet') !== false) {
        gwf_forward_request();
    }
}, 0);

// Encaminha a requisição para o ngrok
function gwf_forward_request() {
    $ngrok_url = rtrim(get_option('gwf_ngrok_url'), '/');
    if (!$ngrok_url) return;

    $target_url = $ngrok_url . $_SERVER['REQUEST_URI'];

    // Pega o corpo da requisição original
    $body = file_get_contents('php://input');

    // Pega o método
    $method = $_SERVER['REQUEST_METHOD'];

    error_log(print_r($method, true));
    error_log(print_r($body, true));
    error_log(print_r($target_url, true));

    // Envia a requisição para o ambiente local
    $response = wp_remote_request($target_url, [
        'method'  => $method,
        'body'    => $body,
        'timeout' => 15,
    ]);

    error_log(print_r($response, true));
    // Finaliza sem processar mais nada
    status_header(200);
    exit;
}

