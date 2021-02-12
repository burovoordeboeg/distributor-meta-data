<?php
namespace BvdB\Distributor\InternalConnections;

use BvdB\Distributor\InternalConnections\Utilities as Utilities;

class CustomFields {

	var $connection;
	var $origin_blog_id = false;
	var $destination_blog_id = false;

	var $post_ids = [];

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
		$meta_fields = apply_filters('bvdb_distributor_push_related_meta_data', [] );

		if( empty( $meta_fields ) ) {
			return $new_post_id;
		}

		$this->set_site_ids( $connection );

		// ray( get_fields( $new_post_id ) );

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
			

			// ray(  $meta_key );
			ray($new_post_id,  $meta_value );

			// Only "transform" the ID's when this meta field really exists
			if( ! $meta_value ) {
				continue;
			}

			
			
			// post meta is saved as strings, so by checking on this and trying if it's possible to cast this $post_meta to an Integer and check if it evaluates to True, then we "kind" of can say it's an single integer value.
			if ( is_string( $meta_value ) && intval( $meta_value ) ) {
				ray('single value');
				$related_post_id = $meta_value;

				$new_meta_value = $this->create_or_get_destination_id( $related_post_id );


			} else if ( is_array( $meta_value ) ) {
				ray('array value');
				// loop and find post by "original post id in this array and replace it with the destination's post id
				$destination_post_ids = [];

				foreach( $meta_value as $related_post_id ) {
					$destination_post_id = $this->create_or_get_destination_id( $related_post_id );
					// $destination_post_ids[ $related_post_id ] = $destination_post_id;
					$destination_post_ids[] = $destination_post_id;
				}

				$new_meta_value = $destination_post_ids;
			} else {
				ray('invalid value');
				$new_meta_value = false;
			}

			ray('new value:' , $new_meta_value );

			if( $new_meta_value ) {
				// Update this custom field with it's new value
				update_post_meta( $new_post_id, $meta_key, $new_meta_value, $meta_value );
			}
		}

		return $new_post_id;
	}

	/**
	 * Search for a Post by meta_key "dt_original_post_id" in the destination subsite.
	 * Otherwise push a new Post from the origin to the destination site.
	 * 
	 * @var $post_id is the origin Post ID
	 */
	public function create_or_get_destination_id( $post_id ) {

		// Move the "origin post" from the "origin site" to the "destination site" Now we have a new destination post_id which we will replace 

		
				// Of we
		\switch_to_blog( $this->origin_blog_id );
		$pt = get_post_type( $post_id );
		\switch_to_blog( $this->destination_blog_id );

		// ray( $pt );
		if( $pt === 'attachment' ) {
			// Check if this "related post" is already present in destination site. 
			$possible_existing_destination_post_id = Utilities::get_media_id_by_original_id( $post_id );
		} else {
			// Check if this "related post" is already present in destination site. 
			$possible_existing_destination_post_id = Utilities::get_post_id_by_original_id( $post_id );
		}

		

		// If found, then just use the found post_id.
		if( $possible_existing_destination_post_id && ! empty( $possible_existing_destination_post_id ) ) {

			$destination_post_id = $possible_existing_destination_post_id;

			return $destination_post_id;
		}


		
		if( $pt !== 'attachment' ) {
			
			// We need to switch back to the origin site, and push this related post towards the current $connection.
			switch_to_blog( $this->origin_blog_id );

			$new_destination_post = $this->connection->push( intval( $post_id ) );
			$destination_post_id = $new_destination_post['id'];

			switch_to_blog( $this->destination_blog_id );

			if( ! is_wp_error( $new_destination_post ) ) {
				// Update the connection map used by Distributor :)
				$this->update_connection_map( $post_id, $destination_post_id );
				return $destination_post_id;
			}

			return false;
		}
		
		// Als het geen attachment is geweest welke al door Distibutor meegestuurd is, dan moeten we hem zelf nog even pushen.
		if( $pt == 'attachment') {
			// We need to switch back to the origin site, and push this related post towards the current $connection.
			switch_to_blog( $this->origin_blog_id );
			$post = get_post( $post_id );

			if( $post ) {
					$media[] = \Distributor\Utils\format_media_post( $post );
					ray($post, $media);    
			}
			switch_to_blog( $this->destination_blog_id );

			if( $media ) {
				\Distributor\Utils\set_media( $post_id, $media, [ 'use_filesystem' => true ] ); // deze functie returned niets, dus maar weer zelf zoeken...
			}

			$possible_existing_destination_post_id = Utilities::get_media_id_by_original_id( $post_id );

			// If found, then just use the found post_id.
			if( $possible_existing_destination_post_id && ! empty( $possible_existing_destination_post_id ) ) {

				$destination_post_id = $possible_existing_destination_post_id;

				return $destination_post_id;
			}	
		}

		return false;
	}

	/**
	 * Save per site ID / connection the destination post ID in the "dt_connection_map" meta field. Because Distributor needs that.
	 * 
	 * "dt_connection_map" is the mapping where the information is save where this origin Post is pushed to.
	 */
	public function update_connection_map( $origin_post_id, $destination_post_id ) {

		switch_to_blog( $this->origin_blog_id );

		$connection = $this->connection;

		$connection_map = (array) get_post_meta( $origin_post_id, 'dt_connection_map', true );

		if ( empty( $connection_map['external'] ) ) {
			$connection_map['external'] = [];
		}

		if ( empty( $connection_map['internal'] ) ) {
			$connection_map['internal'] = [];
		}

		$connection_map['internal'][ (int) $connection->site->blog_id ] = array(
			'post_id' => (int) $destination_post_id,
			'time'    => time(),
		);

		update_post_meta( $origin_post_id, 'dt_connection_map', $connection_map );

		switch_to_blog( $this->destination_blog_id );

		return $connection_map;
	}

	/**
	 * Helper: Set site ID's to use
	 */
	function set_site_ids( $connection ) {

		$this->connection = $connection;

		if( ! $this->destination_blog_id ) {
				$this->destination_blog_id = $connection->site->blog_id;

				// Switch to origin to get id
				restore_current_blog();
		}
		
		if ( ! $this->origin_blog_id ) {
				$this->origin_blog_id = get_current_blog_id();

				// Go back
				switch_to_blog( $this->destination_blog_id );
		}
	}
}