<?php
/**
 * Plugin Name: Distributor Meta Data
 * Plugin URI: https://github.com/burovoordeboeg/distributor-meta-data
 * Description: Push and replace related post_id's in meta data and Gutenberg content blocks. 
 * Version: 0.2.0
 * Author: Buro voor de Boeg
 * Author URI: https://www.burovoordeboeg.nl
 * License: MIT
 * Requires PHP: 7.3
 * Tested up to: 5.6
 * Test up to Distributor: 1.6.2
 */

namespace BvdB\Distributor;

if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
    return;
}

// Autoload all the needed classes
if ( ! class_exists(Setup::class) && is_file( __DIR__ . '/vendor/autoload.php' ) ) {
    require_once __DIR__ . '/vendor/autoload.php';
}

/**
 * Distribution: Setup content distribtion logica
 */
add_action( 'plugins_loaded', function(){
    
    // Configurate some default Distributor options
    ( new Config() )->register_hooks();
});

/**
 * Distributor: Change content on save.
 */
add_action( 'save_post', function( $post_id ){

    // Alter the_content while pushing posts
    ( new InternalConnections\PostContent() )->register_hooks();

    // Migrate post_ids located in (ACF) custom meta fields
    ( new InternalConnections\PostMeta() )->register_hooks();

    // Migrate post_id's Block located in Gutenberg blocks (the_content)
    ( new InternalConnections\BlockMeta() )->register_hooks();
});
