<?php

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) exit;


// settings in wp-admin
function pp_lc_add_settings() {

	$show = get_option('show_avatars');

	if( $show ) {

		add_filter( 'avatar_defaults', 'pp_lc_add_avatar_default_option', 11, 1 );

		add_filter( 'default_avatar_select', 'pp_lc_add_avatar_default_option_img', 11, 1 );

		$default_avatar = get_option('avatar_default');

		if( $default_avatar == 'identicon_local' )
			pp_lc_add_settings_section();

	}

}
add_action('admin_init', 'pp_lc_add_settings');

// add an option in Settings > Discussion > Avatars
function pp_lc_add_avatar_default_option( $avatar_defaults ) {

	$avatar_defaults['identicon_local'] =  __('BuddyPress Identicon (Generated and Stored Locally)');

	return $avatar_defaults;
}

// add an icon to the option in Settings > Discussion > Avatars
function pp_lc_add_avatar_default_option_img( $avatar_list ) {

	$str_array = array( 'http://0.gravatar.com/avatar/ffd294ab5833ba14aaf175f9acc71cc4?s=64&amp;d=identicon_local&amp;r=g&amp;forcedefault=1 2x', 'http://0.gravatar.com/avatar/ffd294ab5833ba14aaf175f9acc71cc4?s=32&amp;d=identicon_local&amp;r=g&amp;forcedefault=1', 'http://1.gravatar.com/avatar/1ea18284b39b7e184779ea1ddc5f4ee2?s=64&amp;d=identicon_local&amp;r=g&amp;forcedefault=1 2x',  'http://1.gravatar.com/avatar/1ea18284b39b7e184779ea1ddc5f4ee2?s=32&amp;d=identicon_local&amp;r=G&amp;forcedefault=1' );

	$icon = plugins_url( 'icon.png', __FILE__ );

	$avatar_list = str_ireplace($str_array, $icon, $avatar_list);

	return $avatar_list;

}

// Add Bulk Generation section to Settings > Discussion > Avatars
function pp_lc_add_settings_section() {
	add_settings_section(
		'generate_avatars',
		'Bulk Generate',
		'pp_lc_generate_avatars_callback',
		'discussion'
	);
}

function pp_lc_generate_avatars_callback( $arg ) {
?>
	<table class="form-table">
	<tr>
	<th scope="row"><?php _e('BuddyPress Avatars'); ?></th>
	<td><fieldset>
		<label for="gen_avatars">
			Generate Identicons and store them locally for all BP Members and Groups without an Avatar. <br/><br/>
			If you have a large number of members without Avatars, <em>this may take too long</em>. <br/><br/>

		    <a href="<?php print wp_nonce_url(admin_url('options-discussion.php?task=bulk-generate'), 'bulk_gen', 'pp_nonce');?>">Generate Identicons</a>

		</label>
	</fieldset></td>
	</tr>
	</table>
<?php
}



/**
 * create class instance
 * maybe bulk generate avatars
 * uses the bp_core_set_avatar_globals hook via bp_setup_globals
 */

function pp_lc_load_class() {
	global $wpdb, $bp;

	$default = get_option('avatar_default');

	if( $default == 'identicon_local' )
		$pp_local_avatar_instance = new PP_Local_Avatars();


	if( is_admin() ) {

		if( isset( $_GET['task'] ) && $_GET['task'] == 'bulk-generate' ) {

			if ( ! wp_verify_nonce($_GET['pp_nonce'], 'bulk_gen') )
				die( 'Security check' );

			else {

				$users = get_users( array( 'fields' => 'ID' ) );

				foreach ( $users as $user )
					$pp_local_avatar_instance->create( $user );


				$group_ids = $wpdb->get_col( "SELECT id FROM {$wpdb->prefix}bp_groups" );
				
				foreach ( $group_ids as $group_id )
					$pp_local_avatar_instance->group_create( $group_id );

				wp_redirect( admin_url( '/options-discussion.php?avs_gen=1' ) );
				exit;

			}
		}
	}
}
add_action( 'bp_core_set_avatar_globals', 'pp_lc_load_class', 100 );


function pp_lc_avatars_admin_notice() {

    if ( ! empty( $_GET['avs_gen'] ) ) {
        echo '<div class="updated"><p>Avatars have been generated.</p></div>';
    }
}
add_action('admin_notices', 'pp_lc_avatars_admin_notice');

class PP_Local_Avatars {

	private $upload_dir;
	private $group_upload_dir;
	
	function __construct() {

		$this->upload_dir = bp_core_avatar_upload_path() . '/avatars';
		$this->group_upload_dir = bp_core_avatar_upload_path() . '/group-avatars';
		
		add_action( 'wp_login', array( $this, 'login' ), 10, 2 );

		add_action( 'user_register', array( $this, 'register' ) );

		add_filter( 'bp_core_fetch_avatar_no_grav', array( $this, 'no_grav' ) );

	}

	function login( $user_login, $user ) {
		$this->create( $user->ID );
	}

	function register( $user_id ) {
		$this->create( $user_id );
	}

	// Creates an identicon if no local avatar exists
	public function create( $user_id ) {
		global $wpdb;

		// Bail if an avatar already exists for this user.
		if ( $this->has_avatar( $user_id ) )
			return;

		wp_mkdir_p( $this->upload_dir . '/' . $user_id );

		$user_email = $wpdb->get_var( "SELECT user_email FROM $wpdb->users WHERE ID = $user_id" );

		// thumbnail
		$dim = BP_AVATAR_THUMB_WIDTH;
		$url = $this->gravatar_url( $user_email, $dim, 'identicon', 'g' );

		$path = $this->upload_dir . '/' . $user_id . '/' . $user_id . '-bpthumb.jpg';
		copy($url, $path);  //NOTE:  requires allow_url_fopen set to true

		// full size
		$dim = BP_AVATAR_FULL_WIDTH;
		$url = $this->gravatar_url( $user_email, $dim, 'identicon', 'g' );

		$path = $this->upload_dir . '/' . $user_id . '/' . $user_id . '-bpfull.jpg';
		copy($url, $path);  //NOTE:  requires allow_url_fopen set to true

	}

	// Creates a Group identicon if no Group avatar exists
	public function group_create( $group_id ) {

		// Bail if an avatar already exists for this group.
		if ( $this->group_has_avatar( $group_id ) )
			return;

		wp_mkdir_p( $this->group_upload_dir . '/' . $group_id );

		$fake_email = uniqid('', true) . '@gmail.com';

		// thumbnail
		$dim = BP_AVATAR_THUMB_WIDTH;
		$url = $this->gravatar_url( $fake_email, $dim, 'identicon', 'g' );

		$path = $this->group_upload_dir . '/' . $group_id . '/' . $group_id . '-bpthumb.jpg';
		copy($url, $path);  //NOTE:  requires allow_url_fopen set to true

		// full size
		$dim = BP_AVATAR_FULL_WIDTH;
		$url = $this->gravatar_url( $fake_email, $dim, 'identicon', 'g' );

		$path = $this->group_upload_dir . '/' . $group_id . '/' . $group_id . '-bpfull.jpg';
		copy($url, $path);  //NOTE:  requires allow_url_fopen set to true

	}	
	
	
	/**
	 * Generate a Gravatar URL for a specified email address
	 * @param string $email The email address
	 * @param string $s Size in pixels, defaults to 80px [ 1 - 2048 ]
	 * @param string $d Default imageset to use [ 404 | mm | identicon | monsterid | wavatar ]
	 * @param string $r Maximum rating (inclusive) [ g | pg | r | x ]
	 * @return String containing a URL
	 * @source http://gravatar.com/site/implement/
	 */

	private function gravatar_url( $email, $s = 50, $d = 'identicon', $r = 'g' ) {

	    $url = 'http://www.gravatar.com/avatar/';
	    $url .= md5( strtolower( trim( $email ) ) );
	    $url .= ".jpg?s=$s&d=$d&r=$r";

		return $url;

	}

	// Checks if a given user has local avatar dir
	private function has_avatar( $user_id ) {

		$dir_path = $this->upload_dir . '/' . $user_id;

		if ( ! file_exists( $dir_path ) )
			return false;
		else
			return true;
	}

	// Checks if a given Group has  avatar dir
	private function group_has_avatar( $group_id ) {

		$dir_path = $this->group_upload_dir . '/' . $group_id;

		if ( ! file_exists( $dir_path ) )
			return false;
		else
			return true;
	}	
	
	// Disables Gravatar.
	function no_grav() {
		return true;
	}

}