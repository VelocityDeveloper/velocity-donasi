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
            add_post_meta($post_id, 'metode_bayar', sanitize_text_field($data['bayar']));
            add_post_meta($post_id, 'bank', sanitize_text_field($data['bankpilihan']));
            add_post_meta($post_id, 'nama_donatur', sanitize_text_field($data['first_name']));
            add_post_meta($post_id, 'email_donatur', sanitize_text_field($data['user_email']));
            add_post_meta($post_id, 'hp_donatur', sanitize_text_field($data['hp']));
            add_post_meta($post_id, 'anonim', sanitize_text_field($data['anonim']));
            add_post_meta($post_id, 'kodeunik', sanitize_text_field($data['kodeunik']));
            add_post_meta($post_id, 'totaldonasi', sanitize_text_field($data['totaldonasi']));
            add_post_meta($post_id, 'user_id', sanitize_text_field($data['user_id']));
            add_post_meta($post_id, 'donasi_id', sanitize_text_field($data['post_id']));
            add_post_meta($post_id, 'status', 'Pending');
            $databank = getBankDonasi($data['bankpilihan']);
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
            $body .= '<p><strong>'.strtoupper($data['bankpilihan']).' ('.$databank['nobank'].' a/n '.$databank['atasnama'].')</strong>.</p>';
            $body .= get_home_url();
            $body .= '</body></html>';

            $headers = 'MIME-Version: 1.0' . "\r\n";
            $headers .= 'Content-type: text/html; charset=iso-8859-1' . "\r\n";
            $subject = 'Terimakasih telah berdonasi, berikut detail donasi anda #'.$data['invoice'];
        
            wp_mail($data['user_email'], $subject, $body, $headers );


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