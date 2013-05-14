<?php
//a collection of functions that help handle errors

if (!function_exists('error_debug')) {	
	function error_debug($message, $file, $line, $bgcolor='#fff') {
		global $_josh;	
		if ($_josh['mode'] != 'debug') return;
		
		if (!isset($_josh['time_lastdebug'])) {
			$_josh['time_lastdebug'] = TIME_START;
			if (isset($_josh['finished_loading'])) error_debug('(Joshlib has already finished loading)', __file__, __line__);
		}
		
		//timer
		$time = round(microtime(true) - $_josh['time_lastdebug'], 3);
		$time = ($time < .001) ? '' : $time . 's';
		if (function_exists('error_path')) $file = error_path($file);
		echo '<div style="background-color:' . $bgcolor . ';text-align:left;float:left;clear:left;margin:10px;padding:10px;border:2px dashed #6699cc;font-family:verdana;color:#000;font-size:15px;min-height:70px;z-index:400;position:relative;">
				<div style="font-weight:normal;font-size:12px;margin-bottom:7px;color:#888;">', $file, ' line ', $line, '
					<div style="float:right;color:#ccc;">', $time, '</div>
				</div>', 
				$message, 
			'</div>';
		
		$_josh['time_lastdebug'] = microtime(true);
	}
}

error_debug('Starting Joshlib', __file__, __line__);
error_debug('including error.php', __file__, __line__);

if (!function_exists('error_deprecated')) {
	function error_deprecated($message) {
		$message = error_handle('Deprecated Function', $message, __file__, __line__);
	}
}

if (!function_exists('error_draw')) {
	function error_draw($title, $html) {	
		global $_josh;
		error_debug('drawing error handling page', __file__, __line__);
		
		//if ajax or cli, return non-html version -- todo, detect cli somewhere else
		if (
			(isset($_josh['error_mode_html']) && !$_josh['error_mode_html']) || //ajax
			(isset($_josh['request']) && !$_josh['request']) //cli
		) return strip_tags(strToUpper($title) . $html);

		//format links
		$html = str_replace('<a href=', '<a style="color:#59c;" href=', $html);
		
		if (function_exists('draw_page')) return draw_page($title, $html, 'Error');

		//if we're at this point, it means the error is happening before the includes in joshlib
		echo '<h1>' . $title . '</h1>' . $html;
		exit;
	}
}

if (!function_exists('error_handle')) {
	function error_handle($type, $message='', $file=false, $line=false) {
		global $_josh;
		error_debug('ERROR! type is:' . $type . ' and message is: ' . $message, __file__, __line__);
		
		$originalmessage = $message; //preserve for later (todo: refactor)
		
		//possiblity this var isn't set yet
		if (!isset($_josh['mode']))			$_josh['mode'] = 'live';
		
		//don't let this happen recursively
		if (isset($_josh['handling_error']) && $_josh['handling_error']) return false;
		$_josh['handling_error'] = true;
			
		//show backtrace stack
		$backtrace = array_reverse(debug_backtrace());
			
		$last_function = ''; //i think it's more readable if the function applies to the previous line (you are here)
		foreach ($backtrace as &$b) {
			if (!isset($b['file'])) $b['file'] = $file;
			if (!isset($b['line'])) $b['line'] = $line;
			$function = (function_exists('draw_tag')) ? draw_tag('span', array('style'=>'color:#aaa;float:right;'), $last_function) : '[' . $last_function . ']';
			if (!empty($b['function'])) $last_function = $b['function'] . '()';
			$b['file'] = error_path($b['file']);
			$thisfile = $b['file'] . ' ' . $b['line'];
			
			//replace with links ~ todo refactor
			if (function_exists('format_text_starts')) {
				if ($filename = format_text_starts('/joshlib/', $b['file'])) {
					$thisfile = draw_link('http://code.google.com/p/joshlib/source/browse/trunk/' . $filename . '#' . $b['line'], $b['file'], true, array('style'=>'color:#59c;')) . ' ' . $b['line'];
				} elseif ($filename = format_text_starts('/Users/joshreisner/Sites/login/', $b['file'])) { //todo genericize this
					$thisfile = draw_link('http://code.google.com/p/bb-login/source/browse/trunk/' . $filename . '#' . $b['line'], '/login/' . $filename, true, array('style'=>'color:#c59;')) . ' ' . $b['line'];
				}
			}
			
			$b = $thisfile . ' ' . $function;
		}
		$message .= draw_list($backtrace, array('style'=>'border-top:1px solid #ddd;padding:10px 0 0 20px;'), 'ol');
		
		//record error if file location is specified
		if (!empty($_josh['error_log_file'])) {
			if (file_exists(DIRECTORY_ROOT . $_josh['error_log_file'])) {
				$errors = array_csv(file_get($_josh['error_log_file']), TAB);
			} else {
				$errors = array();
			}
			
			//get backtrace again because it got messed up earlier
			$backtrace = array_pop(debug_backtrace());

			$errors[] = array(
				'timestamp'=>time(), 
				'title'=>$type, 
				'description'=>str_replace(TAB, ' ', $originalmessage), 
				'user'=>@$_SESSION['full_name'], 
				'url'=>$_josh['request']['url'], 
				'referrer'=>$_josh['referrer']['url'], 
				'file'=>str_replace(DIRECTORY_ROOT, '', $backtrace['file']), 
				'line'=>$backtrace['line']
			);
			
			//die(draw_table($errors, false, true));
			file_put($_josh['error_log_file'], file_csv($errors));
		}

		//notify in your face if dev
		if ($_josh['mode'] != 'live') {
			echo error_draw($type, $message);
			db_close();
		}
		
		//notify over api if specified
		if (!empty($_josh['error_log_api'])) {
			error_debug('attempting to send error message to API: ' . $_josh['error_log_api'], __file__, __line__);
			$array = array(
				'title'=>$type,
				'description'=>$message,
				'app_name'=>@$_josh['app_name'],
				'url'=>$_josh['request']['url'],
				'sanswww'=>$_josh['request']['sanswww'],
				'user_email'=>@$_SESSION['email'],
				'user_name'=>@$_SESSION['full_name']
			);
			
			array_send($array, $_josh['error_log_api']);
		}

		//notify by email if email_admin is specified
		if (!empty($_josh['email_admin'])) {
			//add more stuff to admin message, set from and subject
			$from = (isset($_josh['email_default'])) ? $_josh['email_default'] : $_josh['email_admin'];

			$message .= draw_p('Request: ' . draw_link($_josh['request']['url'], $_josh['request']['url'], false, array('style'=>'color:#336699;')));
			if (!empty($_josh['referrer'])) $message .= draw_p('Referrer: ' . draw_link($_josh['referrer']['url'], $_josh['referrer']['url'], false, array('style'=>'color:#336699;')));
			if (isset($_SESSION['email']) && isset($_SESSION['full_name'])) {
				$message .= draw_p('User: ' . draw_link('mailto:' . $_SESSION['email'], $_SESSION['full_name'], false, array('style'=>'color:#336699;')));
				$from = array($_SESSION['email']=>$_SESSION['full_name']);
			}
			if (isset($_SERVER['HTTP_USER_AGENT'])) $message .= draw_p('Browser: ' . $_SERVER['HTTP_USER_AGENT']);
					
			//send email to admin
			$subject = '[Joshlib Error] ' . $type;
			email($_josh['email_admin'], error_draw($type, $message), $subject, $from);
		}
	}
}

if (!function_exists('error_handle_exception')) {
	function error_handle_exception($exception) {
		return error_handle('Exception Error', $exception, __file__, __line__);
	}
}

if (!function_exists('error_handle_php')) {
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
		
		error_handle($title, $message, __file__, __line__);
	}
}

if (!function_exists('error_path')) {
	function error_path($str) {
		//remove excess pathinfo when reporting errors
		if (defined('DIRECTORY_ROOT')) $str = str_replace(DIRECTORY_ROOT, '', $str);
		if (defined('DIRECTORY_JOSHLIB')) $str = str_replace(DIRECTORY_JOSHLIB, '/joshlib/', $str);
		return $str;
	}
}