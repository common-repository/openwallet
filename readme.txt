=== openWallet ===
Contributors: tompahoward
Donate link: http://windyroad.org/openwallet/#donate
Tags: security, login, openpgp, Windy Road
Requires at least: 2.2
Tested up to: 2.2
Stable tag: 0.0.1

openWallet is a login system for websites, which replaces user names and passwords with digital keys and a password.

== Description ==

openWallet is a login system for websites, which replaces user names and passwords with digital keys and a password.

What makes openWallet so great is that you no longer have to remember what your user name for each site is (hmm... did I use JohnSmith or JohnSmith76 for my ebay login?) and your password is the same regardless of the site.

For the security conscious out there, you'll be pleased to know that openWallet never sends your password over the net and is highly resilient to phishing.

== Installation ==

1. copy the 'openwallet' directory to your 'wp-contents/plugins' directory.
1. Activate the openWallet plugin in your plugins administration page.
1. Users can now login to the site using the openWallet client.

== Frequently Asked Questions ==

= How does openWallet Work? =

openWallet is made up of two main pieces, a client that you run on your computer and a server component that runs on the website.

When you go to login to a site using openWallet, your browser is redirected a page on the openWallet client running on your computer. This page will tell you what site is asking for authentication and ask for your password.

When you enter your password, it is used with your private key (openWallet will create this for you) to digitally sign some data that the website sent to your openWallet client. This signature is sent back to the website as your login.

The website uses your public key (openWallet will create this for you as well) to verify the signature and log you in.

= Isn't using the same password for lots of sites dangerous? =

Normally, yes. If for instance you used the same user name and password on this site as you do on paypal, then we could try to login to your paypal account using the details you have given to us. However with openWallet, in order to gain access to your account another site, I would need your password and your private key. openWallet never sends either of these across the net, so it's a lot harder for a website administrator to gain access to this information.

= How does the website know my public key? =

When the website tries to verify your signature, if it cannot find your public key, it will ask your openWallet client for it. You can choose to accept or reject the request.

= How can I be sure that openWallet is secure? =

Well, unless you can read code and understand public key cryptography, you'll just have to take my word for it. Otherwise, if you have these skills, openWallet is completely Open Source (GPL), so feel free to check out the code and see if there are problems we have missed.

= I'm a geek, how does it work? =

openWallet is based on OpenPGP. When you try to login with openWallet, the openWallet website generates a cryptographic nonce, stores it in your session and sends a http redirect to the openWallet client, specifying the site's URL and the nonce in the query string. The openWallet client uses your password (well really it's a passphrase) in combination with your private key to sign the URL and the nonce. The openWallet client then sends a http redirect to the URL that was just signed with the signature in the query string. The website uses your public key to verify the signature against the nonce in your session and the URL of the site. Signing the URL prevents a signature for paypal allowing access to ebay and is openWallets primary defense against phishing attacks. The nonce is then removed from the session to prevent the signature from being reused by another party. The ID of the key used to create the signature is extracted from the signature and a lookup is performed to see if the key ID belongs to a users. If the lookup is successful, then the user web site logs the user in.

= I'm a geek, how does key upload work? =

When an openWallet website needs to upload your public key, the site generates a cryptographic nonce, stores it in your session and sends a http redirect to the openWallet client, specifying the site's URL, the nonce and optionally the ID of the key it's requesting in the query string. The openWallet client uses your password (well really it's a passphrase) in combination with your private key to sign the URL and the nonce. The openWallet client then sends a http redirect to the URL that was just signed with the signature and your public key in the query string. The website uses the public key to verify the signature against the nonce in your session and the URL of the site. Providing a signature with the public key provides proof that you own the key.

= Why does the key have a primary ID of 'openWallet' and not my name and email address? =

openWallet is very conscious of your privacy. If we put your name and emails address in your key, then sites can access this information from the signature when you login. If they want this information, then they should explicitly ask you for it.

= I've used openWallet to login to a new site and it's saying my user ID is FD8...ADA. What on earth is that? =

That is your openWallet ID. It's unique to you and is created when openWallet creates your keys. Websites use you openWallet ID to identify you in the same way that they would normally identify you with a user name or email address.

== Release Notes ==
* 0.0.1
	* Fixed defect in retrieving from the database.
* 0.0.0 
	* Initial Release