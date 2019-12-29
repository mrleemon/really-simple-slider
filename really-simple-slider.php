<?php
/*
Plugin Name: Really Simple Slider
Version: v1.0
Plugin URI:
Author: Oscar Ciutat
Author URI: http://oscarciutat.com/code/
Description: A simple slider
*/

class Really_Simple_Slider {
    
    /**
     * Plugin instance.
     *
     * @since 1.0
     *
     */
    protected static $instance = null;


    /**
     * Access this plugin’s working instance
     *
     * @since 1.0
     *
     */
    public static function get_instance() {
        
        if ( !self::$instance ) {
            self::$instance = new self;
        }

        return self::$instance;

    }

    
    /**
     * Used for regular plugin work.
     *
     * @since 1.0
     *
     */
    public function plugin_setup() {

        $this->includes();

        add_action( 'init', array( $this, 'load_language' ) );
        add_action( 'init', array( $this, 'register_post_type' ) );

        add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_scripts' ) );
        add_action( 'save_post_slider', array( $this, 'save_slider' ) );
        add_action( 'media_buttons', array( $this, 'display_button' ) );
        add_filter( 'wp_editor_settings', array( $this, 'slider_editor_settings' ) );

        add_action( 'show_slider', array($this, 'show_slider' ) );
        
        // Columns
        add_filter( 'manage_slider_posts_columns', array( $this, 'slider_columns' ) );
        add_action( 'manage_slider_posts_custom_column',  array( $this, 'slider_custom_column' ), 5, 2 );

        // Attachment fields
        add_filter( 'attachment_fields_to_edit', array( $this, 'attachment_fields_to_edit' ), 10, 2 );
        add_filter( 'attachment_fields_to_save', array( $this, 'attachment_fields_to_save' ), 10, 2 );

        // Enqueue the thickbox (required for button to work)
        add_action( 'admin_footer', array( $this, 'print_thickbox' ) );
        
        add_shortcode( 'slider', array( $this, 'shortcode_slider' ) );
    
    }

    
    /**
     * Constructor. Intentionally left empty and public.
     *
     * @since 1.0
     *
     */
    public function __construct() {}
    
    
     /**
     * Includes required core files used in admin and on the frontend.
     *
     * @since 1.0
     *
     */
    protected function includes() {}


    /**
     * load_language
     */
    public function load_language() {
        load_plugin_textdomain( 'really-simple-slider', '', dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
    }

    
    /**
     * enqueue_scripts
     */
    function enqueue_scripts() {
        // enqueue styles
        wp_enqueue_style( 'slick-css', plugins_url( '/assets/css/slick.css', __FILE__ ) );
        wp_enqueue_style( 'really-simple-slider-css', plugins_url( '/style.css', __FILE__ ), array( 'dashicons' ) );
        // enqueue scripts
        wp_enqueue_script( 'slick', plugins_url( '/assets/js/slick.min.js', __FILE__ ), array( 'jquery' ), false );
        wp_enqueue_script( 'really-simple-slider-frontend', plugins_url( '/assets/js/frontend.js', __FILE__ ), array( 'jquery', 'slick' ), false );
    }


    /**
     * admin_enqueue_scripts
     */
    function admin_enqueue_scripts() {
        wp_enqueue_media();
        wp_enqueue_style( 'gallery-css', plugins_url( '/assets/css/slider-gallery.css', __FILE__ ) );
        wp_enqueue_script( 'gallery-script', plugins_url( '/assets/js/slider-gallery.js', __FILE__ ), array( 'jquery' ), false, true );
    }

        
    /*
     * register_post_type
     *
     * @since 1.0
     */
    function register_post_type() {
        
        $labels = array(
            'name' => __( 'Sliders', 'really-simple-slider' ),
            'singular_name' => __( 'Slider', 'really-simple-slider' ),
            'add_new' => __( 'Add New Slider', 'really-simple-slider' ),
            'add_new_item' => __( 'Add New Slider', 'really-simple-slider' ),
            'edit_item' => __( 'Edit Slider', 'really-simple-slider' ),
            'new_item' => __( 'New Slider', 'really-simple-slider' ),
            'view_item' => __( 'View Slider', 'really-simple-slider' ),
            'search_items' => __( 'Search Sliders', 'really-simple-slider' ),
            'not_found' => __( 'No Sliders found', 'really-simple-slider' ),
            'not_found_in_trash' => __( 'No Sliders found in Trash', 'really-simple-slider' )
        );
      
        $args = array(
            'query_var' => false,
            'rewrite' => false,
            'public' => true,
            'exclude_from_search' => true,
            'publicly_queryable' => false,
            'show_in_nav_menus' => false,
            'show_ui' => true,
            'menu_position' => 5,
            'menu_icon' => 'dashicons-images-alt2',
            'supports' => array( 'title', 'editor' ), 
            'labels' => $labels,
            'register_meta_box_cb' => array( $this , 'add_slider_meta_boxes' )
        );

        register_post_type( 'slider', $args );
        
    }

    /*
    * Removes media buttons from slider post type.
    */
    function slider_editor_settings( $settings ) {
        $current_screen = get_current_screen();

        // Post types for which the media buttons should be removed.
        $post_types = array( 'slider' );

        // Bail out if media buttons should not be removed for the current post type.
        if ( ! $current_screen || ! in_array( $current_screen->post_type, $post_types, true ) ) {
            return $settings;
        }

        $settings['media_buttons'] = false;

        return $settings;
    }

    /*
    * add_slider_meta_boxes
    */

    function add_slider_meta_boxes() {
        add_meta_box( 'slider-shortcode', __( 'Shortcode', 'really-simple-slider' ), array( $this , 'slider_shortcode_meta_box' ), 'slider', 'side', 'default' );
        add_meta_box( 'slider-options', __( 'Options', 'really-simple-slider' ), array( $this , 'slider_options_meta_box' ), 'slider', 'side', 'default' );
        add_meta_box( 'slider-items', __( 'Slider items', 'really-simple-slider' ), array( $this , 'slider_items_meta_box' ), 'slider', 'normal', 'default' );
    }


    /*
    * slider_shortcode_meta_box
    */
  
    function slider_shortcode_meta_box() {
        global $post;
        $shortcode = '[slider id="' . $post->ID . '"]';
    ?>
        <div class="form-wrap">
        <div class="form-field">
        <label for="slider_get_shortcode"><?php _e( 'Your Shortcode:', 'really-simple-slider' ); ?></label>
        <input readonly="true" id="slider_get_shortcode" type="text" class="widefat" name="" value="<?php echo esc_attr( $shortcode ); ?>" />
        <p><?php _e( 'Copy and paste this shortcode into your Post, Page or Custom Post editor.', 'really-simple-slider' ); ?></p>
        </div>
        </div>
    <?php
    }

    
    /*
    * slider_options_meta_box
    */
  
    function slider_options_meta_box() {
        global $post;
        $slider_fx = ( get_post_meta( $post->ID, '_rss_slider_fx', true ) ) ? get_post_meta( $post->ID, '_rss_slider_fx', true ) : 'fade';
        $slider_text_position = ( get_post_meta( $post->ID, '_rss_slider_text_position', true ) ) ? get_post_meta( $post->ID, '_rss_slider_text_position', true ) : 'top';
        $slider_auto = ( get_post_meta( $post->ID, '_rss_slider_auto', true ) ) ? get_post_meta( $post->ID, '_rss_slider_auto', true ) : '';
    ?>
        <div class="form-wrap">
        <div class="form-field">
        <label for="slider_fx"><?php _e( 'Effect:', 'really-simple-slider' ); ?></label>
        <select id="slider_fx" name="slider_fx">
        <option value="fade" <?php selected( $slider_fx, 'fade' ); ?>><?php _e( 'Fade', 'really-simple-slider' ); ?></option>
        <option value="scrollHorz" <?php selected( $slider_fx, 'scrollHorz' ); ?>><?php _e( 'Slide', 'really-simple-slider' ); ?></option>
        </select>
        </div>
        <div class="form-field">
        <label for="slider_text_position"><?php _e( 'Text Position:', 'really-simple-slider' ); ?></label>
        <select id="slider_text_position" name="slider_text_position">
        <option value="top" <?php selected( $slider_text_position, 'top' ); ?>><?php _e( 'Over the images', 'really-simple-slider' ); ?></option>
        <option value="bottom" <?php selected( $slider_text_position, 'bottom' ); ?>><?php _e( 'Under the images', 'really-simple-slider' ); ?></option>
        <option value="hidden" <?php selected( $slider_text_position, 'hidden' ); ?>><?php _e( 'Hidden behind the images', 'really-simple-slider' ); ?></option>
        </select>
        </div>
        <div class="form-field">
        <label for="slider_auto"><?php _e( 'Automatic Playback:', 'really-simple-slider' ); ?>
        <input type="checkbox" id="slider_auto" name="slider_auto" value="true" <?php checked( $slider_auto, 'true' ); ?> />
        </label>
        </div>
        </div>
    <?php
    }

    
    /*
    * slider_items_meta_box
    */
  
    function slider_items_meta_box() {
        global $post;
    ?>
        <div id="slider_images_container">
            <ul class="slider_images">
                <?php
                    if ( metadata_exists( 'post', $post->ID, '_rss_slider_items' ) ) {
                        $slider_items = get_post_meta( $post->ID, '_rss_slider_items', true );
                    } else {
                        $args = array(
                            'post_parent'    => $post->ID,
                            'post_type'      => 'attachment',
                            'numberposts'    => -1,
                            'orderby'        => 'menu_order',
                            'order'          => 'ASC',
                            'fields'         => 'ids'
                        );
                        $attachment_ids = get_posts( $args );
                        $slider_items = implode( ',', $attachment_ids );
                    }
                    
                    $attachments         = array_filter( explode( ',', $slider_items ) );
                    $update_meta         = false;
                    $updated_gallery_ids = array();

                    if ( ! empty( $attachments ) ) {
                        foreach ( $attachments as $attachment_id ) {
                            if ( wp_attachment_is_image( $attachment_id ) ) {
                                $attachment = wp_get_attachment_image( $attachment_id, 'thumbnail' );
                                echo '<li class="image" data-attachment_id="' . esc_attr( $attachment_id ) . '">
                                    ' . $attachment . '
                                    <ul class="actions">
                                        <li><a href="#" class="delete tips" data-tip="' . esc_attr__( 'Delete item', 'really-simple-slider' ) . '">' . __( 'Delete', 'really-simple-slider' ) . '</a></li>
                                    </ul>
                                </li>';
                                // rebuild ids to be saved
                                $updated_gallery_ids[] = $attachment_id;
                            }
                        }

                        // need to update slider meta to set new gallery ids
                        if ( $update_meta ) {
                            update_post_meta( $post->ID, '_rss_slider_items', implode( ',', $updated_gallery_ids ) );
                        }
                    }
                ?>
            </ul>

            <input type="hidden" id="slider_items" name="slider_items" value="<?php echo esc_attr( $slider_items ); ?>" />

        </div>
        <p class="add_slider_images hide-if-no-js">
            <a href="#" data-choose="<?php esc_attr_e( 'Add items to slider', 'really-simple-slider' ); ?>"><?php _e( 'Add slider items', 'really-simple-slider' ); ?></a>
        </p>
        <?php
    }


    /*
    * save_slider
    */
 
    function save_slider( $post_id ) {
        // verify nonce
        if ( isset( $_POST['metabox_nonce'] ) && !wp_verify_nonce( $_POST['metabox_nonce'], basename( __FILE__ ) ) ) {
            return $post_id;
        }
    
        // is autosave?
        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
            return $post_id;
        }

        // check permissions
        if ( isset( $_POST['post_type'] ) ) {
            if ( 'page' == $_POST['post_type'] ) {
                if ( !current_user_can( 'edit_page', $post_id ) ) {
                    return $post_id;
                }
            } elseif ( !current_user_can( 'edit_post', $post_id ) ) {
                return $post_id;
            }
        }

        if ( isset( $_POST['post_type'] ) && ( 'slider' == $_POST['post_type'] ) ) {
            
            $slider_fx = isset( $_POST['slider_fx'] ) ? sanitize_text_field( $_POST['slider_fx'] ) : 'fade';
            update_post_meta( $post_id, '_rss_slider_fx', $slider_fx );

            $slider_text_position = isset( $_POST['slider_text_position'] ) ? sanitize_text_field( $_POST['slider_text_position'] ) : 'top';
            update_post_meta( $post_id, '_rss_slider_text_position', $slider_text_position );

            $slider_auto = isset( $_POST['slider_auto'] ) ? $_POST['slider_auto'] : '';
            update_post_meta( $post_id, '_rss_slider_auto', $slider_auto );

            $attachment_ids = isset( $_POST['slider_items'] ) ? array_filter( explode( ',', sanitize_text_field( $_POST['slider_items'] ) ) ) : array();
            update_post_meta( $post_id, '_rss_slider_items', implode( ',', $attachment_ids ) );

        }
        
    }

    
    /**
     * shortcode_slider
     */
    function shortcode_slider( $attr ) {
        $html = $this->shortcode_atts( $attr );
        return $html;
    }

    
    /**
     * shortcode_atts
     */
    function shortcode_atts( $attr ) {
        $atts = shortcode_atts( array(
            'id' => ''
        ), $attr, 'slider' );
        $id = $atts['id'];
        $html = '';
        if ( 'slider' === get_post_type( $id ) ) {
            $html = $this->slider_markup( $id );
        }
        return $html;
    }

    
    /**
     * show_slider
     */
    function show_slider( $id ) {
        $html = '';
        if ( 'slider' === get_post_type( $id ) ) {
            $html = $this->slider_markup( $id );
        }
        echo $html;
    }
    
    
    /**
     * slider_markup
     */
    function slider_markup( $id ) {
        $slider = get_post( $id );
        $slider_text = $slider->post_content;
        $slider_fx = ( get_post_meta( $id, '_rss_slider_fx', true ) ) ? get_post_meta( $id, '_rss_slider_fx', true ) : 'fade';
        $slider_text_position = ( get_post_meta( $id, '_rss_slider_text_position', true ) ) ? get_post_meta( $id, '_rss_slider_text_position', true ) : 'top';
        $slider_auto = ( get_post_meta( $id, '_rss_slider_auto', true ) ) ? 8000 : 0;
        $slider_items = get_post_meta( $id, '_rss_slider_items', true );
        
        $html = '';

        $attachments = array_filter( explode( ',', $slider_items ) );
        if ( ! empty( $attachments ) ) {
        
            $attrs = array(
                'fade' => ( $slider_fx === 'fade' ) ? true : false,
                'autoplay' => $slider_auto,
                'speed' => 500,
                'adaptiveHeight' => true,
                'appendArrows' => false,
                'pauseOnFocus' => false,
                'cssEase' => 'linear',
                'lazyLoad' => 'anticipated',
                'nextArrow' => sprintf( '#slider-%d .slide', $id ),
            );

            $html = '<!-- Begin slider markup -->
            
                    <div id="slider-' . esc_attr( $id ) . '" class="slider text-position-' . esc_attr( $slider_text_position ) . '">';

            if ( $slider_text_position === 'hidden' ) {
                $html .= '<div class="slider-switch">
                            <span class="toggle-text">' . __( 'text', 'really-simple-slider' ) . '</span>
                            <span class="toggle-images">' . __( 'images', 'really-simple-slider' ) . '</span>
                            </div>
                            <div class="slider-text">
                        ' . $slider_text . '
                        </div>';
            }

            if ( $slider_text_position === 'top' ) {
                $html .= '<div class="slider-text">
                        ' . $slider_text . '
                        </div>';
            }


            $html .= "<div class='slider-items' data-slick='" . json_encode( $attrs ) . "'>";

            foreach ( $attachments as $attachment_id ) {
                if ( wp_attachment_is_image( $attachment_id ) ) {
                    $html .= '<div class="slide">';
                    $html .= wp_get_attachment_image( $attachment_id, 'full' );
                    $html .= '</div>';
                }
            }

            $html .= '</div>';

            if ( $slider_text_position === 'bottom' ) {
                $html .= '<div class="slider-text">
                        ' . $slider_text . '
                        </div>';
            }

            $html .= '</div>
                      
                      <!-- End slider markup -->';
    
        }
        return $html;
    }

    
    /**
     * Displays the media button
     *
     * @return void
     */
     public function display_button() {
        // Print the button's HTML and CSS
        ?>
            <style type="text/css">
                .wp-media-buttons .insert-slider span.wp-media-buttons-icon {
                    margin-top: -2px;
                }
                .wp-media-buttons .insert-slider span.wp-media-buttons-icon:before {
                    content: "\f233";
                    font: 400 18px/1 dashicons;
                    speak: none;
                    -webkit-font-smoothing: antialiased;
                    -moz-osx-font-smoothing: grayscale;
                }
            </style>
            
            <a href="#TB_inline?width=480&amp;inlineId=select-slider" class="button thickbox insert-slider" data-editor="<?php echo esc_attr( $editor_id ); ?>" title="<?php _e( 'Add a Slider', 'really-simple-slider' ); ?>">
                <span class="wp-media-buttons-icon dashicons dashicons-format-image"></span><?php _e( 'Add Slider', 'really-simple-slider' ); ?>
            </a>
        <?php

    }

    
    /**
     * Prints the thickbox for our media button
     *
     * @return void
     */
    public function print_thickbox() {
        ?>
            <style type="text/css">
                #TB_window .section {
                    padding: 15px 15px 0 15px;
                }
            </style>

            <script type="text/javascript">
                /**
                 * Sends a shortcode to the post/page editor
                 */
                function insertSlider() {

                    // Get the slider ID
                    var id = jQuery( '#slider' ).val();

                    // Display alert and bail if no slideshow was selected
                    if ( '-1' === id ) {
                        return alert( "<?php _e( 'Please select a Slider', 'really-simple-slider' ); ?>" );
                    }

                    // Send shortcode to editor
                    send_to_editor( '[<?php echo esc_attr( 'slider' ); ?> id=\"'+ id +'\"]' );

                    // Close thickbox
                    tb_remove();

                }
            </script>

            <div id="select-slider" style="display: none;">
                <div class="section">
                    <h2><?php _e( 'Add a slider', 'really-simple-slider' ); ?></h2>
                    <span><?php _e( 'Select a slider to insert from the dropdown below:', 'really-simple-slider' ); ?></span>
                </div>

                <div class="section">
                    <select name="slider" id="slider">
                        <option value="-1"><?php _e( 'Select a slider', 'really-simple-slider' ); ?></option>
                        <?php
                            $args = array(
                                'post_type'   => 'slider',
                                'numberposts' => -1,
                                'orderby'     => 'ID',
                                'order'       => 'DESC'
                            );
                            $sliders = get_posts( $args );
                        ?>
                        <?php foreach ( $sliders as $slider ) : ?>
                            <option value="<?php echo esc_attr( $slider->ID ); ?>"><?php echo esc_html( sprintf( "%s ( ID #%d )", $slider->post_title, $slider->ID)); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="section">
                    <button id="insert-slider" class="button-primary" onClick="insertSlider();"><?php _e( 'Insert Slider', 'really-simple-slider' ); ?></button>
                    <button id="close-slider-thickbox" class="button-secondary" style="margin-left: 5px;" onClick="tb_remove();"><?php _e( 'Close', 'really-simple-slider' ); ?></a>
                </div>
            </div>
        <?php
    }

    
    /*
    * slider_columns
    */

    function slider_columns( $columns ) {
        $new = array();
        foreach( $columns as $key => $value ) {
            if ( $key == 'date' ) {
                // Put the Shortcode column before the Date column
                $new['shortcode'] = __( 'Shortcode', 'really-simple-slider' );
            }
            $new[$key] = $value;
        }
        return $new;
    }


    /*
    * slider_custom_column
    */

    function slider_custom_column( $column, $post_id ) {
        switch ( $column ) {
            case 'shortcode':
                $shortcode = sprintf( esc_html( "[slider id=\"%d\"]" ), $post_id );
                echo $shortcode;
                break;
        }
    }

    /*
    * attachment_fields_to_edit
    */
    function attachment_fields_to_edit( $form_fields, $post ) {
        $form_fields['oembed-header']['tr'] = '
            <tr>
                <td colspan="2">
                    <h2>' . __( 'Embed Media Item', 'really-simple-slider' ) . '</h2>
                </td>
            </tr>';
        $form_fields['oembed-url'] = array(
            'label' => __( 'URL' ),
            'input' => 'html',
            'html' => '<input class="text" id="attachments-' . $post->ID . '-oembed-url" name="attachments[' . $post->ID . '][oembed-url]" type="url"
                        value="' . get_post_meta( $post->ID, '_rss_slider_oembed_url', true ) . '" />',
            'helps' => __( 'If provided, this media item will be displayed instead of the image', 'really-simple-slider' )
        );
        $form_fields['oembed-width'] = array(
            'label' => __( 'Width' ),
            'input' => 'html',
            'html' => '<input class="text" id="attachments-' . $post->ID . '-oembed-width" name="attachments[' . $post->ID . '][oembed-width]" type="number" min="1"
                        value="' . get_post_meta( $post->ID, '_rss_slider_oembed_width', true ) .'" />'
        );
        $form_fields['oembed-height'] = array(
            'label' => __( 'Height' ),
            'input' => 'html',
            'html' => '<input class="text" id="attachments-' . $post->ID . '-oembed-height" name="attachments[' . $post->ID . '][oembed-height]" type="number" min="1"
                        value="' . get_post_meta( $post->ID, '_rss_slider_oembed_height', true ) .'" />'
        );
        return $form_fields;
    }


    /*
    * attachment_fields_to_save
    */
    function attachment_fields_to_save( $post, $attachment ) {
        if ( isset( $attachment['oembed-url'] ) ) {
            $url = $attachment['oembed-url'];
            //if ( preg_match( '/^((https?|ftp)://)?([a-z0-9+!*(),;?&=$_.-]+(:[a-z0-9+!*(),;?&=$_.-]+)?@)?([a-z0-9-.]*).([a-z]{2,3})(:[0-9]{2,5})?(/([a-z0-9+$_-].?)+)*/?(?[a-z+&$_.-][a-z0-9;:@&%=+/$_.-]*)?(#[a-z_.-][a-z0-9+$_.-]*)?/', $url ) ) {
                update_post_meta( $post['ID'], '_rss_slider_oembed_url', $url );    
            //} else {
                //$post['errors']['oembed-url']['errors'][] = __( 'This is not a valid URL.' );
            //}
        }
        if ( isset( $attachment['oembed-width'] ) ) {
            $number = (int) $attachment['oembed-width'];
            if ( $number <> 0 ) {
                if ( $number < 0 ) {
                    $number = 0;
                } elseif ( $number > 800 ) {
                    $number = 800;
                }
                update_post_meta( $post['ID'], '_rss_slider_oembed_width', $number );
            } else {
                delete_post_meta( $post['ID'], '_rss_slider_oembed_width');
            }
        }
        if ( isset( $attachment['oembed-height'] ) ) {
            $number = (int) $attachment['oembed-height'];
            if ( $number <> 0 ) {
                if ( $number < 0 ) {
                    $number = 0;
                } elseif ( $number > 800 ) {
                    $number = 800;
                }
                update_post_meta( $post['ID'], '_rss_slider_oembed_height', $number );
            } else {
                delete_post_meta( $post['ID'], '_rss_slider_oembed_height' );
            }
        }
        return $post;
    }

}

add_action( 'plugins_loaded', array ( Really_Simple_Slider::get_instance(), 'plugin_setup' ) );