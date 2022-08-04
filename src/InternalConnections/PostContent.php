<?php
namespace BvdB\Distributor\InternalConnections;

use BvdB\Distributor\InternalConnections\Utilities as Utilities;

class PostContent {

	public function register_hooks() {

		/**
		 * As of 1.7.0 Distributor slashes the content of an  InternalConnection themselfs
		 * See: https://github.com/10up/distributor/pull/890
		 */
		if( defined( 'DT_VERSION' ) && (float)DT_VERSION <= 1.6 ) {
			Utilities::add_filter_once( 'dt_pull_post_args', [ $this, 'alter_post_content' ], 4, 10  );
			Utilities::add_filter_once( 'dt_push_post_args', [ $this, 'alter_post_content' ], 4, 10  );
		}	
  	}

	/**
	 * Change the post_content so it will be saved and displayed correctly in the destination site.
	 */
	public function alter_post_content( $new_post_args, $post, $args, $connection ) {

		if( is_a( $connection,  '\Distributor\InternalConnections\NetworkSiteConnection' ) ) {

			// Don't run this if your post doens't use Gutenberg. Because when calling 'wp_update_post' from WP-CLI will result in your code hanging...
			if( \Distributor\Utils\is_using_gutenberg( $post ) ){
				$new_post_args['post_content'] =  self::prepare_content_before_save( $new_post_args['post_content'] );
			}
		}
		
		return $new_post_args;
	}

	/**
	 * Prepare WordPress and the content before passing it to wp_post_insert.
	 */
	public static function prepare_content_before_save( $content ) {

		// Remove filters that may alter content updates, which prevent removal of slashes in front of newlines ("\r\n") etc
		remove_all_filters( 'content_save_pre' );

		// WP expects all data to be slashed and will unslash it in wp_post_insert()
		$content = addslashes( $content );

		return $content;
	}
}
