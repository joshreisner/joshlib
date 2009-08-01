<?php
error_debug("including error.php", __file__, __line__);

function error_break() {
	global $_josh;
	unset($_josh['ignored_words']); //too long. gets in the way!
	echo draw_array($_josh);
	exit;
}

function error_debug($message, $file=false, $line=false) {
	global $_josh;
	$_josh['debug_log'][] = $message;
	if ($file && $line) {
		if (isset($_josh['root']) && stristr($file, $_josh['root'])) $file = str_replace($_josh['root'], "", $file);
		if (isset($_josh['joshlib_folder']) && stristr($file, $_josh['joshlib_folder'])) $file = "joshlib " . str_replace($_josh['joshlib_folder'], "", $file);
		$message = '<div style="font-weight:bold;">' . $file . ', line ' . $line . '</div>' . $message;
	}
	if ($_josh['debug']) {
		echo '<div style="width:400px; padding-bottom:10px; border-bottom:2px solid #999; font-family:verdana; font-size:12px;">' . $message . '</div>';
	}
}

function error_draw($title, $html) {	
	global $_josh;
	error_debug("drawing error handling page");
	if (isset($_josh['request']) && !$_josh['request']) return strip_tags($title . $_josh['newline'] . $_josh['newline'] . $html); //this is a cron, so no html needed
	return '<html><head><title>' . strip_tags($title) . '</title></head>
			<body style="margin:0px;">
				<table width="100%" height="100%" cellpadding="0" cellspacing="0" border="0" style="background-color:#ddd; font-family:verdana, arial, sans-serif; font-size:13px; line-height:20px; color:#444;">
					<tr><td align="center">
					<div style="background-color:#fff; text-align:left; padding:10px 20px 10px 20px; width:360px; min-height:260px;">
						<h1 style="color:#444; font-weight:normal; font-size:24px; margin-bottom:30px;"><span style="background-color:#5599cc; color:#fff; padding:0px 11px 3px 11px">error</span> ' . $title . '</h1>' . $html . '
					</div>
				</td></tr></table>
			</body>
		</html>';
}

function error_handle($type, $message="") {
	global $_josh, $_SESSION, $_SERVER;
	error_debug('ERROR! type is:' . $type . ' and message is: ' . $message);
	
	//small possiblity these vars aren't set yet
	if (!isset($_josh['mode']))  $_josh['mode'] = 'dev';
	if (!isset($_josh['debug'])) $_josh['debug'] = false;
	if (!isset($_josh['email_admin'])) $_josh['email_admin'] = 'josh@joshreisner.com';
	
	//don't let this happen recursively
	if (isset($_josh['handling_error']) && $_josh['handling_error']) return false;
	$_josh['handling_error'] = true;
	
	if (($_josh['mode'] == 'dev') && $_josh['debug']) exit;
	
	//get backtrace
	$backtrace = debug_backtrace();
	$level = 1;
	if (isset($backtrace[$level]['line']) && isset($backtrace[$level]['file'])) {
		$message .= '<p>At line ' . $backtrace[$level]['line'] . ' of ' . $backtrace[$level]['file'];
		if (isset($backtrace[$level+1]['function'])) $message .= ', inside function ' . $backtrace[$level+1]['function'];
		$message .= ".</p>";
	}

	//take out full path -- security hazard and decreases readability
	if (isset($_josh['root'])) $message = str_replace($_josh['root'], "", $message);
	if (isset($_josh['joshlib_folder'])) $message = str_replace($_josh['joshlib_folder'], "/joshlib", $message);
	
	//add more stuff to admin message, set from and subject
	$from = (isset($_josh['email_default'])) ? $_josh['email_default'] : $_josh['email_admin'];
	if ($_josh['mode'] != 'dev') $message .= draw_container('p', 'Page: ' . draw_link($_josh['request']['url']));
	if (isset($_SESSION['email']) && isset($_SESSION['full_name'])) {
		$message .= "<p>User: <a href='mailto:" . $_SESSION['email'] . "'>" . $_SESSION['full_name'] . "</a></p>";
		$from	= $_SESSION['full_name'] . " <" . $_SESSION['email'] . ">";
	}
	if (isset($_SESSION['HTTP_USER_AGENT'])) $message .= '<p>Browser: ' . $_SESSION['HTTP_USER_AGENT'] . '</p>';
	
	/*backtrace
	$message .= "<p>Backtrace:";
	foreach ($backtrace as $b) {
		if (isset($b['args'])) unset($b['args']);
		if (isset($b['file'])) $b['file'] = str_replace($_josh['root'], "", $b['file']);
		$message .= draw_array($b, true) . "<br>";
	}
	
	//cookies
	if (isset($_SERVER['HTTP_COOKIE'])) $message .= "Cookies: " . draw_array(array_url($_SERVER['HTTP_COOKIE'], false, ";")) . "</p>";
	$message .= "</p>";
	*/
	$subject = 'Error: ' . $type;
	
	//render
	$message = error_draw($type, $message);

	//quit if it's dev
	if ($_josh['mode'] == 'dev') {
		echo $message;	
		db_close();
	}
	
	//try to post to work website
	//i don't like to squash variables, but need to here because an error could occur between setting error handler and getting the config vars
	if (!isset($_SESSION['email']) || ($_SESSION['email'] != $_josh['email_admin'])) {
		if (@$_josh['error_log_api'] && array_send(array('subject'=>$subject, 'message'=>$message, 'url'=>$_josh['request']['url'], 'email'=>$from), $_josh['error_log_api'])) {
			//cool, we sent the request via json and fsockopen!
		} elseif (@$_josh['email_admin']) {
			//send email to admin
			email($_josh['email_admin'], $message, $subject, $from);
		}
	}
}

function error_handle_exception($exception) {
	return error_handle('Exception Error', $description);
}

function error_handle_php($number, $message, $file, $line) {
	$number = $number & error_reporting();
	if ($number == 0) return;
	if (!defined('E_STRICT'))            define('E_STRICT', 2048);
	if (!defined('E_RECOVERABLE_ERROR')) define('E_RECOVERABLE_ERROR', 4096);

	$title = 'PHP ';
	switch($number){
		case E_ERROR:				$title .= 'Error';						break;
		case E_WARNING:				$title .= 'Warning';					break;
		case E_PARSE:				$title .= 'Parse Error';				break;
		case E_NOTICE:				$title .= 'Notice';						break;
		case E_CORE_ERROR:			$title .= 'Core Error';					break;
		case E_CORE_WARNING:		$title .= 'Core Warning';				break;
		case E_COMPILE_ERROR:		$title .= 'Compile Error';				break;
		case E_COMPILE_WARNING:		$title .= 'Compile Warning';			break;
		case E_USER_ERROR:			$title .= 'User Error';					break;
		case E_USER_WARNING:		$title .= 'User Warning';				break;
		case E_USER_NOTICE:			$title .= 'User Notice';				break;
		case E_STRICT:				$title .= 'Strict Notice';				break;
		case E_RECOVERABLE_ERROR:	$title .= 'Recoverable Error';			break;
		default:					$title .= 'Unknown error ($number)';	break;
	}
	
	//debug();
	error_handle($title, format_code($message));
}
?>