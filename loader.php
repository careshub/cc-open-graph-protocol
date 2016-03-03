<?php
/*
Plugin Name: CC Open Graph Headers
Description: Add Open Graph meta tags to page headers.
Version: 1.0.0
Author: David Cavins
Licence: GPLv3
*/


/**
 * Creates instance of CC_Open_Graph
 * This is where most of the running gears are.
 *
 * @package CC Open Graph
 * @since 1.0.0
 */

function cc_open_graph_class_init(){
	// Get the class fired up
	require( dirname( __FILE__ ) . '/class-cc-open-graph.php' );
	add_action( 'init', array( 'CC_Open_Graph', 'get_instance' ), 11 );
}
add_action( 'init', 'cc_open_graph_class_init' );