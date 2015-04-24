<?php
/*
Plugin Name: BP Local Avatars
Description: Requires BuddyPress. Adds an option to create Identicon Avatars and store them locally.
Version: 1.6
Author: PhiloPress
Author URI: http://philopress.com/
*/

// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) exit;

function pp_local_avatars_bp_check() {
	if ( !class_exists('BuddyPress') ) {
		add_action( 'admin_notices', 'pp_local_avatars_install_buddypress_notice' );
	}
}
add_action('plugins_loaded', 'pp_local_avatars_bp_check', 999);

function pp_local_avatars_install_buddypress_notice() {
	echo '<div id="message" class="error fade"><p style="line-height: 150%">';
	_e('<strong>BP Local Avatars</strong></a> requires the BuddyPress plugin. Please <a href="http://buddypress.org/download">install BuddyPress</a> first, or <a href="plugins.php">deactivate BP Local Avatars</a>.');
	echo '</p></div>';
}


function pp_local_avatars_init() {
    require( dirname( __FILE__ ) . '/pp-local-avatars.php' );
}
add_action( 'bp_include', 'pp_local_avatars_init' );


// if Default Avatar was set to BuddyPress Identicon, reset default avatar to Mystery to prevent broken avatar icons
function pp_local_avatars_deactivation () {
	
	$default_avatar = get_option('avatar_default');

	if( $default_avatar == 'identicon_local' )
		update_option( 'avatar_default', 'mystery' );

}
register_deactivation_hook(__FILE__, 'pp_local_avatars_deactivation');
