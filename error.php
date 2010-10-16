<?php
//a collection of functions that help handle errors
//reviewed 10/16/2010

error_debug('including error.php', __file__, __line__);

/* function deprecated 10/16/2010 due to disuse, only found commented in FSS
function error_break() {
	//todo -- what's this for?
	global $_josh;
	unset($_josh['ignored_words']); //too long. gets in the way!
	echo draw_array($_josh);
	exit;
} */

function error_debug($message, $file, $line, $bgcolor='#fff') {
	global $_josh;	
	if (!$_josh['debug']) return;
	
	if (!isset($_josh['time_lastdebug'])) {
		$_josh['time_lastdebug'] = TIME_START;
		error_debug('<b>error_debug</b> welcome to joshlib!', __file__, __line__);
	}
	
	//timer
	$time = round(microtime(true) - $_josh['time_lastdebug'], 3);
	$time = ($time < .001) ? '' : $time . 's';
	echo '<div style="background-color:' . $bgcolor . ';text-align:left;float:left;clear:left;margin:10px;padding:10px;border:2px dashed #6699cc;font-family:verdana;color:#000;font-size:15px;min-height:70px;">
			<div style="font-weight:normal;font-size:12px;margin-bottom:7px;color:#888;">', error_path($file), ' line ', $line, '
				<div style="float:right;color:#ccc;">', $time, '</div>
			</div>', 
			$message, 
		'</div>';
	
	$_josh['time_lastdebug'] = microtime(true);
}

function error_deprecated($message) {
	$message = error_handle('use of deprecated function', $message);
}

function error_draw($title, $html) {	
	global $_josh;
	error_debug('drawing error handling page', __file__, __line__);
	
	//suppress HTML output if it's a CRON job
	if (isset($_josh['request']) && !$_josh['request']) return strip_tags($title . NEWLINE . NEWLINE . $html);

	//add attractive error element
	$title = '<div style="background-color:#59c;color:#fff;height:36px;line-height:36px;padding:0px 20px 0px 20px;position:absolute;top:-36px;left:0px;">Error</div>' . $title;
	
	if (function_exists('draw_page')) return draw_page($title, $html);

	//if we're at this point, it means the error is happening before the includes in joshlib
	echo '<h1>' . $title . '</h1>' . $html;
	exit;
}

function error_handle($type, $message='', $file=false, $line=false, $function=false) {
	global $_josh;
	error_debug('ERROR! type is:' . $type . ' and message is: ' . $message, __file__, __line__);
	
	//possiblity these vars aren't set yet
	if (!isset($_josh['mode']))			$_josh['mode'] = 'dev';
	if (!isset($_josh['debug']))		$_josh['debug'] = false;
	if (!isset($_josh['email_admin']))	$_josh['email_admin'] = 'josh@joshreisner.com';
	
	//don't let this happen recursively
	if (isset($_josh['handling_error']) && $_josh['handling_error']) return false;
	$_josh['handling_error'] = true;
	
	if (($_josh['mode'] == 'dev') && $_josh['debug']) exit;
	
	//when throwing an error, specify file and line
	if ($file && $line) {
		$message .= 'At line ' . $line . ' of ' . $file;
		if ($function) $message .= ', inside function ' . $function;
		$message .= draw_p($message);
	} else {
		$backtrace = debug_backtrace();
		array_shift($backtrace);
		foreach ($backtrace as &$b) $b = $b['file'] . ' ' . $b['line'] . ' ' . draw_tag('span', array('style'=>'color:#aaa;float:right;'), $b['function']);
		$message .= draw_list($backtrace, array('style'=>'border-top:1px solid #ddd;padding:10px 0 0 20px;'), 'ol');
	}

	//add more stuff to admin message, set from and subject
	$from = (isset($_josh['email_default'])) ? $_josh['email_default'] : $_josh['email_admin'];
	if ($_josh['mode'] != 'dev') {
		$message .= draw_p('Request: ' . draw_link($_josh['request']['url'], false, false, array('style'=>'color:#336699;')));
		if (isset($_SESSION['email']) && isset($_SESSION['full_name'])) {
			$message .= draw_p('User: ' . draw_link('mailto:' . $_SESSION['email'], $_SESSION['full_name'], false, array('style'=>'color:#336699;')));
			$from	= array($_SESSION['email']=>$_SESSION['full_name']);
		}
		if (isset($_SERVER['HTTP_USER_AGENT'])) $message .= draw_p('Browser: ' . $_SERVER['HTTP_USER_AGENT']);
	}
			
	$subject = '[Joshlib Error] ' . $type;
	$message = error_draw($type, error_path($message));
	
	//notify
	if ($_josh['mode'] == 'dev') {
		echo $message;	
		db_close();
	} elseif (!empty($_josh['error_log_api']) && array_send(array('subject'=>$subject, 'message'=>$message, 'url'=>$_josh['request']['url'], 'sanswww'=>$_josh['request']['sanswww']), $_josh['error_log_api'])) {
		//cool, we sent the request via json and fsockopen!
	} elseif (!empty($_josh['email_admin'])) {
		//send email to admin
		email($_josh['email_admin'], $message, $subject, $from);
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

function error_path($str) {
	//remove excess pathinfo when reporting errors
	if (defined('DIRECTORY_ROOT')) $str = str_replace(DIRECTORY_ROOT, '', $str);
	if (defined('DIRECTORY_JOSHLIB')) $str = str_replace(DIRECTORY_JOSHLIB, '/joshlib/', $str);
	return $str;
}

?>