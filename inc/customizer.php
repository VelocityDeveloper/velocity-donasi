<?php
/**
 * Customizer implementation without Kirki dependency.
 *
 * @package Velocity Donasi
 */

defined('ABSPATH') || exit;

if (!class_exists('WP_Customize_Control') && file_exists(ABSPATH . WPINC . '/class-wp-customize-control.php')) {
    require_once ABSPATH . WPINC . '/class-wp-customize-control.php';
}

/**
 * Repeater control (follow velocity-pakete style).
 */
class Velocity_Donasi_Repeater_Control extends WP_Customize_Control {
    public $type = 'velocity_repeater';
    public $fields = [];
    public $button_label = '';
    public $row_label = '';
    public $default_label = '';

    public function __construct($manager, $id, $args = [], $options = []) {
        if (isset($args['fields'])) {
            $this->fields = (array) $args['fields'];
            unset($args['fields']);
        }

        if (isset($args['button_label'])) {
            $this->button_label = $args['button_label'];
        }

        if (isset($args['default_label'])) {
            $this->default_label = $args['default_label'];
        }

        parent::__construct($manager, $id, $args);
    }

    public function render_content() {
        if (empty($this->fields)) {
            return;
        }

        $value = $this->value();
        if (is_string($value)) {
            $decoded = json_decode($value, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $value = $decoded;
            }
        }

        if (!is_array($value)) {
            $value = [];
        }

        $encoded_value = wp_json_encode($value);
        if (empty($encoded_value)) {
            $encoded_value = '[]';
        }
        ?>
        <div class="velocity-repeater-control" data-default-label="<?php echo esc_attr($this->default_label ? $this->default_label : __('Item', 'velocity-donasi')); ?>">
            <?php if (!empty($this->label)) : ?>
                <span class="customize-control-title"><?php echo esc_html($this->label); ?></span>
            <?php endif; ?>
            <?php if (!empty($this->description)) : ?>
                <p class="description"><?php echo wp_kses_post($this->description); ?></p>
            <?php endif; ?>

            <div class="velocity-repeater" data-fields="<?php echo esc_attr(wp_json_encode($this->fields)); ?>" data-default-label="<?php echo esc_attr($this->row_label ? $this->row_label : __('Item', 'velocity-donasi')); ?>">
                <input type="hidden" class="velocity-repeater-store" <?php $this->link(); ?> value="<?php echo esc_attr($encoded_value); ?>">
                <div class="velocity-repeater-items">
                    <?php
                    if (!empty($value)) {
                        foreach ($value as $item) {
                            echo $this->get_single_item_markup($item); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                        }
                    }
                    ?>
                </div>
                <button type="button" class="button button-primary velocity-repeater-add">
                    <?php echo esc_html($this->button_label ?: __('Tambah Baris', 'velocity-donasi')); ?>
                </button>
                <script type="text/html" class="velocity-repeater-template">
                    <?php echo $this->get_single_item_markup([]); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                </script>
            </div>
        </div>
        <?php
    }

    private function get_summary_field_key() {
        if (!empty($this->row_label)) {
            return $this->row_label;
        }

        $keys = array_keys($this->fields);
        return isset($keys[0]) ? $keys[0] : '';
    }

    private function get_single_item_markup($item_values = []) {
        ob_start();
        $summary_field = $this->get_summary_field_key();
        $summary_value = isset($item_values[$summary_field]) ? $item_values[$summary_field] : '';
        $summary       = '';
        if ($summary_field && isset($this->fields[$summary_field])) {
            $summary_field_config = $this->fields[$summary_field];
            if (isset($summary_field_config['type']) && $summary_field_config['type'] === 'select') {
                $choices = isset($summary_field_config['choices']) ? (array) $summary_field_config['choices'] : [];
                if (isset($choices[$summary_value]) && $choices[$summary_value]) {
                    $summary = $choices[$summary_value];
                } elseif (isset($choices[0]) && $choices[0]) {
                    $summary = $choices[0];
                }
            }
        }
        if (!$summary) {
            $summary = $summary_value ? $summary_value : ($this->default_label ? $this->default_label : __('--Pilih Bank--', 'velocity-donasi'));
        }
        ?>
        <div class="velocity-repeater-item">
            <button type="button" class="velocity-repeater-toggle" aria-expanded="true">
                <span class="velocity-repeater-item-label"><?php echo esc_html($summary); ?></span>
                <span class="velocity-repeater-toggle-icon" aria-hidden="true"></span>
            </button>
            <div class="velocity-repeater-item-body">
                <?php foreach ($this->fields as $field_key => $field) :
                    $field_type    = isset($field['type']) ? $field['type'] : 'text';
                    $field_label   = isset($field['label']) ? $field['label'] : '';
                    $field_value   = isset($item_values[$field_key]) ? $item_values[$field_key] : '';
                    $field_default = isset($field['default']) ? $field['default'] : '';
                    $field_desc    = isset($field['description']) ? $field['description'] : '';
                    $choices       = isset($field['choices']) ? (array) $field['choices'] : [];
                    ?>
                        <label class="velocity-repeater-field">
                            <span class="velocity-repeater-field-label"><?php echo esc_html($field_label); ?></span>
                            <?php if ('textarea' === $field_type) : ?>
                                <textarea data-field="<?php echo esc_attr($field_key); ?>" data-default="<?php echo esc_attr($field_default); ?>" <?php echo ($field_key === $summary_field) ? 'data-summary-field="true"' : ''; ?>><?php echo esc_textarea($field_value); ?></textarea>
                            <?php elseif ('select' === $field_type) : ?>
                                <select data-field="<?php echo esc_attr($field_key); ?>" data-default="<?php echo esc_attr($field_default); ?>" <?php echo ($field_key === $summary_field) ? 'data-summary-field="true"' : ''; ?>>
                                    <?php foreach ($choices as $choice_key => $choice_label) : ?>
                                        <option value="<?php echo esc_attr($choice_key); ?>" <?php selected($field_value, $choice_key); ?>>
                                            <?php echo esc_html($choice_label); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            <?php else : ?>
                                <input type="<?php echo esc_attr($field_type === 'image' ? 'text' : $field_type); ?>" data-field="<?php echo esc_attr($field_key); ?>" data-default="<?php echo esc_attr($field_default); ?>" value="<?php echo esc_attr($field_value); ?>" <?php echo ($field_key === $summary_field) ? 'data-summary-field="true"' : ''; ?>>
                            <?php endif; ?>
                            <?php if (!empty($field_desc)) : ?>
                                <span class="description customize-control-description"><?php echo esc_html($field_desc); ?></span>
                            <?php endif; ?>
                        </label>
                <?php endforeach; ?>
                <div class="velocity-repeater-actions">
                    <button type="button" class="button velocity-repeater-clone"><?php esc_html_e('Clone', 'velocity-donasi'); ?></button>
                    <button type="button" class="button button-secondary velocity-repeater-remove"><?php esc_html_e('Hapus', 'velocity-donasi'); ?></button>
                </div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
}

/**
 * Bank choices used across controls and sanitizers.
 *
 * @return array
 */
function velocity_donasi_bank_choices() {
    return [
        0            => esc_html__('Pilih Bank', 'velocity-donasi'),
        'bsi'        => esc_html__('BSI', 'velocity-donasi'),
        'bca'        => esc_html__('BCA', 'velocity-donasi'),
        'mandiri'    => esc_html__('Mandiri', 'velocity-donasi'),
        'bni'        => esc_html__('BNI', 'velocity-donasi'),
        'bri'        => esc_html__('BRI', 'velocity-donasi'),
        'permata'    => esc_html__('Permata', 'velocity-donasi'),
        'cimb_niaga' => esc_html__('CIMB Niaga', 'velocity-donasi'),
        'mega'       => esc_html__('Bank Mega', 'velocity-donasi'),
        'muamalat'   => esc_html__('Muamalat', 'velocity-donasi'),
        'maybank'    => esc_html__('Maybank', 'velocity-donasi'),
        'danamon'    => esc_html__('Danamon', 'velocity-donasi'),
        'panin'      => esc_html__('Bank Panin', 'velocity-donasi'),
        'seabank'    => esc_html__('SeaBank', 'velocity-donasi'),
    ];
}

/**
 * Decode repeater JSON/string to an array.
 *
 * @param mixed $input Input from Customizer.
 * @return array
 */
function velocity_donasi_decode_repeater($input) {
    if (is_array($input)) {
        return $input;
    }

    if (is_string($input)) {
        $decoded = json_decode(wp_unslash($input), true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
            return $decoded;
        }
    }

    return [];
}

/**
 * Sanitize bank repeater values.
 *
 * @param mixed $input Raw input.
 * @return array
 */
function velocity_donasi_sanitize_bank_repeater($input) {
    $data          = velocity_donasi_decode_repeater($input);
    $allowed_banks = array_keys(velocity_donasi_bank_choices());
    $sanitized     = [];

    foreach ($data as $row) {
        if (!is_array($row)) {
            continue;
        }

        $namabank = isset($row['namabank']) && in_array($row['namabank'], $allowed_banks, true) ? $row['namabank'] : '';
        $nobank   = isset($row['nobank']) ? sanitize_text_field($row['nobank']) : '';
        $atasnama = isset($row['atasnama']) ? sanitize_text_field($row['atasnama']) : '';

        if ($namabank !== '' || $nobank !== '' || $atasnama !== '') {
            $sanitized[] = [
                'namabank' => $namabank,
                'nobank'   => $nobank,
                'atasnama' => $atasnama,
            ];
        }
    }

    return $sanitized;
}

/**
 * Sanitize custom bank repeater values.
 *
 * @param mixed $input Raw input.
 * @return array
 */
function velocity_donasi_sanitize_custom_bank_repeater($input) {
    $data      = velocity_donasi_decode_repeater($input);
    $sanitized = [];

    foreach ($data as $row) {
        if (!is_array($row)) {
            continue;
        }

        $namabank = isset($row['namabank']) ? sanitize_text_field($row['namabank']) : '';
        $nobank   = isset($row['nobank']) ? sanitize_text_field($row['nobank']) : '';
        $atasnama = isset($row['atasnama']) ? sanitize_text_field($row['atasnama']) : '';
        $logo     = isset($row['logo']) ? esc_url_raw($row['logo']) : '';

        if ($namabank !== '' || $nobank !== '' || $atasnama !== '' || $logo !== '') {
            $sanitized[] = [
                'namabank' => $namabank,
                'nobank'   => $nobank,
                'atasnama' => $atasnama,
                'logo'     => $logo,
            ];
        }
    }

    return $sanitized;
}

/**
 * Register Customizer settings and controls.
 *
 * @param WP_Customize_Manager $wp_customize Customize manager instance.
 */
function velocity_donasi_customize_register($wp_customize) {
    $panel_id = 'velocitydonasi_id';

    $wp_customize->add_panel(
        $panel_id,
        [
            'priority'    => 20,
            'title'       => esc_html__('Velocity Donasi', 'velocity-donasi'),
            'description' => esc_html__('Velocity Donasi Settings.', 'velocity-donasi'),
        ]
    );

    $wp_customize->add_section(
        'section_pengaturan_donasi',
        [
            'title'    => esc_html__('Pengaturan', 'velocity-donasi'),
            'panel'    => $panel_id,
            'priority' => 160,
        ]
    );

    $wp_customize->add_setting(
        'halaman_donatur',
        [
            'default'           => 42,
            'sanitize_callback' => 'absint',
        ]
    );

    $wp_customize->add_control(
        'halaman_donatur',
        [
            'type'        => 'dropdown-pages',
            'label'       => esc_html__('Halaman Donatur', 'velocity-donasi'),
            'description' => esc_html__('Pilih halaman untuk donatur, pastikan didalamnya ada shortcode [halaman-donatur].', 'velocity-donasi'),
            'section'     => 'section_pengaturan_donasi',
            'priority'    => 10,
        ]
    );

    $wp_customize->add_setting(
        'email_admin_donasi',
        [
            'default'           => '',
            'sanitize_callback' => 'sanitize_email',
        ]
    );

    $wp_customize->add_control(
        'email_admin_donasi',
        [
            'type'        => 'text',
            'label'       => esc_html__('Email Admin', 'velocity-donasi'),
            'description' => esc_html__('Email Admin, untuk menerima email donasi. Jika dikosongkan secara default menggunakan email admin website.', 'velocity-donasi'),
            'section'     => 'section_pengaturan_donasi',
            'priority'    => 20,
        ]
    );

    $wp_customize->add_section(
        'section_bank',
        [
            'title'       => esc_html__('Bank', 'velocity-donasi'),
            'description' => esc_html__('Anda dapat mengatur data bank disini.', 'velocity-donasi'),
            'panel'       => $panel_id,
            'priority'    => 180,
        ]
    );

    $wp_customize->add_setting(
        'bank_velocitydonasi',
        [
            'default'           => [],
            'sanitize_callback' => 'velocity_donasi_sanitize_bank_repeater',
        ]
    );

    $wp_customize->add_control(
        new Velocity_Donasi_Repeater_Control(
            $wp_customize,
            'bank_velocitydonasi',
            [
                'label'        => esc_html__('Data Bank', 'velocity-donasi'),
                'section'      => 'section_bank',
                'priority'     => 10,
                'button_label' => esc_html__('Tambah Bank', 'velocity-donasi'),
                'row_label'    => '',
                'default_label'=> esc_html__('--Pilih Bank--', 'velocity-donasi'),
                'fields'       => [
                    'namabank' => [
                        'type'    => 'select',
                        'label'   => esc_html__('Bank', 'velocity-donasi'),
                        'choices' => velocity_donasi_bank_choices(),
                        'default' => 0,
                    ],
                    'nobank'   => [
                        'type'  => 'text',
                        'label' => esc_html__('Nomor Rekening', 'velocity-donasi'),
                    ],
                    'atasnama' => [
                        'type'  => 'text',
                        'label' => esc_html__('Atas Nama', 'velocity-donasi'),
                    ],
                ],
            ]
        )
    );

    $wp_customize->add_setting(
        'banklain_velocitydonasi',
        [
            'default'           => [],
            'sanitize_callback' => 'velocity_donasi_sanitize_custom_bank_repeater',
        ]
    );

    $wp_customize->add_control(
        new Velocity_Donasi_Repeater_Control(
            $wp_customize,
            'banklain_velocitydonasi',
            [
                'label'        => esc_html__('Data Bank Lain', 'velocity-donasi'),
                'section'      => 'section_bank',
                'priority'     => 20,
                'button_label' => esc_html__('Tambah Bank', 'velocity-donasi'),
                'row_label'    => '',
                'default_label'=> esc_html__('--Pilih Bank--', 'velocity-donasi'),
                'fields'       => [
                    'namabank' => [
                        'type'  => 'text',
                        'label' => esc_html__('Nama Bank', 'velocity-donasi'),
                    ],
                    'nobank'   => [
                        'type'  => 'text',
                        'label' => esc_html__('Nomor Rekening', 'velocity-donasi'),
                    ],
                    'atasnama' => [
                        'type'  => 'text',
                        'label' => esc_html__('Atas Nama', 'velocity-donasi'),
                    ],
                    'logo'     => [
                        'type'         => 'image',
                        'label'        => esc_html__('Logo Bank', 'velocity-donasi'),
                        'select_label' => esc_html__('Pilih Logo', 'velocity-donasi'),
                    ],
                ],
            ]
        )
    );
}
add_action('customize_register', 'velocity_donasi_customize_register');

/**
 * Enqueue Customizer assets for repeater control.
 */
function velocity_donasi_customize_controls_assets() {
    wp_enqueue_style(
        'velocity-donasi-customizer-repeater',
        VELOCITY_DONASI_DIR_URI . 'css/customizer-repeater.css',
        [],
        VELOCITY_DONASI_VERSION
    );

    wp_enqueue_script(
        'velocity-donasi-customizer-repeater',
        VELOCITY_DONASI_DIR_URI . 'js/customizer-repeater.js',
        ['customize-controls', 'jquery', 'wp-mediaelement', 'media-editor'],
        VELOCITY_DONASI_VERSION,
        true
    );
}
add_action('customize_controls_enqueue_scripts', 'velocity_donasi_customize_controls_assets');
