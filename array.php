<?php
error_debug('including array.php', __file__, __line__);

function array_2d($array) {
	//to take a scalar array and convert it to a two-dimensional array by doubling the keys / values
	error_deprecated();
	$return = array();
	foreach ($array as $a) $return[$a] = $a;
	return $return;
}

function array_ajax($source=false) {
	//returns an array of ajax-posted content
	if (!$source) $source = file_get_contents('php://input');
	$array = url_query_parse($source);
	foreach ($array as $key=>$value) $array[$key] = format_quotes($value);
	return $array;
}

function array_chunk_html($string, $length) {
	//split an html string into smaller chunks while not breaking tags
	//used by language_translate

	//maybe string doesn't need to be split bc is too short
	if (strlen($string) < $length) return array($string);
	
	$words = array_explode_html(' ', $string);
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
	//input function.  pass it a file_get() 'ed CSV and it will give you an array back
	//if a header row is present, it will return an array of associative arrays
	//written by josh on 5/15/09 for work mgmt: harvest import
	//todo == make header optional
	
	$rows = explode($_josh['newline'], trim($content));
	
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

function array_explode_html($separator, $string) { 
	//used by array_chunk_html
	//splits an HTML string into an array like explode() but treats HTML tags as a piece
	//lifted from stefan at NOSPAM dot elakpistol dot com http://theserverpages.com/php/manual/en/function.explode.php
	//made modifications -- todo clean up
	$res = array();
	for ($i=0, $j=0; $i < strlen($string); $i++) { 
		if ($string{$i} == $separator) { 
		   while ($string{$i+1} == $separator) 
		   $i++; 
		   $j++; 
		   continue; 
		}
		if (!isset($res[$j])) $res[$j] = '';		
		if ($string{$i} == '<') { 
		   if (strlen($res[$j]) > 0) $j++; 
		   $pos = strpos($string, '>', $i); 
		   if (isset($res[$j])) {
			   $res[$j] .= substr($string, $i, $pos - $i+1); 
		   } else {
			   $res[$j] = substr($string, $i, $pos - $i+1); 
		   }
		   $i += ($pos - $i); 
		   $j++; 
		   continue; 
		} 
		if ((($string{$i} == "\n") || ($string{$i} == "\r")) && (strlen($res[$j]) == 0)) continue; 
		$res[$j] .= $string{$i}; 
	} 
	return $res; 
}

function array_insert($array, $position, $value) {
	//insert an arbitrary $value into a flat $array at a particular $position
    $array_clip = array_splice($array, $position);
    $array[] = $value;
    $array = array_merge($array, $array_clip);
    return $array;
}

function array_key_filter($array, $key, $value) {
	//only return array keys of a particular value
	//todo ~~ what's this used for?  something tells me jeffrey or coddington?
	$return = array();
	foreach ($array as $element) if ($element[$key] == $value) $return[] = $element;
	return $return;
}

function array_object($object) {
    if (is_object($object)) $object = get_object_vars($object);
    return is_array($object) ? array_map(__FUNCTION__, $object) : $object;
}

function array_post_fields($fieldnames, $delimiter=',') {
	//this function is used by format_post_nulls() etc to format $_POST variables
	//array is a comma-delimited string, spaces are ok
	//this should be renamed to be more generic, and/or combined with array_csv
	$return = array();
	$fields = explode($delimiter, $fieldnames);
	foreach ($fields as $f) $return[] = trim($f);
	return $return;
}

function array_post_filter($control, $delimiter='_') {
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

function array_remove($needle, $haystack) {
	//remove an array element with a specific key.  arguments should probably be reversed?
	$return = array();
	foreach ($haystack as $value) if ($value != $needle) $return[] = $value;
	return $return;
}

function array_send($array, $target) {
	//POST an array as a JSON post request to a remote site
	global $_josh;
	
	debug();
	
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

function array_url($str, $defaults=false, $separator='&') {
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

function array_xml($stringxml) {
	//for harvest import -- take data in string xml format and return it as an associative array
	$data = new SimpleXMLElement($stringxml);
	if (is_object($data)) {
		return array_object($data->children());
	}
}
?>