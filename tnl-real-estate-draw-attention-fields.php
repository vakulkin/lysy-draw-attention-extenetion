<?php
/**
 * Plugin Name: TNL Real Estate Draw Attention Additional Fields
 * Description: Adds additional fields to the Draw Attention plugin.
 * Version: 1.0
 * Author: TNL
 */

if (!defined('ABSPATH')) {
    exit;
}

if (!class_exists('TNL_RealEstateDrawAttentionFields')) {

    class TNL_RealEstateDrawAttentionFields
    {

        public function __construct()
        {
            add_filter('da_hotspot_area_group_details', array($this, 'add_additional_fields'), 0);
            add_filter('drawattention_hotspot_title', array($this, 'display_additional_fields'), 10, 2);
            add_action('wp_enqueue_scripts', array($this, 'enqueue_styles'));
        }

        public function add_additional_fields($metaboxes)
        {
            if (isset($metaboxes['fields']) && is_array($metaboxes['fields'])) {
                $additional_fields = array(
                    'numer_porzadkowy_dzialki' => array(
                        'name' => 'numer porządkowy działki',
                        'id' => 'numer_porzadkowy_dzialki',
                        'type' => 'text',
                    ),
                    'powierzchnia' => array(
                        'name' => 'powierzchnia',
                        'id' => 'powierzchnia',
                        'type' => 'text',
                    ),
                    'dostep_do_dzialki' => array(
                        'name' => 'dostęp do działki',
                        'id' => 'dostep_do_dzialki',
                        'type' => 'text',
                    ),
                    'media' => array(
                        'name' => 'media',
                        'id' => 'media',
                        'type' => 'text',
                    ),
                    'cechy_dodatkowe' => array(
                        'name' => 'cechy dodatkowe',
                        'id' => 'cechy_dodatkowe',
                        'type' => 'text',
                    ),
                    'cena' => array(
                        'name' => 'cena',
                        'id' => 'cena',
                        'type' => 'text',
                    ),
                    'detail_image2' => array(
                        'name' => __('Detail Image2', 'draw-attention'),
                        'desc' => __('Upload an image or enter a URL to show in the more info box', 'draw-attention'),
                        'id' => 'detail_image2',
                        'type' => 'file',
                        // 'attributes' => array(
                        //     'data-action' => 'more-info',
                        // ),
                    ),
                );

                foreach ($metaboxes['fields'] as &$field) {
                    if ($field['type'] === 'group' && isset($field['fields']) && is_array($field['fields'])) {
                        $field['fields'] = array_merge($field['fields'], $additional_fields);

                        if (isset($field['fields']['description'])) {
                            unset($field['fields']['description']);
                        }
                        
                        if (isset($field['fields']['detail_image'])) {
                            unset($field['fields']['detail_image']);
                        }
                    }
                }
            }

            return $metaboxes;
        }

        public function get_hotspot_thumbnail_html($hotspot)
        {
            $thumbnail_html = '';

            if (!empty($hotspot['detail_image2_id'])) {
                $thumbnail_html .= '<div class="hotspot-thumb">';
                $detail_image2_img_tag = wp_get_attachment_image($hotspot['detail_image2_id'], 'large');

                if (empty($detail_image2_img_tag) && !empty($hotspot['detail_image2'])) {
                    $detail_image2_img_tag = '<img src="' . esc_url($hotspot['detail_image2']) . '" />';
                }

                $thumbnail_html .= $detail_image2_img_tag;
                $thumbnail_html .= '</div>';
            } elseif (empty($hotspot['detail_image2_id']) && !empty($hotspot['detail_image2'])) {
                $thumbnail_html .= '<div class="hotspot-thumb">';
                $thumbnail_html .= '<img src="' . esc_url($hotspot['detail_image2']) . '">';
                $thumbnail_html .= '</div>';
            }

            return $thumbnail_html;
        }


        public function display_additional_fields($title_html, $hotspot)
        {
            $fields_to_display = array(
                'numer_porzadkowy_dzialki' => 'numer porządkowy działki',
                'powierzchnia' => 'powierzchnia',
                'dostep_do_dzialki' => 'dostęp do działki',
                'media' => 'media',
                'cechy_dodatkowe' => 'cechy dodatkowe',
                'cena' => 'cena'
            );


            if (!empty($fields_to_display)) {
                $table_html = '<table class="real-estate-fields-table">';

                foreach ($fields_to_display as $key => $label) {
                    if (!empty($hotspot[$key])) {
                        $table_html .= '<tr><th>' . esc_html($label) . '</th><td>' . esc_html($hotspot[$key]) . '</td></tr>';
                    }
                }

                $table_html .= '</table>';

                $title_html .= $table_html;

                $image_html = $this->get_hotspot_thumbnail_html($hotspot);

                $title_html = "<div class=\"real-estate-fields-container\">
                    <div class=\"real-estate-fields-left\">
                        {$title_html}
                    </div>
                    {$image_html}
                </div>";
            }


            return $title_html;
        }

        public function enqueue_styles()
        {
            wp_register_style('tnl-real-estate-style', false);
            wp_enqueue_style('tnl-real-estate-style');
            $custom_css = "
            body .real-estate-fields-left {
                margin-bottom: 20px;
            }

            @media screen and (min-width: 1100px) {
                body .featherlight .featherlight-content {
                    max-width: 90%;
                    padding: 50px;
                    width: 1000px;
                }

                body .featherlight .featherlight-close-icon {
                    top: 5px;
                    right: 10px;
                    width: auto;
                    font-size: 30px;
                    line-height: 1;
                }

                body .hotspot-info {
                    width: 100%;
                }

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
            }
            @media screen and (min-width: 1300px) {
                body .featherlight .featherlight-content {
                    width: 1200px;
                }
            }
            @media screen and (min-width: 1700px) {
                body .featherlight .featherlight-content {
                    width: 1600px;
                }
            }
            ";
            wp_add_inline_style('tnl-real-estate-style', $custom_css);
        }
    }

    new TNL_RealEstateDrawAttentionFields();
}
