<?php

/*

Jappix - An open social platform
This is the Register API

-------------------------------------------------

License: AGPL
Author: Valérian Saliou
Last revision: 19/02/13

*/

// PHP base
define('JAPPIX_BASE', '..');

// Start PHP session (for CAPTCHA check)
session_start();

// Get the configuration
require_once('./functions.php');
require_once('./read-main.php');
require_once('./read-hosts.php');

// Optimize the page rendering
hideErrors();
compressThis();

// Headers
header('Content-Type: text/xml');

// API vars
$xml_output = null;
$error = false;
$error_reason = 'none';

// Get POST data
$query_id = isset($_POST['id']) ? trim($_POST['id']) : 'none';

// Not enabled?
if(REGISTER_API == 'on') {
	// Get POST data
	$username = isset($_POST['username']) ? trim($_POST['username']) : null;
	$password = isset($_POST['password']) ? trim($_POST['password']) : null;
	$domain = isset($_POST['domain']) ? trim($_POST['domain']) : null;
	$captcha = isset($_POST['captcha']) ? trim($_POST['captcha']) : null;
	
	// Enough data?
	if(!$username || !$password || !$domain || !$captcha) {
		$error = true;
		
		if(!$username)
			$error_reason = 'Username POST Field Missing';
		else if(!$password)
			$error_reason = 'Password POST Field Missing';
		else if(!$domain)
			$error_reason = 'Domain POST Field Missing';
		else if(!$captcha)
			$error_reason = 'CAPTCHA POST Field Missing';
		else
			$error_reason = 'POST Field Missing';
	} else if($domain != HOST_MAIN) {
		$error = true;
		$error_reason = 'Domain Not Allowed';
	} else if(!isset($_SESSION['captcha'])) {
		$error = true;
		$error_reason = 'CAPTCHA Session Missing';
	} else if(strtolower(trim($captcha)) != strtolower(trim($_SESSION['captcha']))) {
		$error = true;
		$error_reason = 'CAPTCHA Not Matching';
	} else {
		// Which command to execute?
		$command_str = null;
		
		if(XMPPD == 'ejabberd') {
			$xmppd_ctl = XMPPD_CTL ? XMPPD_CTL : 'ejabberdctl';
			
			// TODO
			$command_str = $xmppd_ctl.' register '.escapeshellarg($username).' '.escapeshellarg($domain).' '.escapeshellarg($password);
			
			//} else {
				$error = true;
				$error_reason = 'Username Unavailable';
			//}
		} else if(XMPPD == 'prosody') {
			$xmppd_ctl = XMPPD_CTL ? XMPPD_CTL : 'prosodyctl';
			
			$command_str = $xmppd_ctl.' adduser '.escapeshellarg($username).'@'.escapeshellarg($domain);
			
			// TODO: raw output password and press enter (simulate command-line interaction)
		} else {
			$error = true;
			$error_reason = 'Unsupported XMPP Daemon';
		}
		
		// Execute command
		if($command_str) {
			// Status vars
			$command_output = array();
			$command_return = 0;
			
			// Here we go
			exec($command_str, $command_output, $command_return);
		} else {
			$error = true;
			$error_reason = 'No Command To Execute';
		}
	}
	
	// Remove CAPTCHA
	if(isset($_SESSION['captcha']))
		unset($_SESSION['captcha']);
} else {
	$error = true;
	$error_reason = 'API Disabled';
}

// Generate the response
$status_code = '1';
$status_message = 'Success';

if($error) {
	$status_code = '0';
	$status_message = 'Unknown error';
	
	if($error_reason)
		$status_message = $error_reason;
}

$api_response = '<jappix xmlns="jappix:account:register">';
	$api_response .= '<query id="'.htmlEntities($query_id, ENT_QUOTES).'">';
		$api_response .= '<status>'.htmlspecialchars($status_code).'</status>';
		$api_response .= '<message>'.htmlspecialchars($status_message).'</message>';
	$api_response .= '</query>';
	
	if($xml_output) {
		$api_response .= '<data>';
			$api_response .= $xml_output;
		$api_response .= '</data>';
	}
$api_response .= '</jappix>';

exit($api_response);

?>