<?php
if ( ! defined( 'ABSPATH' ) ) exit;
$act    = isset($_GET['act']) ? $_GET['act'] : '';
$id     = isset($_GET['id']) ? $_GET['id'] : '';
$donasi_id = get_post_meta($id, 'donasi_id', true);
$check = velocity_donasi_validate_recaptcha();

if(isset($_POST['metode']) && $check != 1){
    echo '<div class="alert alert-danger">Isikan captcha.</div>';
} elseif(isset($_POST['metode']) && $check == 1){

    $admin_email = velocitydonasi_option('email_admin_donasi',get_bloginfo('admin_email')); 

    $headers = 'MIME-Version: 1.0' . "\r\n";
    $headers .= 'Content-type: text/html; charset=iso-8859-1' . "\r\n";
    $headers .= 'From: '.get_bloginfo('name').' <'.$admin_email.'>';
    $subject = 'Konfirmasi pembayaran untuk invoice #'.get_the_title($id);
    $body    = 'Metode Pembayaran: '.esc_html($_POST['metode']).'<br>';
    if(isset($_POST['detail'])){
        $body    .= 'Detail: '.esc_html($_POST['detail']).'<br>';
    }

    require_once(ABSPATH . 'wp-admin/includes/image.php');
    require_once(ABSPATH . 'wp-admin/includes/file.php');
    require_once(ABSPATH . 'wp-admin/includes/media.php');

    $allowed_file_size = 2097152; // Allowed file size -> 2MB
    $upload_errors = '';
    if($_FILES["file"]['name']){
        
        $uploadedfile = $_FILES['file'];
        $upload_overrides = array( 'test_form' => false );
        $filetype = wp_check_filetype($uploadedfile['name']);
        $allowed_file_types = array('image/jpg', 'image/jpeg', 'image/png', 'image/gif');
    
        if (!in_array($filetype['type'], $allowed_file_types)) {
            $upload_errors .= 'Gagal upload: Hanya file gambar yang diperbolehkan. ';
        }
        if ( $_FILES['file']['size'] > $allowed_file_size ) {
            $upload_errors .= 'Gagal upload: File yang diupload terlalu besar. Maksimal ukuran file adalah 2MB. ';
        }
        if ( empty( $upload_errors ) ) {
            $movefile = wp_handle_upload( $uploadedfile, $upload_overrides );
            if ( $movefile && ! isset( $movefile['error'] ) ) {
                //var_dump( $movefile );

                $attachment_id = wp_insert_attachment(array(
                    'post_mime_type' => $movefile['type'],
                    'post_title' => sanitize_file_name($movefile['file']),
                    'post_content' => '',
                    'post_status' => 'inherit'
                ), $movefile['file']);
        
                $attachment_data = wp_generate_attachment_metadata($attachment_id, $movefile['file']);
                wp_update_attachment_metadata($attachment_id, $attachment_data);
        
                // Menetapkan gambar unggahan sebagai featured image
                set_post_thumbnail($id, $attachment_id);

                $url[] = $movefile['file'];
                wp_mail( $admin_email, $subject, $body, $headers,$url[0] );
                //wp_delete_file( $url[0] );

                update_post_meta($id, 'status', 'Butuh Konfirmasi');

            } else {
                echo '<div class="alert alert-danger">';
                    echo $movefile['error'];
                echo '</div>';
            }
        }
    }
    if ( empty( $upload_errors ) ) {
        echo '<div class="alert alert-success">Terimakasih telah melakukan konfirmasi pembayaran</div>';
    } else {
        echo '<div class="alert alert-danger">'.$upload_errors.'</div>';
    }
} else { ?>
    <h3 class="fw-bold fs-5 mb-3">Konfirmasi Pembayaran #<?php echo get_the_title($id);?> (<?php echo get_the_title($donasi_id);?>)</h3>
    <form class="mt-2" method="POST" enctype="multipart/form-data">
        <div class="form-group mb-3">
            <label for="exampleInputEmail1">Metode Pembayaran</label>
            <input type="text" class="form-control form-control-sm" name="metode" placeholder="Con. Transfer ke Bank BNI" required>
        </div>
        <div class="form-group mb-3">
            <label for="detail">Detail</label>
            <textarea class="form-control form-control-sm" name="detail" placeholder="Con. Sudah sy transfer sesuai tagihan tadi malam mas."></textarea>
            </div>
        <div class="form-group mb-3">
            <label for="detail">Bukti Transfer</label>
            <input type="file" class="form-control form-control-sm" name="file" required>
            <small class="form-text text-muted">Ukuran gambar maksimal 2MB</small>
        </div>
        <div class="form-group mb-3">
            <?php echo velocity_donasi_recaptcha(); ?>
        </div>
        <button type="submit" class="btn btn-dark">Konfirmasi</button>
    </form>
<?php } ?>