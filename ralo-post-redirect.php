<?php
/**
 * 
 * 	Plugin Name: Post Redirect by Ralo
 * 	Version: 1.0
 * 	Wordpress Version: 4.2
 * 	Plugin URI: 
 * 	Description: Simple post redirect
 * 	Author: Citrus7 - renatosakamoto@gmail.com
 * 	Author URI: http://renatosakamoto.com.br
 *
 * Text Domain: ralo-post-redirect
 * Domain Path: /languages
 
 */


class RaloPostRedirect{
    const REDIRECT_FIELDNAME='ralo-redirect';
    const REDIRECT_NONCE='ralo-redirect-nonce';

    static function setupPlugin(){
        add_action( 'add_meta_boxes', array(__CLASS__, 'addMetaBoxes') );
        add_action( 'save_post', array(__CLASS__, 'savePost') );
        add_action( 'template_redirect', array(__CLASS__, 'templateRedirect') );

        add_filter( 'post_link', array(__CLASS__, 'postLink'), 10, 3 );
        add_filter( 'post_type_link', array(__CLASS__, 'postLink'), 10, 3 );

        add_action( 'plugins_loaded', array(__CLASS__, 'loadTextdomain' ));

        $ralo_redirect_metakey = apply_filters('ralo_redirect_metakey', 'redirect');
        define(RALO_REDIRECT_METAKEY, $ralo_redirect_metakey);
    }

    function loadTextdomain() {
        load_plugin_textdomain( 'ralo-redirect', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' ); 
    }

    static function getPostTypes(){

       $postsTypes = get_post_types(
            array(
                'show_in_menu'=>true,
                'show_in_nav_menus'=>true
            ),
            'names'
        ); 
       if(!$postsTypes) $postsTypes=array();

       $postsTypes = apply_filters('ralo_redirect_posttypes', $postsTypes);

       return $postsTypes;

    }

    static function addMetaBoxes(){
        $screens = self::getPostTypes();

        foreach ( $screens as $screen ) {

            add_meta_box(
                'ralo_post_redirect',
                __( 'Redirect', 'ralo-post-redirect' ),
                array(__CLASS__, 'metaBox'),
                $screen
            );
        }
    }

    static function metaBox($post){

        wp_nonce_field( 'redirect_meta_box', self::REDIRECT_NONCE );

        $redirect = get_post_meta( $post->ID, RALO_REDIRECT_METAKEY, true );

        echo '
        <label for="'.self::REDIRECT_FIELDNAME.'" style="display: block">
            '.__( 'Redirect to URL', 'ralo-post-redirect').
        '</label>
        <input 
            type="text" 
            id="'.self::REDIRECT_FIELDNAME.'" 
            name="'.self::REDIRECT_FIELDNAME.'" 
            value="' . esc_attr( $redirect ) . '" 
            style="width: 80%" />';
    }

    static function savePost($post_id){

        if ( ( ! isset( $_POST[self::REDIRECT_NONCE] ) ) || ( ! wp_verify_nonce( $_POST[self::REDIRECT_NONCE], 'redirect_meta_box' ) ) ) {
            return;
        }

        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
            return;
        }

        $postsTypes = self::getPostTypes();

        if ( isset( $_POST['post_type'] ) && (!in_array($_POST['post_type'], $postsTypes ) ) ) {
            return;
        }

        if ( ! isset( $_POST[self::REDIRECT_FIELDNAME] ) ) {
            return;
        }

        // Sanitize user input.
        $redirect = sanitize_text_field( trim( $_POST[self::REDIRECT_FIELDNAME] ) );
        if(strlen($redirect)){
            if(!preg_match('/^https?\:\/\//', $redirect)){
                $redirect='http://'.$redirect;
            }
        }
        update_post_meta( $post_id, RALO_REDIRECT_METAKEY, $redirect );
    }

    function postLink( $url, $post, $leavename ) {
        if(!$leavename){
            $redirect = get_post_meta( $post->ID, RALO_REDIRECT_METAKEY, true );

            if(strlen($redirect)) $url=$redirect;
        }
        return $url;
    }

    function templateRedirect(){;
        $postsTypes=self::getPostTypes();
        if(is_singular($postsTypes)){
            global $post;
            $redirect = get_post_meta( $post->ID, RALO_REDIRECT_METAKEY, true );
            if(strlen($redirect)){
                Header('HTTP/1.1 301 Moved Permanently');
                wp_redirect($redirect);
                exit;
            }            
        }
    }
}

RaloPostRedirect::setupPlugin();