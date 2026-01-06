<?php
if ( ! defined( 'ABSPATH' ) ) exit;


// [velocity-donasi-loop]
function velocity_donasi_loop($atts) {
    global $post;
    $atribut = shortcode_atts(array(
        'post_id'     	=> $post->ID,
    ), $atts);
    $post_id = $atribut['post_id'];
    $html = '<div class="velocity-post-list">';
    if (has_post_thumbnail($post_id)) {
        $urlbesar   = get_the_post_thumbnail_url($post_id, 'full');  
        $html       .= '<a class="ratio ratio-16x9 d-block" href="'.get_the_permalink($post_id).'">';
            $html       .= '<img class="w-100 img-fluid" src="'.$urlbesar.'">';
        $html       .= '</a>';
    }
    $html .= '<div class="p-3">';
        $html .= '<a class="h6 fw-bold text-dark" href="'.get_the_permalink($post_id).'">'.get_the_title($post_id).'</a>';
        $html .= do_shortcode('[velocity-progress-donasi]');
    $html .= '</div>';
    $html .= '</div>';
    return $html;
}
add_shortcode( 'velocity-donasi-loop', 'velocity_donasi_loop' );


//[bank-donasi logo="true" atasnama="true" norek="true"]
function velocity_donasi_bank($atts)
{
    ob_start();
    $atribut = shortcode_atts(array(
        'logo'      => 'true',
        'atasnama'  => 'true',
        'norek'     => 'true',
    ), $atts);
    $logo           = $atribut['logo'];
    $atasnama       = $atribut['atasnama'];
    $norek          = $atribut['norek'];

    $databank       = velocitydonasi_option('bank_velocitydonasi', []);
    $databanklain   = velocitydonasi_option('banklain_velocitydonasi');

    if ($databanklain) {
        foreach ($databanklain as $key => $value) {
            if ($value['namabank']) {
                array_push($databank, $databanklain[$key]);
            }
        }
    }

    echo '<div class="frame-bank" style="text-align:center">';
    if ($databank) {
        foreach ($databank as $key => $bank) {
            if ($bank['namabank']) {
                echo '<span class="d-inline-block" data-bank="' . $bank['namabank'] . '">';
                if ($logo == 'true') {
                    if (isset($bank['logo'])) {
                        $urllogo = $bank['logo'];
                    } else {
                        $urllogo = VELOCITY_DONASI_DIR_URI . 'img/b-' . $bank['namabank'] . '.gif';
                    }
                    echo '<img style="display:block;margin:0 auto;" width="100" alt="Bank ' . strtoupper($bank['namabank']) . '" src="' . $urllogo . '">';
                }
                if ($norek == 'true') {
                    echo '<span>' . $bank['nobank'] . '<br>';
                }
                if ($atasnama == 'true') {
                    echo '<small>a/n ' . $bank['atasnama'] . '</small></span>';
                }
                echo '</span>';
            }
        }
    }
    echo '</div>';

    return ob_get_clean();
}
add_shortcode('bank-donasi', 'velocity_donasi_bank');



// [velocity-donasi-share]
function velocity_donasi_share($atts) {
    $atts = shortcode_atts(array(
        'post_id' => null,
    ), $atts);
    
    if ($atts['post_id']) {
        $post_id = intval($atts['post_id']);
    } elseif (is_singular()) {
        global $post;
        $post_id = $post->ID;
    } else {
        $post_id = null; // Jika bukan halaman/postingan tunggal dan 'post_id' tidak disediakan, tidak menampilkan tombol berbagi.
    }
    
    if ($post_id) {
        // Get current URL 
        $sb_url = urlencode(get_permalink($post_id));

        // Get current web title
        $sb_title = esc_html(get_the_title($post_id));

        // Construct sharing URLs
        $twitterURL = 'https://twitter.com/intent/tweet?text=' . $sb_title . '&url=' . $sb_url;
        $facebookURL = 'https://www.facebook.com/sharer/sharer.php?u=' . $sb_url;
        $linkedInURL = 'https://www.linkedin.com/shareArticle?mini=true&url=' . $sb_url . '&title=' . $sb_title;
        $pinterestURL = 'https://pinterest.com/pin/create/button/?url=' . $sb_url . '&description=' . $sb_title;
        $whatsappURL = 'https://api.whatsapp.com/send?text=' . $sb_title . ' ' . $sb_url;
        $telegramURL = 'https://telegram.me/share/url?url=' . $sb_url;
        $emailURL = 'mailto:?subject=I wanted you to see this site&body=' . $sb_title . ' ' . $sb_url;

        // Add sharing buttons at the end of page/content
        $content = '<div class="vdd-share-box">';
        $content .= '<a class="btn btn-sm btn-secondary me-2 mb-1 vdd-x-twitter vdd-share-button" href="' . $twitterURL . '" target="_blank" rel="nofollow" title="X"><svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-twitter-x" viewBox="0 0 16 16"> <path d="M12.6.75h2.454l-5.36 6.142L16 15.25h-4.937l-3.867-5.07-4.425 5.07H.316l5.733-6.57L0 .75h5.063l3.495 4.633L12.601.75Zm-.86 13.028h1.36L4.323 2.145H2.865z"/> </svg></a>';
        $content .= '<a class="btn btn-sm btn-secondary me-2 mb-1 vdd-facebook vdd-share-button" href="' . $facebookURL . '" target="_blank" rel="nofollow" title="Facebook"><svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-facebook" viewBox="0 0 16 16"> <path d="M16 8.049c0-4.446-3.582-8.05-8-8.05C3.58 0-.002 3.603-.002 8.05c0 4.017 2.926 7.347 6.75 7.951v-5.625h-2.03V8.05H6.75V6.275c0-2.017 1.195-3.131 3.022-3.131.876 0 1.791.157 1.791.157v1.98h-1.009c-.993 0-1.303.621-1.303 1.258v1.51h2.218l-.354 2.326H9.25V16c3.824-.604 6.75-3.934 6.75-7.951"/> </svg></a>';
        $content .= '<a class="btn btn-sm btn-secondary me-2 mb-1 vdd-whatsapp vdd-share-button" href="' . $whatsappURL . '" target="_blank" rel="nofollow" title="Whatsapp"><svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-whatsapp" viewBox="0 0 16 16"> <path d="M13.601 2.326A7.85 7.85 0 0 0 7.994 0C3.627 0 .068 3.558.064 7.926c0 1.399.366 2.76 1.057 3.965L0 16l4.204-1.102a7.9 7.9 0 0 0 3.79.965h.004c4.368 0 7.926-3.558 7.93-7.93A7.9 7.9 0 0 0 13.6 2.326zM7.994 14.521a6.6 6.6 0 0 1-3.356-.92l-.24-.144-2.494.654.666-2.433-.156-.251a6.56 6.56 0 0 1-1.007-3.505c0-3.626 2.957-6.584 6.591-6.584a6.56 6.56 0 0 1 4.66 1.931 6.56 6.56 0 0 1 1.928 4.66c-.004 3.639-2.961 6.592-6.592 6.592m3.615-4.934c-.197-.099-1.17-.578-1.353-.646-.182-.065-.315-.099-.445.099-.133.197-.513.646-.627.775-.114.133-.232.148-.43.05-.197-.1-.836-.308-1.592-.985-.59-.525-.985-1.175-1.103-1.372-.114-.198-.011-.304.088-.403.087-.088.197-.232.296-.346.1-.114.133-.198.198-.33.065-.134.034-.248-.015-.347-.05-.099-.445-1.076-.612-1.47-.16-.389-.323-.335-.445-.34-.114-.007-.247-.007-.38-.007a.73.73 0 0 0-.529.247c-.182.198-.691.677-.691 1.654s.71 1.916.81 2.049c.098.133 1.394 2.132 3.383 2.992.47.205.84.326 1.129.418.475.152.904.129 1.246.08.38-.058 1.171-.48 1.338-.943.164-.464.164-.86.114-.943-.049-.084-.182-.133-.38-.232"/> </svg></a>';
        $content .= '<a class="btn btn-sm btn-secondary me-2 mb-1 vdd-pinterest vdd-share-button" href="' . $pinterestURL . '" data-pin-custom="true" target="_blank" rel="nofollow" title="Pinterest"><svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-pinterest" viewBox="0 0 16 16"> <path d="M8 0a8 8 0 0 0-2.915 15.452c-.07-.633-.134-1.606.027-2.297.146-.625.938-3.977.938-3.977s-.239-.479-.239-1.187c0-1.113.645-1.943 1.448-1.943.682 0 1.012.512 1.012 1.127 0 .686-.437 1.712-.663 2.663-.188.796.4 1.446 1.185 1.446 1.422 0 2.515-1.5 2.515-3.664 0-1.915-1.377-3.254-3.342-3.254-2.276 0-3.612 1.707-3.612 3.471 0 .688.265 1.425.595 1.826a.24.24 0 0 1 .056.23c-.061.252-.196.796-.222.907-.035.146-.116.177-.268.107-1-.465-1.624-1.926-1.624-3.1 0-2.523 1.834-4.84 5.286-4.84 2.775 0 4.932 1.977 4.932 4.62 0 2.757-1.739 4.976-4.151 4.976-.811 0-1.573-.421-1.834-.919l-.498 1.902c-.181.695-.669 1.566-.995 2.097A8 8 0 1 0 8 0"/> </svg></a>';
        $content .= '<a class="btn btn-sm btn-secondary me-2 mb-1 vdd-linkedin vdd-share-button" href="' . $linkedInURL . '" target="_blank" rel="nofollow" title="LinkedIn"><svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-linkedin" viewBox="0 0 16 16"> <path d="M0 1.146C0 .513.526 0 1.175 0h13.65C15.474 0 16 .513 16 1.146v13.708c0 .633-.526 1.146-1.175 1.146H1.175C.526 16 0 15.487 0 14.854zm4.943 12.248V6.169H2.542v7.225zm-1.2-8.212c.837 0 1.358-.554 1.358-1.248-.015-.709-.52-1.248-1.342-1.248S2.4 3.226 2.4 3.934c0 .694.521 1.248 1.327 1.248zm4.908 8.212V9.359c0-.216.016-.432.08-.586.173-.431.568-.878 1.232-.878.869 0 1.216.662 1.216 1.634v3.865h2.401V9.25c0-2.22-1.184-3.252-2.764-3.252-1.274 0-1.845.7-2.165 1.193v.025h-.016l.016-.025V6.169h-2.4c.03.678 0 7.225 0 7.225z"/> </svg></a>';
        $content .= '<a class="btn btn-sm btn-info me-2 mb-1 vdd-telegram vdd-share-button" href="' . $telegramURL . '" target="_blank" rel="nofollow" title="Telegram"><svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-telegram" viewBox="0 0 16 16"> <path d="M16 8A8 8 0 1 1 0 8a8 8 0 0 1 16 0M8.287 5.906q-1.168.486-4.666 2.01-.567.225-.595.442c-.03.243.275.339.69.47l.175.055c.408.133.958.288 1.243.294q.39.01.868-.32 3.269-2.206 3.374-2.23c.05-.012.12-.026.166.016s.042.12.037.141c-.03.129-1.227 1.241-1.846 1.817-.193.18-.33.307-.358.336a8 8 0 0 1-.188.186c-.38.366-.664.64.015 1.088.327.216.589.393.85.571.284.194.568.387.936.629q.14.092.27.187c.331.236.63.448.997.414.214-.02.435-.22.547-.82.265-1.417.786-4.486.906-5.751a1.4 1.4 0 0 0-.013-.315.34.34 0 0 0-.114-.217.53.53 0 0 0-.31-.093c-.3.005-.763.166-2.984 1.09"/> </svg></a>';
        $content .= '<a class="btn btn-sm btn-secondary me-2 mb-1 vdd-email vdd-share-button" href="' . $emailURL . '" target="_blank" rel="nofollow" title="Email"><svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-envelope" viewBox="0 0 16 16"> <path d="M0 4a2 2 0 0 1 2-2h12a2 2 0 0 1 2 2v8a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2zm2-1a1 1 0 0 0-1 1v.217l7 4.2 7-4.2V4a1 1 0 0 0-1-1zm13 2.383-4.708 2.825L15 11.105zm-.034 6.876-5.64-3.471L8 9.583l-1.326-.795-5.64 3.47A1 1 0 0 0 2 13h12a1 1 0 0 0 .966-.741M1 11.105l4.708-2.897L1 5.383z"/> </svg></a>';
        $content .= '</div>';
    } else {
        // Jika tidak ada $post_id valid, kembalikan konten kosong
        $content = '';
    }

    return $content;
}
add_shortcode('velocity-donasi-share', 'velocity_donasi_share');




// [velocity-donasi-button post_id=""]
function velocity_donasi_button($atts) {
	ob_start();
    global $post;
    $atribut = shortcode_atts(array(
        'text' => 'Donasi',
        'post_id' => $post->ID,
    ), $atts);
    $text = $atribut['text'];
    $post_id = $atribut['post_id'];
    $random_number = rand(100, 999);
    $kodeunik = rand(1,199);
    $defaultnominal = 10000;
    $id = $post_id . $random_number;
    
    if(masa_aktif_donasi($post_id)){
    echo '<div class="btn btn-primary w-100" data-bs-toggle="modal" data-bs-target="#donasi-' . $id . '">' . $text . '</div>';
    echo '<div class="modal fade" id="donasi-' . $id . '" tabindex="-1" aria-labelledby="donasi-' . $id . '-label" aria-hidden="true">';
        echo '<div class="modal-dialog">';
            echo '<div class="modal-content">';
                echo '<div class="modal-header">';
                    echo '<h5 class="modal-title" id="donasi-' . $id . '-label">'.get_the_title($post_id).'</h5>';
                    echo '<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>';
                echo '</div>';
                echo '<form class="form-donasi" method="post">';
                    echo '<div class="modal-body">';
                        echo '<div class="mb-3">';
                            echo '<label class="mb-1 fw-bold">Isi Nominal Donasi</label>';
                            echo '<input type="number" class="form-control nominal-donasi" value="'.$defaultnominal.'" min="10000" required>';
                            echo '<small>minimal 10000</small>';
                        echo '</div>';
                        $duitku_active = class_exists('Velocity_Addons_Duitku') && method_exists('Velocity_Addons_Duitku', 'is_active') && Velocity_Addons_Duitku::is_active();
                        echo '<div class="mb-3">';
                            echo '<label class="mb-1 fw-bold">Pilih Pembayaran</label>';
                            echo '<div class="form-check">';
                                echo '<input class="form-check-input pilih-bayar" type="radio" name="bayar" id="paymentBank" value="bank" required>';
                                echo '<label class="form-check-label" for="paymentBank">Bank Transfer</label>';
                            echo '</div>';
                            if ($duitku_active) {
                                echo '<div class="form-check">';
                                    echo '<input class="form-check-input pilih-bayar" type="radio" name="bayar" id="paymentDuitku" value="duitku" required>';
                                    echo '<label class="form-check-label" for="paymentDuitku">Payment Gateway (Duitku)</label>';
                                echo '</div>';
                            }
                            echo '<div id="bankOptions" style="display: none;">';
                                $databank = getBankDonasi();
                                if($databank){
                                    echo '<div class="mb-3 ms-3 border p-2">';
                                        echo '<label class="form-label">Pilih Bank</label>';
                                        echo '<div class="form-bank-pilihan">';
                                            $i = 0;
                                            foreach($databank as $namabank => $bankpilihan){
                                                $no = ++$i;
                                                $checked = $no == '1' ? 'checked' : '';
                                                echo '<div class="form-check py-1 border-top">';
                                                    echo '<input class="form-check-input mt-3" type="radio" name="bankpilihan" id="'.$no.$id.'" value="'.$namabank.'" '.$checked.'>';
                                                    echo '<label class="form-check-label" for="'.$no.$id.'">';
                                                        echo '<img class="w-auto" src="'.$bankpilihan['logo'].'" alt="'.$namabank.'" style="max-height: 48px;">';
                                                    echo '</label>';
                                                echo '</div>';
                                            }
                                        echo '</div>';
                                    echo '</div>';
                                }
                            echo '</div>';
                        echo '</div><hr>';
                        echo '<div class="mb-2 fw-bold">Data Diri</div>';
                        if ( is_user_logged_in() ) {
                            $current_user = wp_get_current_user();
                            $phone = get_user_meta($current_user->ID, 'hp', true);
                            $namasaya = $current_user->first_name ? $current_user->first_name : $current_user->user_login;
                            $emailsaya = esc_html($current_user->user_email);
                            echo '<div class="mb-3 text-muted">';
                                echo '<div class="mb-1">Nama: <strong>'.esc_html($namasaya).'</strong></div>';
                                echo '<div class="mb-1">Email: <strong>'.$emailsaya.'</strong></div>';
                                echo '<div class="mb-1">No. Telepon/Whatsapp: <strong>'.esc_html($phone).'</strong></div>';
                                echo '<a href="'.get_edit_profile_url().'">Ubah Data</a>';
                                echo '<input type="hidden" name="first_name" value="'.esc_html($namasaya).'">';
                                echo '<input type="hidden" name="user_email" value="'.$emailsaya.'">';
                                echo '<input type="hidden" name="hp" value="'.$phone.'">';
                            echo '</div>';
                        } else {
                            echo '<p class="text-muted"><a href="'.esc_url(wp_login_url(get_permalink())).'">Masuk</a> atau lengkapi data di bawah ini.</p>';
                            echo '<div class="mb-3">';
                                echo '<label class="mb-1">Nama Lengkap</label>';
                                echo '<input type="text" class="form-control" name="first_name" required>';
                            echo '</div>';
                            echo '<div class="mb-3">';
                                echo '<label class="mb-1">Email Aktif</label>';
                                echo '<input type="email" class="form-control" name="user_email" required>';
                            echo '</div>';
                            echo '<div class="mb-3">';
                                echo '<label class="mb-1">No. Telepon/Whatsapp</label>';
                                echo '<input type="text" class="form-control" name="hp" required>';
                            echo '</div>';
                        }
                        echo '<div class="form-check form-switch mb-3">';
                            echo '<input class="form-check-input" type="checkbox" name="anonim" id="cekanonim">';
                            echo '<label class="form-check-label" for="cekanonim">';
                                echo 'Sembunyikan nama saya (donasi sebagai anonim)';
                            echo '</label>';
                        echo '</div><hr>';
                        echo '<div class="mb-2 fw-bold">Pesan</div>';
                        echo '<div class="mb-3">';
                            echo '<label class="mb-1">Sertakan doa dan dukungan (opsional)</label>';
                            echo '<textarea class="form-control" name="pesan" rows="3"></textarea>';
                        echo '</div>';
                        echo velocity_donasi_recaptcha();
                    echo '</div>';
                    echo '<div class="modal-footer m-0 hasil-donasi">';
                        echo '<div class="col-5 col-md-6 mx-0">';
                            $total = $defaultnominal + $kodeunik;
                            echo '<small class="d-block">Kode Unik: '.$kodeunik.' (akan didonasikan)</small>';
                            echo '<div class="fw-bold text-primary">Total: <span class="total-donasi">'.DonasiCurrency($total).'</span></div>';
                            echo '<input id="kodeunik" type="hidden" name="kodeunik" value="'.$kodeunik.'">';
                            echo '<input id="total-donasi" type="hidden" name="totaldonasi" value="'.$total.'">';
                        echo '</div>';
                        echo '<div class="col-7 col-md-6 mx-0">';
                            echo '<button type="submit" class="btn btn-primary w-100">Donasi Sekarang<span class="loadd"></span></button>';
                        echo '</div>';
                    echo '</div>';
                    echo '<input type="hidden" name="user_id" value="'.get_current_user_id().'"/>';
                    echo '<input type="hidden" name="post_id" value="'.$post_id.'"/>';
                    echo '<input type="hidden" name="invoice" value="'.mt_rand(100000000, 999999999).'"/>';
                echo '</form>';
            echo '</div>';
        echo '</div>';
    echo '</div>';
    }
    
    // $halaman_donatur   = velocitydonasi_option('halaman_donatur');
    // echo $halaman_donatur;
    return ob_get_clean();
}
add_shortcode('velocity-donasi-button', 'velocity_donasi_button');


// Untuk menampilkan progres donasi.
function progress_donasi_shortcode($atts) {
    $atts = shortcode_atts(array(
        'post_id' => '',
    ), $atts, 'progress_donasi');

    $donasi_id = intval($atts['post_id']);

    ob_start();
    velocity_progress_donasi($donasi_id);
    return ob_get_clean();
}
add_shortcode('velocity-progress-donasi', 'progress_donasi_shortcode');


// [velocity-daftar-donatur]
function daftar_donatur_shortcode($atts) {
    $atts = shortcode_atts(array(
        'post_id' => get_the_ID(),
    ), $atts, 'velocity-daftar-donatur');

    $donasi_id = intval($atts['post_id']);

    ob_start();
    tampilkan_daftar_donatur($donasi_id);
    return ob_get_clean();
}
add_shortcode('velocity-daftar-donatur', 'daftar_donatur_shortcode');


// [velocity-update-donasi]
function velocity_update_donasi($atts) {
    $atts = shortcode_atts(array(
        'post_id' => get_the_ID(),
    ), $atts);

    $donasi_id = intval($atts['post_id']);
    $update_info  = get_post_meta($donasi_id, 'update_info', true);
    
    $html = '';
    if(!empty($update_info)){
        foreach($update_info as $data){
          $html .= '<div class="list">';
            $html .= '<div class="text-success font-weight-bold h6">'.$data['judulupdate'].'</div>';
            $html .= '<div class="text-muted mb-2 float-right"><small>'.$data['tanggal'].'</small></div>';
            $html .= '<div class="text-dark">'.$data['deskripsiupdate'].'</div>';
            if(!empty($data['imageupdate'])){
                $html .= '<div class="mt-2"><img class="w-100" src="'.$data['imageupdate'].'" /></div>';
            }
            $html .= '</div>';
           $html .= '<hr>';
        }
    } else {
        $html .= '<div class="alert alert-warning mb-0" role="alert">';
            $html .= 'Belum ada kabar terbaru.';
        $html .= '</div>';
    }
    return $html;
}
add_shortcode('velocity-update-donasi', 'velocity_update_donasi');


// [halaman-donatur]
function halaman_donatur() {
    ob_start();
    require_once VELOCITY_DONASI_DIR.'inc/templates/halaman-donatur.php';
    return ob_get_clean();
}
add_shortcode ('halaman-donatur', 'halaman_donatur');
