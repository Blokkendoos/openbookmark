<?php
define ("ABSOLUTE_PATH", dirname (__FILE__) . "/");

/*
if (extension_loaded ('zlib')) {
    ob_start ('ob_gzhandler');
}
*/
require_once (ABSOLUTE_PATH . "lib/webstart.php");
if (! is_file (ABSOLUTE_PATH . "config/config.php")) {
	die ('You have to <a href="./install.php">install</a> OpenBookmark.');
}
else {
	require_once (ABSOLUTE_PATH . "config/config.php");
}
require_once (ABSOLUTE_PATH . "lib/mysql.php");
$mysql = new mysql;
require_once (ABSOLUTE_PATH . "lib/auth.php");
$auth = new Auth;
require_once (ABSOLUTE_PATH . "lib/lib.php");
require_once (ABSOLUTE_PATH . "lib/login.php");

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
	<head>
		<title>OpenBookmark</title>
		<meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
		<meta http-equiv="Pragma" content="No-cache"/>
		<meta name="viewport" content="width=device-width, initial-scale=1.0"/>  
		<link rel="stylesheet" type="text/css" href="./style.css"/>
		<?php echo (@$settings["theme"]!="") ? '<link rel="stylesheet" type="text/css" href="./style'.$settings["theme"].'.css" />' : ""; ?>		
		<link rel="shortcut icon" href="favicon.ico"/>
		<script type="text/javascript" src="./lib/lib.js"></script>
		<script type="text/javascript" src="./jquery/jquery-1.11.1.min.js"></script>
		<link rel="stylesheet" type="text/css" href="./jquery-ui-1.11.4/jquery-ui.min.css" />
		<script type="text/javascript" src="./jquery-ui-1.11.4/jquery-ui.js"></script>
		<link rel="shortcut icon" href="favicon.ico"/>
		</head>
<body>

<?php

if (is_file (ABSOLUTE_PATH . "install.php")) {
	message ('Remove "install.php" before using OpenBookmark.');
}

if ($display_login_form) {
	$auth->display_login_form ();
	require_once (ABSOLUTE_PATH . "footer.php");
}

?>
