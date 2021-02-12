<?php
namespace BvdB\Distributor\InternalConnections;

class PostContent {

	public function register_hooks() {
		add_filter( 'dt_push_post_args', [ $this, 'dt_push_post_args' ], 4, 10  );
  	}

	/**
	 * BugFix: Add slashes to the_content which are present in the_content of the original post, but not in the_content of the pushed post.
	 */
	public function dt_push_post_args( $new_post_args, $post, $args, $connection ) {
	
		if( is_a( $connection,  '\Distributor\InternalConnections\NetworkSiteConnection' ) ) {
			$new_post_args['post_content'] = addslashes( $new_post_args['post_content'] );
		}
		
		return $new_post_args;
	}
}