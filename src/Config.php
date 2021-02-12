<?php
namespace BvdB\Distributor;

class Config {

    public function register_hooks() {

        add_action( 'plugin_loaded',      [ $this, 'configure_environment'] );
        add_filter( 'option_dt_settings', [ $this, 'dt_settings'] );
        
        /**
         * Distributor: Don't delete and replace images, voorkomt wat extra overhead?
         */
        add_filter('dt_sync_media_delete_and_replace', '__return_false');

        /**
         * WordPress: So that guid's don't get 'lorem-scaled.jpg' and then search images by GUID won't work.
         * 
         * Needed for the "Utilities::get_media_id_by_guid_filename()" which tries to find an attachment by it's filename.
         * When the treshold is turned on, a filename because lorem-scaled.jpg instead of lorem.jpg. While the original filename is saved in the guid column.
         */
        add_filter( 'big_image_size_threshold', '__return_false' );
    }

    /**
     * Distributor: Configure application passwords for local and development enviroments.
     */
    public function configure_environment () {

        // For local development, Distributor adviced this
        if( wp_get_environment_type() === 'local' || wp_get_environment_type() !== 'development') {
            add_filter( 'wp_is_application_passwords_available', '__return_true' );

            add_action( 'wp_authorize_application_password_request_errors', function( $error ) {
                $error->remove( 'invalid_redirect_scheme' );
            } );
        }
    }
    
    /**
     * Distributor: Force setting to push all media.
     * 
     * Options are 'attached' or 'featured'.
     */
    public function dt_settings( $option ) {

        if ( isset( $option['media_handling'] ) ) {
            $option['media_handling'] = 'attached';
        }

        return $option;
    }
}