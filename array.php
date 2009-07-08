<?php
error_debug("including array.php", __file__, __line__);

function array_2d($array) {
	//to take a scalar array and convert it to a 2d array by doubling the keys / values
	//what's it used for??
	$return = array();
	foreach ($array as $a) $return[$a] = $a;
	return $return;
}

function array_ajax($source=false) {
	//returns an array of ajax-posted content
	if (!$source) $source = file_get_contents("php://input");
	return url_query_parse(urldecode($source));
}

function array_csv($content, $delimiter=",") {
	error_debug("doing an array csv on delimiter " . $delimiter, __file__, __line__);
	//input function.  pass it a file_get() 'ed CSV and it will give you an array back
	//if a header row is present, it will return an array of associative arrays
	//written by josh on 5/15/09 for work mgmt: harvest import
	//todo == make header optional
	
	$rows = explode("\n", trim($content));
	
	//parse header
	$columns = array();
	$header = array_shift($rows);
	$cells = explode($delimiter, trim($header));
	foreach ($cells as $c) $columns[] = format_text_code($c);
	$count = count($columns);
	
	//do rows
	$return = array();
	foreach ($rows as $r) {
		$cells = explode($delimiter, trim($r));
		$thisrow = array();
		for ($i = 0; $i < $count; $i++) $thisrow[$columns[$i]] = trim(@$cells[$i]);
		$return[] = $thisrow;
	}
	
	return $return;
}

function array_insert($array, $position, $value) {
	//insert an arbitrary $value into a flat $array at a particular $position
    $array_clip = array_splice($array, $position);
    $array[] = $value;
    $array = array_merge($array, $array_clip);
    return $array;
}

function array_key_compare_asc($a, $b) {
	//for use by array_sort() below
	//can't imagine a situation where you'd use this normally
	//todo == figure out whether these should be encapsulated in array_sort
	global $_josh;
	error_debug("<b>arrayKeyCompare</b> comparing" . $a[$_josh["sort_key"]], __file__, __line__);
	return strcmp($a[$_josh["sort_key"]], $b[$_josh["sort_key"]]);
}

function array_key_compare_desc($a, $b) {
	//for use by array_sort() below
	//can't imagine a situation where you'd use this normally
	//todo == figure out whether these should be encapsulated in array_sort
	global $_josh;
	error_debug("<b>arrayKeyCompare</b> comparing" . $a[$_josh["sort_key"]], __file__, __line__);
	return strcmp($b[$_josh["sort_key"]], $a[$_josh["sort_key"]]);
}

function array_key_filter($array, $key, $value) {
	//only return array keys of a particular value
	//todo ~~ what's this used for?  something tells me jeffrey or coddington?
	$return = array();
	foreach ($array as $element) if ($element[$key] == $value) $return[] = $element;
	return $return;
}

function array_post_fields($fieldnames, $delimiter=",") {
	//this function is used by format_post_nulls() etc to format $_POST variables
	//array is a comma-delimited string, spaces are ok
	//this should be renamed to be more generic, and/or combined with array_csv
	$return = array();
	$fields = explode($delimiter, $fieldnames);
	foreach ($fields as $f) $return[] = trim($f);
	return $return;
}

function array_post_filter($control, $delimiter="_") {
	//this was started for livingcities, but applicable for db_checkboxes
	global $_POST;
	$return = array();
	$trim_length = strlen($control) + strlen($delimiter);
	foreach ($_POST as $key=>$value) {
		$array = explode($delimiter, $key);
		if ($array[0] == $control) {
			$return[substr($key, $trim_length)] = $value;
		}
	}
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
		error_debug("<b>array_send</b> has a stream to " . $target["host"], __file__, __line__);
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
		error_debug("<b>array_send</b> was returned " . $response, __file__, __line__);
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
	error_debug("<b>arraySort</b> running for $key", __file__, __line__);
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