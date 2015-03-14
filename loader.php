<?php
/*
Plugin Name: BP Local Avatars
Description: Requires BuddyPress. Adds an option to create an Identicon Avatar and store it locally.
Version: 1.5
Author: PhiloPress
Author URI: http://philopress.com/
*/

// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) exit;

function pp_local_avatars_init() {
    require( dirname( __FILE__ ) . '/pp-local-avatars.php' );
}
add_action( 'bp_include', 'pp_local_avatars_init' );
