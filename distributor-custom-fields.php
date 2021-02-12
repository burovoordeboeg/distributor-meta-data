<?php
/**
 * Plugin Name: Distributor Custom Fields
 * Plugin URI: https://github.com/burovoordeboeg/distributor-custom-fields
 * Description: Push and replace related post_id's in meta data and Gutenberg content blocks. 
 * Version: 0.1.0
 * Author: Buro voor de Boeg
 * Author URI: https://www.burovoordeboeg.nl
 * License: MIT
 * Requires PHP: 7.3
 * Tested up to: 5.6
 */

namespace BvdB\Distributor;

// This will add the `ray()` function
if ( ! class_exists(Setup::class) && is_file( __DIR__ . '/vendor/autoload.php' ) ) {
    require_once __DIR__ . '/vendor/autoload.php';
}

/**
 * Distribution: Setup content distribtion logica
 */
( new Setup() )->register_hooks();