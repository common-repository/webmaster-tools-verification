<?php
/*
Plugin Name: Webmaster Tools Verification
Plugin URI: http://www.jamespegram.com/webmaster-tools-verification
Description: All three of the major search engines have some type of website service. This plugin allows you to easily add the ability to verify your site with Google Webmaster Tools, Yahoo Site Explorer and Bing Webmaster Center. Works with both Wordpress and Wordpress MU
Author: James Pegram
Version: 1.2
Author URI: http://www.jamespegram.com
*/

/*  Copyright 2009-2011  
	
    James Pegram (email : jwpegram [make-an-at] gmail [make-a-dot] com)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

define('WMTV_VERSION', '1.2');	// Current version of the Plugin
define('WMTV_NAME', 'Webmaster Tools Verification');	// Name of the Plugin

define ('WMTV_WP_ROLE','manage_options');


// Establish a few variables we may want to use later.
$wmtv_path       = preg_replace('/^.*wp-content[\\\\\/]plugins[\\\\\/]/', '', __FILE__);
$wmtv_path       = str_replace('\\','/',$wmtv_path);
$wmtv_fullpath   = $wmtv_siteurl.'/wp-content/plugins/'.substr($wmtv_path,0,strrpos($wmtv_path,'/')).'/';

register_activation_hook( __FILE__, 'wmtv_activate' );
register_uninstall_hook(__FILE__, 'wmtv_uninstall' );

if ( isset( $_POST['wmtv_uninstall'], $_POST['wmtv_uninstall_confirm'] ) ) { wmtv_uninstall(); }

add_action( 'init', 'wmtv_init' );
add_action('admin_menu', 'wmtv_admin_options');
add_action('wp_head', 'wmtvools_add_meta',99);
//wmtv_admin_warnings();


if ($_GET['page'] == 'wmtv') {
	wp_register_style('wmtv.css', $wmtv_fullpath . 'wmtv.css');
	wp_enqueue_style('wmtv.css');
}



// Initialize plugin
function wmtv_init() {
	if ( function_exists( 'load_plugin_textdomain' ) ) {
		load_plugin_textdomain( 'webmaster-tools', PLUGINDIR.'/'.dirname(plugin_basename(__FILE__)) );
	}
}


function wmtvools_add_meta() {
	
	$options = get_option('wmtv_options');
	
if ($options['google'] != '') { ?>
<meta name="google-site-verification" content="<?php echo $options['google']; ?>" />
<?php } if ($options['yahoo'] != '') { ?>
<meta name="y_key" content="<?php echo $options['yahoo']; ?>" />
<?php } if ($options['bing'] != '') { ?>
<meta name="msvalidate.01" content="<?php echo $options['bing']; ?>" />
<?php } 
}


/*
============================================
ADMIN
============================================
*/

function wmtv_activate() {

	$default_options = array( 
		'google' => '',
		'yahoo' => '',
		'bing' => '',
		);
		
	add_option('wmtv_options', $default_options);
	update_option('wmtv_version', WMTV_VERSION);

	return true;	
}


function wmtv_admin_options() {
	if ( function_exists('add_management_page') ) {
		add_options_page('Webmaster Tools Verification', 'Webmaster Tools Verification', 'manage_options', 'wmtv', 'wmtv_admin_settings');

		//call register settings function
		add_action( 'admin_init', 'wmtv_register_settings' );
		
	}
}


// Let's do a bit of validation on the submitted values, just in case something strange got submitted
function wmtv_options_validate($input) {
	
	// preg_replace everything but the verification code
	preg_match('#content=\'(.*?)\'>#', $input['google'], $matchG);
	if(!empty($matchG)) { $input['google'] = $matchG[1]; }
	
	preg_match('#content=\'(.*?)\'>#', $input['yahoo'], $matchY);
	if(!empty($matchY)) { $input['yahoo'] = $matchY[1]; }	
	
	preg_match('#content=\'(.*?)\'>#', $input['bing'], $matchB);
	if(!empty($matchB)) { $input['bing'] = $matchB[1]; }	

	$options = get_option('wmtv_options');
	$options['google'] = wp_filter_kses($input['google']);
	$options['yahoo'] = wp_filter_kses($input['yahoo']);
	$options['bing'] = wp_filter_kses($input['bing']);
	return $options;
}


// Administration menu
function wmtv_admin_settings() {
	
	global $wmtv_fullpath;

    // Check that the user has the required permission level 
    if (!current_user_can('manage_options')) { wp_die( __('You do not have sufficient permissions to access this page.') ); }

    
    $options = get_option('wmtv_options');
?>
<div class="wrap">
<div id="icon-options-general" class="icon32"><br /></div>
<h2><?php echo WMTV_NAME .' ( v.'. WMTV_VERSION .' )'; ?></h2>

<?php

    wmtv_admin_message();
     
    
    ?>

	<div class="postbox-container" style="width: 70%;">
	<div class="metabox-holder">	
	<div class="meta-box-sortables">
	
	<?php if ( $_GET['module'] == 'help')  { include('help.php'); get_help(); } else {	?>
		<form method="post" id="wmtvadmin" action="options.php">
		<?php settings_fields( 'wmtv_admin_options' ); ?>
			<div class="postbox"><?php do_settings_sections( 'wmtv_settings' ); ?></div>
		
		</form>
	<?php } ?>
	</div></div>
	</div>

	<div class="postbox-container" style="width:26%;">
	<div class="metabox-holder">	
	<div class="meta-box-sortables">
		
		<?php wmtv_postbox_support(); ?>	
		<?php wmtv_postbox_uninstall(); ?>	
	
	</div></div>
	</div>
</div>

			
<?php


}

function wmtv_register_settings() {
	register_setting( 'wmtv_admin_options', 'wmtv_options','wmtv_options_validate');
	add_settings_section('wmtv_tools', 'Webmaster Tools Verification', 'wmtv_settings', 'wmtv_settings');
}

function wmtv_settings() { 

	$options = get_option('wmtv_options');
	
	echo '<div class="inside"><div class="intro">	<p>Enter your meta key "content" value to verify your blog with <a href="https://www.google.com/webmasters/tools/">Google Webmaster Tools</a>, <a href="https://siteexplorer.search.yahoo.com/">Yahoo Site Explorer</a>, and <a href="http://www.bing.com/webmaster">Bing Webmaster Center</a>
	</div>'; 
	
	echo '<fieldset>';
	echo '<dl><dt><label>Google Webmaster Tools:</label></dt>
	<dd><input name="wmtv_options[google]" type="text" size="50" value="'. $options['google'] .'" /></dd>
	<p class="sub2">Example: <code>&lt;meta name=\'google-site-verification\' content=\'<strong><font color="red">dBw5CvburAxi537Rp9qi5uG2174Vb6JwHwIRwPSLIK8</font></strong>\'&gt;</code></p></dl>';	

	echo '<dl><dt><label>Yahoo Site Explorer:</label></dt>
	<dd><input name="wmtv_options[yahoo]" type="text" size="50" value="'. $options['yahoo'] .'" /></dd>
	<p class="sub2">Example: <code>&lt;meta name=\'y_key\' content=\'<strong><font color="red">3236dee82aabe064</font></strong>\'&gt;</code></p></dl>';	

	echo '<dl><dt><label>Bing Webmaster Center:</label></dt>
	<dd><input name="wmtv_options[bing]" type="text" size="50" value="'. $options['bing'] .'" /></dd>
	<p class="sub2">Example: <code>&lt;meta name=\'msvalidate.01\' content=\'<strong><font color="red">12C1203B5086AECE94EB3A3D9830B2E</font></strong>\'&gt;</code></p></dl>';		
	
	echo '</fieldset><div style="clear:both;"></div>';
	if (get_bloginfo('version') >= '3.1') { submit_button('Save Changes','primary'); } else { echo '<input type="submit" name="submit" id="submit" class="button-primary" value="Save Changes"  />'; }		
	echo '</div>';

} 





function wmtv_admin_message() { 
	global $wmtv_fullpath;
	$options = get_option('wmtv_options');
	
	// Check to see if the CSS override is true
	if ($options['wmtv_override_css'] == true) { ?>
	
	<div style="border:2px solid #888888;margin-bottom:10px;background-color:#fefdf6;padding:2px;">
	<strong style="margin-left:5px;">Add the following CSS to your themes style sheet and customize to your liking:</strong>
	<p>
	#wmtv_form { clear:both; margin:20px 0; }<br />
	#wmtv_form label { font-size: 16px; font-weight:bold; color: #999; margin:0; padding:10px 0;}<br />
	#wmtv_form .question { font-size: 14px; font-weight:normal; margin:0; padding:5px 0;}<br />
	#wmtv_form .answer { font-size: 12px; }<br />
	#wmtv_form .notice { font-size: 11px; }
	</p>
	</div>

	<?php }

	
}


 // On uninstall all Block Spam By Math Reloaded options will be removed from database
function wmtv_uninstall() {

	delete_option( 'wmtv_version' );
	delete_option( 'wmtv_options' );

	$current = get_option('active_plugins');
	array_splice($current, array_search( $_POST['plugin'], $current), 1 ); // Array-function!
	update_option('active_plugins', $current);
	header('Location: plugins.php?deactivate=true');
}

function wmtv_build_postbox( $id, $title, $content, $ech = TRUE ) {

	$output  = '<div id="wmtv_' . $id . '" class="postbox">';
	$output .= '<div class="handlediv" title="Click to toggle"><br /></div>';
	$output .= '<h3 class="hndle"><span>' . $title . '</span></h3>';
	$output .= '<div class="inside">';
	$output .= $content;
	$output .= '</div></div>';

	if ( $ech === TRUE )
		echo $output;
	else
		return $output;

}



function wmtv_postbox_support() {
	
$output  = '<p>' . __( 'If you require support, or would like to contribute to the further development of this plugin, please choose one of the following;', 'wmtv' ) . '</p>';
	$output .= '<ul style="list-style:circle;margin-left:25px;">';
	$output .= '<li><a href="http://www.jamespegram.com/">' . __( 'Author Homepage', 'wmtv' ) . '</a></li>';
	$output .= '<li><a href="http://www.jamespegram.com/webmaster-tools-verification/">' . __( 'Plugin Homepage', 'wmtv' ) . '</a></li>';
	$output .= '<li><a href="http://wordpress.org/extend/plugins/webmaster-tools-verification/">' . __( 'Rate This Plugin', 'wmtv' ) . '</a></li>';
	$output .= '<li><a href="https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=10845671">' . __( 'Donate To The Cause', 'wmtv' ) . '</a></li>';
	$output .= '</ul>';	
	
	wmtv_build_postbox( 'display_options', __( 'Support', 'wmtv' ), $output );	
}


function wmtv_postbox_uninstall() {
	
	$output  = '<form action="" method="post">';
	$output .= '<input type="hidden" name="plugin" id="plugin" value="webmaster-tools/webmaster-tools.php" />';

	if ( isset( $_POST['wmtv_uninstall'] ) && ! isset( $_POST['wmtv_uninstall_confirm'] ) ) {
		$output .= '<p class="error">' . __( 'You must check the confirm box before continuing.', 'wmtv' ) . '</p>';
	}

	$output .= '<p>' . __( 'The options for this plugin are not removed on deactivation to ensure that no data is lost unintentionally.', 'wmtv' ) . '</p>';
	$output .= '<p>' . __( 'If you wish to remove all plugin information for your database be sure to run this uninstall utility first.', 'wmtv' ) . '</p>';
	$output .= '<p class="aside"><input type="checkbox" name="wmtv_uninstall_confirm" value="1" /> ' . __( 'Please confirm before proceeding.', 'wmtv' ) . '</p>';
	$output .= '<p class="wmtv_submit center"><input type="submit" name="wmtv_uninstall" class="button-secondary" value="' . __( 'Uninstall', 'wmtv' ) . '" /></p>';

	$output .= '</form>';
	
	wmtv_build_postbox( 'display_options', __( 'Uninstall Plugin', 'wmtv' ), $output );	
}


?>