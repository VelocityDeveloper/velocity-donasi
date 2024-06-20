<?php
/**
 * Customizer use Kirki
 *
 * @package Velocity Donasi
 */
 
// Exit if accessed directly.
defined('ABSPATH') || exit;

if (!class_exists('Kirki') || class_exists('Kirki') && KIRKI_VERSION < 4)
return false;

//Panel Velocity Donasi
new \Kirki\Panel(
    'velocitydonasi_id',
    [
        'priority'    => 20,
        'title'       => esc_html__( 'Velocity Donasi', 'velocity-donasi' ),
        'description' => esc_html__( 'Velocity Donasi Settings.', 'velocity-donasi' ),
    ]
);
    //Pengaturan Donasi Section
    new \Kirki\Section(
        'section_pengaturan_donasi',
        [
            'title'       => esc_html__( 'Pengaturan', 'velocity-donasi' ),
            'panel'       => 'velocitydonasi_id',
            'priority'    => 160,
        ]
    );

        new \Kirki\Field\Dropdown_Pages(
            [
                'settings' => 'halaman_donatur',
                'label'    => esc_html__( 'Halaman Donatur', 'velocity-donasi' ),
                'section'  => 'section_pengaturan_donasi',
                'description'   => esc_html__( 'Pilih halaman untuk donatur, pastikan didalamnya ada shortcode [halaman-donatur].', 'velocity-donasi' ),
                'default'  => 42,
                'priority' => 10,
            ]
        );
        new \Kirki\Field\Text(
            [
                'settings'      => 'email_admin_donasi',
                'label'         => esc_html__( 'Email Admin', 'velocity-donasi' ),
                'section'       => 'section_pengaturan_donasi',
                'description'   => esc_html__( 'Email Admin, untuk menerima email donasi. Jika dikosongkan secara default menggunakan email admin website.', 'velocity-donasi' ),
                'default'       => '',
                'priority'      => 10,
            ]
        ); 

    //Bank Section
    new \Kirki\Section(
        'section_bank',
        [
            'title'       => esc_html__( 'Bank', 'velocity-donasi' ),
            'description' => esc_html__( 'Anda dapat mengatur data bank disini.', 'velocity-donasi' ),
            'panel'       => 'velocitydonasi_id',
            'priority'    => 160,
        ]
    );
        new \Kirki\Field\Repeater(
            [
                'settings'      => 'bank_velocitydonasi',
                'label'         => esc_html__( 'Data Bank', 'velocity-donasi' ),
                'section'       => 'section_bank',
                'priority'      => 10,
                'default'       => '',
                'button_label'  => esc_html__( 'Tambah Bank', 'velocity-donasi' ),
                'row_label'     => [
                        'type'  => 'field',
                        'value' => esc_html__( 'Bank', 'velocity-donasi' ),
                        'field' => 'kodebank',
                ],
                'fields'        => [                
                    'namabank'    => [
                        'type'        => 'select',
                        'label'       => esc_html__( 'Bank', 'velocity-donasi' ),
                        'choices'     => [
                            0               => esc_html__( 'Pilih Bank', 'velocity-donasi' ),
                            'bca'           => esc_html__( 'BCA', 'velocity-donasi' ),
                            'mandiri'       => esc_html__( 'Mandiri', 'velocity-donasi' ),
                            'bni'           => esc_html__( 'BNI', 'velocity-donasi' ),
                            'bri'           => esc_html__( 'BRI', 'velocity-donasi' ),
                            'permata'       => esc_html__( 'Permata', 'velocity-donasi' ),
                            'cimb_niaga'    => esc_html__( 'CIMB Niaga', 'velocity-donasi' ),
                            'Mega'          => esc_html__( 'Bank Mega', 'velocity-donasi' ),
                            'muamalat'      => esc_html__( 'Muamalat', 'velocity-donasi' ),
                            'danamon'       => esc_html__( 'Danamon', 'velocity-donasi' ),
                        ],
                    ],               
                    'nobank'    => [
                        'type'        => 'text',
                        'label'       => esc_html__( 'Nomor Rekening', 'velocity-donasi' ),
                    ],             
                    'atasnama'    => [
                        'type'        => 'text',
                        'label'       => esc_html__( 'Atas Nama', 'velocity-donasi' ),
                    ],
                ]
            ]
        );        
        new \Kirki\Field\Repeater(
            [
                'settings'      => 'banklain_velocitydonasi',
                'label'         => esc_html__( 'Data Bank Lain', 'velocity-donasi' ),
                'section'       => 'section_bank',
                'priority'      => 10,
                'default'       => '',
                'button_label'  => esc_html__( 'Tambah Bank', 'velocity-donasi' ),
                'row_label'     => [
                        'type'  => 'field',
                        'value' => esc_html__( 'Bank', 'velocity-donasi' ),
                        'field' => 'namabank',
                ],
                'fields'        => [              
                    'namabank'  => [
                        'type'        => 'text',
                        'label'       => esc_html__( 'Nama Bank', 'velocity-donasi' ),
                    ],               
                    'nobank'    => [
                        'type'        => 'text',
                        'label'       => esc_html__( 'Nomor Rekening', 'velocity-donasi' ),
                    ],             
                    'atasnama'    => [
                        'type'        => 'text',
                        'label'       => esc_html__( 'Atas Nama', 'velocity-donasi' ),
                    ],            
                    'logo'    => [
                        'type'        => 'image',
                        'label'       => esc_html__( 'Logo Bank', 'velocity-donasi' ),
                    ],
                ]
            ]
        );
        