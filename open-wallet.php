<?php
/*
Plugin Name: openWallet
Plugin URI: http://windyroad.org/software/wordpress/live-preview
Description: Allows users to login to your site using <a href="http://windyroad.org/openwallet/">openWallet</a>
Version: 0.0.1
Author: Windy Road
Author URI: http://windyroad.org

Copyright (C)2007 Windy Road

This program is free software; you can redistribute it and/or
modify it under the terms of the GNU General Public License
as published by the Free Software Foundation; either version 2
of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.This work is licensed under a Creative Commons Attribution 2.5 Australia License http://creativecommons.org/licenses/by/2.5/au/

*/ 

define( OW_URL, "http://localhost:7274/" );
define( OW_KEY_TABLE, 'openwallet_keyid');

function open_wallet_handle_action() {
	if( isset( $_GET[ 'ow-action' ] ) ) {
		$args = array();
		$args[ 'ow-nonce' ] = substr(wp_hash(ceil(time()) . $args[ 'ow-action' ]), -12, 10);
		session_start();
		$_SESSION[ 'ow-nonce' ] = $args[ 'ow-nonce' ];
		$args[ 'ow-url' ] = get_bloginfo( 'url' );
		$url = OW_URL;
		if( $_GET[ 'ow-action' ] == 'login' ) {
			$url .= 'login/';
		}
		else if( $_GET[ 'ow-action' ] == 'upload-key' ) {
			$url .= 'requestkey/';
		}
		$url = add_query_arg( $args, $url );
		wp_redirect($url);
		exit;
	}
	if( isset( $_GET[ 'ow-login' ] ) ) {
		open_wallet_verify_user();
	}	
	if( isset( $_GET[ 'ow-keyupload' ] ) ) {
		open_wallet_store_key();
	}	
}

function open_wallet_verify_sig() {
    $output = array();
    $result = 0;

    $data_fn = tempnam("c:\\temp","ow-");
    $data_f = fopen($data_fn, "w");
    session_start();
    fwrite( $data_f, get_bloginfo('url') . $_SESSION[ 'ow-nonce' ] . "\n" );
    // each nonce is for one use only, so once we have read it,
    // we can remove it from the session.
    unset( $_SESSION[ 'ow-nonce'] );
    session_write_close();
    fclose( $data_f );

    $sig_fn = tempnam("c:\\temp","ow-");
    $sig_f = fopen($sig_fn, "w");
    fwrite( $sig_f, $_GET[ 'ow-sig' ] );
    fclose( $sig_f );
	$cmd = open_wallet_get_gpg() . ' ' . open_wallet_get_gpg_homedir_param() . ' --batch --no-tty --verbose --verbose --verify  ' . $sig_fn . ' ' . $data_fn . ' 2>&1'; 
    exec( $cmd, $output, $result );

//	echo "<pre>";
//	print_r( $_GET );
//	print_r( $_SESSION );
//	print_r( "<br/>cmd: " . $cmd . "<br/>" );
//	print_r( "result: " . $result . "<br/>" );
//	print_r( $output ); exit;

    unlink( $data_fn );
    unlink( $sig_fn );


    if( $result == 0 )
    {
	    $words = explode( ' ', $output[ 2 ] );
	    $id = $words[ count( $words )-1 ];
	    
//		echo "<pre>";
//		print_r( "<br/>id: " . $id . "<br/>" );
//		print_r( "<br/>cmd: " . $cmd . "<br/>" );
//		print_r( "result: " . $result . "<br/>" );
//		print_r( $output ); exit;

		// woohoo!  verified the user
		return $id;
    }
    else
    {
		if( count( $output ) == 11 )
		{
		    $words = explode( ' ', $output[ 10 ] );
		    if( $words[ count( $words )-1 ] == "found" )
		    {
		    	// we don't have the users public key, go fetch
				$url = '/?ow-action=upload-key/';
				wp_redirect($url);
				exit();
		    }
		    else if( false && $words[ 1 ] == "BAD" )
		    {
				// TODO: pretty error message.
				echo "Sorry, we could not verify your login.";
				exit();
		    }
		}
		else if( count( $output ) == 1 ) {
		    $words = explode( ' ', $output[ 0 ] );
			if( $words[ 3 ] == "failed:" ) {
				echo "Sorry, we could not verify your login.";
				exit();
			}						
		}
		open_wallet_failed( $result, $output );
    }
}


function open_wallet_verify_user() {
	$id = open_wallet_verify_sig();
	open_wallet_login( $id );
}

function open_wallet_failed( $result, $output ) {
	header('HTTP/1.1 500 Internal Server Error' );
    header('Status: 500 Internal Server Error' );
	
	?><h1>500 Internal Server Error</h1><?php
	?><h2>Unexpected GnuPG Output</h2><?php
	echo "<pre>";
	print_r( "result: " . $result . "<br/>" );
	print_r( "output:<br/>" );
	print_r( $output );
	echo "<pre>";
	exit;
}

function open_wallet_store_key()
{
    $output = array();
    $result = 0;

    $tempfn = tempnam("c:\\temp","ow-");
    $fh = fopen($tempfn, "w");
    fwrite( $fh, $_GET[ 'ow-key' ]);
    fclose( $fh );

//    if( isLoggedIn() )
//    {
//	exec( GPG . ' ' . GPG_HOMEDIR . ' --batch --no-tty --yes --delete-keys ' . $_SESSION[ 'id' ] . ' 2>&1', $output2, $result );
//        $_SESSION[ 'output2' ] = $output2;
//    }

	$cmd = open_wallet_get_gpg() . ' ' . open_wallet_get_gpg_homedir_param() . ' --batch --no-tty --import --verbose --verbose ' . $tempfn . ' 2>&1';
	exec( $cmd, $output, $result );

//	echo "<pre>";
//	print_r( $_GET[ 'ow-key' ] );
//	print_r( "<br/>cmd: " . $cmd . "<br/>" );
//	print_r( "result: " . $result . "<br/>" );
//	print_r( $output ); exit;


    unlink( $tempfn );

    if( $result == 0 )
    {
    	get_currentuserinfo();
    	global $current_user;
    	if( $current_user->ID == 0 ) {
    		// user is not yet logged in.
    		// go and verify the signature.
			open_wallet_verify_user();
    	}
    	else {
    		// firstly, lets verify that the signature is good,
    		// otherwise someone might be trying to trick the system
    		$key_id = open_wallet_verify_sig();
			// NOTE: for security reasons, $key_id is the key id from the signature,
			// not the key they sent us.  We can only ever trust signatures.
			    		
    		// first we need to see if this key is currently associated with
    		// another user.
    		$matching_user_id = open_wallet_find_user( $key_id );
    		if( NULL !== $matching_user_id )  {
    			// this key belongs to another user.
    			// This can occur if the user has multiple accounts on
    			// this blog and they are trying to associate the key
    			// with both accounts.
    			// TODO: pretty error message
    			echo "Error: this key is already associated with another account";
    			exit();
    		}
    		else {
    			// associate the key with the current user.
				global $wpdb, $current_user;
				$user_id = $current_user->ID;
				$table_name = $wpdb->prefix . OW_KEY_TABLE;
				$wpdb->query("INSERT INTO {$table_name} (user_id, key_id) VALUES ({$user_id}, '" . $wpdb->escape($key_id) . "')");
				wp_redirect("/wordpress/wp-admin/profile.php");		
				exit;
    		}
    	}
    }
    else
    {
		open_wallet_failed( $result, $output );
    }
}

function open_wallet_do_login( $user_id ) {
	$user = new WP_User( $user_id );
				
	if( wp_login( $user->user_login, md5($user->user_pass), true ) ) {
		do_action('wp_login', $user->user_login);
		wp_clearcookie();
		wp_setcookie($user->user_login, md5($user->user_pass), true, '', '', true);
		wp_redirect("/wordpress/wp-admin/profile.php");
		exit;
	}
}

function open_wallet_find_user( $key_id ) {
	global $wpdb;
	$table_name = $wpdb->prefix . OW_KEY_TABLE;
	$row = $wpdb->get_row("SELECT user_ID FROM {$table_name} WHERE key_id = '" . $wpdb->escape($key_id) . "' LIMIT 1");
	return $row->user_ID;				
}

function open_wallet_get_user_keys() {
	global $current_user;
	$user_id = $current_user->ID;
	global $wpdb;
	$table_name = $wpdb->prefix . OW_KEY_TABLE;
	$results = $wpdb->get_results("SELECT key_id FROM {$table_name} WHERE user_ID = " . $wpdb->escape($user_id));
	$rval = array();
	foreach( $results as $row ) {
		$rval[] = $row->key_id;
	}
	return $rval;				
}

function open_wallet_create_user( $key_id ) {
	@include_once( ABSPATH . 'wp-admin/upgrade-functions.php');	// 2.1
 	@include_once( ABSPATH . WPINC . '/registration-functions.php'); // 2.0.4
			
	$username = $key_id;
	$password = substr( md5( uniqid( microtime() ) ), 0, 7);
			
	$user_id = wp_create_user( $username, $password );
	global $wpdb;
	$table_name = $wpdb->prefix . OW_KEY_TABLE;
	$wpdb->query("INSERT INTO {$table_name} (user_id, key_id) VALUES ({$user_id}, '" . $wpdb->escape($key_id) . "')");
	
	if( $user_id ) {	// created ok
		update_usermeta( $user_id, 'registered_with_openwallet', true );

		$user = new WP_User( $user_id );

		if( wp_login( $user->user_login, md5($user->user_pass), true ) ) {
			do_action('wp_login', $user->user_login);

			// Call the usual user-registration hooks
			do_action('user_register', $user_id);
			wp_new_user_notification( $user->user_login );

			wp_clearcookie();
			wp_setcookie($user->user_login, md5($user->user_pass), true, '', '', true);
			wp_redirect("/wordpress/wp-admin/profile.php");
			exit;
		}
		else {
			// failed to create user for some reason.
			echo "openWallet authentication successful, but failed to login the Wordpress user. This is probably a bug.";
			exit;				
		}
	} 
	else {
		// failed to create user for some reason.
		echo "openWallet authentication successful, but failed to create Wordpress user. This is probably a bug.";
		exit;
	}
	
}

function open_wallet_login( $id ) {
	$matching_user_id = open_wallet_find_user( $id );
	if( NULL !== $matching_user_id ) {
		open_wallet_do_login( $matching_user_id );
	}
	else {
		// key id is new, so create a new user
		open_wallet_create_user( $id );
	}
}


add_action('init', 'open_wallet_handle_action' );


function open_wallet_register_link( $link ) {
	if ( ! is_user_logged_in() && get_option('users_can_register') ) {
		$link = str_replace("<a ", '<a id="register-link" ', $link);	
		$link .= '<script type="text/javascript">update_register_link();</script>';
	}
	return $link;
}
add_filter( 'register', 'open_wallet_register_link');

function open_wallet_loginout_link( $link ) {
	if ( ! is_user_logged_in() ) {
		$oldlink = str_replace("<a ", '<a id="loginout-link" ', $link);	
		$oldlink = str_replace("Login", 'Legacy Login', $oldlink);	
		$link = '<a id="loginout-link-ow" href="/?ow-action=login">Login with openWallet</a></li><li>' . $oldlink;	
		$link .= '</li><li id="get-ow-link"><a href="http://windyroad.org/openwallet/">Get openWallet</a>';	
		$link .= '<script type="text/javascript">update_loginout_link();</script>';
	}
	return $link;
}
add_filter( 'loginout', 'open_wallet_loginout_link');


function open_wallet_loginout_script() {
	wp_enqueue_script( 'open-wallet', '/wp-content/plugins/open-wallet/open-wallet.js' );
	$deps = array();
	$deps[] = 'open-wallet';
	// we add the time because we don't want the browser to cache the ping script'
	wp_enqueue_script( 'open-wallet-ping', OW_URL . 'ping.js?time=' . time(), $deps );
}

add_action( 'wp_print_scripts', 'open_wallet_loginout_script');

function openwallet_install() {
	global $wpdb;
	$table_name = $wpdb->prefix . 'openwallet_keyid';
	
	if($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
		$query = <<<OWDATA
CREATE TABLE `{$table_name}` (
  `user_ID` bigint(20) NOT NULL,
  `key_id` varchar(60) NOT NULL,
  PRIMARY KEY  (`user_ID`,`key_id`)
);
OWDATA;
	    require_once(ABSPATH . 'wp-admin/upgrade-functions.php');
    	dbDelta($query);
		add_option("open_wallet_db_version", "0.0.0");
	}
}

// installing openWallet
add_action('activate_open-wallet/open-wallet.php', 'openwallet_install');
//add_action('init', 'openwallet_install');


function open_wallet_login_form( $text ) {
	?><p style="text-align: center;">Or<br/><?php
	?><strong style="font-size: 200%; line-height: 110%;"><a style="color: white; " href="/?ow-action=login">Login Using openWallet &raquo;<a/></strong><br/><?php
	?><a style="float: right; color: white;" title="What is openWallet?" href="http://windyroad.org/openwallet/">What is openWallet?</a><?php
	?></p><?php
}
add_filter( 'login_form', 'open_wallet_login_form' );


function open_wallet_user_profile() {
?>
<fieldset>
<legend>openWallet Keys</legend>
<p class="desc">You can add <a href="http://windyroad.org/openwallet/">openWallet</a> keys that are associated with your account.</p>
<?php
	$url = '/?ow-action=upload-key';
?>
<a href="<?php echo $url; ?>">Upload Key &raquo;</a>
<p><label for="ow-keys">Current Key(s):</label></p>
<ul id="ow-keys">
<?php
$keys = open_wallet_get_user_keys();
foreach( $keys as $key ) {
	?><li><?php echo $key; ?></li><?php;	
}
?>
</ul>
</fieldset>
<?php
}

add_action( 'show_user_profile', 'open_wallet_user_profile' );


if ( !function_exists('wp_nonce_field') ) {
	define('OPEN_WALLET_NONCE', -1);
    function open_wallet_nonce_field() { return; }        
} 
else {
	define('OPEN_WALLET_NONCE', 'open_wallet-update-key');
    function open_wallet_nonce_field() { return wp_nonce_field(OPEN_WALLET_NONCE); }
}



function open_wallet_options_page() { 
	$updated = false;
	$options = get_option('open_wallet_options');
	if ( isset($_POST['submit']) 
		&& isset($_POST['action']) 
		&& $_POST['action'] == 'open_wallet_save_options' ) {

			
	    if ( function_exists('current_user_can') && !current_user_can('manage_options') )
	      die(__('Cheatin’ uh?'));
	
	    check_admin_referer(OPEN_WALLET_NONCE);
	
		$options = open_wallet_save_options( $options );
		$updated = true;
	}
	else {
		$options = get_option('open_wallet_options');
	}
	if($updated){
		?><div class="updated"><p><strong>Options saved.</strong></p></div><?php
 	}
    ?><div class="wrap" id="open_wallet-options"><?php
		?><h2>Open Wallet Options</h2><?php
		?><form method="post" action="<?php echo $_SERVER['PHP_SELF']."?".$_SERVER['QUERY_STRING']; ?>"><?php
			?><fieldset><?php
				?><input type="hidden" name="action" value="open_wallet_save_options" /><?php
								
				?><p><label for="open_wallet_gpg" style="font-weight: bold;">Path to GPG executable</label><br/><?php
				?><input type="text" name="open_wallet_gpg" value="<?php echo htmlspecialchars( open_wallet_get_gpg() ); ?>" style="width: 100%;"/><br/><?php
				?>Change this value if the gpg exectuable is not in the PATH. e.g., '"C:\\Program Files\\GNU\\GnuPG\\gpg.exe"' on Windows.</p><?php
				
				?><p><label for="open_wallet_gpg_homedir" style="font-weight: bold;">GPG home directory</label><br/><?php
				?><input type="text" name="open_wallet_gpg_homedir" value="<?php echo htmlspecialchars( open_wallet_get_gpg_homedir() ); ?>" style="width: 100%;"/><br/><?php
				?>If you canot write to the default GPG home directory, change this value to a directory that does have write access. e.g., '/home/foobar/_priv/gpg'<br /><?php
				?><strong>Note:</strong>Do not specify a home directory that within your web root.</p><?php

				open_wallet_nonce_field();
			?></fieldset><?php
			?><p class="submit"><?php
				?><input type="submit" name="submit" value="Update Options &raquo;" /><?php
			?></p><?php
		?></form><?php
	?></div><?php
}

function open_wallet_get_gpg() {
	$open_wallet_options = get_option('open_wallet_options');
	if( empty($open_wallet_options) || !isset( $open_wallet_options['gpg'] ) ) {
		return "gpg";
	}		
	else {
		return $open_wallet_options['gpg'];
	}	
}

function open_wallet_get_gpg_homedir() {
	$open_wallet_options = get_option('open_wallet_options');
	if( empty($open_wallet_options) || !isset( $open_wallet_options['gpg_homedir'] ) ) {
		return null;
	}		
	else {
		return $open_wallet_options['gpg_homedir'];
	}		
}

function open_wallet_get_gpg_homedir_param() {
	$home = open_wallet_get_gpg_homedir();
	if( !empty( $home ) ) return '--homedir ' . $home;
	return "";
}

function open_wallet_add_admin() {
	// Add a new menu under Options:
	add_options_page('openWallet', 'openWallet', 8, basename(__FILE__), 'open_wallet_options_page');
}

add_action('admin_menu', 'open_wallet_add_admin'); 		// Insert the Admin panel.

function open_wallet_process_options() {
	$curr_options = get_option('open_wallet_options');
	if ( isset($_POST['submit']) 
		&& isset($_POST['action']) 
		&& $_POST['action'] == 'open_wallet_save_options' ) {

			
	    if ( function_exists('current_user_can') && !current_user_can('manage_options') )
	      die(__('Cheatin’ uh?'));
	
	    check_admin_referer(OPEN_WALLET_NONCE);
	
		open_wallet_save_options( $curr_options );
	}	
}

add_action('init', 'open_wallet_process_options'); //Process the post options for the admin page.

function open_wallet_save_options( $curr_options ) {
	// create array
	$open_wallet_options = array();
	$open_wallet_options['gpg'] = stripslashes( $_POST['open_wallet_gpg'] );
	$open_wallet_options['gpg_homedir'] = stripslashes( $_POST['open_wallet_gpg_homedir'] );
	if( $curr_options != $open_wallet_options )
		update_option('open_wallet_options', $open_wallet_options);
	return $open_wallet_options;
}

?>