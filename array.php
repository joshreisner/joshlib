<?php
error_debug('including array.php', __file__, __line__);

function array_2d($array) {
	//to take a scalar array and convert it to a two-dimensional array by doubling the keys / values
	//used by draw_form_date() and other places
	$return = array();
	foreach ($array as $a) $return[$a] = $a;
	return $return;
}

function array_ajax($required=false) {
	//returns an array of ajax-posted content
	//if (!$source) $source = file_get_contents('php://input');
	$array = url_query_parse(file_get_contents('php://input'));
	foreach ($array as $key=>$value) $array[$key] = format_quotes($value);
	if ($required) {
		$required = array_separated($required);
		foreach ($required as $r) if (!isset($array[$r])) exit; //intended to just quietly bomb.  lots of spiders seem to want to post empty to ajax urls for some reason.
	}
	return $array;
}

function array_arguments($arguments=false) {
	if (!$arguments) return array();
	if (is_string($arguments)) return array('class'=>$arguments);
	return $arguments;
}

function array_chunk_html($string, $length) {
	//split an html string into smaller chunks while not breaking inside an HTML tag
	//used by language_translate

	//maybe string doesn't need to be split bc is too short
	if (strlen($string) < $length) return array($string);
	
	$words = array_explode_html($string, ' ');
	$wordcount = count($words);
	
	//iterator variables
	$lengthcounter = 0;
	$wordcounter = 0;
	$array_position = 0;
	$return = array($array_position=>'');

	foreach ($words as $w) {
		$wordcounter++;
		if ($wordcounter != $wordcount) $w .= ' ';
		$wordlength = strlen($w);
		
		if ($wordlength > $length) {
			//todo - just break the word up anyway?
			error_handle('word too long', 'array_chunk_html has a word ' . $w . ' that longer than ' . $length . ' characters, which is set as its max');
			return '';
		}
		
		if (($lengthcounter + $wordlength) <= $length) {
			//add to current array position
			$return[$array_position] .= $w;
			$lengthcounter += $wordlength;
		} else {
			//add to new array position, reset counter
			$array_position++;
			$return[$array_position] = $w;
			$lengthcounter = $wordlength;
		}
	}
	
	return $return;
}

function array_csv($content, $delimiter=',') {
	global $_josh;
	error_debug('doing an array csv on delimiter ' . $delimiter, __file__, __line__);
	//input function.  pass it a file_get() 'ed CSV and it will give you an associative array back
	//assumes first line is header
	//written by josh on 5/15/09 for work mgmt: harvest import
	
	$rows = array_separated($content, NEWLINE);
	
	//parse header
	$columns = array();
	$header = array_shift($rows);
	$cells = explode($delimiter, trim($header));
	foreach ($cells as $c) $columns[] = format_text_code($c);
	$count = count($columns);
	
	//do rows
	$return = array();
	foreach ($rows as $r) {
		$cells = array_separated($r, $delimiter);
		$thisrow = array();
		for ($i = 0; $i < $count; $i++) $thisrow[$columns[$i]] = trim(@$cells[$i]);
		$return[] = $thisrow;
	}
	
	return $return;
}

function array_explode_html($string, $separator) { 
	//used by array_chunk_html
	//splits an HTML string into an array like explode() but keeps HTML tags together
	//adapted from stefan at NOSPAM dot elakpistol dot com http://theserverpages.com/php/manual/en/function.explode.php
	$return = array();
	for ($i = 0, $j = 0; $i < strlen($string); $i++) {
		if ($string[$i] == $separator) {
			while ($string[$i + 1] == $separator) $i++;
			$j++;
			continue;
		}
		if (!isset($return[$j])) $return[$j] = '';
		if ($string[$i] == '<') {
			if (strlen($return[$j]) > 0) $j++;
			$pos = strpos($string, '>', $i);
			if (isset($return[$j])) {
				$return[$j] .= substr($string, $i, $pos - $i + 1);
			} else {
				$return[$j] = substr($string, $i, $pos - $i + 1);
			}
			$i += ($pos - $i);
			$j++;
			continue;
		}
		if ((($string[$i] == "\n") || ($string[$i] == "\r")) && (strlen($return[$j]) == 0)) continue;
		$return[$j] .= $string[$i];
	}
	return $return;
}

function array_insert($array, $position, $value) {
	//insert a $value into a flat $array at a particular $position
    $array_clip = array_splice($array, $position);
    $array[] = $value;
    $array = array_merge($array, $array_clip);
    return $array;
}

function array_insert_assoc($array, $position, $key, $value) {
	//insert a $value into an associative $array at a particular $position
    $array_clip = array_splice($array, $position);
    $array[$key] = $value;
    $array = array_merge($array, $array_clip);
    return $array;
}

function array_key_filter($array, $key, $value) {
	//only return array keys of a particular value
	$return = array();
	foreach ($array as $element) if ($element[$key] == $value) $return[] = $element;
	return $return;
}

function array_object($object) {
	//convert object to associative array
    if (is_object($object)) $object = get_object_vars($object);
    return is_array($object) ? array_map(__FUNCTION__, $object) : $object;
}

function array_post_checkboxes($field_name) {
	//used by db_checkboxes and living cities internal newsletter
	$return = array();
	foreach ($_POST as $key=>$value) {
		$array = explode('-', $key);
		if ((count($array) == 3) && ($array[0] == 'chk') && ($array[1] == $field_name)) $return[] = $array[2];
	}
	return $return;
}

/* function array_post_fields($fieldnames, $delimiter=',') {
	error_deprecated(__function__ . ' was deprecated on 3/11/2010.  use array_separated instead');
	return array_separated($fieldnames, $delimiter);
}*/

function array_range($start, $end, $increment=1) {
	//numeric, sequential arrays for draw_form_date
	$return = array();
	if (($increment > 0) && ($start < $end)) {
		//ascending increment
		while ($start <= $end) {
			$return[] = $start;
			$start += $increment;
		}
	} elseif (($increment < 0) && ($start > $end)) {
		//descending increment
		while ($start >= $end) {
			$return[] = $start;
			$start -= $increment;
		}
	}
	return $return;
}

function array_query_string($str, $defaults=false, $separator='&') {
	//takes a key/pair string in the form you'd find in a query string and returns an array
	//separator is an argument because cookie strings are separated with semicolons
	$return = array();
	$pairs = explode($separator, $str);
	foreach ($pairs as $p) {
		list($key, $value) = explode('=', trim($p));
		$return[urldecode($key)] = urldecode($value);
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
	//POST an array as a JSON post request to a remote site
	global $_josh;
		
	//prepare POSTdata
	if (is_array($array)) {
		//must have JSON
		if (!function_exists('json_encode')) return error_handle('JSON Not Installed', 'You need the JSON library for array_send to work.');
		$postdata = utf8_encode(json_encode($array));
	} else {
		$postdata = $array;
	}

	$target = url_parse($target);

	//check to make sure is REMOTE host -- can't be posting to yrself
	if ($target['host'] == $_josh['request']['host']) continue;
		
	if ($pointer = fsockopen($target['host'], 80, $errno, $errstr, 30)) {
		error_debug('<b>array_send</b> has a stream to ' . $target['host'], __file__, __line__);
		fputs($pointer, 'POST ' . $target['path_query'] . ' HTTP/1.0\r\n');
		fputs($pointer, 'Host: ' . $target['host'] . '\r\n');
		fputs($pointer, 'Content-type: application/json; charset=utf-8\r\n');
		fputs($pointer, 'Content-length: ' . strlen($postdata) . '\r\n');
		fputs($pointer, 'User-agent: Mozilla/4.0 (compatible: MSIE 7.0; Windows NT 6.0)\r\n');
		fputs($pointer, 'Connection: close\r\n\r\n');
		fputs($pointer, $postdata);
		
		//get server response and strip it of http headers
		$response = '';
		while (!feof($pointer)) $response .= fgets($pointer, 128);
		fclose($pointer);		
		error_debug('<b>array_send</b> was returned ' . $response, __file__, __line__);
		$response = substr($response, strpos($response, '\r\n\r\n') + 4);
	}
	echo $response;
	return false;	
}

function array_separated($content, $separator=',') {
	//like explode, but strips empty spaces and null content
	
	if (is_array($content)) return $content; //might not need splitting

	$return = array();
	$fields = explode($separator, $content);
	foreach ($fields as $f) {
		$f = trim($f);
		if (!empty($f)) $return[] = $f;
	}
	return $return;
}

function array_slice_assoc($array, $start=false, $length=false) {
	$keys = array_slice(array_keys($array), $start, $length);
    return array_intersect_key($array, array_2d($keys));
}

function array_sort($array, $direction='asc', $key=false) {
	//sort an array's values for a particular key
	global $_josh;
	
	//key defaults to the first key
	$_josh['sort_key'] = ($key) ? $key : array_shift(array_keys($array[0]));
	
	error_debug('<b>arraySort</b> running for $key', __file__, __line__);
	
	//define our custom callback functions
	if (!function_exists('array_sort_asc')) {
		function array_sort_asc($a, $b) {
			global $_josh;
			error_debug('<b>arrayKeyCompare</b> comparing' . $a[$_josh['sort_key']], __file__, __line__);
			return strcmp($a[$_josh['sort_key']], $b[$_josh['sort_key']]);
		}
	}

	if (!function_exists('array_sort_desc')) {
		function array_sort_desc($a, $b) {
			global $_josh;
			error_debug('<b>arrayKeyCompare</b> comparing' . $a[$_josh['sort_key']], __file__, __line__);
			return strcmp($b[$_josh['sort_key']], $a[$_josh['sort_key']]);
		}
	}
	
	usort($array, 'array_sort_' . strToLower($direction));

	//don't need this anymore
	unset($_josh['sort_key']);
	
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

/* function array_url($str, $defaults=false, $separator='&') {
	error_deprecated(__function__ . ' was deprecated on 3/11/2010.  use array_query_string instead.  this one\'s going to go fast because url_parse is going to fill this spot.');
	return array_query_string($str, $defaults, $separator);
} */

function array_random($array) {
	//return a random value from a one-dimensional array
	return $array[rand(0, count($array)-1)];
}

function array_xml($stringxml) {
	//for harvest import -- take data in string xml format and return it as an associative array
	//todo verify this works
	$data = new SimpleXMLElement($stringxml);
	if (is_object($data)) return array_object($data->children());
}
?>