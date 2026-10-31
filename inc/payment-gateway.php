<?php
if (!defined('ABSPATH')) {
    exit;
}

/*
 * Jembatan ke plugin VD Payment Gateway (DOKU, dst.). Duitku tetap lewat Velocity_Addons_Duitku
 * (asli addons maupun alias dari vd-payment-gateway). Pola sama dengan vd-store.
 */

define('VELOCITY_DONASI_PG_SOURCE', 'velocity-donasi');
define('VELOCITY_DONASI_PG_EXPIRE_GRACE', 5 * 60); // detik toleransi selisih jam untuk notifikasi EXPIRED

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
 * Invoice dipakai donasi lain, atau transaksi vd-payment-gateway (DOKU, Duitku) milik pesanan lain.
 */
function velocity_donasi_invoice_taken($invoice, $post_id = 0) {
    global $wpdb;
    $taken = (int) $wpdb->get_var($wpdb->prepare(
        "SELECT ID FROM {$wpdb->posts} WHERE post_type = 'donasi-masuk' AND post_title = %s AND ID <> %d LIMIT 1",
        (string) $invoice,
        (int) $post_id
    ));
    if ($taken) {
        return true;
    }
    if (function_exists('vd_pg_get_transaction')) {
        foreach (array_merge(array_keys(velocity_donasi_pg_methods()), array('duitku')) as $gateway) {
            if (vd_pg_get_transaction($gateway, (string) $invoice)) {
                return true;
            }
        }
    }
    return false;
}

/**
 * Invoice donasi baru, dibuat di server (bukan dari form): 9 digit acak yang belum dipakai.
 * Filter velocity_donasi_new_invoice boleh mengusulkan nomor lain; nomor yang tidak sah/terpakai diganti.
 */
function velocity_donasi_new_invoice($post_id = 0) {
    for ($i = 0; $i < 10; $i++) {
        $invoice = (string) apply_filters('velocity_donasi_new_invoice', (string) wp_rand(100000000, 999999999), (int) $post_id, $i);
        if (preg_match('/^\d{6,12}$/', $invoice) && !velocity_donasi_invoice_taken($invoice, $post_id)) {
            return $invoice;
        }
    }
    return 'D' . (int) $post_id . '-' . wp_rand(1000, 9999);
}

/** Invoice donasi yang sudah ada dipakai bila masih unik; selain itu diganti invoice baru. */
function velocity_donasi_pg_unique_invoice($method, $invoice, $post_id) {
    $invoice = (string) $invoice;
    if (preg_match('/^\d{6,12}$/', $invoice) && !velocity_donasi_invoice_taken($invoice, $post_id)) {
        return $invoice;
    }
    return velocity_donasi_new_invoice($post_id);
}

/**
 * Donasi untuk satu invoice (judul post donasi-masuk), cocok persis dan hanya yang terbit.
 * Data lama bisa punya invoice kembar: dengan $method, hanya donasi bermetode itu yang dipilih (terbaru dulu).
 *
 * @return WP_Post|null
 */
function velocity_donasi_find_by_invoice($invoice, $method = '') {
    global $wpdb;
    $invoice = trim((string) $invoice);
    if ($invoice === '') {
        return null;
    }
    $ids = $wpdb->get_col($wpdb->prepare(
        "SELECT ID FROM {$wpdb->posts} WHERE post_type = 'donasi-masuk' AND post_status = 'publish' AND post_title = %s ORDER BY ID DESC LIMIT 20",
        $invoice
    ));
    foreach ($ids as $id) {
        if ($method === '' || get_post_meta((int) $id, 'metode_bayar', true) === $method) {
            return get_post((int) $id);
        }
    }
    return null;
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
        'return_url'  => velocity_donasi_invoice_url($invoice, $post_id),
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
    if (!velocity_donasi_pg_can_pay($post_id)) {
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
    if (is_wp_error($result)) {
        return $result;
    }
    // Donasi Gagal karena link kedaluwarsa kembali menunggu pembayaran (invoice & transaksi yang sama).
    update_post_meta($post_id, 'status', 'Menunggu Pembayaran');

    return (string) $result['payment_url'];
}

/**
 * Donasi DOKU masih bisa dibayar: Pending/Menunggu Pembayaran, atau Gagal karena tagihannya kedaluwarsa
 * (bukan dibatalkan admin). Transaksi lunas tidak pernah dibuat ulang.
 */
function velocity_donasi_pg_can_pay($post_id) {
    $method = (string) get_post_meta($post_id, 'metode_bayar', true);
    $status = (string) get_post_meta($post_id, 'status', true);
    if (!velocity_donasi_pg_handles($method)) {
        return false;
    }
    if (in_array($status, array('Pending', 'Menunggu Pembayaran'), true)) {
        return true;
    }
    // Gagal karena dibatalkan/diubah admin tidak punya penanda pg_expired.
    if ($status !== 'Gagal' || get_post_meta($post_id, 'pg_expired', true) !== '1' || !function_exists('vd_pg_get_transaction')) {
        return false;
    }
    $transaction = vd_pg_get_transaction($method, get_the_title($post_id));
    return $transaction && $transaction->status === 'expired' && $transaction->source === VELOCITY_DONASI_PG_SOURCE && (int) $transaction->ref_id === (int) $post_id;
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

/**
 * Link halaman donatur untuk satu invoice. Dengan $post_id, link diberi token (t=) supaya penerima
 * email/link bayar bisa melihat data dirinya utuh; tanpa token data donatur ditampilkan tersamar.
 */
function velocity_donasi_invoice_url($invoice, $post_id = 0) {
    $page_id  = velocitydonasi_option('halaman_donatur');
    $page_url = $page_id ? get_permalink($page_id) : get_home_url();
    $args     = array('inv' => rawurlencode((string) $invoice));
    if ($post_id) {
        $args['t'] = velocity_donasi_invoice_token($post_id);
    }
    return add_query_arg($args, $page_url);
}

function velocity_donasi_invoice_token($post_id) {
    return substr(wp_hash('velocity-donasi-invoice|' . (int) $post_id . '|' . get_the_title($post_id)), 0, 20);
}

/** Data pribadi donatur boleh tampil utuh untuk admin, pemilik donasi (login), atau pemegang link bertoken. */
function velocity_donasi_can_view_private($post_id) {
    if (current_user_can('edit_post', $post_id)) {
        return true;
    }
    $owner = (int) get_post_meta($post_id, 'user_id', true);
    if ($owner > 0 && $owner === get_current_user_id()) {
        return true;
    }
    $token = isset($_GET['t']) ? sanitize_text_field(wp_unslash($_GET['t'])) : '';
    return $token !== '' && hash_equals(velocity_donasi_invoice_token($post_id), $token);
}

/** B*** S***, bu***@contoh.com, 0812****2222 */
function velocity_donasi_mask($type, $value) {
    $value = trim((string) $value);
    if ($value === '') {
        return '';
    }
    if ($type === 'email' && strpos($value, '@') !== false) {
        list($user, $domain) = explode('@', $value, 2);
        $keep = mb_strlen($user) > 3 ? 2 : (mb_strlen($user) > 1 ? 1 : 0);
        return mb_substr($user, 0, $keep) . '***@' . $domain;
    }
    if ($type === 'phone') {
        $digits = preg_replace('/\D+/', '', $value);
        return strlen($digits) > 8 ? substr($digits, 0, 4) . str_repeat('*', strlen($digits) - 8) . substr($digits, -4) : '***';
    }
    return implode(' ', array_map(function ($word) {
        return mb_substr($word, 0, 1) . '***';
    }, preg_split('/\s+/', $value)));
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
    if ($status === 'expired') {
        // Hanya donasi yang masih menunggu pembayaran yang jadi Gagal; status buatan admin (Gagal/Sukses/...) tidak ditimpa.
        if (!in_array((string) get_post_meta($post_id, 'status', true), array('Pending', 'Menunggu Pembayaran'), true)) {
            return;
        }
        // EXPIRED terlambat untuk link lama, padahal link baru (invoice sama) masih berlaku.
        // Catatan: transaksi vdpg sudah "expired", jadi EXPIRED berikutnya tidak memicu event lagi; donasi tetap
        // "Menunggu Pembayaran" (hanya tampilan), link dibuat ulang saat dibuka dan pembayaran tetap bisa lunas.
        if ((int) get_post_meta($post_id, 'pg_expires_at', true) > time() + VELOCITY_DONASI_PG_EXPIRE_GRACE) {
            return;
        }
    }

    update_post_meta($post_id, 'status', $map[$status]);
    if ($status === 'expired') {
        // Ditulis sesudah status: perubahan status lain (admin, bayar ulang) menghapus penanda ini.
        update_post_meta($post_id, 'pg_expired', 1);
    }
    update_post_meta($post_id, 'pg_reference', sanitize_text_field((string) $transaction->reference));
    update_post_meta($post_id, 'pg_channel', sanitize_text_field((string) $transaction->channel));
    if ($status === 'paid') {
        update_post_meta($post_id, 'pg_paid_amount', (int) $transaction->amount);
        delete_option('velocity_donasi_pay_regen_' . $post_id);
    }

    do_action('velocity_donasi_payment_' . $status, $post_id, $transaction, is_array($payload) ? $payload : array());
}
add_action('vd_pg_payment_status_changed', 'velocity_donasi_pg_status_changed', 10, 3);

/** Setiap perubahan status donasi (meta box admin, bayar ulang, callback) menghapus penanda kedaluwarsa. */
function velocity_donasi_pg_clear_expired_flag($meta_id, $post_id, $meta_key) {
    if ($meta_key === 'status') {
        delete_post_meta($post_id, 'pg_expired');
    }
}
add_action('added_post_meta', 'velocity_donasi_pg_clear_expired_flag', 10, 3);
add_action('updated_post_meta', 'velocity_donasi_pg_clear_expired_flag', 10, 3);

/** Token t= di URL halaman donatur jangan ikut terkirim lewat Referer ke situs lain. */
function velocity_donasi_invoice_referrer_policy() {
    if (isset($_GET['inv'], $_GET['t']) && !headers_sent()) {
        header('Referrer-Policy: no-referrer');
    }
}
add_action('template_redirect', 'velocity_donasi_invoice_referrer_policy');
