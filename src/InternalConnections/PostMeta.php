<?php
namespace BvdB\Distributor\InternalConnections;

use BvdB\Distributor\InternalConnections\Utilities as Utilities;

class PostMeta extends AbstractMeta {

	public function register_hooks() {
		add_filter( 'dt_push_post', [ $this, 'dt_push_post' ], 4, 10  );
	}

	/**
	 * Push related Custom Post Types posts that are related through custom meta fields.  
	 * And replace the meta fields values with these newly pushed post_id.
	 * 
	 * @example 'som_event_conductor_id' has a single integer, which needs to be replaced with the post ID of the destination site.
	 */
	public function dt_push_post( $new_post_id, $post_id, $args, $connection ) {

		// Check if we are using the internal Multisite connection
		if( ! is_a( $connection,  '\Distributor\InternalConnections\NetworkSiteConnection' ) ) {
			return $new_post_id;
		}

		// All related meta fields that need to be pushed and replaced.
		$meta_fields = apply_filters('dtmd_push_related_meta_data', [] );

		if( empty( $meta_fields ) ) {
			return $new_post_id;
		}

		$this->set_site_ids( $connection );

		/**
		 * Loop over all defined custom meta fields.
		 * 
		 * We have all the meta from the origin post here in the destination $post through the $post->meta property that Distributor itself added. 
		 * 
		 * This is done in "Utils\prepare_post".
		 */
		foreach( $meta_fields as $meta_key ) {
			
			
			$meta_value = get_post_meta( $new_post_id, $meta_key, true );
			// \switch_to_blog( $this->origin_blog_id );
			// $original_meta_value = get_post_meta( $post_id, $meta_key, true);
			// \restore_current_blog();

			// Only "transform" the ID's when this meta field really exists
			if( ! $meta_value ) {
				continue;
			}
			
			// post meta is saved as strings, so by checking on this and trying if it's possible to cast this $post_meta to an Integer and check if it evaluates to True, then we "kind" of can say it's an single integer value.
			if ( is_string( $meta_value ) && intval( $meta_value ) ) {

				$related_post_id = $meta_value;

				$new_meta_value = $this->create_or_get_destination_id( $related_post_id );

			} else if ( is_array( $meta_value ) ) {

				// loop and find post by "original post id in this array and replace it with the destination's post id
				$destination_post_ids = [];

				foreach( $meta_value as $related_post_id ) {

					$destination_post_id = $this->create_or_get_destination_id( $related_post_id );

					$destination_post_ids[] = $destination_post_id;
				}

				$new_meta_value = $destination_post_ids;

			} else {
				$new_meta_value = false;
			}

			if( $new_meta_value ) {
				// Update this custom field with it's new value
				update_post_meta( $new_post_id, $meta_key, $new_meta_value, $meta_value );
			}
		}

		return $new_post_id;
	}
}