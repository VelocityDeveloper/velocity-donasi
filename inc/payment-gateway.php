<?php
if (!defined('ABSPATH')) {
    exit;
}

/*
 * Jembatan ke plugin VD Payment Gateway (DOKU, dst.). Duitku tetap lewat Velocity_Addons_Duitku
 * (asli addons maupun alias dari vd-payment-gateway). Pola sama dengan vd-store.
 */

define('VELOCITY_DONASI_PG_SOURCE', 'velocity-donasi');

/** Metode pembayaran donasi yang ditangani langsung oleh vd-payment-gateway. */
function velocity_donasi_pg_methods() {
    return array('doku' => 'DOKU');
}

function velocity_donasi_pg_handles($method) {
    $methods = velocity_donasi_pg_methods();
    return isset($methods[sanitize_key((string) $method)]);
}

function velocity_donasi_pg_is_available($method) {
    return velocity_donasi_pg_handles($method) && function_exists('vd_pg_is_available') && vd_pg_is_available($method);
}

/** @return array id => label metode vd-payment-gateway yang aktif, untuk pilihan di form donasi. */
function velocity_donasi_pg_available_methods() {
    $list = array();
    foreach (velocity_donasi_pg_methods() as $id => $label) {
        if (velocity_donasi_pg_is_available($id)) {
            $list[$id] = $label;
        }
    }
    return $list;
}

/**
 * Invoice dari form (angka acak di browser) hanya dipakai bila belum dipakai donasi lain maupun
 * transaksi vd-payment-gateway; invoice yang sama bisa menimpa transaksi milik pesanan lain.
 */
function velocity_donasi_pg_unique_invoice($method, $invoice, $post_id) {
    global $wpdb;
    $invoice = preg_match('/^\d{6,12}$/', (string) $invoice) ? (string) $invoice : '';
    for ($i = 0; $i < 10; $i++) {
        if ($invoice !== '') {
            $taken = (int) $wpdb->get_var($wpdb->prepare(
                "SELECT ID FROM {$wpdb->posts} WHERE post_type = 'donasi-masuk' AND post_title = %s AND ID <> %d LIMIT 1",
                $invoice,
                $post_id
            ));
            if (!$taken && !vd_pg_get_transaction($method, $invoice)) {
                return $invoice;
            }
        }
        $invoice = (string) wp_rand(100000000, 999999999);
    }
    return 'D' . $post_id . '-' . wp_rand(1000, 9999);
}

/**
 * Buat tagihan untuk satu donasi-masuk.
 *
 * @return array|WP_Error hasil vd_pg_create_payment()
 */
function velocity_donasi_pg_create_payment($method, $post_id) {
    if (!velocity_donasi_pg_is_available($method)) {
        $methods = velocity_donasi_pg_methods();
        return new WP_Error('velocity_donasi_pg_not_available', sprintf('Gateway %s tidak tersedia.', isset($methods[$method]) ? $methods[$method] : $method));
    }

    $post_id   = (int) $post_id;
    $invoice   = get_the_title($post_id);
    $amount    = (int) get_post_meta($post_id, 'totaldonasi', true);
    $campaign  = (int) get_post_meta($post_id, 'donasi_id', true);
    $title     = $campaign ? wp_strip_all_tags(get_the_title($campaign)) : 'Donasi';

    $result = vd_pg_create_payment($method, array(
        'invoice'     => $invoice,
        'amount'      => $amount,
        'source'      => VELOCITY_DONASI_PG_SOURCE,
        'ref_id'      => (string) $post_id,
        'return_url'  => velocity_donasi_invoice_url($invoice),
        'description' => 'Donasi ' . $title,
        'customer'    => array(
            'id'    => (string) get_post_meta($post_id, 'user_id', true),
            'name'  => (string) get_post_meta($post_id, 'nama_donatur', true),
            'email' => (string) get_post_meta($post_id, 'email_donatur', true),
            'phone' => (string) get_post_meta($post_id, 'hp_donatur', true),
        ),
        'items'       => array(
            array('name' => $title, 'price' => $amount, 'qty' => 1),
        ),
    ));
    if (is_wp_error($result)) {
        return $result;
    }

    update_post_meta($post_id, 'pg_payment_url', esc_url_raw((string) $result['payment_url']));
    update_post_meta($post_id, 'pg_expires_at', (int) (isset($result['expires_at']) ? $result['expires_at'] : 0));
    update_post_meta($post_id, 'pg_reference', sanitize_text_field((string) (isset($result['reference']) ? $result['reference'] : '')));
    update_post_meta($post_id, 'pg_transaction_id', (int) (isset($result['transaction_id']) ? $result['transaction_id'] : 0));

    return $result;
}

/**
 * Link bayar untuk halaman donatur. Link kedaluwarsa dibuat ulang (invoice sama) selama donasi
 * belum dibayar; dibatasi 1x per 5 menit karena halaman ini bisa dibuka siapa saja yang tahu invoice.
 *
 * @return string|WP_Error
 */
function velocity_donasi_pg_payment_link($post_id) {
    $post_id    = (int) $post_id;
    $method     = (string) get_post_meta($post_id, 'metode_bayar', true);
    $url        = (string) get_post_meta($post_id, 'pg_payment_url', true);
    $expires_at = (int) get_post_meta($post_id, 'pg_expires_at', true);

    if ($url !== '' && ($expires_at === 0 || $expires_at > time() + 60)) {
        return $url;
    }
    if (!in_array((string) get_post_meta($post_id, 'status', true), array('Pending', 'Menunggu Pembayaran'), true)) {
        return $url;
    }

    if (!velocity_donasi_pg_regen_lock($post_id)) {
        return new WP_Error('velocity_donasi_pay_regen_wait', 'Link pembayaran sedang dibuat ulang, coba beberapa menit lagi.');
    }
    // Permintaan lain mungkin sudah membuat link baru sebelum kunci ini didapat.
    wp_cache_delete($post_id, 'post_meta');
    $url        = (string) get_post_meta($post_id, 'pg_payment_url', true);
    $expires_at = (int) get_post_meta($post_id, 'pg_expires_at', true);
    if ($url !== '' && ($expires_at === 0 || $expires_at > time() + 60)) {
        return $url;
    }

    $result = velocity_donasi_pg_create_payment($method, $post_id);

    return is_wp_error($result) ? $result : (string) $result['payment_url'];
}

/**
 * Kunci atomik 1x per 5 menit (add_option gagal bila baris sudah ada, tidak seperti get/set_transient).
 */
function velocity_donasi_pg_regen_lock($post_id) {
    $name = 'velocity_donasi_pay_regen_' . (int) $post_id;
    if (add_option($name, time(), '', 'no')) {
        return true;
    }
    $since = (int) get_option($name);
    if ($since > time() - 5 * MINUTE_IN_SECONDS) {
        return false;
    }
    // Kunci basi: hanya satu permintaan yang berhasil memperbaruinya.
    global $wpdb;
    $taken = (bool) $wpdb->query($wpdb->prepare("UPDATE {$wpdb->options} SET option_value = %s WHERE option_name = %s AND option_value = %s", (string) time(), $name, (string) $since));
    wp_cache_delete($name, 'options');
    return $taken;
}

/** Tombol bayar untuk donasi DOKU (riwayat donasi & hasil submit). */
function velocity_donasi_pg_pay_button($post_id, $class = 'btn btn-sm btn-primary mt-2') {
    $method  = (string) get_post_meta($post_id, 'metode_bayar', true);
    $methods = velocity_donasi_pg_methods();
    $label   = isset($methods[$method]) ? $methods[$method] : $method;
    $link    = velocity_donasi_pg_payment_link($post_id);
    if (is_wp_error($link) || $link === '') {
        return '<div class="small text-muted mt-2">Link pembayaran belum tersedia. Silakan hubungi admin.</div>';
    }
    return '<a class="' . esc_attr($class) . '" href="' . esc_url($link) . '">Bayar via ' . esc_html($label) . '</a>';
}

function velocity_donasi_invoice_url($invoice) {
    $page_id  = velocitydonasi_option('halaman_donatur');
    $page_url = $page_id ? get_permalink($page_id) : get_home_url();
    return add_query_arg('inv', rawurlencode((string) $invoice), $page_url);
}

/** Status dari vd-payment-gateway (notifikasi atau cek status) diteruskan ke donasi-masuk. */
function velocity_donasi_pg_status_changed($transaction, $status, $payload) {
    if (!is_object($transaction) || $transaction->source !== VELOCITY_DONASI_PG_SOURCE) {
        return;
    }
    $post_id = (int) $transaction->ref_id;
    if ($post_id <= 0 || get_post_type($post_id) !== 'donasi-masuk') {
        return;
    }

    $map = array('paid' => 'Sukses', 'expired' => 'Gagal', 'refunded' => 'Refund');
    if (!isset($map[$status])) {
        return;
    }
    // Donasi yang sudah Sukses tidak turun jadi Gagal karena link lama kedaluwarsa.
    if ($status === 'expired' && get_post_meta($post_id, 'status', true) === 'Sukses') {
        return;
    }

    update_post_meta($post_id, 'status', $map[$status]);
    update_post_meta($post_id, 'pg_reference', sanitize_text_field((string) $transaction->reference));
    update_post_meta($post_id, 'pg_channel', sanitize_text_field((string) $transaction->channel));
    if ($status === 'paid') {
        update_post_meta($post_id, 'pg_paid_amount', (int) $transaction->amount);
        delete_option('velocity_donasi_pay_regen_' . $post_id);
    }

    do_action('velocity_donasi_payment_' . $status, $post_id, $transaction, is_array($payload) ? $payload : array());
}
add_action('vd_pg_payment_status_changed', 'velocity_donasi_pg_status_changed', 10, 3);
