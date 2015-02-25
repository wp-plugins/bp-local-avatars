<?php

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) exit;

// setup the additions to Settings > Discussion > Avatars
function pp_add_settings() {

	$show = get_option('show_avatars');

	if( $show ) {

		add_filter( 'avatar_defaults', 'pp_add_avatar_default_option', 11, 1 );

		add_filter( 'default_avatar_select', 'pp_add_avatar_default_option_img', 11, 1 );

		$default_avatar = get_option('avatar_default');

		if( $default_avatar == 'identicon_local' ) 
			pp_add_settings_section();

	}

}
add_action('admin_init', 'pp_add_settings');

// add an option in Settings > Discussion > Avatars
function pp_add_avatar_default_option( $avatar_defaults ) {

	$avatar_defaults['identicon_local'] =  __('BuddyPress Identicon (Generated and Stored Locally)');

	return $avatar_defaults;
}

// add an icon to the option in Settings > Discussion > Avatars
function pp_add_avatar_default_option_img( $avatar_list ) {

	$str = 'http://1.gravatar.com/avatar/1ea18284b39b7e184779ea1ddc5f4ee2?s=32&amp;d=identicon_local&amp;r=G&amp;forcedefault=1';

	$icon = plugins_url( 'icon.png', __FILE__ );

	$avatar_list = str_replace($str, $icon, $avatar_list);

	return $avatar_list;

}

// Add Bulk Generation section to Settings > Discussion > Avatars
function pp_add_settings_section() {
	add_settings_section(
		'generate_avatars',
		'Bulk Generate',
		'pp_generate_avatars_callback',
		'discussion'
	);
}

function pp_generate_avatars_callback( $arg ) {
?>
	<table class="form-table">
	<tr>
	<th scope="row"><?php _e('BuddyPress Avatars'); ?></th>
	<td><fieldset>
		<label for="gen_avatars">
			Generate Identicons and store them locally for all BuddyPress members without an Avatar. <br/><br/>
			If you have a large number of members without Avatars, <em>this may take too long</em>. <br/><br/>

		    <a href="<?php print wp_nonce_url(admin_url('options-discussion.php?task=bulk-generate'), 'bulk_gen', 'pp_nonce');?>">Generate Identicons</a>

		</label>
	</fieldset></td>
	</tr>
	</table>
<?php
}

// bulk generation of avatars from Settings > Discussion > Avatars
function pp_bulk_generation() {
	global $pp_local_avatar_instance;

	echo '<h4>Generating Avatars...</h4>';

	$users = get_users( array( 'fields' => 'ID' ) );

	foreach ( $users as $user ) {

		$pp_local_avatar_instance->create( $user );

	}

	echo '<h4>Finished.</h4>';
}


function pp_load_class() {
	global $pp_local_avatar_instance;

	$default = get_option('avatar_default');

	if( $default == 'identicon_local' )
		$pp_local_avatar_instance = new BP_Local_Avatars();


	if( isset( $_GET['task'] ) && $_GET['task'] == 'bulk-generate' ) {

		if ( ! wp_verify_nonce($_GET['pp_nonce'], 'bulk_gen') )
			die( 'Security check' );
		else 
			pp_bulk_generation();
	
	}

}
add_action( 'bp_core_set_avatar_globals', 'pp_load_class', 100 );


class BP_Local_Avatars {

	public $upload_dir;

	function __construct() {

		$this->upload_dir = bp_core_avatar_upload_path() . '/avatars';

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

	// Creates a Gravatar identicon if no local avatar exists
	public function create( $user_id ) {
		global $wpdb; 
		
		// Bail if an avatar already exists for this user
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

	// Disable Gravatar calls in bp_core_fetch_avatar()
	function no_grav() {
		return true;
	}

}