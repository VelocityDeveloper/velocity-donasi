<?php
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Submit donasi via REST API.
 */
function velocity_donasi_register_rest_routes() {
    register_rest_route(
        'velocity-donasi/v1',
        '/submit',
        array(
            'methods'             => WP_REST_Server::CREATABLE,
            'callback'            => 'velocity_donasi_rest_submit',
            'permission_callback' => '__return_true',
        )
    );
}
add_action('rest_api_init', 'velocity_donasi_register_rest_routes');

function velocity_donasi_rest_submit(WP_REST_Request $request) {
    $data = $request->get_params();

    $check = velocity_donasi_validate_recaptcha(isset($data['g-recaptcha-response']) ? $data['g-recaptcha-response'] : null);
    if ($check !== true) {
        return new WP_REST_Response(array(
            'success' => false,
            'html'    => '<div class="alert alert-danger">Gagal, coba lagi.</div>',
        ), 200);
    }

    $new_post = array(
        'post_title'   => sanitize_text_field(isset($data['invoice']) ? $data['invoice'] : ''),
        'post_content' => sanitize_textarea_field(isset($data['pesan']) ? $data['pesan'] : ''),
        'post_status'  => 'publish',
        'post_type'    => 'donasi-masuk',
    );

    $post_id = wp_insert_post($new_post);

    if (!$post_id) {
        return new WP_REST_Response(array(
            'success' => false,
            'html'    => '<div class="alert alert-danger">Gagal, coba lagi.</div>',
        ), 200);
    }

    $metode_bayar = sanitize_text_field(isset($data['bayar']) ? $data['bayar'] : '');
    $bankpilihan  = isset($data['bankpilihan']) ? sanitize_text_field($data['bankpilihan']) : '';

    add_post_meta($post_id, 'metode_bayar', $metode_bayar);
    add_post_meta($post_id, 'bank', $bankpilihan);
    add_post_meta($post_id, 'nama_donatur', sanitize_text_field(isset($data['first_name']) ? $data['first_name'] : ''));
    add_post_meta($post_id, 'email_donatur', sanitize_text_field(isset($data['user_email']) ? $data['user_email'] : ''));
    add_post_meta($post_id, 'hp_donatur', sanitize_text_field(isset($data['hp']) ? $data['hp'] : ''));
    add_post_meta($post_id, 'anonim', sanitize_text_field(isset($data['anonim']) ? $data['anonim'] : ''));
    add_post_meta($post_id, 'kodeunik', sanitize_text_field(isset($data['kodeunik']) ? $data['kodeunik'] : ''));
    add_post_meta($post_id, 'totaldonasi', sanitize_text_field(isset($data['totaldonasi']) ? $data['totaldonasi'] : ''));
    add_post_meta($post_id, 'user_id', sanitize_text_field(isset($data['user_id']) ? $data['user_id'] : ''));
    add_post_meta($post_id, 'donasi_id', sanitize_text_field(isset($data['post_id']) ? $data['post_id'] : ''));
    add_post_meta($post_id, 'status', 'Pending');

    if ($metode_bayar === 'duitku') {
        $duitku_ready = class_exists('Velocity_Addons_Duitku') && method_exists('Velocity_Addons_Duitku', 'is_active') && Velocity_Addons_Duitku::is_active();
        if (!$duitku_ready) {
            update_post_meta($post_id, 'status', 'Gagal');
            return new WP_REST_Response(array(
                'success' => false,
                'html'    => '<div class="alert alert-danger">Payment Gateway tidak tersedia atau belum diaktifkan. Silakan coba metode lain.</div>',
            ), 200);
        }

        $duitku = new Velocity_Addons_Duitku();
        $paymentAmount   = intval(isset($data['totaldonasi']) ? $data['totaldonasi'] : 0);
        $merchantOrderId = sanitize_text_field(isset($data['invoice']) ? $data['invoice'] : '');
        $productDetails  = get_the_title(isset($data['post_id']) ? $data['post_id'] : 0);
        $customerVaName  = sanitize_text_field(isset($data['first_name']) ? $data['first_name'] : '');
        $email           = sanitize_email(isset($data['user_email']) ? $data['user_email'] : '');
        $phoneNumber     = sanitize_text_field(isset($data['hp']) ? $data['hp'] : '');
        $address = array(
            'firstName'   => $customerVaName,
            'lastName'    => '',
            'address'     => '',
            'city'        => '',
            'postalCode'  => '',
            'phone'       => $phoneNumber,
            'countryCode' => 'ID',
        );
        $customerDetail = array(
            'firstName'       => $customerVaName,
            'lastName'        => '',
            'email'           => $email,
            'phoneNumber'     => $phoneNumber,
            'billingAddress'  => $address,
            'shippingAddress' => $address,
        );
        $itemDetails = array(
            array(
                'name'     => $productDetails,
                'price'    => $paymentAmount,
                'quantity' => 1,
            ),
        );
        $params = array(
            'paymentAmount'   => $paymentAmount,
            'merchantOrderId' => $merchantOrderId,
            'productDetails'  => $productDetails,
            'additionalParam' => isset($data['post_id']) ? (string) $data['post_id'] : '',
            'merchantUserInfo'=> isset($data['user_id']) ? (string) $data['user_id'] : '',
            'customerVaName'  => $customerVaName,
            'email'           => $email,
            'phoneNumber'     => $phoneNumber,
            'itemDetails'     => $itemDetails,
            'customerDetail'  => $customerDetail,
        );

        $invoice_response = $duitku->createInvoice($params);

        if (is_wp_error($invoice_response)) {
            update_post_meta($post_id, 'status', 'Gagal');
            return new WP_REST_Response(array(
                'success' => false,
                'html'    => '<div class="alert alert-danger">Gagal membuat pembayaran. '.$invoice_response->get_error_message().'</div>',
            ), 200);
        }

        update_post_meta($post_id, 'status', 'Menunggu Pembayaran');
        update_post_meta($post_id, 'duitku_reference', isset($invoice_response['reference']) ? $invoice_response['reference'] : '');
        update_post_meta($post_id, 'duitku_payment_url', isset($invoice_response['paymentUrl']) ? $invoice_response['paymentUrl'] : '');
        update_post_meta($post_id, 'duitku_status_code', isset($invoice_response['statusCode']) ? $invoice_response['statusCode'] : '');
        update_post_meta($post_id, 'duitku_status_message', isset($invoice_response['statusMessage']) ? $invoice_response['statusMessage'] : '');

        $button = do_shortcode('[tombol_bayar_duitku invoice="'.$merchantOrderId.'" class="btn btn-primary w-100"]');
        $html  = '<div class="alert alert-success">';
        $html .= '<p>Donasi sedang diproses melalui Duitku.</p>';
        $html .= '<p class="mb-2">Klik tombol di bawah untuk menyelesaikan pembayaran.</p>';
        $html .= $button;
        $html .= '</div>';

        return new WP_REST_Response(array(
            'success' => true,
            'html'    => $html,
        ), 200);
    }

    $databank = getBankDonasi($bankpilihan);
    $html  = '<div class="alert alert-success">';
    $html .= '<p>Donasi sedang diproses.</p>';
    $html .= '<p class="mb-1">Silahkan transfer uang sebesar <strong>'.DonasiCurrency($data['totaldonasi']).'</strong> ke rekening dibawah ini.</p>';
    if ($databank && isset($databank['logo']) && $databank['logo']) {
        $html .= '<img class="d-inline-block mb-2" src="'.$databank['logo'].'" />';
    }
    if ($databank) {
        $html .= '<p class="mb-0 fw-bold">'.$databank['nobank'].' a/n '.$databank['atasnama'].'</p>';
    }
    $html .= '</div>';

    // Email dikirim ke donatur
    $body = '<html><body>';
    $body .= '<p>Detail donasi untuk '.get_the_title($data['post_id']).'.</p>';
    $body .= '<p>Silahkan transfer uang sebesar <strong>'.DonasiCurrency($data['totaldonasi']).'</strong> ke rekening dibawah ini.</p>';
    if ($databank) {
        $body .= '<p><strong>'.strtoupper($bankpilihan).' ('.$databank['nobank'].' a/n '.$databank['atasnama'].')</strong>.</p>';
    }
    $body .= get_home_url();
    $body .= '</body></html>';

    $headers = 'MIME-Version: 1.0' . "\r\n";
    $headers .= 'Content-type: text/html; charset=iso-8859-1' . "\r\n";
    $subject = 'Terimakasih telah berdonasi, berikut detail donasi anda #'.$data['invoice'];

    wp_mail($data['user_email'], $subject, $body, $headers);

    return new WP_REST_Response(array(
        'success' => true,
        'html'    => $html,
    ), 200);
}
