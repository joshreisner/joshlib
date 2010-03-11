<?php
error_debug("including error.php", __file__, __line__);

function error_break() {
	//todo -- what's this for?
	global $_josh;
	unset($_josh['ignored_words']); //too long. gets in the way!
	echo draw_array($_josh);
	exit;
}

function error_debug($message, $file, $line) {
	global $_josh;	
	if (!$_josh['debug']) return;
	
	if (!isset($_josh['time_lastdebug'])) {
		$_josh['time_lastdebug'] = $_josh['time_start'];
		error_debug('<b>error_debug</b> welcome to joshlib!', __file__, __line__);
	}
	
	if (isset($_josh['dir']['root']) && stristr($file, $_josh['dir']['root'])) $file = str_replace($_josh['dir']['root'], "", $file);
	if (isset($_josh['dir']['joshlib']) && stristr($file, $_josh['dir']['joshlib'])) $file = str_replace($_josh['dir']['joshlib'], "/joshlib", $file);

	//timer
	$time = round(microtime(true) - $_josh['time_lastdebug'], 3);
	$time = ($time < .001) ? '' : $time . 's';
	echo '<div style="background-color:#fff;text-align:left;width:400px;margin:10px;padding:10px;border:2px dashed #6699cc;font-family:verdana;font-size:15px;min-height:70px;">
			<div style="font-weight:normal;font-size:12px;margin-bottom:7px;color:#888;">', $file, ' line ', $line, '
				<div style="float:right;color:#ccc;">', $time, '</div>
			</div>', 
			$message, 
		'</div>';
	
	$_josh['time_lastdebug'] = microtime(true);
}

function error_deprecated($message) {
	global $_josh;
	//use this to deprecate use of various functions
	$message = error_draw('use of deprecated function', $message);
	if (isset($_josh['mode']) && ($_josh['mode'] == 'dev')) die($message);
	email($_josh['email_admin'], $message, 'use of deprecated function');
}

function error_draw($title, $html) {	
	global $_josh;
	error_debug('drawing error handling page', __file__, __line__);
	
	//suppress HTML output if it's a CRON job
	if (isset($_josh['request']) && !$_josh['request']) return strip_tags($title . NEWLINE . NEWLINE . $html);

	//add fancy error element
	$title = '<div style="background-color:#59c;color:#fff;height:36px;line-height:36px;padding:0px 20px 0px 20px;position:absolute;top:-36px;left:0px;">Error</div>' . $title;
	
	if (function_exists('draw_page')) return draw_page($title, $html);

	//if we're at this point, it means the error is happening before the includes in joshlib
	echo '<h1>' . $title . '</h1>' . $html;
	exit;
}

function error_handle($type, $message='', $file=false, $line=false, $function=false) {
	global $_josh;
	error_debug('ERROR! type is:' . $type . ' and message is: ' . $message, __file__, __line__);
	
	//small possiblity these vars aren't set yet
	if (!isset($_josh['mode']))  $_josh['mode'] = 'dev';
	if (!isset($_josh['debug'])) $_josh['debug'] = false;
	if (!isset($_josh['email_admin'])) $_josh['email_admin'] = 'josh@joshreisner.com';
	
	//don't let this happen recursively
	if (isset($_josh['handling_error']) && $_josh['handling_error']) return false;
	$_josh['handling_error'] = true;
	
	if (($_josh['mode'] == 'dev') && $_josh['debug']) exit;
	
	//where possible, specify your own file and line
	if ($file && $line) {
		$message .= '<p>At line ' . $line . ' of ' . $file;
		if ($function) $message .= ', inside function ' . $function;
		$message .= ".</p>";
	} else {
		//backtrace is problematic because every error seems to stem from a different place
		$backtrace = debug_backtrace();
		$level = 1;
		if (isset($backtrace[$level]['line']) && isset($backtrace[$level]['file'])) {
			$message .= '<p>At line ' . $backtrace[$level]['line'] . ' of ' . $backtrace[$level]['file'];
			if (isset($backtrace[$level+1]['function'])) $message .= ', inside function ' . $backtrace[$level+1]['function'];
			$message .= ".</p>";
		}
	}

	//take out full path -- security hazard and decreases readability
	if (isset($_josh['dir']['root'])) $message = str_replace($_josh['dir']['root'], "", $message);
	if (isset($_josh['dir']['joshlib'])) $message = str_replace($_josh['dir']['joshlib'], "/joshlib", $message);
	
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
		if (isset($b['file'])) $b['file'] = str_replace($_josh['dir']['root'], "", $b['file']);
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
		if (!empty($_josh['error_log_api']) && array_send(array('subject'=>$subject, 'message'=>$message, 'url'=>$_josh['request']['url'], 'email'=>$_josh['email_admin']), $_josh['error_log_api'])) {
			//cool, we sent the request via json and fsockopen!
		} elseif (@$_josh['email_admin']) {
			//send email to admin
			email($_josh['email_admin'], $message, $subject, $from);
		}
	}
}

function error_handle_exception($exception) {
	return error_handle('Exception Error', $exception);
}

function error_handle_php($number, $message, $file, $line) {
	$number = $number & error_reporting();
	if ($number == 0) return;
	if (!defined('E_STRICT'))            define('E_STRICT', 2048);
	if (!defined('E_RECOVERABLE_ERROR')) define('E_RECOVERABLE_ERROR', 4096);

	$title = 'PHP ';
	switch ($number) {
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
	//format_code($message)
	error_handle($title, $message);
}
?>