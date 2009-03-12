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
	$backtrace = debug_backtrace();
	$level = 1;
	if (isset($backtrace[$level]["file"]) && isset($backtrace[$level]["line"])) {
		$message = "<b>" . str_replace($_josh["root"], "", $backtrace[$level]["file"]) . ", line " . $backtrace[$level]["line"] . "</b> " . $message;
	}
	$_josh["debug_log"][] = $message;
	if ($_josh["debug"]) {
		echo $message . "<br/><hr noshade color='#cccccc' size='1'/>";
	}
}

function error_draw($title, $html) {	
	global $_josh;
	error_debug("drawing error handling page");
	if (!$_josh["request"]) return strip_tags($title . $_josh["newline"] . $_josh["newline"] . $html); //this is a cron, so no html needed
	return "<html><head><title>" . strip_tags($title) . "</title></head>
			<body style='margin:0px;'>
				<table width='100%' height='100%' cellpadding='0' cellspacing='0' border='0' style='background-color:#ddd; font-family:verdana, arial, sans-serif; font-size:13px; line-height:20px; color:#444;'><tr><td align='center'>
					<div style='background-color:#fff; text-align:left; padding:10px 20px 10px 20px; width:360px; min-height:260px;'>
						<h1 style='color:#444; font-weight:normal; font-size:24px; margin-bottom:30px;'><span style='background-color:#5599cc; color:#fff; padding:0px 11px 3px 11px'>error</span> " . $title . "</h1>" . $html . "
					</div>
				</td></tr></table>
			</body>
		</html>";
}

function error_handle($type, $message) {
	global $_josh, $_SESSION;
	error_debug("ERROR! type is:" . $type . " and message is: " . $message);
	
	if (!isset($_josh["mode"])) $_josh["mode"] = "dev";
	
	$backtrace = debug_backtrace();
	//$level = count($backtrace) - 1;
	$level = 1;
	if (isset($backtrace[$level]["line"]) && isset($backtrace[$level]["file"])) {
		$message .= "<p>At line " . $backtrace[$level]["line"] . " of " . $backtrace[$level]["file"];
		if (isset($backtrace[$level+1]["function"])) $message .= ", inside function " . $backtrace[$level+1]["function"];
		//$message .= " with arguments " . implode(", ", $backtrace[$level]["args"]);
		$message .= ".</p>";
		//$message .= draw_array($backtrace);
	}

	//take out full path -- security hazard and decreases readability
	$message = str_replace($_josh["root"], "", $message);
	
	//display error to user if it's a dev situation
	if ($_josh["mode"] == "dev") echo error_draw($type, $message, true);
	
	//add more stuff to admin message, set from and subject
	$from		 = $_josh["email_default"];
	$message  	.= "<p>Page: <a href='" . $_josh["request"]["url"] . "'>" . $_josh["request"]["url"] . "</a></p>";
	if (isset($_SESSION["email"]) && isset($_SESSION["full_name"])) {
		$message .= "<p>User: <a href='mailto:" . $_SESSION["email"] . "'>" . $_SESSION["full_name"] . "</a></p>";
		$from	= $_SESSION["full_name"] . " <" . $_SESSION["email"] . ">";
	}
	$subject = "Error: " . $type;
	$message = error_draw($type, $message);
	
	//try to post to work website, or otherwise
	//don't like to squash variables, but kind of need to because error could occur between setting error handler and getting config vars
	if (@$_josh["error_log_api"] && array_send(array("subject"=>$subject, "message"=>$message, "url"=>$_josh["request"]["url"], "email"=>$from), $_josh["error_log_api"])) {
		//cool, we sent the request via json and fsockopen!
	} elseif (@$_josh["email_admin"]) {
		//send email to admin
		if (!isset($_SESSION["email"]) || ($_SESSION["email"] != $_josh["email_admin"])) email($_josh["email_admin"], $email, $subject, $from);
	}

	//quit if it's dev
	if ($_josh["mode"] == "dev") db_close();

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
?>