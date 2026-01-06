<?php

add_action('wp_ajax_nopriv_submitdonasi', 'submitdonasi_ajax');
add_action('wp_ajax_submitdonasi', 'submitdonasi_ajax');
function submitdonasi_ajax() {
    $data   = $_POST;
    $check = velocity_donasi_validate_recaptcha();
    if($check !== true){
        echo '<div class="alert alert-danger">Gagal, coba lagi.</div>';
    } else {
        //echo '<pre>'.print_r($data,1).'</pre>';
        $new_post = array(
            'post_title'    => sanitize_text_field($data['invoice']),
            'post_content'  => sanitize_textarea_field($data['pesan']),
            'post_status'   => 'publish',
            'post_type'   => 'donasi-masuk',
        );
        $post_id = wp_insert_post($new_post);
        if ($post_id) {
            $metode_bayar = sanitize_text_field($data['bayar']);
            $bankpilihan  = isset($data['bankpilihan']) ? sanitize_text_field($data['bankpilihan']) : '';
            add_post_meta($post_id, 'metode_bayar', $metode_bayar);
            add_post_meta($post_id, 'bank', $bankpilihan);
            add_post_meta($post_id, 'nama_donatur', sanitize_text_field($data['first_name']));
            add_post_meta($post_id, 'email_donatur', sanitize_text_field($data['user_email']));
            add_post_meta($post_id, 'hp_donatur', sanitize_text_field($data['hp']));
            add_post_meta($post_id, 'anonim', sanitize_text_field($data['anonim']));
            add_post_meta($post_id, 'kodeunik', sanitize_text_field($data['kodeunik']));
            add_post_meta($post_id, 'totaldonasi', sanitize_text_field($data['totaldonasi']));
            add_post_meta($post_id, 'user_id', sanitize_text_field($data['user_id']));
            add_post_meta($post_id, 'donasi_id', sanitize_text_field($data['post_id']));
            add_post_meta($post_id, 'status', 'Pending');

            if ($metode_bayar === 'duitku') {
                $duitku_ready = class_exists('Velocity_Addons_Duitku') && method_exists('Velocity_Addons_Duitku', 'is_active') && Velocity_Addons_Duitku::is_active();
                if (!$duitku_ready) {
                    update_post_meta($post_id, 'status', 'Gagal');
                    echo '<div class="alert alert-danger">Payment Gateway tidak tersedia atau belum diaktifkan. Silakan coba metode lain.</div>';
                    wp_die();
                }

                $duitku = new Velocity_Addons_Duitku();
                $paymentAmount   = intval($data['totaldonasi']);
                $merchantOrderId = sanitize_text_field($data['invoice']);
                $productDetails  = get_the_title($data['post_id']);
                $customerVaName  = sanitize_text_field($data['first_name']);
                $email           = sanitize_email($data['user_email']);
                $phoneNumber     = sanitize_text_field($data['hp']);
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
                    'additionalParam' => (string) $data['post_id'],
                    'merchantUserInfo'=> (string) $data['user_id'],
                    'customerVaName'  => $customerVaName,
                    'email'           => $email,
                    'phoneNumber'     => $phoneNumber,
                    'itemDetails'     => $itemDetails,
                    'customerDetail'  => $customerDetail,
                );

                $invoice_response = $duitku->createInvoice($params);

                if (is_wp_error($invoice_response)) {
                    update_post_meta($post_id, 'status', 'Gagal');
                    echo '<div class="alert alert-danger">Gagal membuat pembayaran. '.$invoice_response->get_error_message().'</div>';
                    wp_die();
                }

                update_post_meta($post_id, 'status', 'Menunggu Pembayaran');
                update_post_meta($post_id, 'duitku_reference', isset($invoice_response['reference']) ? $invoice_response['reference'] : '');
                update_post_meta($post_id, 'duitku_payment_url', isset($invoice_response['paymentUrl']) ? $invoice_response['paymentUrl'] : '');
                update_post_meta($post_id, 'duitku_status_code', isset($invoice_response['statusCode']) ? $invoice_response['statusCode'] : '');
                update_post_meta($post_id, 'duitku_status_message', isset($invoice_response['statusMessage']) ? $invoice_response['statusMessage'] : '');

                $button = do_shortcode('[tombol_bayar_duitku invoice="'.$merchantOrderId.'" class="btn btn-primary w-100"]');
                echo '<div class="alert alert-success">';
                    echo '<p>Donasi sedang diproses melalui Duitku.</p>';
                    echo '<p class="mb-2">Klik tombol di bawah untuk menyelesaikan pembayaran.</p>';
                    echo $button;
                echo '</div>';

            } else {
                $databank = getBankDonasi($bankpilihan);
                echo '<div class="alert alert-success">';
                    echo '<p>Donasi sedang diproses.</p>';
                    echo '<p class="mb-1">Silahkan transfer uang sebesar <strong>'.DonasiCurrency($data['totaldonasi']).'</strong> ke rekening dibawah ini.</p>';
                    if($databank['logo']){
                        echo '<img class="d-inline-block mb-2" src="'.$databank['logo'].'" />';
                    }
                    echo '<p class="mb-0 fw-bold">'.$databank['nobank'].' a/n '.$databank['atasnama'].'</p>';
                echo '</div>';

                // Email dikirim ke donatur
                $body = '<html><body>';
                $body .= '<p>Detail donasi untuk '.get_the_title($data['post_id']).'.</p>';
                $body .= '<p>Silahkan transfer uang sebesar <strong>'.DonasiCurrency($data['totaldonasi']).'</strong> ke rekening dibawah ini.</p>';
                $body .= '<p><strong>'.strtoupper($bankpilihan).' ('.$databank['nobank'].' a/n '.$databank['atasnama'].')</strong>.</p>';
                $body .= get_home_url();
                $body .= '</body></html>';

                $headers = 'MIME-Version: 1.0' . "\r\n";
                $headers .= 'Content-type: text/html; charset=iso-8859-1' . "\r\n";
                $subject = 'Terimakasih telah berdonasi, berikut detail donasi anda #'.$data['invoice'];
            
                wp_mail($data['user_email'], $subject, $body, $headers );
            }


            // Email dikirim ke Admin
            /*
            $admin_email = velocitydonasi_option('email_admin_donasi',get_bloginfo('admin_email')); 
            $subject = 'Ada donasi masuk dengan invoice #'.$data['invoice'];
            $message = '<html><body>';
            $message .= '<p>Donasi untuk '.get_the_title($data['post_id']).'.</p>';
            $message .= '<p>Jumlah Donasi: <strong>'.DonasiCurrency($data['totaldonasi']).'</strong>.</p>';
            $message .= get_home_url();
            $message .= '</body></html>';
            wp_mail($admin_email, $subject, $message, $headers );
            */
        } else {
            echo '<div class="alert alert-danger">Gagal, coba lagi.</div>';
        }
    }
    wp_die();
}
