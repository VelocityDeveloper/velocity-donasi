<?php
/**
 * Metabox definitions tanpa CMB2, berbasis skema array.
 *
 * @package Velocity Donasi
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Skema meta untuk setiap CPT.
 *
 * @return array
 */
function velocity_donasi_meta_schema() {
    return [
        'donasi' => [
            [
                'type'    => 'number',
                'name'    => 'target',
                'label'   => 'Target Donasi',
                'default' => 0,
                'placeholder' => '0',
                'required' => false,
            ],
            [
                'type'    => 'date',
                'name'    => 'tanggal_berakhir',
                'label'   => 'Tanggal Berakhir',
                'default' => '',
                'placeholder' => 'YYYY-MM-DD',
                'required' => false,
            ],
            [
                'type'    => 'repeater',
                'name'    => 'update_info',
                'label'   => 'Update Info',
                'default' => [],
                'fields'  => [
                    [
                        'type'    => 'date',
                        'name'    => 'tanggal',
                        'label'   => 'Tanggal',
                        'default' => '',
                        'placeholder' => 'YYYY-MM-DD',
                        'required' => false,
                    ],
                    [
                        'type'    => 'text',
                        'name'    => 'judulupdate',
                        'label'   => 'Judul Update',
                        'default' => '',
                        'placeholder' => 'Masukkan judul update',
                        'required' => false,
                    ],
                    [
                        'type'    => 'textarea',
                        'name'    => 'deskripsiupdate',
                        'label'   => 'Informasi',
                        'default' => '',
                        'placeholder' => 'Deskripsi singkat',
                        'required' => false,
                    ],
            [
                'type'    => 'media',
                'name'    => 'imageupdate',
                'label'   => 'Gambar (URL)',
                'default' => '',
                'placeholder' => 'https://contoh.com/gambar.jpg',
                'required' => false,
                    ],
                ],
            ],
        ],

        'donasi-masuk' => [
            [
                'type'    => 'select',
                'name'    => 'status',
                'label'   => 'Status Donasi',
                'default' => 'Pending',
                'placeholder' => '',
                'required' => true,
                'options' => [
                    'Pending'           => 'Pending',
                    'Butuh Konfirmasi'  => 'Butuh Konfirmasi',
                    'Gagal'             => 'Gagal',
                    'Sukses'            => 'Sukses',
                    'Refund'            => 'Refund',
                ],
            ],
            [
                'type'    => 'select',
                'name'    => 'donasi_id',
                'label'   => 'Donasi',
                'default' => '',
                'placeholder' => '',
                'required' => true,
                'options' => 'velocity_donasi_posts_options',
            ],
            [
                'type'    => 'text',
                'name'    => 'metode_bayar',
                'label'   => 'Metode Pembayaran',
                'default' => '',
                'placeholder' => 'bank / duitku',
                'required' => true,
            ],
            [
                'type'    => 'text',
                'name'    => 'bank',
                'label'   => 'Bank Pembayaran',
                'default' => '',
                'placeholder' => 'Nama bank',
                'required' => false,
            ],
            [
                'type'    => 'number',
                'name'    => 'kodeunik',
                'label'   => 'Kode Unik',
                'default' => '',
                'placeholder' => '0',
                'required' => false,
            ],
            [
                'type'    => 'number',
                'name'    => 'totaldonasi',
                'label'   => 'Total Donasi',
                'default' => 0,
                'placeholder' => '0',
                'required' => true,
            ],
            [
                'type'    => 'text',
                'name'    => 'nama_donatur',
                'label'   => 'Nama Donatur',
                'default' => '',
                'placeholder' => 'Nama donatur',
                'required' => false,
            ],
            [
                'type'    => 'email',
                'name'    => 'email_donatur',
                'label'   => 'Email Donatur',
                'default' => '',
                'placeholder' => 'email@contoh.com',
                'required' => false,
            ],
            [
                'type'    => 'text',
                'name'    => 'hp_donatur',
                'label'   => 'Telepon',
                'default' => '',
                'placeholder' => '08xxxx',
                'required' => false,
            ],
            [
                'type'    => 'select',
                'name'    => 'user_id',
                'label'   => 'Pengguna',
                'default' => '',
                'placeholder' => '',
                'required' => false,
                'options' => 'velocity_donasi_users_options',
            ],
            [
                'type'    => 'select',
                'name'    => 'anonim',
                'label'   => 'Sembunyikan Nama Donatur?',
                'default' => 'off',
                'placeholder' => '',
                'required' => false,
                'options' => [
                    'on'  => 'Ya',
                    'off' => 'Tidak',
                ],
            ],
        ],
    ];
}

/**
 * Register metaboxes.
 */
function velocity_donasi_register_metaboxes() {
    add_meta_box(
        'velocity_donasi_detail',
        __('Detail Donasi', 'velocity-donasi'),
        'velocity_donasi_render_metabox_donasi',
        'donasi',
        'normal',
        'high'
    );

    add_meta_box(
        'velocity_donasi_masuk_detail',
        __('Detail Donasi Masuk', 'velocity-donasi'),
        'velocity_donasi_render_metabox_donasi_masuk',
        'donasi-masuk',
        'normal',
        'high'
    );
}
add_action('add_meta_boxes', 'velocity_donasi_register_metaboxes');

/**
 * Enqueue admin styles for metaboxes.
 */
function velocity_donasi_admin_assets($hook) {
    $screen = get_current_screen();
    if (!$screen || !in_array($screen->post_type, array('donasi', 'donasi-masuk'), true)) {
        return;
    }
    wp_enqueue_media();
    wp_enqueue_style(
        'velocity-donasi-metabox',
        VELOCITY_DONASI_DIR_URI . 'css/admin-metabox.css',
        array(),
        VELOCITY_DONASI_VERSION
    );
}
add_action('admin_enqueue_scripts', 'velocity_donasi_admin_assets');

/**
 * Render metabox for post type donasi.
 */
function velocity_donasi_render_metabox_donasi($post) {
    wp_nonce_field('velocity_donasi_save_metabox', 'velocity_donasi_nonce');
    $schema = velocity_donasi_meta_schema();
    $fields = isset($schema['donasi']) ? $schema['donasi'] : [];
    $values = [];
    foreach ($fields as $field) {
        $values[$field['name']] = get_post_meta($post->ID, $field['name'], true);
    }
    ?>
    <div class="velocity-donasi-fields velocity-donasi-fields--donasi">
        <?php velocity_donasi_render_fields($fields, $values); ?>
    </div>
    <?php
}

/**
 * Render metabox for post type donasi-masuk.
 */
function velocity_donasi_render_metabox_donasi_masuk($post) {
    wp_nonce_field('velocity_donasi_save_metabox', 'velocity_donasi_nonce');
    $schema = velocity_donasi_meta_schema();
    $fields = isset($schema['donasi-masuk']) ? $schema['donasi-masuk'] : [];
    $values = [];
    foreach ($fields as $field) {
        $values[$field['name']] = get_post_meta($post->ID, $field['name'], true);
    }
    ?>
    <div class="velocity-donasi-fields velocity-donasi-fields--donasi-masuk">
        <?php velocity_donasi_render_fields($fields, $values); ?>
    </div>
    <?php
}

/**
 * Render kumpulan field.
 *
 * @param array $fields
 * @param array $values
 */
function velocity_donasi_render_fields($fields, $values) {
    foreach ($fields as $field) {
        $type = isset($field['type']) ? $field['type'] : 'text';
        $name = $field['name'];
        $label = isset($field['label']) ? $field['label'] : $name;
        $value = isset($values[$name]) ? $values[$name] : (isset($field['default']) ? $field['default'] : '');
        $placeholder = isset($field['placeholder']) ? $field['placeholder'] : '';
        $required = !empty($field['required']);

        if ($type === 'repeater') {
            velocity_donasi_render_repeater($field, $value);
            continue;
        }

        ?>
        <div class="velocity-field">
            <label for="velocity_<?php echo esc_attr($name); ?>"><strong><?php echo esc_html($label); ?></strong></label>
            <?php echo velocity_donasi_render_field_input($field, $value, $placeholder, $required); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
        </div>
        <?php
    }
}

/**
 * Render input sederhana.
 *
 * @param array  $field
 * @param mixed  $value
 * @return string
 */
function velocity_donasi_render_field_input($field, $value, $placeholder = '', $required = false) {
    $type = $field['type'];
    $name = $field['name'];
    $options = isset($field['options']) ? $field['options'] : [];
    $req = $required ? 'required' : '';
    $ph = $placeholder ? 'placeholder="' . esc_attr($placeholder) . '"' : '';

    switch ($type) {
        case 'textarea':
            return '<textarea id="velocity_' . esc_attr($name) . '" name="velocity_meta[' . esc_attr($name) . ']" rows="3" ' . $ph . ' ' . $req . '>' . esc_textarea($value) . '</textarea>';
        case 'media':
            $input_id = 'velocity_' . esc_attr($name);
            $button_id = 'btn_' . esc_attr($name) . '_' . wp_generate_uuid4();
            return '<div class="velocity-media-field">'
                . '<input type="url" id="' . $input_id . '" name="velocity_meta[' . esc_attr($name) . ']" value="' . esc_attr($value) . '" ' . $ph . ' ' . $req . '>'
                . '<button type="button" class="button velocity-media-button" data-target="#' . $input_id . '" id="' . $button_id . '">' . esc_html__('Pilih Gambar', 'velocity-donasi') . '</button>'
            . '</div>';
        case 'select':
            $choices = [];
            if (is_callable($options)) {
                $choices = call_user_func($options, 'donasi');
            } elseif (is_array($options)) {
                $choices = $options;
            }
            $html = '<select id="velocity_' . esc_attr($name) . '" name="velocity_meta[' . esc_attr($name) . ']" ' . $req . '>';
            foreach ($choices as $opt_value => $opt_label) {
                $html .= '<option value="' . esc_attr($opt_value) . '" ' . selected($value, $opt_value, false) . '>' . esc_html($opt_label) . '</option>';
            }
            $html .= '</select>';
            return $html;
        default:
            $input_type = $type === 'url' ? 'url' : ($type === 'email' ? 'email' : ($type === 'date' ? 'date' : ($type === 'number' ? 'number' : 'text')));
            return '<input type="' . esc_attr($input_type) . '" id="velocity_' . esc_attr($name) . '" name="velocity_meta[' . esc_attr($name) . ']" value="' . esc_attr($value) . '" ' . $ph . ' ' . $req . '>';
    }
}

/**
 * Render repeater field.
 *
 * @param array $field
 * @param array $value
 */
function velocity_donasi_render_repeater($field, $value) {
    $items = is_array($value) ? $value : [];
    $subfields = isset($field['fields']) ? $field['fields'] : [];
    $name = $field['name'];
    ?>
    <div class="velocity-update-header">
        <h4><?php echo esc_html($field['label']); ?></h4>
        <button type="button" class="button button-secondary velocity-repeater-add" data-target="velocity_repeater_<?php echo esc_attr($name); ?>"><?php esc_html_e('Tambah Update', 'velocity-donasi'); ?></button>
    </div>
    <div class="velocity-update-wrapper velocity-repeater" id="velocity_repeater_<?php echo esc_attr($name); ?>" data-field="<?php echo esc_attr($name); ?>" data-template="velocity_template_<?php echo esc_attr($name); ?>">
        <?php
        if (!empty($items)) {
            foreach ($items as $index => $row) {
                echo velocity_donasi_render_repeater_row($name, $subfields, $index, $row); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
            }
        }
        ?>
    </div>
    <template id="velocity_template_<?php echo esc_attr($name); ?>">
        <?php echo velocity_donasi_render_repeater_row($name, $subfields, '__INDEX__', []); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
    </template>
    <?php
    velocity_donasi_repeater_script();
}

/**
 * Render satu baris repeater.
 *
 * @param string $name
 * @param array  $subfields
 * @param mixed  $index
 * @param array  $row
 * @return string
 */
function velocity_donasi_render_repeater_row($name, $subfields, $index, $row) {
    ob_start();
    $summary_source = isset($subfields[0]['name']) ? $subfields[0]['name'] : '';
    $summary_val = $summary_source && isset($row[$summary_source]) ? $row[$summary_source] : '';
    $summary_text = $summary_val ? $summary_val : __('Update', 'velocity-donasi');
    ?>
    <div class="velocity-update-row">
        <div class="velocity-update-head">
            <button type="button" class="velocity-repeater-toggle">
                <span class="velocity-repeater-title"><?php echo esc_html($summary_text); ?></span>
                <span class="velocity-repeater-chevron" aria-hidden="true"></span>
            </button>
        </div>
        <div class="velocity-update-body">
        <?php foreach ($subfields as $subfield) :
            $sub_name = $subfield['name'];
            $sub_value = isset($row[$sub_name]) ? $row[$sub_name] : (isset($subfield['default']) ? $subfield['default'] : '');
            $input_type = $subfield['type'];
            $ph = isset($subfield['placeholder']) ? $subfield['placeholder'] : '';
            $req = !empty($subfield['required']) ? 'required' : '';
            $field_id = 'velocity_' . esc_attr($name) . '_' . esc_attr($index) . '_' . esc_attr($sub_name);
            ?>
            <div class="velocity-field">
                <label><strong><?php echo esc_html($subfield['label']); ?></strong></label>
                <?php
                if ($input_type === 'textarea') {
                    echo '<textarea name="velocity_meta[' . esc_attr($name) . '][' . esc_attr($index) . '][' . esc_attr($sub_name) . ']" rows="3" ' . ($ph ? 'placeholder="' . esc_attr($ph) . '"' : '') . ' ' . $req . '>' . esc_textarea($sub_value) . '</textarea>';
                } elseif ($input_type === 'media') {
                    echo '<div class="velocity-media-field">';
                    echo '<input type="url" id="' . esc_attr($field_id) . '" name="velocity_meta[' . esc_attr($name) . '][' . esc_attr($index) . '][' . esc_attr($sub_name) . ']" value="' . esc_attr($sub_value) . '" ' . ($ph ? 'placeholder="' . esc_attr($ph) . '"' : '') . ' ' . $req . '>';
                    echo '<button type="button" class="button velocity-media-button" data-target="#' . esc_attr($field_id) . '">' . esc_html__('Pilih Gambar', 'velocity-donasi') . '</button>';
                    echo '</div>';
                    echo '<label id="label-velocity-media-preview"></label>';
                    echo '<div class="velocity-media-preview" data-preview-for="#' . esc_attr($field_id) . '">';
                    if ($sub_value) {
                        echo '<img src="' . esc_url($sub_value) . '" alt="" />';
                    }
                    echo '</div>';
                } else {
                    $type_attr = $input_type === 'url' ? 'url' : ($input_type === 'date' ? 'date' : 'text');
                    echo '<input type="' . esc_attr($type_attr) . '" name="velocity_meta[' . esc_attr($name) . '][' . esc_attr($index) . '][' . esc_attr($sub_name) . ']" value="' . esc_attr($sub_value) . '" ' . ($ph ? 'placeholder="' . esc_attr($ph) . '"' : '') . ' ' . $req . '>';
                }
                ?>
            </div>
        <?php endforeach; ?>
            <div class="velocity-repeater-actions">
                <a href="#" class="velocity-remove-update button-link-delete"><?php esc_html_e('Hapus', 'velocity-donasi'); ?></a>
            </div>
        </div>
    </div>
    <?php
    return ob_get_clean();
}

/**
 * Inline script untuk repeater (dicetak sekali).
 */
function velocity_donasi_repeater_script() {
    static $printed = false;
    if ($printed) {
        return;
    }
    $printed = true;
    ?>
    <script>
    (function($){
        $(document).on('click', '.velocity-repeater-add', function(e){
            e.preventDefault();
            const target = $(this).data('target');
            const wrapper = $('#' + target);
            const tmplId = wrapper.data('template');
            const template = $('#' + tmplId).html();
            const index = wrapper.children('.velocity-update-row').length;
            const html = template.replace(/__INDEX__/g, index);
            const $row = $(html);
            $row.addClass('is-open');
            $row.find('.velocity-update-body').show();
            wrapper.append($row);
            velocityUpdateRepeaterTitles($row);
        });

        $(document).on('click', '.velocity-remove-update', function(e){
            e.preventDefault();
            $(this).closest('.velocity-update-row').remove();
        });

        $(document).on('click', '.velocity-repeater-toggle', function(e){
            e.preventDefault();
            const $row = $(this).closest('.velocity-update-row');
            const $body = $row.find('.velocity-update-body');
            const isOpen = $row.hasClass('is-open');
            if (isOpen) {
                $row.removeClass('is-open');
                $body.stop(true, true).slideUp(150);
            } else {
                $row.addClass('is-open');
                $body.stop(true, true).slideDown(150);
            }
        });

        $(document).on('input change', '.velocity-update-row input, .velocity-update-row textarea', function(){
            const $row = $(this).closest('.velocity-update-row');
            velocityUpdateRepeaterTitles($row);
        });

        $(document).on('click', '.velocity-media-button', function(e){
            e.preventDefault();
            const target = $(this).data('target');
            const $input = $(target);
            if (!wp || !wp.media) return;
            const frame = wp.media({
                title: '<?php echo esc_js(__('Pilih Gambar', 'velocity-donasi')); ?>',
                button: { text: '<?php echo esc_js(__('Gunakan Gambar', 'velocity-donasi')); ?>' },
                library: { type: 'image' },
                multiple: false
            });
            frame.on('select', function(){
                const attachment = frame.state().get('selection').first().toJSON();
                $input.val(attachment.url || '');
                $input.trigger('change');
                const $preview = $('[data-preview-for="' + target + '"]');
                if ($preview.length) {
                    $preview.html(attachment.url ? '<img src="' + attachment.url + '" alt="" />' : '');
                }
            });
            frame.open();
        });

        function velocityUpdateRepeaterTitles($row) {
            if (!$row || !$row.length) return;
            let title = '';
            const $firstInput = $row.find('input[type="text"], input[type="date"], textarea').first();
            if ($firstInput.length && $firstInput.val()) {
                title = $firstInput.val();
            }
            if (!title) {
                title = '<?php echo esc_js(__('Update', 'velocity-donasi')); ?>';
            }
            $row.find('.velocity-repeater-title').text(title);
        }

        $(document).ready(function(){
            $('.velocity-update-row').each(function(){
                velocityUpdateRepeaterTitles($(this));
            });
        });
    })(jQuery);
    </script>
    <?php
}

/**
 * Save metabox data.
 */
function velocity_donasi_save_metabox($post_id) {
    if (!isset($_POST['velocity_donasi_nonce']) || !wp_verify_nonce($_POST['velocity_donasi_nonce'], 'velocity_donasi_save_metabox')) {
        return;
    }

    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }

    $post_type = get_post_type($post_id);
    $schema = velocity_donasi_meta_schema();
    if (!isset($schema[$post_type])) {
        return;
    }

    $data = isset($_POST['velocity_meta']) && is_array($_POST['velocity_meta']) ? $_POST['velocity_meta'] : [];

    foreach ($schema[$post_type] as $field) {
        $name = $field['name'];
        $type = $field['type'];

        if ($type === 'repeater') {
            $raw_items = isset($data[$name]) && is_array($data[$name]) ? $data[$name] : [];
            $clean_items = [];
            if (!empty($field['fields'])) {
                foreach ($raw_items as $row) {
                    if (!is_array($row)) {
                        continue;
                    }
                    $clean_row = [];
                    foreach ($field['fields'] as $subfield) {
                        $sub_name = $subfield['name'];
                        $sub_type = $subfield['type'];
                        $sub_value = isset($row[$sub_name]) ? $row[$sub_name] : '';
                        $clean_row[$sub_name] = velocity_donasi_sanitize_value($sub_value, $sub_type);
                    }
                    if (!empty(array_filter($clean_row))) {
                        $clean_items[] = $clean_row;
                    }
                }
            }
            update_post_meta($post_id, $name, $clean_items);
            continue;
        }

        $raw_value = isset($data[$name]) ? $data[$name] : (isset($field['default']) ? $field['default'] : '');
        $clean_value = velocity_donasi_sanitize_value($raw_value, $type, $field);
        update_post_meta($post_id, $name, $clean_value);
    }
}
add_action('save_post', 'velocity_donasi_save_metabox');

/**
 * Sanitasi nilai berdasarkan tipe.
 *
 * @param mixed $value
 * @param string $type
 * @param array $field
 * @return mixed
 */
function velocity_donasi_sanitize_value($value, $type, $field = []) {
    switch ($type) {
        case 'number':
            return $value === '' ? '' : floatval($value);
        case 'email':
            return sanitize_email($value);
        case 'media':
        case 'url':
            return esc_url_raw($value);
        case 'textarea':
            return sanitize_textarea_field($value);
        case 'date':
            return sanitize_text_field($value);
        case 'select':
            if (isset($field['options']) && is_array($field['options'])) {
                return array_key_exists($value, $field['options']) ? $value : (isset($field['default']) ? $field['default'] : '');
            }
            return sanitize_text_field($value);
        default:
            return sanitize_text_field($value);
    }
}

/**
 * Callback untuk mendapatkan daftar pengguna.
 */
function velocity_donasi_users_options() {
    $users = get_users();
    $user_options = array(
        '' => __('Pilih Pengguna', 'velocity-donasi'),
    );
    foreach ($users as $user) {
        $user_options[$user->ID] = $user->display_name;
    }
    return $user_options;
}

/**
 * Callback untuk mendapatkan daftar posting jenis 'donasi'.
 *
 * @param string $post_type Post type.
 * @return array
 */
function velocity_donasi_posts_options($post_type) {
    $args = array(
        'post_type'      => $post_type,
        'posts_per_page' => -1,
        'post_status'    => 'publish',
    );
    $donation_posts = get_posts($args);
    $donation_post_options = array(
        '' => __('Pilih Donasi', 'velocity-donasi'),
    );
    foreach ($donation_posts as $donation_post) {
        $donation_post_options[$donation_post->ID] = $donation_post->post_title;
    }
    return $donation_post_options;
}
