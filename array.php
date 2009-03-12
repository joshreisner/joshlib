<?php
error_debug("~ including array.php");

function array_key_compare_asc($a, $b) {
	//for use by array_sort() below
	global $_josh;
	error_debug("<b>arrayKeyCompare</b> comparing" . $a[$_josh["sort_key"]]);
	return strcmp($a[$_josh["sort_key"]], $b[$_josh["sort_key"]]);
}

function array_key_compare_desc($a, $b) {
	//for use by array_sort() below
	global $_josh;
	error_debug("<b>arrayKeyCompare</b> comparing" . $a[$_josh["sort_key"]]);
	return strcmp($b[$_josh["sort_key"]], $a[$_josh["sort_key"]]);
}

function array_key_filter($array, $key, $value) {
	//only return array keys of a particular value
	$return = array();
	foreach ($array as $element) {
		if ($element[$key] == $value) $return[] = $element;
	}
	return $return;
}

function array_post_fields($fieldnames, $delimiter=",") {
	//this function is used by format_post_nulls() etc to format $_POST variables
	//array is a comma-delimited string, spaces are ok
	$return = array();
	$fields = explode($delimiter, $fieldnames);
	foreach ($fields as $f) $return[] = trim($f);
	return $return;
}

function array_remove($needle, $haystack) {
	//remove an array element with a specific key.  arguments should probably be reversed?
	$return = array();
	foreach ($haystack as $value) if ($value != $needle) $return[] = $value;
	return $return;
}

function array_send($array, $target) {
	global $_josh;
	//'send' an array in the form of a POST request to a remote website.  function name perhaps to poetic.  
	
	//must have JSON
	if (!function_exists("json_encode")) return error_handle("JSON Not Installed", "You need the JSON library for array_send to work.");
	$postdata = utf8_encode(json_encode($array));

	$target = url_parse($target);

	if ($target["host"] == $_josh["request"]["host"]) continue; //i'm worried that since API site uses joshlib, an error loop could be created
	
	if ($pointer = fsockopen($target["host"], 80, $errno, $errstr, 30)) {
		error_debug("<b>array_send</b> has a stream to " . $target["host"]);
		fputs($pointer, "POST " . $target["path_query"] . " HTTP/1.0\r\n");
		fputs($pointer, "Host: " . $target["host"] . "\r\n");
		fputs($pointer, "Content-type: application/json; charset=utf-8\r\n");
		fputs($pointer, "Content-length: " . strlen($postdata) . "\r\n");
		fputs($pointer, "User-agent: Mozilla/4.0 (compatible: MSIE 7.0; Windows NT 6.0)\r\n");
		fputs($pointer, "Connection: close\r\n\r\n");
		fputs($pointer, $postdata);
		
		//get server response and strip it of http headers
		$response = "";
		while (!feof($pointer)) $response .= fgets($pointer, 128);
		fclose($pointer);		
		error_debug("<b>array_send</b> was returned " . $response);
		$response = substr($response, strpos($response, "\r\n\r\n") + 4);
		if ($response == "you seem like a nice enough person") return true;
	}
	echo $response;
	return false;	
}

function array_sort($array, $direction="asc", $key=false) {
	//sort an array by the names of its keys
	global $_josh;
	$_josh["sort_key"] = ($key) ? $key : array_shift(array_keys($array[0]));
	error_debug("<b>arraySort</b> running for $key");
	usort($array, "array_key_compare_" . strToLower($direction));
	return $array;
}

function array_to_lower($array) {
	//format a one-dimensional array in lowercase
	//used in format_title()
	if (!is_array($array)) return false;
	$return = array();
	foreach ($array as $a) $return[] = strToLower($a);
	return $return;
}

function array_url($str, $defaults=false, $separator="&") {
	//takes a key/pair string in the form you'd find in a query string and returns an array
	//separator is an argument because cookie strings are separated with semicolons
	$return = array();
	$pairs = explode($separator, $str);
	foreach ($pairs as $p) {
		list($key, $value) = explode("=", trim($p));
		$return[urldecode($key)] = urldecode($value);
	}
	return $return;
}
?>