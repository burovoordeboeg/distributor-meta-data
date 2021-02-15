<?php
namespace BvdB\Distributor\InternalConnections;

class PostContent {

	public function register_hooks() {
		add_filter( 'dt_pull_post_args', [ $this, 'add_slashes_to_content' ], 4, 10  );
		add_filter( 'dt_push_post_args', [ $this, 'add_slashes_to_content' ], 4, 10  );
  	}

	/**
	 * BugFix: Add slashes to the_content which are present in the_content of the original post, but not in the_content of the pushed post.
	 */
	public function add_slashes_to_content( $new_post_args, $post, $args, $connection ) {
	
		if( is_a( $connection,  '\Distributor\InternalConnections\NetworkSiteConnection' ) ) {

			// Remove filters that may alter content updates.
			remove_all_filters( 'content_save_pre' );
			
			$new_post_args['post_content'] = addslashes( $new_post_args['post_content'] );

			// $content = str_replace( "rnrn", "\r\n\r\n", $content );
		}
		
		return $new_post_args;
	}
}