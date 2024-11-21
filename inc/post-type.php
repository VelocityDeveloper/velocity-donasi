<?php
if ( ! defined( 'ABSPATH' ) ) exit;


// Register new post_type and taxonomy
add_action('init', 'velocity_donasi_admin_init');
function velocity_donasi_admin_init() {
    // Register 'donasi' post type
    register_post_type('donasi', array(
        'labels' => array(
            'name' => 'Donasi',
            'singular_name' => 'donasi',
            'add_new' => 'Tambah Donasi',
            'add_new_item' => 'Tambah Donasi',
            'edit_item' => 'Edit Donasi',
            'view_item' => 'Lihat Donasi',
            'search_items' => 'Cari Donasi',
            'not_found' => 'Tidak ditemukan',
            'not_found_in_trash' => 'Tidak ada donasi di kotak sampah'
        ),
        'menu_icon' => 'dashicons-screenoptions',
        'public' => true,
        'has_archive' => true,
        'taxonomies' => array('kategori-donasi'),
        'supports' => array(
            'title',
            'editor',
            'thumbnail',
        ),
    ));
    
    // Register 'donasi-masuk' post type
    register_post_type('donasi-masuk', array(
        'labels' => array(
            'name' => 'Donasi Masuk',
            'singular_name' => 'donasi-masuk',
            'add_new' => 'Tambah Donasi Masuk',
            'add_new_item' => 'Tambah Donasi Masuk',
            'edit_item' => 'Edit Donasi Masuk',
            'view_item' => 'Lihat Donasi Masuk',
            'search_items' => 'Cari Donasi Masuk',
            'not_found' => 'Tidak ditemukan',
            'not_found_in_trash' => 'Tidak ada donasi masuk di kotak sampah'
        ),
        'public' => true, // dapat diakses oleh publik
        'has_archive' => false, // Tidak ada halaman arsip
        'publicly_queryable' => false, // Tidak ada halaman tunggal
        'exclude_from_search' => true, // Dikecualikan dari hasil pencarian
        'show_in_menu' => false,
        'supports' => array(
            'title',
            'editor',
            'thumbnail',
        ),
    ));
    
    // Register 'kategori-donasi' taxonomy
    register_taxonomy(
        'kategori-donasi',
        'donasi',
        array(
            'label' => __( 'Kategori Donasi' ),
            'hierarchical' => true,
            'show_admin_column' => true,
        )
    );

    // Mengatur ulang permalink WordPress
    if (!get_option('vdonasi_activated')) {
        global $wp_rewrite;
        $structure = get_option('permalink_structure');
        $wp_rewrite->set_permalink_structure($structure);
        $wp_rewrite->flush_rules();
        update_option('vdonasi_activated', true);
    }
}


// Use admin_menu hook to add donasi-masuk as submenu
add_action('admin_menu', 'velocity_donasi_add_submenu');
function velocity_donasi_add_submenu() {
    add_submenu_page(
        'edit.php?post_type=donasi', // Parent slug
        'Donasi Masuk', // Page title
        'Donasi Masuk', // Menu title
        'manage_options', // Capability
        'edit.php?post_type=donasi-masuk' // Menu slug
    );
}


//register product template
add_filter( 'template_include', 'velocity_donasi_register_template' );
function velocity_donasi_register_template( $template ) {    
    if ( is_singular('donasi') ) {
        $template = VELOCITY_DONASI_DIR . 'inc/templates/single-donasi.php';
    }
    return $template;
}

//Remove title archive donasi
add_filter( 'get_the_archive_title', function ($title) {
    if (is_post_type_archive('donasi') ) {
        $title = post_type_archive_title( '', false );
    } elseif ( is_tax() ) {
        $title = sprintf( __( '%1$s' ), single_term_title( '', false ) );
    }
    return $title;
});



// Tambahkan kolom khusus untuk menampilkan custom post meta pada halaman admin 'donasi-masuk'
function donasi_masuk_custom_columns($columns) {
    $screen = get_current_screen();
    if ($screen->post_type == 'donasi-masuk') {
        $columns = array(
            'cb' => '<input type="checkbox" />',
            'title' => 'Invoice',
            'donasi' => 'Donasi',
            'totaldonasi' => 'Jumlah Donasi',
            'pembayaran' => 'Pembayaran',
            'status_donasi' => 'Status Donasi',
            'date' => 'Tanggal Donasi',
        );
        return $columns;
    } else {
        return $columns;
    }
}
add_filter('manage_donasi-masuk_posts_columns', 'donasi_masuk_custom_columns');

// Isi nilai kolom khusus dengan custom post meta
function donasi_masuk_custom_columns_data($column, $post_id) {
    switch ($column) {
        case 'donasi':
			$donasi_id = get_post_meta($post_id, 'donasi_id', true);
			echo '<a href="'.get_edit_post_link($donasi_id).'">'.get_the_title($donasi_id).'</a>';
            break;
        case 'totaldonasi':
            $total_donasi = get_post_meta($post_id, 'totaldonasi', true);
            echo DonasiCurrency($total_donasi);
            break;
        case 'status_donasi':
            $status_donasi = get_post_meta($post_id, 'status', true);
            echo $status_donasi;
            break;
        case 'pembayaran':
            $metode_bayar = get_post_meta($post_id, 'metode_bayar', true);
            if($metode_bayar == 'bank'){
                $bank = get_post_meta($post_id, 'bank', true);
                echo strtoupper($bank);
            } else {
                echo $metode_bayar;
            }
            break;
    }
}
add_action('manage_posts_custom_column', 'donasi_masuk_custom_columns_data', 10, 2);


// Hapus donasi-masuk jika donasi dihapus
function delete_related_donasi_masuk($post_id) {
    // Periksa apakah post type yang dihapus adalah 'donasi'
    $post_type = get_post_type($post_id);
    if ($post_type !== 'donasi') {
        return;
    }

    // Query semua post type 'donasi-masuk' yang memiliki post meta 'donasi_id' sama dengan $post_id
    $related_posts = get_posts([
        'post_type'  => 'donasi-masuk',
        'meta_query' => [
            [
                'key'   => 'donasi_id',
                'value' => $post_id,
                'compare' => '=',
            ],
        ],
        'fields' => 'ids', // Hanya ambil ID post
        'posts_per_page' => -1,
    ]);

    // Hapus semua post terkait
    if (!empty($related_posts)) {
        foreach ($related_posts as $related_post_id) {
            wp_delete_post($related_post_id, true); // true untuk menghapus secara permanen
        }
    }
}
add_action('before_delete_post', 'delete_related_donasi_masuk');
