<?php
error_debug("~ including error.php");

function error_break() {
	global $_josh;
	unset($_josh["ignored_words"]); //too long. gets in the way!
	echo draw_array($_josh);
	exit;
}

function error_debug($message) {
	global $_josh;
	if ($_josh["debug"]) {
		$backtrace = debug_backtrace();
		$level = 0;
		echo $message . "<br/>" . $backtrace[$level]["file"] . ", line " . $backtrace[$level]["line"] . "<br/><hr noshade color='#cccccc' size='1'/>";
	}
}

function error_handle($type, $message, $run_trace=true) {
	global $_josh;
	if ($run_trace) {
		$backtrace = debug_backtrace();
		$level = count($backtrace) - 1;
		$message .= " on line " . $backtrace[$level]["line"] . " of file " . $backtrace[$level]["file"];
	}
	if (function_exists("error_email")) {
		$email	= $message;
		$email .= "<br><br>Of page: <a href='" . $_josh["request"]["uri"] . "'>" . $_josh["request"]["uri"] . "</a>";
		$email .= "<br><br>Encountered by user: <!--user-->";
		error_email(draw_page($type, $email, true, true));
	}
	if (isset($_josh["mode"]) && ($_josh["mode"] == "dev")) draw_page($type, $message, true);
}

function error_handle_php($number, $str, $file, $line) {
	$number = $number & error_reporting();
	if ($number == 0) return;
	if (!defined('E_STRICT'))            define('E_STRICT', 2048);
	if (!defined('E_RECOVERABLE_ERROR')) define('E_RECOVERABLE_ERROR', 4096);

	$title = "PHP ";
	switch($number){
		case E_ERROR:				$title .= "Error";						break;
		case E_WARNING:				$title .= "Warning";					break;
		case E_PARSE:				$title .= "Parse Error";				break;
		case E_NOTICE:				$title .= "Notice";						break;
		case E_CORE_ERROR:			$title .= "Core Error";					break;
		case E_CORE_WARNING:		$title .= "Core Warning";				break;
		case E_COMPILE_ERROR:		$title .= "Compile Error";				break;
		case E_COMPILE_WARNING:		$title .= "Compile Warning";			break;
		case E_USER_ERROR:			$title .= "User Error";					break;
		case E_USER_WARNING:		$title .= "User Warning";				break;
		case E_USER_NOTICE:			$title .= "User Notice";				break;
		case E_STRICT:				$title .= "Strict Notice";				break;
		case E_RECOVERABLE_ERROR:	$title .= "Recoverable Error";			break;
		default:					$title .= "Unknown error ($number)";	break;
	}
	
	$message = "<span class='josh_code'>$str</span> in <b>$file</b> on line <b>$line</b>";
	error_handle("PHP Error", $message, false);
}

?>