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

function error_draw($title, $html) {	
	global $_josh;
	error_debug("drawing error handling page");
	return "<html><head><title>" . strip_tags($title) . "</title></head>
			<body style='background-color:#d2d2d2; font-family:verdana, arial, sans-serif; font-size:13px; line-height:20px; color:#444;'>
				<div style='background-color:#fff; padding:10px 20px 10px 20px; width:360px; height:260px; position:absolute; left:50%; top:50%; margin:-150px 0px 0px -200px;'>
					<h1 style='color:#5599cc; font-weight:normal; font-size:30px;'>" . $title . "</h1>" . $html . "
				</div>
			</body>
		</html>";
}

error_handle("this is a test", "this is test of the error-handling system.  please be patient.  this is only a test.");

function error_handle($type, $message, $run_trace=true) {
	global $_josh;
	if ($run_trace) {
		$backtrace = debug_backtrace();
		$level = count($backtrace) - 1;
		$message .= "<br><br>on line " . $backtrace[$level]["line"] . " of " . str_replace($_josh["root"], "", $backtrace[$level]["file"]);
	}
	if (function_exists("error_email")) {
		$email	= $message;
		$email .= "<br><br>Of page: <a href='" . $_josh["request"]["uri"] . "'>" . $_josh["request"]["uri"] . "</a>";
		$email .= "<br><br>Encountered by user: <!--user-->";
		error_email(error_draw($type, $email));
	}
	if ($_josh["mode"] == "dev") {
		echo error_draw($type, $message, true);
		db_close();
	}
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