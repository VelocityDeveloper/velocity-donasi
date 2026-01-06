<?php
if ( ! defined( 'ABSPATH' ) ) exit;

if(!function_exists('velocitydonasi_option')) {
    function velocitydonasi_option($option_name = null, $default = null) {

        if(empty($option_name)) {
            return false;
        }

        if ( empty($default) && class_exists( 'Kirki' ) && isset( Kirki::$all_fields[ $option_name ] ) && isset( Kirki::$all_fields[ $option_name ]['default'] ) ) {
            $default = Kirki::$all_fields[ $option_name ]['default'];
        }

        $option_value = get_theme_mod($option_name,$default);

        // Normalize stored values that might be JSON/serialized strings (from Customizer repeater).
        if (is_string($option_value)) {
            $maybe_unserialized = maybe_unserialize($option_value);
            if ($maybe_unserialized !== $option_value) {
                $option_value = $maybe_unserialized;
            } elseif (isset($option_value[0]) && ($option_value[0] === '{' || $option_value[0] === '[')) {
                $decoded = json_decode($option_value, true);
                if (json_last_error() === JSON_ERROR_NONE) {
                    $option_value = $decoded;
                }
            }
        }

        return $option_value;

    }
}



// Tambahkan field pada halaman edit profil admin
function add_custom_user_meta_fields($user) {
    $textdomain = 'velocity-donasi';
    ?>
    <h3><?php _e('Custom User Meta',$textdomain); ?></h3>
    <table class="form-table">
        <tr>
            <th><label for="hp"><?php _e('Phone Number',$textdomain); ?></label></th>
            <td>
                <input type="text" name="hp" id="hp" value="<?php echo esc_attr(get_user_meta($user->ID, 'hp', true)); ?>" class="regular-text" /><br />
                <span class="description"><?php _e('Please enter your phone number.',$textdomain); ?></span>
            </td>
        </tr>
    </table>
    <?php
}
add_action('show_user_profile', 'add_custom_user_meta_fields');
add_action('edit_user_profile', 'add_custom_user_meta_fields');


// Simpan nilai custom user meta
function save_custom_user_meta_fields($user_id) {
    if (!current_user_can('edit_user', $user_id)) {
        return false;
    }
    // Simpan phone number
    if (isset($_POST['hp'])) {
        update_user_meta($user_id, 'hp', sanitize_text_field($_POST['hp']));
    }
}
add_action('personal_options_update', 'save_custom_user_meta_fields');
add_action('edit_user_profile_update', 'save_custom_user_meta_fields');


function velocity_donasi_recaptcha() {
    echo '<div class="velocitytoko-recaptcha my-2">';
        if (class_exists('Velocity_Addons_Captcha')){
            $captcha = new Velocity_Addons_Captcha;
            $captcha->display();
        }
    echo '</div>';
}
function velocity_donasi_validate_recaptcha($gresponse = null) {
    if (class_exists('Velocity_Addons_Captcha')) {
        $captcha = new Velocity_Addons_Captcha();
        $verify = $captcha->verify($gresponse);
        
        if (!$verify['success']) {
            return $verify['message'];
        }
    }    
    return true;
}

function DonasiCurrency($number) {
    if (!is_numeric($number)) {
        return '';
    }
    return 'Rp' . number_format($number, 0, ',', '.');
}


function getBankDonasi($bankName = null) {
    $databank       = velocitydonasi_option('bank_velocitydonasi', []);
    $databanklain   = velocitydonasi_option('banklain_velocitydonasi');

    $banks = [];
    if($databank){
        foreach ($databank as $key => $bank) {
            $namabank = $bank['namabank'];
            $banks[$namabank] = [
                'logo' => VELOCITY_DONASI_DIR_URI . 'img/b-' . $namabank . '.gif',
                'nobank' => $bank['nobank'],
                'atasnama' => $bank['atasnama']
            ];
        }
    } 
    if($databanklain){
        foreach ($databanklain as $bank) {
            $namabank = $bank['namabank'];
            $banks[$namabank] = [
                'logo' => $bank['logo'],
                'nobank' => $bank['nobank'],
                'atasnama' => $bank['atasnama']
            ];
        }
    }
    
    if ($bankName) {
        return isset($banks[$bankName]) ? $banks[$bankName] : null;
    } else {
        return $banks;
    }
}


function DaftarDonasiMasuk($post_id = null) {

    if(empty($post_id)){
        return false;
    }
    $donasi_id = get_post_meta($post_id, 'donasi_id', true);
    $total_donasi = get_post_meta($post_id, 'totaldonasi', true);
    $status_donasi = get_post_meta($post_id, 'status', true);
    $metode_bayar = get_post_meta($post_id, 'metode_bayar', true);
    $bank = get_post_meta($post_id, 'bank', true);
    $tanggal = get_the_date( 'd M Y', $post_id );
    $nama_donatur = get_post_meta($post_id, 'nama_donatur', true);
    $email_donatur = get_post_meta($post_id, 'email_donatur', true);
    $hp_donatur = get_post_meta($post_id, 'hp_donatur', true);
    
    $icon = '<svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" fill="currentColor" class="ms-1 bi bi-info-circle" viewBox="0 0 16 16">
    <path d="M8 15A7 7 0 1 1 8 1a7 7 0 0 1 0 14m0 1A8 8 0 1 0 8 0a8 8 0 0 0 0 16"/>
    <path d="m8.93 6.588-2.29.287-.082.38.45.083c.294.07.352.176.288.469l-.738 3.468c-.194.897.105 1.319.808 1.319.545 0 1.178-.252 1.465-.598l.088-.416c-.2.176-.492.246-.686.246-.275 0-.375-.193-.304-.533zM9 4.5a1 1 0 1 1-2 0 1 1 0 0 1 2 0"/>
  </svg>';
    echo '<tr>';
        echo '<td><a data-bs-toggle="collapse" href="#donasimasuk'.$post_id.'" role="button" aria-expanded="false" aria-controls="donasimasuk'.$post_id.'">'.get_the_title($post_id).' '.$icon.'</a></td>';
        echo '<td><a href="'.get_the_permalink($donasi_id).'" target="_blank">'.get_the_title($donasi_id).'</a></td>';
        echo '<td>'.DonasiCurrency($total_donasi).'</td>';
        $status_ini = $status_donasi == 'Butuh Konfirmasi' ? 'Sedang Diproses' : $status_donasi;
        echo '<td>'.$status_ini.'</td>';
        echo '<td>'.$tanggal.'</td>';
    echo '</tr>';
    echo '<tr class="collapse" id="donasimasuk'.$post_id.'">';
        echo '<td colspan="5">';
            echo '<div class="row mx-0 mb-3">';
                echo '<div class="col-md-6 ps-0 mb-1">';
                    echo '<div class="fw-bold mb-2">Data Diri</div>';
                    echo '<div class="mb-2">Nama Donatur: '.$nama_donatur.'</div>';
                    echo '<div class="mb-2">Email Donatur: '.$email_donatur.'</div>';
                    echo '<div class="mb-2">No. Telp: '.$hp_donatur.'</div>';
                echo '</div>';
                echo '<div class="col-md-6 pe-0 ps-0 ps-md-2">';
                    echo '<div class="fw-bold mb-2">Metode Pembayaran: <span class="text-uppercase">'.$metode_bayar.'</span></div>';
                    if($metode_bayar == 'bank'){
                        echo '<div class="mt-2">';
                            $bankdonasi = getBankDonasi($bank);
                            if($bankdonasi['logo']){
                                echo '<img class="w-auto" src="'.$bankdonasi['logo'].'" alt="'.$bank.'" style="max-height: 48px;">';
                            } else {
                                echo '<strong class="text-uppercase">'.$bank.': </strong>';
                            }
                            echo $bankdonasi['nobank'].' a/n '.$bankdonasi['atasnama'];
                        echo '</div>';
                    }
                    echo '<div class="mt-2">';
                        if(has_post_thumbnail($post_id)){
                            $thumbnail_url = get_the_post_thumbnail_url($post_id, 'full');
                            echo '<a class="px-3 btn btn-sm btn-primary" href="'.$thumbnail_url.'" target="_blank">Bukti Transfer</a>';
                        } elseif($status_donasi == 'Pending'){
                            echo '<a class="px-3 btn btn-sm btn-success" href="?act=konfirmasi&id='.$post_id.'" target="_blank">Konfirmasi</a>';
                        }
                    echo '</div>';
                echo '</div>';
            echo '</div>';
        echo '</td>';
    echo '</tr>';

    return true;
}



function total_donasi_user($status = 'Sukses', $user_id = null) {

    // Jika user_id tidak diberikan, gunakan ID pengguna yang sedang login
    if (!$user_id) {
        $user_id = get_current_user_id();
    }

    // Jika pengguna belum login, kembalikan 0
    if (!$user_id) {
        return 0;
    }

    // Menyiapkan query args untuk get_posts
    $args = array(
        'post_type' => 'donasi-masuk',
        'post_status' => 'publish',
        'numberposts' => -1, // Mengambil semua post yang cocok
        'meta_query' => array(
            array(
                'key' => 'status',
                'value' => $status,
                'compare' => '='
            ),
            array(
                'key' => 'user_id',
                'value' => $user_id,
                'compare' => '='
            )
        )
    );

    // Mendapatkan post berdasarkan query args
    $posts = get_posts($args);

    // Inisialisasi total donasi
    $total_donations = 0;

    // Loop melalui hasil post
    foreach ($posts as $post) {
        // Mengambil nilai donasi dari post meta 'totaldonasi'
        $donation_amount = get_post_meta($post->ID, 'totaldonasi', true);
        
        // Menambahkan nilai donasi ke total
        $total_donations += floatval($donation_amount);
    }

    // Mengembalikan total donasi dalam format mata uang
    return DonasiCurrency($total_donations);
}


function masa_aktif_donasi($post_id = null) {
    if (empty($post_id)) {
        $post_id = get_the_ID();
    }

    // Mendapatkan nilai tanggal berakhir dari post meta
    $end_date = get_post_meta($post_id, 'tanggal_berakhir', true);
    
    if (!$end_date) {
        // Jika tidak ada tanggal berakhir, anggap donasi masih aktif
        return true;
    }

    // Mendapatkan tanggal saat ini
    $current_date = current_time('Y-m-d');

    // Membandingkan tanggal berakhir dengan tanggal saat ini
    if ($current_date > $end_date) {
        return false; // Donasi sudah berakhir
    } else {
        return true; // Donasi masih aktif
    }
}



// Menghitung total donasi yang sudah diterima untuk sebuah donasi.
function get_total_donasi_masuk($donasi_id = null) {
    if (empty($donasi_id)) {
        $donasi_id = get_the_ID();
    }

    $args = array(
        'post_type' => 'donasi-masuk',
        'post_status' => 'publish',
        'numberposts' => -1, // Mengambil semua post yang cocok
        'meta_query' => array(
            array(
                'key' => 'status',
                'value' => 'Sukses',
                'compare' => '='
            ),
            array(
                'key' => 'donasi_id',
                'value' => $donasi_id,
                'compare' => '='
            )
        )
    );

    // Mendapatkan post berdasarkan query args
    $posts = get_posts($args);

    // Inisialisasi total donasi
    $total_donations = 0;

    // Loop melalui hasil post
    foreach ($posts as $post) {
        // Mengambil nilai donasi dari post meta 'totaldonasi'
        $donation_amount = get_post_meta($post->ID, 'totaldonasi', true);
        
        // Menambahkan nilai donasi ke total
        $total_donations += floatval($donation_amount);
    }

    return $total_donations;
}


// Menampilkan progres donasi
function velocity_progress_donasi($donasi_id = null) {
    if (empty($donasi_id)) {
        $donasi_id = get_the_ID();
    }

    $total_donasi_masuk = get_total_donasi_masuk($donasi_id);

    // Mendapatkan nilai target donasi dari post meta
    $tanggal_berakhir = get_post_meta($donasi_id, 'tanggal_berakhir', true);
    $target_donasi = get_post_meta($donasi_id, 'target', true);
    $target_donasi = floatval($target_donasi);

    // Menghitung persentase progres donasi
    $progress_percentage = ($target_donasi > 0) ? ($total_donasi_masuk / $target_donasi) * 100 : 100;
    $progress_percentage = min($progress_percentage, 100); // Batasi nilai maksimal menjadi 100%

    // Format uang dalam mata uang lokal
    $total_donasi_masuk_formatted = DonasiCurrency($total_donasi_masuk);
    $target_donasi_formatted = DonasiCurrency($target_donasi);


    $class_progress = '';
    if(!masa_aktif_donasi($donasi_id)){
        $class_progress = ' bg-danger';
    }

    // Tampilkan progress bar 
    echo '<div class="fw-bold fs-6 text-success">'.$total_donasi_masuk_formatted.'</div>';
    if (empty($target_donasi)) {
        $progress_percentage = $total_donasi_masuk ? $progress_percentage : '0';
        echo '<small class="d-block">Tak terbatas</small>';
    } else {
        echo '<small class="d-block">Terkumpul dari <strong>' . $target_donasi_formatted . ' (' . number_format($progress_percentage, 0) . '%)</strong></small>';
    }
    echo '<div class="mt-2 progress donasi-progress">';
    echo '<div class="progress-bar'.$class_progress.'" role="progressbar" style="width: ' . $progress_percentage . '%;" aria-valuenow="' . $progress_percentage . '" aria-valuemin="0" aria-valuemax="100"></div>';
    echo '</div>';

    if($tanggal_berakhir){
        echo '<div class="text-end mt-1">';
            echo '<small>Berakhir pada <b>'.$tanggal_berakhir.'</b></small>';
        echo '</div>';
    }
}



// Fungsi untuk mengambil donasi berdasarkan post_id donasi.
function get_donasi_masuk($donasi_id = null) {
    if(empty($donasi_id)){
        $donasi_id = get_the_ID();
    }
    $args = array(
        'post_type' => 'donasi-masuk',
        'post_status' => 'publish',
        'numberposts' => -1, // Mengambil semua post yang cocok
        'meta_query' => array(
            array(
                'key' => 'donasi_id',
                'value' => $donasi_id,
                'compare' => '='
            ),
            array(
                'key' => 'status',
                'value' => 'Sukses',
                'compare' => '='
            )
        )
    );

    $donasi_posts = get_posts($args);

    $donasi_data = array();
    foreach ($donasi_posts as $post) {
        $total_donasi = get_post_meta($post->ID, 'totaldonasi', true);
        $nama_donatur = get_post_meta($post->ID, 'nama_donatur', true);
        $anonim = get_post_meta($post->ID, 'anonim', true);

        $donasi_data[] = array(
            'total_donasi' => $total_donasi,
            'nama_donatur' => $anonim == 'on' ? 'Orang Baik' : $nama_donatur,
            'pesan' => $post->post_content,
            'tanggal' => get_the_date('d-m-Y', $post->ID),
        );
    }

    return $donasi_data;
}


// Fungsi untuk menampilkan daftar donatur.
function tampilkan_daftar_donatur($donasi_id) {

    $donasi_data = get_donasi_masuk($donasi_id);

    if (empty($donasi_data)) {
        echo '<p>Belum ada donasi yang masuk.</p>';
        return;
    }

    echo '<ul class="list-group list-group-flush">';
    foreach ($donasi_data as $donasi) {
        echo '<li class="px-0 pb-0 list-group-item">';
            echo '<div class="mb-2 overflow-hidden">';
                echo '<div class="fs-6 float-start text-dark fw-bold">' . esc_html($donasi['nama_donatur']) . '</div>';
                echo '<small class="float-end">' . esc_html($donasi['tanggal']) . '</small>';
            echo '</div>';
            echo '<p class="text-muted">Berdonasi sebesar <strong class="text-success">'.DonasiCurrency($donasi['total_donasi']).'</strong></p>';
            if($donasi['pesan']){
                echo '<div class="text-dark">' . $donasi['pesan'] . '</div>';
            }
        echo '</li>';
    }
    echo '</ul>';
}

/**
 * Update status donasi saat callback Duitku diterima.
 */
function velocity_donasi_duitku_callback_handler($payload) {
    if (empty($payload['merchantOrderId'])) {
        return;
    }

    $invoice = sanitize_text_field($payload['merchantOrderId']);
    $reference = isset($payload['reference']) ? sanitize_text_field($payload['reference']) : '';
    $result_code = isset($payload['resultCode']) ? sanitize_text_field($payload['resultCode']) : '';
    $payment_code = isset($payload['paymentCode']) ? sanitize_text_field($payload['paymentCode']) : '';
    $amount = isset($payload['amount']) ? sanitize_text_field($payload['amount']) : '';

    $donasi = get_page_by_title($invoice, OBJECT, 'donasi-masuk');
    if (!$donasi) {
        return;
    }

    $status = $result_code === '00' ? 'Sukses' : 'Pending';

    update_post_meta($donasi->ID, 'status', $status);
    update_post_meta($donasi->ID, 'duitku_reference', $reference);
    update_post_meta($donasi->ID, 'duitku_result_code', $result_code);
    update_post_meta($donasi->ID, 'duitku_payment_code', $payment_code);
    update_post_meta($donasi->ID, 'duitku_amount', $amount);
    update_post_meta($donasi->ID, 'metode_bayar', 'duitku');
}
add_action('velocity_duitku_callback', 'velocity_donasi_duitku_callback_handler', 10, 1);
