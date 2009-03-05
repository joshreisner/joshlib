<?php
/*
this section is all formatting functions, usually to format strings into special configurations
*/

error_debug("~ including format.php");

function format_accents($string) {
	$string = str_replace("Õ", "&rsquo;", $string);
	$string = str_replace("Ñ", "&mdash;", $string);
	$string = str_replace("Ò", "&ldquo;", $string);
	$string = str_replace("Ó", "&rdquo;", $string);
	$string = str_replace("Ÿ", "&uuml;",  $string);
	$string = str_replace("ç", "&ccedil;", $string);
	return $string;
}

function format_array_text($array) {
	if (count($array) > 1) {
		$last = array_pop($array);
		return implode(", ", $array) . " and " . $last;
	} else {
		return $array[0];
	}
}

function format_binary($blob) {
	global $_josh;
	if ($_josh["db"]["language"] == "mssql") {
		$return = unpack("H*hex", $blob);
		return "0x" . $return["hex"];
	} elseif ($_josh["db"]["language"] == "mysql") {
		return "'" . addslashes($blob) . "'";
	}
}

function format_boolean($value, $options="Yes|No") {
	$options = explode("|", $options);
	if ($value) return $options[0];
	return $options[1];
}

function format_check($variable, $type="int") {
	//alias
	return format_verify($variable, $type);
}

function format_code($code) {
	return "<div class='josh_code'>" . nl2br(str_replace("\t", "&nbsp;", $code)) . "</div>";
}

function format_date($timestamp=false, $error="", $format="M d, Y", $relativetime=true) {
	global $_josh;

	if ($timestamp === false) $timestamp = time();

	if (strToUpper($format) == "SQL") {
		$format = "Y-m-d H:i:00";
		$todaytime = $relativetime = false;
	}
	
	//reject or convert
	if (empty($timestamp) || ($timestamp == "Jan 1 1900 12:00AM")) return $error;
	if (!is_int($timestamp)) $timestamp = strToTime($timestamp);
	
	//get timestamp for today
	$todaysdate = mktime(0, 0, 1, $_josh["month"], $_josh["today"], $_josh["year"]);

	//get timestamp for argument, without time
	$returnday    = date("d", $timestamp);
	$returnyear   = date("Y", $timestamp);
	$returnmonth  = date("n", $timestamp);
	$returndate   = mktime(0, 0, 1, $returnmonth, $returnday, $returnyear);
	
	if ($relativetime) {
		//setup return date
		$datediff = ($returndate - $todaysdate) / 86400;
		if ($datediff == 0) {
			$return = "Today";
		} elseif ($datediff == -1) {
			$return = "Yesterday";
		} elseif ($datediff == 1) {
			$return = "Tomorrow";
		} elseif (($datediff < -1) && ($datediff > -7)) { //last six days
			$return = date("l", $timestamp); //return day of week
		} else {
			$return = date($format, $timestamp);
		}
	} else {
		$return = date($format, $timestamp);
	}
	
	if ($return === 1) return $error;
	return $return;
}

function format_date_iso8601($timestamp=false) {
	//this looks like DATE_W3C http://www.php.net/manual/en/datetime.constants.php
	//use this for xml
	if (!$timestamp) $timestamp = time();
	if (!is_int($timestamp)) $timestamp = strToTime($timestamp);
	return date("Y-m-d", $timestamp) . "T" . date("H:i:s", $timestamp) . "-07:00";
}

function format_date_rss($timestamp=false) {
	if (!$timestamp) $timestamp = time();
	if (!is_int($timestamp)) $timestamp = strToTime($timestamp);
	return date(DATE_RSS, $timestamp);
}

function format_date_sql($month, $day, $year, $hour=0, $minute=0, $second=1) {
	global $_POST, $_josh;
	if (!empty($month) && !empty($day) && !empty($year)) {
		return "'" . date("Y-m-d H:i:00", mktime($hour, $minute, $second, $month, $day, $year)) . "'";
	} else {
		return "NULL";
	}
}

function format_date_time($timestamp=false, $error="", $separator="&nbsp;", $suppressMidnight=true, $relativetime=true) {
	if ($timestamp === false) $timestamp = time();
	$return = format_date($timestamp, $error, "M d, Y", $relativetime);
	//if (($return == "Today") || ($return == "Yesterday") || ($return == "Tomorrow")) 
	$time = format_time($timestamp);
	if ($suppressMidnight && ($time == "12:00am")) return $return;
	return $return . $separator . $time;;
}

function format_date_excel($timestamp) {
	if (!empty($timestamp)) return @date("n/j/Y", strToTime($timestamp));
}

function format_date_xml($timestamp=false) {
	if (!$timestamp) $timestamp = "now";
	if (!empty($timestamp) && $timestamp) return @date("Y-m-d", strToTime($timestamp)) . "T00:00:00.000";
}

function format_email($address) {
	//simple patch to prevent email form hijacking
	$address = trim($address);
	$address = strToLower($address);
	$address = str_replace("'", "", $address);
	$address = str_replace('"', "", $address);
	$address = preg_replace("/\r/", "", $address);
	$address = preg_replace("/\n/", "", $address);
	
	if (!stristr($address, "@")) return false;
	if (!stristr($address, ".")) return false;	
	return $address;
}

function format_file_name($str, $ext) {
	//formatting for downloaded files
	//TODO: only truly invalid characters should be checked.  i'm sure it's ok to download files with spaces, for example.
	$str = str_replace("  ",	" ",	$str);
	$str = str_replace("'",		"", 	$str);
	$str = str_replace(".",		"", 	$str);
	$str = str_replace(":",		"",		$str);
	$str = str_replace(" ",		"_",	$str);
	$str = substr($str, 0, 30);
	return strtolower($str . "." . $ext);
}  

function format_file_size($file) {
	$size = @filesize($file);
	return format_size($size);
} 

function format_highlight($haystack, $needles, $style="background-color:#FFFFBB;padding:1px;font-weight:bold;") {
	//sometimes you want to use a highlighter on html -- usually in search results
	if (is_array($needles)) $needles = implode("|", $needles);
	return preg_replace("/($needles)/i","<span style='" . $style . "'><b>\\0</b></span>", $haystack);
}

function format_hilite($haystack, $needles, $style="background-color:#FFFFBB;padding:1px;font-weight:bold;") {
	//alias of format_highlight
	return format_highlight($haystack, $needles, $style);
}

function format_html($text) {
	//clean up microsoft formatting when pasted into TinyMCE
	error_debug("<b>format_html</b> about to clean up " . format_string($text, 300));
	$text = htmLawed($text, array('comment'=>1, 'cdata'=>1));
	$text = str_replace("<p>&nbsp;</p>", "", $text);
	$text = str_replace(">&nbsp;</", "></", $text);
	return $text;

}

function format_html_text($str) {
	$return = strip_tags($str);
	$return = str_replace("&nbsp;", " ", $return);
	$return = trim($return);
	if (empty($return)) return false;
	return $return;
}

function format_js_desanitize() {
	//javascript function for decoding sanitized strings
	return '
	function desanitize(string) {
		return string.replace(/replacedash/g, "-").replace(/replaceslash/g, "/").replace(/replacespace/g, " ").substring(1);
	}
	';
}

function format_js_sanitize($string) {
	//return javascript-sanitized key
	//need for rollover script for seedco financial and phoebe murer
	$string = "a" . $string; //doesn't like variables that start with numbers
	$string = str_replace("-", "replacedash", $string); //or contain dashes
	$string = str_replace("/", "replaceslash", $string); //or contain slashes
	$string = str_replace(" ", "replacespace", $string); //or contain spaces
	return $string;
}

function format_money($value, $dollarsign=true, $comma=true, $error="") {
	$negative = ($value < 0);
	$value = format_num($value, 2, $comma, $error);
	if ($value == $error) return $value;
	if ($dollarsign) {
		if ($negative) {
			$value = "-$" . str_replace("-", "", $value);
		} else {
			$value = "$" . $value;
		}
	}
	return $value;
}

function format_null($value="") {
	//could also be a db function?
	if (empty($value)) return "NULL";
	if (!is_numeric($value)) return "'" . $value . "'";
	return $value;
}

function format_num($value, $decimals=false, $comma=true, $error="n/a") {
	//output function
	if (empty($value)) return $error;
	if (!format_verify($value, "num")) return $error;
	if ($comma) $comma = ",";
	return number_format($value, $decimals, ".", $comma);
}

/* 
this relationship between above and below is confusing! 
format_num is like number_format but with some checking.
format_numeric forces out a number from a string
*/

function format_numeric($value, $integer=false) {
	//input function
	$characters = "0123456789";
	if (!$integer) $characters .= ".";
	$newval = "";
	for ($i = 0; $i < strlen($value); $i++) if (strpos($characters, $value[$i]) !== false) $newval .= $value[$i];
	if (empty($newval)) {
		error_debug("<b>format_numeric</b> received $value and is sending back false");
		return false;
	} else {
		error_debug("<b>format_numeric</b> received $value and is sending back " . $newval);
		return $newval - 0;
	}
}

function format_percentage($float, $precision=2) {
	return round($float * 100, $precision) . "%";
}

function format_phone($string, $fail=false) { //format a phone number to (123) 456-7890 format
	$number = "";
	for ($i = 0; $i < strlen($string); $i++) if (is_numeric($string[$i])) $number .= $string[$i];
	if ((strlen($number) != 10) || ($number == "9999999999")) {
		if ($fail) return false;
		return $string;
	}
	return "(" . substr($number, 0, 3) . ") " . substr($number, 3, 3) . "-" . substr($number, 6, 4);
}

function format_pluralize($entity) {
	$length = strlen($entity);
	if (substr($entity, -1) == "y") {
		return substr($entity, 0, ($length - 1)) . "ies";
	} else {
		return $entity . "s";
	}
}

function format_post_bits($fieldnames) {
	//takes a comma-separated list of POST keys (checkboxes) and sets bit values in their places
	global $_POST;
	$fields = array_post_fields($fieldnames);
	foreach ($fields as $field) $_POST[$field] = (isset($_POST[$field])) ? 1 : 0;
}

function format_post_date($str) {
	global $_POST;
	
	$month  = $_POST[$str . "Month"];
	$day    = $_POST[$str . "Day"];
	$year   = $_POST[$str . "Year"];
	
	$hour   = isset($_POST[$str . "Hour"])   ? $_POST[$str . "Hour"]   : 0;
	$minute = isset($_POST[$str . "Minute"]) ? $_POST[$str . "Minute"] : 0;
	$second = isset($_POST[$str . "Second"]) ? $_POST[$str . "Second"] : 0;
	
	if (isset($_POST[$str . "AMPM"])) {
		if ($_POST[$str . "AMPM"] == "AM") {
			if ($hour == 12) $hour = 0;
		} else {
			if ($hour != 12) $hour +=12;
		}
	}
	error_debug("<b>format_post_date</b> for $str into mdyhms: $month, $day, $year, $hour, $minute, $second");
	return format_date_sql($month, $day, $year, $hour, $minute, $second);
}

function format_post_float($fieldnames) {
	//takes a comma-separated list of POST keys and replaces them with monetary values or NULLs if they're empty
	global $_POST;
	$fields = array_post_fields($fieldnames);
	foreach ($fields as $field) {
		$_POST[$field] = format_numeric($_POST[$field]);
		if ($_POST[$field] === false) $_POST[$field] = "NULL";
	}
}

function format_post_html($fieldnames) {
	//takes a comma-separated list of POST keys and formats the html in them
	global $_POST;
	$fields = array_post_fields($fieldnames);
	foreach ($fields as $field) {
		$return = format_html($_POST[$field]);
		$_POST[$field] = (empty($return)) ? "NULL" : "'" . $_POST[$field] . "'";
	}
}

function format_post_nulls($fieldnames) {
	//takes a comma-separated list of POST keys and replaces them with NULLs if they're empty
	global $_POST;
	error_debug("<b>format_post_nulls</b> for " . $fieldnames);
	$fields = array_post_fields($fieldnames);
	foreach ($fields as $field) {
		if (!strlen($_POST[$field])) {
			error_debug("<b>format_post_nulls</b> nullifying " . $field);
			$_POST[$field] = "NULL";
		}
	}
}

function format_post_urls($fieldnames) {
	//takes a comma-separated list of POST keys and formats them as NULLs or urls
	global $_POST;
	error_debug("<b>format_post_urls</b> for " . $fieldnames);
	$fields = array_post_fields($fieldnames);
	foreach ($fields as $field) $_POST[$field] = format_null(format_url($_POST[$field]));
}

function format_q($quantity, $entity, $capitalize=true) {
	//alias for format_quantitize
	return format_quantitize($quantity, $entity, $capitalize);
}

function format_quantitize($quantity, $entity, $capitalize=true) {
	global $_josh;
	if ($quantity == 0) {
		$return = "no " . format_pluralize($entity);
	} elseif ($quantity == 1) {
		$return = "one " . $entity;
	} elseif (format_verify($quantity) && ($quantity < 10)) {
		$return = $_josh["numbers"][$quantity] . " " . format_pluralize($entity);
	} else {
		$return = $quantity . " " . format_pluralize($entity);
	}
	if ($capitalize) $return = format_title($return);
	return $return;
}

function format_quotes($value) {
	if (format_verify($value, "string")) $value = trim(str_replace("'", "''", stripslashes($value)));
	return $value;
}

function format_size($size) {
	$a = array("B", "KB", "MB", "GB", "TB", "PB");
	$pos = 0;
	while ($size >= 1024) {
		$size /= 1024;
		$pos++;
	}
	return round($size) . " " . $a[$pos];
}

function format_ssn($str) {
	return substr($str, 0, 3) . "-" . substr($str, 3, 2) . "-" . substr($str, 5, 4);
}

function format_string($str, $len=30, $tail="&hellip;") {
	if (strlen($str) > $len) {
		return substr($str, 0, ($len - 3)) . $tail;
	} else {
		return $str;
	}
}

function format_text_code($str) {
	$return = strToLower($str);
	$return = str_replace("/",	"_",	$return);
	$return = str_replace(" ",	"_",	$return);
	$return = str_replace("&",	"and",	$return);
	return $return;
}

function format_text_ends($needle, $haystack) {
	if (strToLower(substr($haystack, (0 - strlen($needle)))) == strToLower($needle)) return true;
	return false;
}

function format_text_human($str, $convertdashes=true) {
	$return = str_replace("_", " ", strToLower($str));
	if ($convertdashes) $return = str_replace("-", " ", $return);
	return format_title($return);
}

function format_text_shorten($text, $length=30, $append="&#8230;", $appendlength=1) {
	if ($append) $length = $length - $appendlength;
	if (strlen($text) > $length) return substr($text, 0, $length) . $append;
	return $text;
}

function format_text_starts($needle, $haystack) {
	if (strToLower(substr($haystack, 0, strlen($needle))) == strToLower($needle)) return true;
	return false;
}

function format_time($timestamp=false, $error="") {
	if ($timestamp === false) {
		$timestamp = time();
	} else {
		if (empty($timestamp) || ($timestamp == "Jan 1 1900 12:00AM")) return $error;
		if (!is_int($timestamp)) $timestamp = strToTime($timestamp);
	}
	return date("g:ia", $timestamp);
}

function format_time_business($start, $end=false) {
	$start = strToTime($start);
	if (empty($end) || !$end) {
		$end = date("U");
	} else {
		$end = strToTime($end);
	}
	$age = $end - $start;
	
	//days
	if ($age > 86400) {
		$days = 0;
		$finished = $start + 86400;
		for ($i = $finished; $i < $end; $i += 86400) {
			if (isWeekDay($i)) $days++;
			$finished = $i;
		}
		if ($days == 1) {
			$return[] = $days . " day";
		} elseif ($days > 1) {
			$return[] = $days . " days";
		}
	} else {
		$finished = $start;
	}

	//hours
	$hours = 0;
	for ($i = $finished + 3600; $i < $end; $i += 3600) {
		if (isBusinessHours($i)) $hours++;
		$finished = $i;
	}
	if ($hours == 1) {
		$return[] = $hours . " hour";
	} elseif ($hours > 1) {
		$return[] = $hours . " hours";
	}

	//minutes
	$minutes = round(($age % 3600) / 60);
	if ($minutes == 1) {
		$return[] = $minutes . " minute";
	} elseif ($minutes > 1) {
		$return[] = $minutes . " minutes";
	}
	
	if (empty($return)) {
		return "<i>just opened</i>";
	} else {
		return implode(", ", $return);
	}
}

//don't know how to categorize these - they only belong to the function above
function isBusinessHours($udate) {
	$hourOfDay = date("G", $udate);
	//echo format_date($udate, true, " ");
	return (($hourOfDay > 9) && ($hourOfDay < 17)) ? true : false;
}

function isWeekDay($udate) {
	$dayOfWeek = date("w", $udate);
	return (($dayOfWeek > 0) && ($dayOfWeek < 6)) ? true : false;
}
	

function format_time_exec($start_time=false, $descriptor=" seconds") {
	if (!$start_time) {
		global $_josh;
		if (isset($_josh["time_start"])) $start_time = $_josh["time_start"];
	}
	return round(microtime(true) - $start_time, 2) . $descriptor;
}

function format_times($num) {
	global $_josh;
	if ($num == 1) {
		return "once";
	} elseif ($num == 2) {
		return "twice";
	} elseif ($num < 10) {
		return $_josh["numbers"][$num] . " times";
	} else {
		return number_format($num) . " times";
	}
}

function format_title($str) {
	error_debug("<b>format_title</b> starting with " . $str);
	$return = array();
	$lower = array("a", "an", "and", "but", "for", "from", "if", "in", "nor", "of", "on", "or", "so", "the", "to", "via", "with");
	$mixed = array("DBs", "CBOs", "iPhone", "iPhones", "IDs", "IPs", "LLCs", "SSNs", "TinyMCE", "URLs", "WordPress");
	$upper = array("ADA", "ASAP", "BIF", "CCT", "CSS", "DB", "EBO", "FSS", "FTP", "HTML", "I", "II", "III", "IP", "IV", "LLC", "NHP", "NVN", "OMG", "ONYC", "OS", "PC", "PHP", "PLC", "RSS", "SF", "SFS", "SQL", "SSN", "URL", "V", "VI", "VII", "VIII", "WTF", "X", "XML");
	$words = explode(" ", ucwords(strToLower(trim($str))));
	$counter = 1;
	$max = count($words);
	foreach ($words as $word) {
		if (in_array(strToLower($word), $lower) && ($counter != 1) && ($counter != $max)) {
			$return[] = strToLower($word);
		} elseif (in_array(strToUpper($word), $upper)) {
			$return[] = strToUpper($word);
		} elseif (!empty($word)) {
			$index = array_search(strToLower($word), array_to_lower($mixed));
			if ($index !== false) { //could return 0, which would be valid
				$return[] = $mixed[$index];
			} else {
				$return[] = $word;
			}
		}
		$counter++;
	}
	return implode(" ", $return);
}

function format_url($str="") {
	if (empty($str)) return false;
	if (format_text_starts("http://", $str) || format_text_starts("https://", $str)) return $str;
	return "http://" . $str;
}

function format_verify($variable, $type="int") {
	error_debug("<b>format_verify</b> for " . $variable);
	if ($type == "int") {
		if (!is_numeric($variable)) return false;
		return ((string) $variable) === ((string)(int) $variable);
	} elseif ($type == "num") {
		if (!is_numeric($variable)) return false;
	} elseif ($type == "key") {
		if (strlen($variable) > 13) return false;
	} elseif ($type == "string") {
		if (!is_string($variable)) return false;
	}
	return true;
}

function format_zip($string, $error=false) { //format a ZIP (5-digit)
	$number = "";
	for ($i = 0; $i < strlen($string); $i++) if (is_numeric($string[$i])) $number .= $string[$i];
	if (strlen($number) >= 5) return substr($number, 0, 5);
	return $error;
}


?>