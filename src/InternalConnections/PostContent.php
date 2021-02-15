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

			// Remove filters that may alter content updates, which prevent removal of slashes in front of newlines ("\r\n\")
			remove_all_filters( 'content_save_pre' );
			
			// WP expects all data to be slashed and will unslash it in wp_post_insert()
			$new_post_args['post_content'] = addslashes( $new_post_args['post_content'] );
		}
		
		return $new_post_args;
	}
}