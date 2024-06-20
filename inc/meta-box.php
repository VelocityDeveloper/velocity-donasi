<?php
/**
 * Register meta boxes for post paket tour.
 * meta box using cmb2
 * @package Velocity Donasi
 */
if ( ! defined( 'ABSPATH' ) ) exit;


add_action( 'cmb2_admin_init', 'velocity_donasi_metaboxes' );
function velocity_donasi_metaboxes() {
    $text_domain = 'velocity-donasi';
	$cmb = new_cmb2_box( array(
		'id'            => 'velocity_metabox',
		'title'         => __( 'Detail', '' ),
		'object_types'  => array('donasi'), // Post type
		'context'       => 'normal',
		'priority'      => 'high',
		'show_names'    => true, // Show field names on the left
		// 'cmb_styles' => false, // false to disable the CMB stylesheet
		// 'closed'     => true, // Keep the metabox closed by default
	) );

	$cmb->add_field( array(
		'name'       => __( 'Target', $text_domain ),
		'desc'       => __( 'Jika dikosongi maka target donasi tak terbatas', $text_domain ),
		'id'         => 'target',
		'type' => 'text',
		'before_field' => 'Rp',
		'attributes' => array(
			'type' => 'number',
		),
	) );

	$cmb->add_field( array(
        'name' => 'Tanggal Berakhir',
        'id'   => 'tanggal_berakhir',
        'type' => 'text_date',
        'date_format' => 'Y-m-d',
        'description' => 'Isikan tanggal berakhirnya donasi, contoh: 2022-01-23',
	) );

	$group_field_id = $cmb->add_field( array(
		'id'          => 'update_info',
		'type'        => 'group',
		'description' => __( 'Isikan update tentang donasi ini', $text_domain ),
		// 'repeatable'  => false, // use false if you want non-repeatable group
		'options'     => array(
			'group_title'       => __( 'Update {#}', $text_domain ), // since version 1.1.4, {#} gets replaced by row number
			'add_button'        => __( 'Tambah', $text_domain ),
			'remove_button'     => __( 'Hapus', $text_domain ),
			'sortable'          => true,
			'closed'         => true, // true to have the groups closed by default
			// 'remove_confirm' => esc_html__( 'Are you sure you want to remove?', $text_domain ), // Performs confirmation before removing group.
		),
	) );

	// Id's for group's fields only need to be unique for the group. Prefix is not needed.
	$cmb->add_group_field( $group_field_id, array(
		'name' => 'Tanggal',
		'id'   => 'tanggal',
		'type' => 'text_date',
		'date_format' => 'Y-m-d',
		'description' => 'Isikan tanggal update, contoh: 2022-01-23',
	) );

	$cmb->add_group_field( $group_field_id, array(
		'name' => 'Judul Update',
		'id'   => 'judulupdate',
		'type' => 'text',
		// 'repeatable' => true, // Repeatable fields are supported w/in repeatable groups (for most types)
	) );

	$cmb->add_group_field( $group_field_id, array(
		'name' => 'Informasi',
		'description' => 'Isikan perincian update',
		'id'   => 'deskripsiupdate',
		'type' => 'textarea_small',
	) );

	$cmb->add_group_field( $group_field_id, array(
		'name' => 'Gambar',
		'id'   => 'imageupdate',
		'type' => 'file',
		'query_args' => array(
			'type' => array(
				'image/gif',
				'image/jpeg',
				'image/png',
			),
		),
	) );



	$cmb_donasi_masuk = new_cmb2_box( array(
		'id'            => 'donasi_masuk_metabox',
		'title'         => __( 'Detail', '' ),
		'object_types'  => array('donasi-masuk'), // Post type
		'context'       => 'normal',
		'priority'      => 'high',
		'show_names'    => true, // Show field names on the left
		// 'cmb_styles' => false, // false to disable the CMB stylesheet
		// 'closed'     => true, // Keep the metabox closed by default
	) );

	$cmb_donasi_masuk->add_field( array(
		'name'       => __( 'Status Donasi', $text_domain ),
		'id'         => 'status',
		'type'		=> 'select',
		'options'   => array(
			'Pending' => __( 'Pending', $text_domain ),
			'Butuh Konfirmasi' => __( 'Butuh Konfirmasi', $text_domain ),
			'Gagal' => __( 'Gagal', $text_domain ),
			'Sukses' => __( 'Sukses', $text_domain ),
			'Refund' => __( 'Refund', $text_domain ),
		),
	) );
	$cmb_donasi_masuk->add_field( array(
		'name'       => __( 'Donasi', $text_domain ),
		'id'         => 'donasi_id',
		'type'		=> 'select',
		'options_cb' => 'velocity_donasi_posts_options',
		'attributes' => array(
			'required' => 'required',
		),
	) );
	$cmb_donasi_masuk->add_field( array(
		'name'       => __( 'Metode Pembayaran', $text_domain ),
		'id'         => 'metode_bayar',
		'type' => 'text',
		'attributes' => array(
			'required' => 'required',
		),
	) );
	$cmb_donasi_masuk->add_field( array(
		'name'       => __( 'Bank Pembayaran', $text_domain ),
		'id'         => 'bank',
		'type' => 'text',
	) );
	$cmb_donasi_masuk->add_field( array(
		'name'       => __( 'Kode Unik', $text_domain ),
		'id'         => 'kodeunik',
		'type' => 'text',
		'attributes' => array(
			'type' => 'number',
			'required' => 'required',
		),
	) );
	$cmb_donasi_masuk->add_field( array(
		'name'       => __( 'Total Donasi', $text_domain ),
		'id'         => 'totaldonasi',
		'type' => 'text',
		'before_field' => 'Rp',
		'attributes' => array(
			'type' => 'number',
			'required' => 'required',
		),
	) );
	
	$cmb_donasi_masuk->add_field( array(
		'name'       => __( 'Data Donatur', $text_domain ),
		'id'         => 'headingdonatur',
		'type'		 => 'title',
	) );
	$cmb_donasi_masuk->add_field( array(
		'name'       => __( 'Nama Donatur', $text_domain ),
		'id'         => 'nama_donatur',
		'type'		=> 'text',
	) );
	$cmb_donasi_masuk->add_field( array(
		'name'       => __( 'Email Donatur', $text_domain ),
		'id'         => 'email_donatur',
		'type'		=> 'text',
	) );
	$cmb_donasi_masuk->add_field( array(
		'name'       => __( 'Telepon', $text_domain ),
		'id'         => 'hp_donatur',
		'type'		=> 'text',
	) );
	$cmb_donasi_masuk->add_field( array(
		'name'       => __( 'Pengguna', $text_domain ),
		'id'         => 'user_id',
		'type'		=> 'select',
		'options_cb' => 'velocity_donasi_users_options',
	) );
	$cmb_donasi_masuk->add_field( array(
		'name'       => __( 'Sembunyikan nama donatur?', $text_domain ),
		'id'         => 'anonim',
		'type'		=> 'select',
		'options'          => array(
			'on' => __( 'Ya', $text_domain ),
			'off'   => __( 'Tidak', $text_domain ),
		),
	) );

}


// Callback untuk mendapatkan daftar pengguna
function velocity_donasi_users_options() {
    $users = get_users();
	$user_options = array(
		'' => 'Pilih Pengguna'
	);
    foreach ($users as $user) {
        $user_options[$user->ID] = $user->display_name;
    }
    return $user_options;
}

// Callback untuk mendapatkan daftar posting jenis 'donasi'
function velocity_donasi_posts_options($post_type) {
    $args = array(
        'post_type' => 'donasi',
        'posts_per_page' => -1,
    );
    $donation_posts = get_posts($args);
	$donation_post_options = array(
		'' => 'Pilih Donasi'
	);
    foreach ($donation_posts as $donation_post) {
        $donation_post_options[$donation_post->ID] = $donation_post->post_title;
    }
    return $donation_post_options;
}