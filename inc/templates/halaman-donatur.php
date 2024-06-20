<?php
if ( ! defined( 'ABSPATH' ) ) exit;
$act        = isset($_GET['act']) ? $_GET['act'] : '';
$id        = isset($_GET['id']) ? $_GET['id'] : '';
$inv        = isset($_GET['inv']) ? $_GET['inv'] : '';
$gresponse  = isset($_GET['g-recaptcha-response']) ? $_GET['g-recaptcha-response'] : '';


if($act == 'konfirmasi' && $id && !has_post_thumbnail($id) && get_post($id)){
    require_once VELOCITY_DONASI_DIR.'inc/templates/halaman-konfirmasi.php';
} else {
 ?>
    <div class="card mb-4">
        <div class="card-header fs-6">
            Status Donasi
        </div>
        <div class="card-body">
            <form method="get">
                <div class="form-floating mb-3">
                    <input type="text" class="form-control" max="10" value="" id="invoice" name="inv" placeholder="123456789" required="">
                    <label for="invoice">Kode Invoice</label>
                </div>
                <div class="text-start">
                    <?php echo velocity_donasi_recaptcha(); ?>
                    <button type="submit" class="btn btn-dark btn-sm px-3">Cek Donasi</button>
                    <?php
                    if (!is_user_logged_in()) {
                        echo '<a class="btn btn-success btn-sm px-3 ms-2" href="'.esc_url(wp_login_url(get_permalink())).'">Masuk</a>';
                    } else {
                        echo '<a class="btn btn-danger btn-sm px-3 ms-2" href="'.wp_logout_url(get_permalink()).'">Keluar</a>';
                    } ?>
                </div>
            </form>
        </div>
    </div>
    <?php
    $check = velocity_donasi_validate_recaptcha($gresponse);
    if ($inv && $check == 1) {        
        global $wpdb;
        
        // Query untuk mencari pos berdasarkan judul secara tepat
        $query = $wpdb->prepare("
            SELECT ID, post_title 
            FROM {$wpdb->posts} 
            WHERE post_type = 'donasi-masuk' 
            AND post_status = 'publish' 
            AND post_title = %s
        ", $inv);
        
        // Lakukan query
        $results = $wpdb->get_results($query);
        
        // Tampilkan hasil pencarian
        if ($results) {
            echo '<div class="card p-3 mb-4">';
                echo '<div class="table-responsive">';
                echo '<table class="table mb-2">';
                echo '<thead>';
                echo '<tr>';
                    echo '<th scope="col">Invoice</th>';
                    echo '<th scope="col">Donasi</th>';
                    echo '<th scope="col">Jumlah Donasi</th>';
                    echo '<th scope="col">Status</th>';
                    echo '<th scope="col">Tanggal</th>';
                    echo '</tr>';
                echo '</thead>';
                echo '<tbody>';
                foreach ($results as $post) {
                    DaftarDonasiMasuk($post->ID);
                }
                echo '</tbody>';
                echo '</table>';
                echo '</div>';
            echo '</div>';
        } else {
            echo '<div class="alert alert-warning">Tidak ada data yang ditemukan untuk invoice <strong>'.$inv.'</strong>.</div>';
        }
    } elseif ($inv) {        
        echo '<div class="alert alert-warning">Isikan captcha.</div>';
    } ?>

 
    <?php

    if ( is_user_logged_in() ) {
        $current_user_id = get_current_user_id();

        // Pastikan pengguna sudah login
        if ($current_user_id) {
            $args = array(
                'post_type'      => 'donasi-masuk',
                'post_status'    => 'publish',
                'meta_query'     => array(
                    array(
                        'key'     => 'user_id',
                        'value'   => $current_user_id,
                        'compare' => '='
                    )
                )
            );
        
            $posts = get_posts($args);
        
            echo '<hr>';
            echo '<div class="row">';
                echo '<h3 class="col-md-3 pe-md-0 fs-5 fw-bold">Riwayat Donasi</h3>';
                echo '<div class="col-md-9 text-md-end text-success">Total Donasi Sukses:  '.total_donasi_user().'</div>';
            echo '</div>';
            if ($posts) {
                echo '<div class="table-responsive">';
                echo '<table class="table">';
                echo '<thead>';
                echo '<tr>';
                    echo '<th scope="col">Invoice</th>';
                    echo '<th scope="col">Donasi</th>';
                    echo '<th scope="col">Jumlah Donasi</th>';
                    echo '<th scope="col">Status</th>';
                    echo '<th scope="col">Tanggal</th>';
                    echo '</tr>';
                echo '</thead>';
                echo '<tbody>';
                foreach ($posts as $post) {
                    DaftarDonasiMasuk($post->ID);
                }
                echo '</tbody>';
                echo '</table>';
                echo '</div>';
            } else {
                echo 'Tidak ada riwayat donasi ditemukan.';
            }
        }
        
    }

}?>