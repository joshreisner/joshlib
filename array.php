<?php
//a collection of functions that return arrays
//code reviewed 10/16/2010

error_debug('including array.php', __file__, __line__);

function array_2d($array) {
	//takes a normal array and makes it associative by doubling, eg (1, 2, 'foo') becomes (1=>1, 2=>2, 'foo'=>'foo'), used by draw_form_date() and elsewhere
	$return = array();
	foreach ($array as $a) $return[$a] = $a;
	return $return;
}

function array_ajax($required_values=false) {
	//returns an array of ajax-posted content
	$array = url_query_parse(file_get_contents('php://input'));
	foreach ($array as $key=>$value) $array[$key] = format_quotes($value);
	if ($required_values) {
		$required_values = array_separated($required_values);
		foreach ($required_values as $r) if (!isset($array[$r])) exit; //intended to just quietly bomb.  lots of spiders seem to want to post empty to ajax urls for some reason.
	}
	return $array;
}

function array_argument(&$array, $value, $key='class', $separator=' ') {
	//for appending class values, eg class="foo bar".  you don't need to know if it exists when you array_argument it.  used by several draw_ functions
	if (empty($value)) return false;
	if (empty($array[$key])) {
		$array[$key] = $value;
	} else { 
		$array[$key] .= $separator . $value;
	}
}

function array_arguments($arguments=false) {
	//for constructing an array of tag arguments, used by draw_ functions.
	if (!$arguments) return array();
	if (is_string($arguments)) return array('class'=>$arguments);
	return $arguments;
}

function array_checkboxes($name, $array='post') {
	//finds checkbox values in an array, default POSTDATA.  used by db_checkboxes()
	if ($array == 'post') $array = $_POST;
	if (empty($array)) return false;
	$return = array();
	foreach ($array as $key=>$value) {
		$parts = explode('-', $key);
		if ((count($parts) == 3) && ($parts[0] == 'chk') && ($parts[1] == $name)) $return[] = $parts[2];
	}
	return $return;
}

function array_chunk_html($string, $length) {
	//split an html string into an array of strings while not breaking inside a tag, used by language_translate()

	if (strlen($string) < $length) return array($string);
	
	$words = array_explode_html($string, ' ');
	$wordcount = count($words);
	
	//iterator variables
	$lengthcounter	= 0;
	$wordcounter	= 0;
	$array_position	= 0;
	$return = array($array_position=>'');

	foreach ($words as $w) {
		$wordcounter++;
		if ($wordcounter != $wordcount) $w .= ' ';
		$wordlength = strlen($w);
		
		if ($wordlength > $length) {
			//todo - just break the word up anyway?
			error_handle('word too long', 'array_chunk_html has a word ' . $w . ' that longer than ' . $length . ' characters, which is set as its max', __file__, __line__);
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
	//makes an associative array out of a delimited file, assumes first line is header
	
	//set up header
	$rows	= array_separated($content, "\r", true);
	$cols	= explode($delimiter, array_shift($rows));
	error_debug(count($rows) . ' rows and ' . count($cols) . ' cols found in content', __file__, __line__);
	foreach ($cols as &$c) $c = format_text_code($c);
	$count	= count($cols);

	//do rows
	foreach ($rows as &$r) {
		$cells = array_separated($r, $delimiter, true);
		$thisrow = array();
		for ($i = 0; $i < $count; $i++) $thisrow[$cols[$i]] = @$cells[$i];
		$r = $thisrow;
	}
	
	return $rows;
}

function array_explode_html($string, $separator) { 
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
	//inserts a $value into a flat $array at a particular $position
    $array_clip = array_splice($array, $position);
    $array[] = $value;
    return array_merge($array, $array_clip);
}

function array_insert_assoc($array, $position, $key, $value) {
	//insert a $value into an associative $array at a particular $position
	//todo possibly combine with array_insert where value is an array(key=>value)
    $array_clip = array_splice($array, $position);
    $array[$key] = $value;
    return array_merge($array, $array_clip);
}

function array_instances($array, $needle) {
	//returns a count of all the instances of needle in array.  used by db_words()
	$count = 0;
	foreach ($array as $value) if ($needle == $value) $count++;
	return $count;
}

function array_key_filter($array, $key, $value) {
	//returns an $array's keys of a particular value.  used by PLC
	//todo deprecate once PLC is backended
	$return = array();
	foreach ($array as $element) if ($element[$key] == $value) $return[] = $element;
	return $return;
}

function array_key_group($array, $key=false) {
	//take and array and 
	//used on living cities new home page for member list
	$return = array();
	foreach ($array as $a) {
		if (!$key) $key = array_shift(array_keys($a));
		if (isset($return[$a[$key]])) {
			$return[$a[$key]][] = $a;
		} else {
			$return[$a[$key]] = array($a);
		}
	}
	return $return;
}

function array_key_promote($array) {
	//makes a two-column resultset into an associative array. used by draw_nav()
	$return = array();
	foreach ($array as $a) {
		$keys = array_keys($a);
		$return[$a[$keys[0]]] = $a[$keys[1]];
	}
	return $return;
}

function array_key_values($array, $key) {
	//take an associative array's values for particular key and return a 1-d array of it
	$return = array();
	foreach ($array as $a) $return[] = $a[$key];
	return $return;
}

function array_object($object) {
	//converts an object to an associative array recursively.  used by array_xml()
    if (is_object($object)) $object = get_object_vars($object);
    return is_array($object) ? array_map(__FUNCTION__, $object) : $object;
}

function array_post_checkboxes($field_name) {
	error_deprecated(__function__ . ' was deprecated on 10/20/2010 because it has been generalized into array_checkboxes');
	return array_checkboxes($field_name);
}

function array_range($start, $end, $increment=1) {
	//returns an array of numeric values.  used by draw_form_date()
	$return = array();
	$increment = abs($increment);
	if ($start < $end) {
		while ($start <= $end) {
			$return[] = $start;
			$start += $increment;
		}
	} elseif ($start > $end) {
		while ($start >= $end) {
			$return[] = $start;
			$start -= $increment;
		}
	}
	return $return;
}

function array_query_string($string, $defaults=false, $separator='&') {
	//coverts a key/value string eg key=value&foo=bar and returns an array
	//separator is a variable because cookie strings are separated with semicolons
	$return	= array();
	$pairs	= array_separated($string, $separator);
	foreach ($pairs as $p) {
		list($key, $value) = array_separated(urldecode($p), '=');
		$return[$key] = $value;
	}
	return $return;
}

function array_receive() {
	//receive json data.  is a pair with array_send()
	return json_decode(file_get_contents('php://input'), true);
}

function array_remove($needle, $haystack) {
	//removes an array element with a specific key.  arguments should probably be reversed?
	//is the opposite of array_filter?
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
		if (!function_exists('json_encode')) return error_handle('JSON Not Installed', 'You need the JSON library for array_send to work.', __file__, __line__);
		$postdata = utf8_encode(json_encode($array));
	} else {
		$postdata = $array;
	}

	$target = url_parse($target);

	//check to make sure is REMOTE host -- can't be posting to yrself
	if ($target['host'] == $_josh['request']['host']) continue;
		
	if ($pointer = fsockopen($target['host'], 80, $errno, $errstr, 30)) {
		error_debug('<b>' . __function__ . '</b> has a stream to ' . $target['host'], __file__, __line__);
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
		error_debug('<b>' . __function__ . '</b> was returned ' . $response, __file__, __line__);
		$response = substr($response, strpos($response, '\r\n\r\n') + 4);
	}
	echo $response;
	return false;	
}

function array_separated($content, $separator=',', $preserve_nulls=false) {
	//returns an array from a string like explode does, but it trims values.  used widely.
	if (is_array($content)) return $content; //might not need splitting
	$return = array();
	$fields = explode($separator, $content);
	foreach ($fields as $f) {
		$f = trim($f);
		if (!empty($f) || $preserve_nulls) $return[] = $f;
	}
	return $return;
}

function array_slice_assoc($array, $start=false, $length=false) {
	//returns a subsection of an array, like array_slice, but works with associative arrays.  used in NBI and PSA
	$keys = array_slice(array_keys($array), $start, $length);
    return array_intersect_key($array, array_2d($keys));
}

function array_sort($array, $direction='asc', $key=false) {
	//sorts an associative array by its values.  used by file_folder()
	
	if (!function_exists('array_sort_asc')) {
		function array_sort_asc($a, $b) {
			global $_josh;
			return strcmp($a[$_josh['sort_key']], $b[$_josh['sort_key']]);
		}
	}

	if (!function_exists('array_sort_desc')) {
		function array_sort_desc($a, $b) {
			global $_josh;
			return strcmp($b[$_josh['sort_key']], $a[$_josh['sort_key']]);
		}
	}
	
	//$key defaults to be the first key
	global $_josh;
	$_josh['sort_key'] = ($key) ? $key : array_shift(array_keys($array[0]));
	error_debug('<b>' . __function__ . '</b> running for ' . $key, __file__, __line__);
	usort($array, 'array_sort_' . strToLower($direction));
	unset($_josh['sort_key']);	
	return $array;
}

function array_to_lower($array) {
	//formats a one-dimensional array in lowercase.  used in format_title()
	if (!is_array($array)) return false;
	foreach ($array as &$a) $a = strToLower($a);
	return $array;
}

function array_random($array) {
	//returns a random value from a one-dimensional array
	return $array[rand(0, count($array)-1)];
}

function array_rss($url) {
	//returns an associative array from a $url
	if ($xml = url_get($url)) return array_xml($xml);
	return false;
}

function array_xml($string) {
	//reads xml data into an associative array
	$data = new SimpleXMLElement($string);
	if (is_object($data)) return array_object($data->children());
}
?>