<?php
namespace BvdB\Distributor\InternalConnections;

use BvdB\Distributor\InternalConnections\Utilities as Utilities;

class AbstractMeta {

	var $connection;
	var $origin_blog_id = false;
	var $destination_blog_id = false;

	/**
	 * Search for a Post by meta_key "dt_original_post_id" in the destination subsite.
	 * Otherwise push a new Post from the origin to the destination site.
	 * 
	 * @var $post_id is the origin Post ID
	 */
	public function create_or_get_destination_id( $post_id ) {

		\switch_to_blog( $this->origin_blog_id );
		$pt = get_post_type( $post_id );
		\switch_to_blog( $this->destination_blog_id );

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

		$this->destination_blog_id = $connection->site->blog_id;

		// Switch to origin to get that blog_id
		restore_current_blog();

		$this->origin_blog_id = get_current_blog_id();

		// Go back as we were
		switch_to_blog( $this->destination_blog_id );
	}
}