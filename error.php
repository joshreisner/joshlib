<?php
error_debug("~ including error.php");
if (!isset($_josh["debug"])) $_josh["debug"] = false;

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
		$level = 1;
		echo $message . "<br/>" . str_replace($_josh["root"], "", $backtrace[$level]["file"]) . ", line " . $backtrace[$level]["line"] . "<br/><hr noshade color='#cccccc' size='1'/>";
	}
}

function error_draw($title, $html) {	
	global $_josh;
	error_debug("drawing error handling page");
	if (!$_josh["request"]) return strip_tags($title . $_josh["newline"] . $_josh["newline"] . $html);
	return "<html><head><title>" . strip_tags($title) . "</title></head>
			<body style='background-color:#ddd; font-family:verdana, arial, sans-serif; font-size:13px; line-height:20px; color:#444;'>
				<div style='border:none; background-color:#fff; text-align:left; padding:10px 20px 10px 20px; width:360px; min-height:260px; position:absolute; left:50%; top:50%; margin:-150px 0px 0px -200px;'>
					<h1 style='color:#444; font-weight:normal; font-size:24px; margin-bottom:30px;'><span style='background-color:#5599cc; color:#fff; padding:0px 11px 3px 11px'>error</span> " . $title . "</h1>" . $html . "
				</div>
			</body>
		</html>";
}

function error_handle($type, $message) {
	global $_josh;
	error_debug("ERROR! type is:" . $type . " and message is: " . $message);

	$backtrace = debug_backtrace();
	//$level = count($backtrace) - 1;
	$level = 1;
	if ($backtrace[$level]["line"] && $backtrace[$level]["file"]) {
		$message .= "<p>At line " . $backtrace[$level]["line"] . " of " . $backtrace[$level]["file"];
	}
	if (isset($backtrace[$level+1]["function"])) $message .= ", inside function " . $backtrace[$level+1]["function"];
	//$message .= " with arguments " . implode(", ", $backtrace[$level]["args"]);
	$message .= ".</p>";
	//$message .= draw_array($backtrace);

	//take out full path -- security hazard and decreases readability
	$message = str_replace($_josh["root"], "", $message);
	
	if (function_exists("errorNotify")) {
		//in your application, you can define an error handling function errorNotify($message)
		//the reason this isn't handled here is because of the <!--user--> thing; you are encouraged to replace this with a link to the user's profile page
		$email	= $message;
		$email .= "<p>Of page: <a href='" . $_josh["request"]["uri"] . "'>" . $_josh["request"]["uri"] . "</a></p>";
		$email .= "<p>Encountered by user: <!--user--></p>";
		errorNotify(error_draw($type, $email));
	}
	if ($_josh["mode"] == "dev") {
		echo error_draw($type, $message, true);
		db_close();
	}
}

function error_handle_exception($exception) {
	return error_handle("Exception Error", $description);
}

function error_handle_php($number, $message, $file, $line) {
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
	
	error_handle($title, format_code($message));
}
set_error_handler("error_handle_php"); //it kind of breaks convention to have this here, but it's fine, right?
set_exception_handler("")
?>