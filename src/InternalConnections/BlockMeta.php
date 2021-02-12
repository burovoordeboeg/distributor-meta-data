<?php
namespace BvdB\Distributor\InternalConnections;

class BlockMeta extends AbstractMeta {

    /**
     * Cached array of found post_id's in Block meta data (the_content).
     */
    var $post_ids;

    /**
     * Add actions
     */
    function register_hooks() {
        add_action( 'dt_push_post', array( $this, 'push_post' ), 10, 4 );
    }

    /**
     * Main function. Updates new posts to contain the correct post IDs by replacing the origin post_id's with the destination post_id's in the post_content of the destination post.
     *
     * @param  int $new_post_id      The newly created post ID.
     * @param  int $original_post_id The original post ID.
     * @param  array $args           Not used (The arguments passed into wp_insert_post.)
     * @param  object $site          The distributor connection being pushed to.
     */
    function push_post( $new_post_id, $original_post_id, $args, $connection ) {

        $this->set_site_ids( $connection );

        // Get original post content and don't reuse the destination post content, that can be old...
        \switch_to_blog( $this->origin_blog_id );
        $post = get_post( $original_post_id );
        \restore_current_blog();
        
        /**
         * Search and Replace for media post_id's within ACF Block fields.
         */
        $post_ids = $this->get_post_ids_from_blocks( $post->post_content );

        // If no ACF Blocks with attachments available, bail early
        if( empty( $post_ids ) ) {
            return $new_post_id;
        }
        
        // Loop over the ACF block field and replace origin image post_id's with destination image post_id's and save that temporary $new_post_content.
        $new_post_content = $post->post_content;

        foreach( $post_ids as $field_data ) {

            /**
             * A single media field contains 1 integer.
             * A single gallery field contains an array with numbers as strings...
             * 
             * So we always want a array from here on of to keep or logic a bit more simpler.
             */
            $post_ids = (array) $field_data['value'];
            
            foreach( $post_ids as $post_id ) {

                $destination_post_id = $this->create_or_get_destination_id( $post_id );

                if( ! is_null( $destination_post_id ) ) { 

                    /**
                     * ACF Single media field post_id's are saved without quotes.
                     * ACF Gallery field post_id's with quotes.
                     * 
                     * If we only search and replace purely on the number then it can happen that this number is present somewhere else in this content and both of the same numbers or be it a partial of a bigger number  will be replace. We don't want that.
                     */

                    if( is_int( $post_id ) ) {
                        // Search for "image: 3455,"
                        $search = '"'. $field_data['key'] .'": '. $post_id . ',' ;
                        $replace = '"'. $field_data['key'] .'": '. $destination_post_id . ',' ;
                    } else {
                        // Search for "3455"
                        $search  = (string) '"' . $post_id . '"';
                        $replace = (string) '"' . $destination_post_id . '"';
                    }

                    // Replace the origin attachment post_id with the destination post_id
                    $new_post_content = str_replace( $search, $replace, $new_post_content );
                }
            } 
        }

        // Update the post_content if it has changed, saves an insert when no replacements are done
        if( $post->post_content !== $new_post_content ) {
            
            $post_id = $post = wp_update_post( [
                'ID' => $new_post_id,
                'post_content' => $new_post_content
            ] );
        }

        return $new_post_id;
    }


    /**
     * Helper : Get media attachment post_id's from Gutenberg blocks
     */
    public function get_post_ids_from_blocks( $post_content ) {

        // If this is already called, then return this "cached" value
        if( $this->post_ids && ! empty( $this->post_ids ) ) {
            return $this->post_ids;
        }
    
        $blocks = parse_blocks( $post_content );

        $flat_post_ids = [];

        // Define the fields with an image post id which need to be distributed
        $meta_keys = apply_filters( 'distribute_block_field_keys', [] );

        foreach ( $blocks as $block ) {

            // Check if attrs.data extist
            if( isset( $block["attrs"] ) && isset( $block["attrs"]["data"] ) ) {

                foreach( $meta_keys as $meta_key ) {

                    // Check if field exists and has a value
                    if( isset( $block['attrs']['data'][ $meta_key ] ) && ! empty( $block['attrs']['data'][ $meta_key ] ) ) {                        
                        $flat_post_ids[] = [ 'key' => $meta_key, 'value' => $block['attrs']['data'][ $meta_key ] ];
                    }
                }	
            }
        }

        // Set to be "cached" between calls.
        $this->post_ids = $flat_post_ids;

        return $flat_post_ids;
    }
}