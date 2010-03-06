<?php
/*
this section is all formatting functions, usually to format strings into special configurations

todo ~ deprecate this entire library and start a string library?
*/
error_debug('including format.php', __file__, __line__);

function format_accents_encode($string) {
	//not sure if this is necessary anymore with the conversion to utf8
	$string = str_replace('“', '&ldquo;',	$string);
	$string = str_replace('”', '&rdquo;',	$string);
	$string = str_replace('‘', '&lsquo;',	$string);
	$string = str_replace('’', '&rsquo;',	$string);
	$string = str_replace('–', '&mdash;',	$string);
	$string = str_replace('ä', '&auml;',	$string);
	$string = str_replace('ç', '&ccedil;',	$string);
	$string = str_replace('é', '&eacute;',	$string);
	$string = str_replace('ñ', '&ntilde;',	$string);
	$string = str_replace('ü', '&uuml;',	$string);
	return $string;
}

function format_array_text($array) {
	if (!is_array($array)) {
		//string
		return $array;
	} elseif (!count($array)) {
		//empty array
		return '';
	} elseif (count($array) == 1) {
		return $array[0];
	} else {
		$last = array_pop($array);
		return implode(', ', $array) . ' and ' . $last;
	}
}

function format_ascii($string) {
	//used by draw_link() for email obfuscation
	$len = strlen($string);
	$return = '';
	for ($i = 0; $i < $len; $i++) $return .= '&#' . ord($string[$i]) . ';';
	return $return;
}

function format_binary($blob) {
	//todo -- db_binary?
	global $_josh;
	if ($_josh['db']['language'] == 'mssql') {
		$return = unpack('H*hex', $blob);
		return '0x' . $return['hex'];
	} elseif ($_josh['db']['language'] == 'mysql') {
		return '"' . addslashes($blob) . '"';
	}
}

function format_boolean($value, $options='Yes|No') {
	$options = explode('|', $options);
	if ($value) return $options[0];
	return $options[1];
}

function format_check($variable, $type='int') {
	//todo compile a set of cases where we use format_check, format_num, format_numeric, format_verify.  i think this can be simplifed
	//alias
	return format_verify($variable, $type);
}

function format_code($code) {
	//afaik this is just for formatting error messages.  
	return '<p style="font-family:courier; font-size:13px;">' . nl2br(str_replace('\t', '&nbsp;', htmlentities($code))) . '</p>';
}

function format_date($timestamp=false, $error='', $format='%b %d, %Y', $relativetime=true) {
	global $_josh;

	if ($timestamp === false) $timestamp = time();

	//reject or convert
	if (empty($timestamp) || ($timestamp == 'Jan 1 1900 12:00AM')) return $error;
	if (!is_int($timestamp)) $timestamp = strToTime($timestamp);
	
	//special thing to format for sql
	if (stristr($format, 'sql')) return date('Y-m-d H:i:00', $timestamp);

	if ($relativetime) {
		//get timestamp for today
		$todaysdate = mktime(0, 0, 1, $_josh['month'], $_josh['today'], $_josh['year']);
	
		//get timestamp for argument, without time
		$returnday    = date('d', $timestamp);
		$returnyear   = date('Y', $timestamp);
		$returnmonth  = date('n', $timestamp);
		$returndate   = mktime(0, 0, 1, $returnmonth, $returnday, $returnyear);
			
		//setup return date
		$datediff = ($returndate - $todaysdate) / 86400;
		if ($datediff == 0) {
			$return = $_josh['date']['strings'][1];
		} elseif ($datediff == -1) {
			$return = $_josh['date']['strings'][0];
		} elseif ($datediff == 1) {
			$return = $_josh['date']['strings'][2];
		} elseif (($datediff < -1) && ($datediff > -7)) { //last six days
			$return = strftime('%A', $timestamp); //return day of week
			//$return = date('l', $timestamp); //return day of week
		} else {
			$return = strftime($format, $timestamp);
			//$return = date($format, $timestamp); //M d, Y
		}
	} else {
		$return = strftime($format, $timestamp);
		//$return = date($format, $timestamp);
	}
	
	if ($return === 1) return $error;
	return $return;
}

function format_date_iso8601($timestamp=false) {
	//this looks like DATE_W3C http://www.php.net/manual/en/datetime.constants.php
	//use this for xml
	if (!$timestamp) $timestamp = time();
	if (!is_int($timestamp)) $timestamp = strToTime($timestamp);
	return date('Y-m-d', $timestamp) . 'T' . date('H:i:s', $timestamp) . '-07:00';
}

function format_date_rss($timestamp=false) {
	//todo ~ define difference between this and format_date_iso8601 above?
	if (!$timestamp) $timestamp = time();
	if (!is_int($timestamp)) $timestamp = strToTime($timestamp);
	return date(DATE_RSS, $timestamp);
}

function format_date_sql($month, $day=false, $year=false, $hour=false, $minute=false, $second=false) {	
	//format a date for sql

	if (!$day) {
		//new functionality; month could be a timestamp that needs to be converted a sql-ready date 
		if (empty($month)) return 'NULL';
		$date = strToTime($month);
	} elseif (!$hour) {
		//restore old defaults
		$hour = 0;
		$minute = 0;
		$second = 1;
		$date = mktime($hour, $minute, $second, $month, $day, $year);
	} else {
		$date = mktime($hour, $minute, $second, $month, $day, $year);
	}
	
	return '"' . date('Y-m-d H:i:00', $date) . '"';
}

function format_date_time($timestamp=false, $error='', $separator='&nbsp;', $suppressMidnight=true, $relativetime=true) {
	//string_datetime?
	if ($timestamp === false) $timestamp = time();
	$return = format_date($timestamp, $error, '%b %d, %Y', $relativetime);
	//if (($return == 'Today') || ($return == 'Yesterday') || ($return == 'Tomorrow')) 
	$time = format_time($timestamp);
	if ($suppressMidnight && ($time == '12:00am')) return $return;
	return $return . $separator . $time;;
}

function format_date_time_range($start, $end) {
	//return a string date range, like Jan 21-22 2010, or Jan 8 from 11-11:30am
	$start	= strtotime($start);
	$end	= strtotime($end);
	
	if (date('Y', $start) == date('Y', $end)) {
		if (date('n', $start) == date('n', $end)) {
			if (date('j', $start) == date('j', $end)) {
				if (date('a', $start) == date('a', $end)) {
					//same am/pm
					return format_date($start) . ' ' . date('g', $start) . '-' . date('g', $end) . date('a', $end);
				}
				//same day
				return format_date($start) . ' from ' . format_time($start) . ' to ' . format_time($end);
			}
			//same month
			return date('M', $start) . ' ' . date('j', $start) . '-' . date('j', $end) . ', ' . date('Y', $end);
		}
		//same year
		return format_date_time($start, false, ' at ') . ' to ' . format_date_time($end, false, ' at ');
	}
	//different years
	return format_date_time($start, false, ' at ') . ' to ' . format_date_time($end, false, ' at ');
}

function format_date_excel($timestamp) {
	if (!empty($timestamp)) return @date('n/j/Y', strToTime($timestamp));
}

function format_date_xml($timestamp=false) {
	//difference between this and format_date_rss and format_date_iso8601?
	if (!$timestamp) $timestamp = 'now';
	if (!empty($timestamp) && $timestamp) return @date('Y-m-d', strToTime($timestamp)) . 'T00:00:00.000';
}

function format_email($address) {
	//simple patch to prevent email form hijacking
	$address = trim($address);
	$address = strToLower($address);
	$address = str_replace("'", '', $address);
	$address = str_replace('"', '', $address);
	$address = preg_replace('/\r/', '', $address);
	$address = preg_replace('/\n/', '', $address);
	
	if (!stristr($address, '@')) return false;
	if (!stristr($address, '.')) return false;	
	return $address;
}

function format_file_name($str, $ext) {
	//formatting for downloaded files
	//TODO: only truly invalid characters should be checked.  i'm sure it's ok to download files with spaces, for example.
	
	$str = html_entity_decode($str);
	
	$str = str_replace('  ',	' ',	$str);
	$str = str_replace('"',		'', 	$str);
	$str = str_replace("'",		'', 	$str);
	$str = str_replace('.',		'', 	$str);
	$str = str_replace(':',		'',		$str);
	$str = str_replace(' ',		'_',	$str);
	$str = substr($str, 0, 30);
	
	return strtolower($str . '.' . $ext);
}  

function format_file_size($file) {
	$size = @filesize($file);
	return format_size($size);
} 

function format_highlight($haystack, $needles, $style='background-color:#FFFFBB;padding:1px;font-weight:bold;') {
	//sometimes you want to use a highlighter on html -- usually in search results
	if (is_array($needles)) $needles = implode('|', $needles);
	return preg_replace('/($needles)/i','<span style="' . $style . '"><b>\\0</b></span>', $haystack);
}

function format_hilite($haystack, $needles, $style='background-color:#FFFFBB;padding:1px;font-weight:bold;') {
	//alias of format_highlight
	return format_highlight($haystack, $needles, $style);
}

function format_html($text) {
	global $_josh;
	require_once(lib_location('simple_html_dom'));
	$html = str_get_html($text);

	$html->set_callback('cleanup');
	
	if (!function_exists('tag_unset')) {
		function tag_unset($e) {
			if (@$e->innertext) $e->innertext = '';
			if (@$e->outertext) $e->outertext = '';
			if (@$e->children) foreach($e->children as $f) tag_unset($f);
		}
	
		function cleanup($e) {
			//this callback is used to clear out bad tags and attributes
			//never want these tags, or anything inside them
			$bad_tags = array('comment', 'form', 'iframe', 'label', 'link', 'noscript', 'script', 'unknown');
			if (in_array($e->tag, $bad_tags)) tag_unset($e);
			
			//these are the tags we want	
			$good_tags	= array(
				'a', 'b', 'blockquote', 'br', 'dir', 'div', 'h1', 'h2', 'h3', 'h4', 'h5', 'i', 'img',
				'p', 'span', 'strike', 'strong', 'text', 'table', 'tr', 'td', 'th', 'ol', 'ul', 'li',
				'object', 'embed', 'param'
			);
			
			
			if (!in_array($e->tag, $good_tags)) $e->outertext = ($e->innertext) ? $e->innertext : '';
					
			//never want these attributes
			$bad_attributes = array('alt', 'onclick', 'onmouseout', 'onmouseover', 'style', 'title');
			foreach ($bad_attributes as $b) if (isset($e->$b)) unset($e->$b);
			
			//certain tags we are wary of
			if ($e->tag == 'a') {
				//no in-page links or anchors
				if (!$e->href || (substr($e->href, 0, 1) == '#')) $e->outertext = '';
			} elseif ($e->tag == 'div') {
				//no empty divs
				if (!$e->children && !strlen(trim($e->plaintext))) $e->outertext = '';
			} elseif ($e->tag == 'em') {
				//personal preference: replace <strong> with <b>
				$e->outertext = '<i>' . $e->innertext . '</i>';
			//} elseif ($e->tag == 'img') {
				//no small, narrow or flat images
			//	if (($e->width && ($e->width < 20)) || ($e->height && ($e->height < 20))) $e->outertext = '';
			} elseif (($e->tag == 'p') && (!$e->innertext || ($e->innertext == '&nbsp;'))) {
				//kill empty p tags -- don't know where these are coming from!
				$e->outertext = '';
			} elseif ($e->tag == 'span') {
				if ($e->src) {
					//nytimes has this -- i'm not sure yet if it's good or not
				} elseif ($e->children || $e->plaintext) {
					//ditch the span, keep the contents
					$e->outertext = $e->innertext;
				} else {
					//ditch the empty span
					$e->outertext = '';
				}
			} elseif ($e->tag == 'strong') {
				//personal preference: replace <strong> with <b>
				$e->outertext = '<b>' . $e->innertext . '</b>';
			} elseif ($e->tag == 'table') {
				//kill table cell alignment?  not sure if this is good
				if (isset($e->align)) unset($e->align);
				if (isset($e->width)) unset($e->width);
			} elseif ($e->tag == 'td') {
				//kill table cell alignment?  not sure if this is good
				if (isset($e->align)) unset($e->align);
				if (isset($e->width)) unset($e->width);
			}
			//this could be a time to trim text
			//if (@$e->outertext && !strlen(trim($e->outertext))) $e->outertext = '';
		}
	}
		
	//reset html to get rid of artifacts and compress
	$text = trim($html->save());
	$html->clear();
	return $text;
}

function format_html_entities($string) {
	//$string = htmlentities($string);
	$string = str_replace('‘', '&lsquo;', $string); //left single quote
	$string = str_replace('’', '&rsquo;', $string); //right single quote
	$string = str_replace('“', '&ldquo;', $string); //left double quote
	$string = str_replace('”', '&rdquo;', $string); //right double quote
	$string = str_replace('—', '&mdash;', $string); //em dash
	return $string;
}

function format_html_trim($text) {
	global $_josh;
	$text = format_html($text);
	
	require_once($_josh['root'] . $_josh['write_folder'] . '/lib/simple_html_dom.php');

	//find td, div or body with longest text block
	$html = str_get_html($text);
	$blocks = $html->find('text');
	foreach ($blocks as $b) format_html_set_max(strlen(trim($b)));
	
	if (!function_exists('get_parent')) {
		function get_parent($e) {
			$options = array('td', 'div', 'body');
			if (!is_object($e)) die($e);
			return (in_array($e->tag, $options)) ? $e : get_parent($e->parent);
		}
	}

	foreach ($blocks as $b) {
		$len = strlen(trim($b));
		if ($len == $_josh['max_text_len']) {
			$e = get_parent($b->parent);
			$text = $e->innertext;
			echo $text;
		}
	}
	
	$html->clear();
	unset($html);
	
	
	$html = str_get_html($text);

	//get rid of any sub-divs
	$blocks = $html->find('div');
	foreach ($blocks as $b) $b->outertext = '';
	
	//reset html to get rid of artifacts and compress
	$text = trim($html->save());
	$html->clear();
	
	die($text);
	
	return $text;
}

function format_html_set_max($len) {
	//helper function for above, due to weird scope reason i don't fully comprehend
	global $_josh;
	if (!isset($_josh['max_text_len'])) $_josh['max_text_len'] = 0;
	if ($len > $_josh['max_text_len']) $_josh['max_text_len'] = $len;
}

function format_html_text($str) {
	$return = strip_tags($str);
	$return = str_replace('&nbsp;', ' ', $return);
	$return = trim($return);
	if (empty($return)) return false;
	return $return;
}

function format_image_resize($source, $max_width=false, $max_height=false) {
	global $_josh;

	//exit on error
	if (!function_exists('imagecreatefromjpeg')) error_handle('library missing', 'the GD library needs to be installed to run format_image_resize', __file__, __line__);
	if (empty($source)) return null;

	if (!function_exists('resize')) {
		function resize($new_width, $new_height, $source_name, $target_name, $width, $height) {
			global $_josh;
			//resize an image and save to the $target_name
			$tmp = imagecreatetruecolor($new_width, $new_height);
			if (!$image = imagecreatefromjpeg($_josh['root'] . $source_name)) error_handle('could not create image', 'the system could not create an image from ' . $source_name);
			imagecopyresampled($tmp, $image, 0, 0, 0, 0, $new_width, $new_height, $width, $height);
			imagejpeg($tmp, $_josh['root'] . $target_name, 100);
			imagedestroy($tmp);
			imagedestroy($image);
		}

		function crop($new_width, $new_height, $target_name) {
			global $_josh;
			//crop an image and save to the $target_name
			list($width, $height) = getimagesize($_josh['root'] . $target_name);
			$tmp = imagecreatetruecolor($new_width, $new_height);
			if (!$image = @imagecreatefromjpeg($_josh['root'] . $target_name)) error_handle('could not create image', 'the system could not create an image from ' . $source_name);
			imagecopyresized($tmp, $image, 0, 0, 0, 0, $new_width, $new_height, $new_width, $new_height);
			imagejpeg($tmp, $_josh['root'] . $target_name, 100);
			imagedestroy($tmp);
			imagedestroy($image);
		}	
	}

	//save to file, is file-based operation, unfortunately
	$source_name = $_josh['write_folder'] . '/temp-source.jpg';
	$target_name = $_josh['write_folder'] . '/temp-target.jpg';
	file_put($source_name, $source);

	//get source image dimensions
	list($width, $height) = getimagesize($_josh['root'] . $source_name);
	
	//execute differently depending on target parameters	
	if ($max_width && $max_height) {
		//resizing both
		if (($width == $max_width) && ($height == $max_height)) {
			//already exact width and height, skip resizing
			copy($_josh['root'] . $source_name, $_josh['root'] . $target_name);
		} else {
			//this was for the scenario where your target was a long landscape and you got a squarish image.
			//this doesn't work if your target is squarish and you get a long landscape
			//maybe we need a ratio function?  
			//square to long scenario: input 400 x 300 (actual 1.3 ratio), target 400 x 100 (target 4) need to resize width then crop target > actual
			//long to square scenario: input 400 x 100 (actual 4 ratio), target 400 x 300 (target 1.3) need to resize height then crop target < actual
			$target_ratio = $max_width / $max_height;
			$actual_ratio = $width / $height;
			//if ($max_width >= $max_height) {
			if ($target_ratio >= $actual_ratio) {
				//landscape or square.  resize width, then crop height
				$new_height = ($height / $width) * $max_width;
				resize($max_width, $new_height, $source_name, $target_name, $width, $height);
			} else {
				//portrait.  resize height, then crop width
				$new_width = ($width / $height) * $max_height;
				resize($new_width, $max_height, $source_name, $target_name, $width, $height);
			}
			crop($max_width, $max_height, $target_name);						
		}
	} elseif ($max_width) { 
		//only resizing width
		if ($width == $max_width) {
			//already exact width, skip resizing
			copy($_josh['root'] . $source_name, $_josh['root'] . $target_name);
		} else {
			//resize width
			$new_height = ($height / $width) * $max_width;
			resize($max_width, $new_height, $source_name, $target_name, $width, $height);

		}
	} elseif ($max_height) { 
		//only resizing height	
		if ($height == $max_height) {
			//already exact height, skip resizing
			copy($_josh['root'] . $source_name, $_josh['root'] . $target_name);
		} else {
			//resize height
			$new_width = ($width / $height) * $max_height;
			resize($new_width, $max_height, $source_name, $target_name, $width, $height);
		}
	}
	$return = file_get($target_name);
	
	//clean up
	file_delete($source_name);
	file_delete($target_name);
	
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
	$string = 'a' . $string; //doesn't like variables that start with numbers
	$string = str_replace('-', 'replacedash', $string); //or contain dashes
	$string = str_replace('/', 'replaceslash', $string); //or contain slashes
	$string = str_replace(' ', 'replacespace', $string); //or contain spaces
	return $string;
}

function format_money($value, $dollarsign=true, $comma=true, $error='') {
	$negative = ($value < 0);
	$value = format_num($value, 2, $comma, $error);
	if ($value == $error) return $value;
	if ($dollarsign) {
		if ($negative) {
			$value = '-$' . str_replace('-', '', $value);
		} else {
			$value = '$' . $value;
		}
	}
	return $value;
}

function format_nobr($string='') {
	//should have been draw_nobr anyway
	error_handle('function deprecated', 'format_nobr was deprecated on 10/28/2009 because it\'s invalid html -- use table width instead, or white-space: nowrap;', __file__, __line__);
	return '<nobr>' . $string . '</nobr>';
}

function format_null($value='') {
	//could also be a db function?
	if (empty($value)) return 'NULL';
	if (!is_numeric($value)) return '"' . $value . '"';
	return $value;
}

function format_num($value, $decimals=false, $comma=true, $error='') {
	//output function
	if (empty($value)) return $error;
	if (!format_verify($value, 'num')) return $error;
	if ($comma) $comma = ',';
	return number_format($value, $decimals, '.', $comma);
}

/* 
this relationship between above and below is confusing! 
format_num is like number_format but with some checking.
format_numeric forces out a number from a string
*/

function format_numeric($value, $integer=false) {
	//take possibly string function and reduce it to just the numeric elements
	$characters = '0123456789';
	$value = $value . ''; //force it to be a string
	if (!$integer) $characters .= '.';
	$newval = '';
	for ($i = 0; $i < strlen($value); $i++) if (strpos($characters, $value[$i]) !== false) $newval .= $value[$i];
	if (empty($newval)) {
		error_debug('<b>format_numeric</b> received $value and is sending back false', __file__, __line__);
		return false;
	} else {
		error_debug('<b>format_numeric</b> received $value and is sending back ' . $newval, __file__, __line__);
		return $newval - 0;
	}
}

function format_percentage($float, $precision=2) {
	return round($float * 100, $precision) . '%';
}

function format_phone($string, $fail=false) { //format a phone number to (123) 456-7890 format
	$number = '';
	for ($i = 0; $i < strlen($string); $i++) if (is_numeric($string[$i])) $number .= $string[$i];
	if ((strlen($number) != 10) || ($number == '9999999999')) {
		if ($fail) return false;
		return $string;
	}
	return '(' . substr($number, 0, 3) . ') ' . substr($number, 3, 3) . '-' . substr($number, 6, 4);
}

function format_pluralize($entity) {
	$length = strlen($entity);
	if (substr($entity, -1) == 'y') {
		return substr($entity, 0, ($length - 1)) . 'ies';
	} else {
		return $entity . 's';
	}
}

function format_post_bits($fieldnames) {
	//takes a comma-separated list of POST keys (checkboxes) and sets bit values in their places
	global $_POST;
	$fields = array_post_fields($fieldnames);
	foreach ($fields as $field) $_POST[$field] = (isset($_POST[$field])) ? 1 : 0;
}

function format_post_date($str, $array=false) {
	global $_POST;
	
	if (!$array) $array = $_POST;
	
	$month  = $array[$str . 'Month'];
	$day    = $array[$str . 'Day'];
	$year   = $array[$str . 'Year'];
	
	$hour   = isset($array[$str . 'Hour'])   ? $array[$str . 'Hour']   : 0;
	$minute = isset($array[$str . 'Minute']) ? $array[$str . 'Minute'] : 0;
	$second = isset($array[$str . 'Second']) ? $array[$str . 'Second'] : 0;
	
	if (isset($array[$str . 'AMPM'])) {
		if ($array[$str . 'AMPM'] == 'AM') {
			if ($hour == 12) $hour = 0;
		} else {
			if ($hour != 12) $hour +=12;
		}
	}
	error_debug('<b>format_post_date</b> for $str into mdyhms: $month, $day, $year, $hour, $minute, $second', __file__, __line__);
	return format_date_sql($month, $day, $year, $hour, $minute, $second);
}

function format_post_float($fieldnames) {
	//takes a comma-separated list of POST keys and replaces them with monetary values or NULLs if they're empty
	global $_POST;
	$fields = array_post_fields($fieldnames);
	foreach ($fields as $field) {
		$_POST[$field] = format_numeric($_POST[$field]);
		if ($_POST[$field] === false) $_POST[$field] = 'NULL';
	}
}

function format_post_html($fieldnames) {
	//takes a comma-separated list of POST keys and formats the html in them
	global $_POST;
	$fields = array_post_fields($fieldnames);
	foreach ($fields as $field) {
		$return = format_html($_POST[$field]);
		$_POST[$field] = (empty($return)) ? 'NULL' : '"' . $_POST[$field] . '"';
	}
}

function format_post_nulls($fieldnames) {
	//takes a comma-separated list of POST keys and replaces them with NULLs if they're empty
	global $_POST;
	error_debug('<b>format_post_nulls</b> for ' . $fieldnames, __file__, __line__);
	$fields = array_post_fields($fieldnames);
	foreach ($fields as $field) {
		if (!isset($_POST[$field]) || !strlen($_POST[$field])) {
			error_debug('<b>format_post_nulls</b> nullifying ' . $field, __file__, __line__);
			$_POST[$field] = 'NULL';
		}
	}
}

function format_post_urls($fieldnames) {
	//takes a comma-separated list of POST keys and formats them as NULLs or urls
	global $_POST;
	error_debug('<b>format_post_urls</b> for ' . $fieldnames, __file__, __line__);
	$fields = array_post_fields($fieldnames);
	foreach ($fields as $field) $_POST[$field] = format_null(format_url($_POST[$field]));
}

function format_q($quantity, $entity, $capitalize=true) {
	//alias for format_quantitize
	return format_quantitize($quantity, $entity, $capitalize);
}

function format_quantitize($quantity, $entity, $title_case=true) {
	global $_josh;
	if ($quantity == 0) {
		$return = 'no ' . format_pluralize($entity);
	} elseif ($quantity == 1) {
		$return = 'one ' . $entity;
	} elseif (format_verify($quantity) && ($quantity < 10)) {
		$return = $_josh['numbers'][$quantity] . ' ' . format_pluralize($entity);
	} else {
		$return = $quantity . ' ' . format_pluralize($entity);
	}
	if ($title_case) $return = format_title($return);
	return $return;
}

function format_quotes($string) {
	if (format_verify($string, 'string')) $string = trim(str_replace("'", "''", stripslashes($string)));
	return $string;
}

function format_singular($string) {
	if (format_text_ends('ies', $string)) {
		return substr($string, 0, $string-3) . 'y';
	} elseif (format_text_ends('s', $string)) {
		return substr($string, 0, $string-1);
	}
	return $string;
}

function format_size($size) {
	//take bytes and make pretty
	$a = array('B', 'K', 'M', 'G', 'T', 'P');
	$pos = 0;
	while ($size >= 1024) {
		$size /= 1024;
		$pos++;
	}
	return round($size) . $a[$pos];
}

function format_size_bytes($size) {
	//take pretty and make bytes
	$multiplier = 1;
	$size = strtoupper($size);
	if (format_text_ends('P', $size)) {
		$multiplier = pow(1024, 5);
	} elseif (format_text_ends('T', $size)) {
		$multiplier = pow(1024, 4);
	} elseif (format_text_ends('G', $size)) {
		$multiplier = pow(1024, 3);
	} elseif (format_text_ends('M', $size)) {
		$multiplier = pow(1024, 2);
	} elseif (format_text_ends('K', $size)) {
		$multiplier = 1024;
	}
	return format_numeric($size) * $multiplier;
}

function format_ssn($str) {
	return substr($str, 0, 3) . '-' . substr($str, 3, 2) . '-' . substr($str, 5, 4);
}

function format_string($string, $target=30, $append='&hellip;') {
	//shorten a $string to $target length
	//difference between this and format_text_shorten?
	//modifying to only break on words for harvard and livingcities
	$string = strip_tags($string);
	if (strlen($string) < $target) return $string;
	$words = explode(' ', $string);
	$length = 0;
	$return = '';
	foreach ($words as $w) {
		if ($length == 0) {
			//first word -- add it no matter what.
			$return .= $w;
			$length += strlen($w);
		} else {
			$wordlength = strlen($w) + 1; //add one for the space
			if ($length + $wordlength > $target) break;
			$return .= ' ' . $w;
			$length += $wordlength;
		}
	}
	return $return . $append;
}

function format_text_code($str) {
	$return = strToLower(trim($str));
	$return = str_replace("'",	'',		$return);
	$return = str_replace(',',	'',		$return);
	$return = str_replace('.',	'',		$return);
	$return = str_replace(':',	'',		$return);
	$return = str_replace('/',	'_',	$return);
	$return = str_replace(' ',	'_',	$return);
	$return = str_replace('&',	'and',	$return);
	return $return;
}

function format_text_ends($needle, $haystack) {
	$needle_length = strlen($needle);
	if (strToLower(substr($haystack, (0 - $needle_length))) == strToLower($needle)) return substr($haystack, 0, strlen($haystack) - $needle_length);
	return false;
}

function format_text_human($str, $convertdashes=true) {
	$return = str_replace('_', ' ', strToLower($str));
	if ($convertdashes) $return = str_replace('-', ' ', $return);
	return format_title($return);
}

function format_text_shorten($text, $length=30, $append='&#8230;', $appendlength=1) {
	//this function is deprecated
	error_handle('deprecated format_text_shorten', 'this function was deprecated 10/3/2009.  use format_string instead');
	if ($append) $length = $length - $appendlength;
	if (strlen($text) > $length) return substr($text, 0, $length) . $append;
	return $text;
}

function format_text_starts($needle, $haystack) {
	//function to see if a $haystack starts with $needle
	//returns $haystack without the needle
	$needle_len = strlen($needle);
	if ($needle == $haystack) return true;
	if (strToLower(substr($haystack, 0, $needle_len)) == strToLower($needle)) return substr($haystack, $needle_len);
	return false;
}

function format_time($timestamp=false, $error='') {
	if ($timestamp === false) {
		$timestamp = time();
	} else {
		if (empty($timestamp) || ($timestamp == 'Jan 1 1900 12:00AM')) return $error;
		if (!is_int($timestamp)) $timestamp = strToTime($timestamp);
	}
	return date('g:ia', $timestamp);
}

function format_time_business($start, $end=false) {
	$start = strToTime($start);
	if (empty($end) || !$end) {
		$end = date('U');
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
			$return[] = $days . ' day';
		} elseif ($days > 1) {
			$return[] = $days . ' days';
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
		$return[] = $hours . ' hour';
	} elseif ($hours > 1) {
		$return[] = $hours . ' hours';
	}

	//minutes
	$minutes = round(($age % 3600) / 60);
	if ($minutes == 1) {
		$return[] = $minutes . ' minute';
	} elseif ($minutes > 1) {
		$return[] = $minutes . ' minutes';
	}
	
	if (empty($return)) {
		return '<i>just opened</i>';
	} else {
		return implode(', ', $return);
	}
}

//don't know how to categorize these - they only belong to the function above
function isBusinessHours($udate) {
	$hourOfDay = date('G', $udate);
	return (($hourOfDay > 9) && ($hourOfDay < 17)) ? true : false;
}

function isWeekDay($udate) {
	$dayOfWeek = date('w', $udate);
	return (($dayOfWeek > 0) && ($dayOfWeek < 6)) ? true : false;
}
	

function format_time_exec($start_time=false, $descriptor=' seconds') {
	if (!$start_time) {
		global $_josh;
		if (isset($_josh['time_start'])) $start_time = $_josh['time_start'];
	}
	return round(microtime(true) - $start_time, 2) . $descriptor;
}

function format_times($num) {
	global $_josh;
	if ($num == 1) {
		return 'once';
	} elseif ($num == 2) {
		return 'twice';
	} elseif ($num < 10) {
		return $_josh['numbers'][$num] . ' times';
	} else {
		return number_format($num) . ' times';
	}
}

function format_title($str, $force_upper=false) {
	error_debug('<b>format_title</b> starting with ' . $str, __file__, __line__);
	$return = array();
	$lower = array('a', 'an', 'and', 'but', 'for', 'from', 'if', 'in', 'nor', 'of', 'on', 'or', 'so', 'the', 'to', 'via', 'with');
	$mixed = array('DBs', 'CBOs', 'iPhone', 'iPhones', 'IDs', 'IPs', 'LLCs', 'MySQL', 'SSNs', 'TinyMCE', 'URLs', 'WordPress');
	$upper = array('ADA', 'ASAP', 'BIF', 'CCT', 'CMS', 'CSS', 'DB', 'DC', 'EBO', 'FSS', 'FTP', 'HR', 'HTML', 'I', 'IE', 'II', 'III', 'IP', 'IV', 
		'LLC', 'NHP', 'NVN', 'OMG', 'ONYC', 'OS', 'PC', 'PDF', 'PHP', 'PLC', 'RSS', 'SF', 'SFS', 'SQL', 'SSI', 'SSN', 'SVN', 'SWF', 'TANF', 'URL', 'U.S.', 
		'V', 'VI', 'VII', 'VIII', 'WTF', 'X', 'XML');
		
	if ($force_upper) {
		$upper2 = explode(',', strToUpper($force_upper));
		foreach ($upper2 as &$u) $u = trim($u);
		$upper = array_merge($upper, $upper2);
	}
	
	$words = explode(' ', ucwords(strToLower(trim($str))));
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
	return implode(' ', $return);
}

function format_url($str='') {
	if (empty($str)) return false;
	if (format_text_starts('http://', $str) || format_text_starts('https://', $str)) return $str;
	return 'http://' . $str;
}

function format_verify($variable, $type='int') {
	if ($type == 'int') {
		if (!is_numeric($variable)) return false;
		return ((string) $variable) === ((string)(int) $variable);
	} elseif ($type == 'num') {
		if (!is_numeric($variable)) return false;
	} elseif ($type == 'key') {
		if (strlen($variable) > 13) return false;
	} elseif ($type == 'string') {
		if (!is_string($variable)) return false;
	}
	return true;
}

function format_zip($string, $error=false) { //format a ZIP (5-digit)
	$number = '';
	for ($i = 0; $i < strlen($string); $i++) if (is_numeric($string[$i])) $number .= $string[$i];
	if (strlen($number) >= 5) return substr($number, 0, 5);
	return $error;
}


?>