<?php
/**
 * Plugin Name: Draw Attention Extention
 * Description: Adds additional fields to the Draw Attention plugin.
 * Version: 1.0
 */

if (!defined('ABSPATH')) {
    exit;
}

if (!class_exists('Lysy_Draw_Attention_Extention')) {

    class Lysy_Draw_Attention_Extention
    {

        public function __construct()
        {
            add_action('wp_enqueue_scripts', [$this, 'enqueue_styles']);
            add_filter('da_hotspot_area_group_details', [$this, 'add_additional_fields']);
            add_filter('get_post_metadata', [$this, 'replace_action_url_in_post_meta'], 10, 3);
            add_shortcode('hotspot_html', [$this, 'get_hotspot_html']);
        }


        function replace_action_url_in_post_meta($metadata, $object_id, $meta_key)
        {
            global $wpdb;
            if (!is_admin() && $meta_key === '_da_hotspots') {
                $meta_value = $wpdb->get_var(
                    $wpdb->prepare(
                        "SELECT meta_value FROM $wpdb->postmeta WHERE post_id = %d AND meta_key = %s",
                        $object_id,
                        $meta_key
                    )
                );

                $meta_value = maybe_unserialize($meta_value);
                if (is_array($meta_value)) {
                    foreach ($meta_value as $key => &$value) {
                        $value['action'] = 'url';
                        $value['action-url-url'] = isset($value['style']) && $value['style'] === 'sprzedane' ? 'javascript:void(0);' :  get_home_url(null, "map-details/?map={$object_id}&index={$key}");
                    }
                    unset($value);
                }
                return [$meta_value];
            }
            return $metadata;
        }

        public function add_additional_fields($metaboxes)
        {
            if (isset($metaboxes['fields']) && is_array($metaboxes['fields'])) {
                $additional_fields = [
                    'numer_porzadkowy_dzialki' => [
                        'name' => 'numer porządkowy działki',
                        'id' => 'numer_porzadkowy_dzialki',
                        'type' => 'text',
                    ],
                    'powierzchnia' => [
                        'name' => 'powierzchnia',
                        'id' => 'powierzchnia',
                        'type' => 'text',
                    ],
                    'dostep_do_dzialki' => [
                        'name' => 'dostęp do działki',
                        'id' => 'dostep_do_dzialki',
                        'type' => 'textarea',
                    ],
                    'media' => [
                        'name' => 'media',
                        'id' => 'media',
                        'type' => 'textarea',
                    ],
                    'cechy_dodatkowe' => [
                        'name' => 'cechy dodatkowe',
                        'id' => 'cechy_dodatkowe',
                        'type' => 'textarea',
                    ],
                    'cena' => [
                        'name' => 'cena',
                        'id' => 'cena',
                        'type' => 'text',
                    ],
                ];

                foreach ($metaboxes['fields'] as &$field) {
                    if ($field['type'] === 'group' && isset($field['fields']) && is_array($field['fields'])) {
                        $field['fields'] = array_merge($additional_fields, $field['fields']);
                    }
                }
                unset($field);
            }
            return $metaboxes;
        }

        public function get_hotspot_thumbnail_html($hotspot)
        {
            $thumbnail_html = '';

            if (!empty($hotspot['detail_image_id'])) {
                $thumbnail_html .= '<div class="hotspot-thumb">';
                $detail_image_img_tag = wp_get_attachment_image($hotspot['detail_image_id'], 'large');

                if (empty($detail_image_img_tag) && !empty($hotspot['detail_image'])) {
                    $detail_image_img_tag = '<img src="' . esc_url($hotspot['detail_image']) . '" />';
                }

                $thumbnail_html .= $detail_image_img_tag;
                $thumbnail_html .= '</div>';
            } elseif (empty($hotspot['detail_image_id']) && !empty($hotspot['detail_image'])) {
                $thumbnail_html .= '<div class="hotspot-thumb">';
                $thumbnail_html .= '<img src="' . esc_url($hotspot['detail_image']) . '">';
                $thumbnail_html .= '</div>';
            }

            return $thumbnail_html;
        }


        public function get_hotspot_html()
        {
            $map = isset($_GET['map']) ? intval(sanitize_text_field($_GET['map'])) : -1;
            $index = isset($_GET['index']) ? intval(sanitize_text_field($_GET['index'])) : -1;
            if ($map > 0 && $index >= 0) {
                $da_hotspots = get_post_meta($map, '_da_hotspots', true);
                if (isset($da_hotspots[$index])) {
                    $hotspot = $da_hotspots[$index];
                    $fields_to_display = [
                        'title' => 'nazwa',
                        'numer_porzadkowy_dzialki' => 'numer porządkowy działki',
                        'powierzchnia' => 'powierzchnia',
                        'dostep_do_dzialki' => 'dostęp do działki',
                        'media' => 'media',
                        'cechy_dodatkowe' => 'cechy dodatkowe',
                        'cena' => 'cena'
                    ];

                    if (!empty($fields_to_display)) {
                        $table_html = '<table class="real-estate-fields-table">';
                        foreach ($fields_to_display as $key => $label) {
                            if (!empty($hotspot[$key])) {
                                $table_html .= '<tr><th>' . esc_html($label) . '</th><td>' . esc_html($hotspot[$key]) . '</td></tr>';
                            }
                        }
                        $table_html .= '</table>';

                        $image_html = $this->get_hotspot_thumbnail_html($hotspot);
                        return "<div class=\"real-estate-fields-container\">
                    <div class=\"real-estate-fields-left\">
                        {$table_html}
                    </div>
                    {$image_html}
                </div>";
                    }
                }
            }
            return "Object is not selected";
        }

        public function enqueue_styles()
        {
            wp_register_style('tnl-real-estate-style', false);
            wp_enqueue_style('tnl-real-estate-style');
            $custom_css = "body .real-estate-fields-left {
                margin-bottom: 20px;
            }

            @media screen and (min-width: 1100px) {
                body .real-estate-fields-container {
                    display: flex;
                }

                body .real-estate-fields-left {
                    flex-grow: 1;
                    width: 40%;
                }

                body .hotspot-thumb {
                    padding-left: 30px;
                    flex-grow: 1;
                    width: 60%;
                }
            }";
            wp_add_inline_style('tnl-real-estate-style', $custom_css);
        }
    }

    new Lysy_Draw_Attention_Extention();
}
