
var open_wallet_ping = false;

function update_loginout_link() {
	if( open_wallet_ping ) {
		var get_open_wallet = document.getElementById('get-ow-link');
		if( get_open_wallet != null ) {
			get_open_wallet.parentNode.removeChild( get_open_wallet );
		}
	}
	else {
		var ow_login = document.getElementById("loginout-link-ow");
		if( ow_login != null ) {
			ow_login.parentNode.removeChild( ow_login );
		}
	}
}

// registration is handled automajically by openWallet
// so there is no need to provide a link.
// We only do this if we were able to ping ow.
function update_register_link() {
	if( open_wallet_ping ) {
		var register_link = document.getElementById('register-link');
		if( register_link != null ) {
			register_link.parentNode.parentNode.removeChild( register_link.parentNode );
		}
	}
}