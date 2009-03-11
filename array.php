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
	$content = utf8_encode(json_encode($array));

	$target = url_parse($target);

	//assemble message
	$headers	= array();
	$headers[]	= 'POST ' . $target["path_query"] . ' HTTP/1.0';
	$headers[]	= 'Content-Type: text/html';
	$headers[]	= 'Host: ' . $_josh["request"]["host"];
	$headers[]	= 'Content-Length: ' . strlen($content);
	$headers[]	= 'Connection: Close';
	$headers	= implode("\r\n", $headers) . "\r\n\r\n" . $content;
	error_debug("<b>array_send</b> headers are " . $headers);
	
	//send message
	if ($pointer = fsockopen($target["host"], 80, $errno, $errstr)) {
		error_debug("<b>array_send</b> has a stream to " . $target["host"]);
	
		if (!fwrite($pointer, $headers)) {
		    fclose($pointer);
		    return false;
		}
	
		//get response (it's only polite)
		$response = '';
		while(!feof($pointer)) { $response .= fgets($pointer, 8192); }
		fclose($pointer);
		echo $response;
		error_debug("<b>array_send</b> was returned " . $response);
	}
	//don't know what the behavior should be now
	return true;	
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
?>