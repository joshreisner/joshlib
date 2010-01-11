<?php
error_debug('including draw.php', __file__, __line__);

function draw_arg($name, $value=false) {
	if ($value === false) return;
	return ' ' . strToLower($name) . '="' . str_replace('"', format_ascii('"'), $value) . '"';
}

function draw_args($array) {
	$return = '';
	foreach ($array as $key=>$value) $return .= draw_arg($key, $value);
	return $return;
}

function draw_array($array, $nice=false) {
	global $_josh;
	if (!is_array($array)) return false;
	$return = '

<table border="1">';
	//if (!$nice) ksort($array);
	while(list($key, $value) = each($array)) {
		$key = urldecode($key);
		if ($nice && (strToLower($key) == 'j')) continue;
		$value = format_quotes($value);
		if (strToLower($key) == 'email') $value = '<a href="mailto:' . $value . '">' . $value . '</a>';
		if (is_array($value)) $value = draw_array($value, $nice);
		$return  .= '
			<tr><td bgcolor="#eee"><b>';
		$return .= ($nice) ? format_text_human($key)  : $key;
		$return .= '&nbsp;</b></td><td>';
		$return .= is_object($value) ? 'object value' : $value;
		$return .= '</td></tr>';
	}
	$return .= '</table>';
	return $return;
}

function draw_autorefresh($minutes=5) {
	return draw_tag('meta', array('http-equiv'=>'refresh', 'content'=>$minutes * 60));
}

function draw_calendar($month=false, $year=false, $events=false, $divclass='calendar') {
	/*
		for livingcities roundup
		$events is an optional 2d array that you can pass in (from db via db_table) that's looking for the following values
			title -- required -- the event title
			day -- required (1, 2, 3 ... 31)
			link -- if the event should be linked
			color -- if there should be a background color to its div
	*/
	global $_josh;
	
	//reprocess the events into a different kind of array
	$cal_events = array();
	if (!$month) $month = $_josh['month'];
	if (!$year) $year = $_josh['year'];
	if ($events) {
		foreach ($events as $e)	{
			$e['title'] = format_string($e['title']);
			if (!empty($e['link'])) $e['title'] = draw_link($e['link'], $e['title'], false, array('id'=>'calendar_link_' . $e['id']));
			if (!isset($cal_events[$e['day']])) $cal_events[$e['day']] = '';
			$style = (isset($e['color'])) ? 'background-color:#' . $e['color'] : false;
			$cal_events[$e['day']] .= draw_div_class('event', $e['title'], array('style'=>$style));
		}
	}
	
	$firstday = date('w', mktime(0, 0, 0, $month, 1, $year));
	$lastday  = date('d', mktime(0, 0, 0, ($month + 1), 0, $year));
	
	$days_short = array('sun', 'mon', 'tue', 'wed', 'thu', 'fri', 'sat');
	$days_long = array('Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday');
	
	$return = ($_josh['mode'] == 'dev') ? $_josh['newline'] . $_josh['newline'] . '<!--calendar ' . $divclass . ' starts -->' . $_josh['newline'] : '';
	for ($i = 0; $i < 7; $i++) $return .= draw_div_class('header ' . $days_short[$i], $days_long[$i]);
	for ($week = 1, $thisday = 1; ($thisday < $lastday); $week++) {
		for ($day = 1; $day <= 7; $day++) {
			$thisday = (((7 * ($week - 1)) + $day) - $firstday);
			if ($thisday > 0 && $thisday <= $lastday) {
				$class = 'day';
				if (($year == $_josh['year']) && ($month == $_josh['month']) && ($thisday == $_josh['today'])) $class .= ' today';
				if (isset($cal_events[$thisday])) $class .= ' events';
				$return .= draw_div_class($class . ' ' . $days_short[$day-1], '<div class="number">' . $thisday . '</div>');
				$return .= @$cal_events[$thisday];
			} else {
				$return .= draw_div_class('blank ' . $days_short[$day-1]);
			}
		}
	}
	
	return draw_div_class($divclass, $return);
}

function draw_css($content) {
	return draw_tag('style', array('type'=>'text/css'), $content);
}

function draw_css_src($location='/styles/screen.css', $media=false) {
	//special 'ie' mode for internet explorer
	$ie = ($media == 'ie');
	if ($ie) $media = 'screen';
	$return = draw_tag('link', array('rel'=>'stylesheet', 'type'=>'text/css', 'media'=>$media, 'href'=>$location));
	if ($ie) $return = '<!--[if IE]>' . $return . '<![endif]-->';
	return $return;
}

function draw_container($tag, $innerhtml, $args=false) {
	//convenience function for draw_tag if you're just writing a simple container tag
	return draw_tag($tag, $args, $innerhtml);
}

function draw_div($id, $innerhtml='', $args=false) {
	//convenience function specifically for DIVs, since they're so ubiquitous
	//todo deprecate this in favor of draw_div_id
	return draw_div_id($id, $innerhtml, $args);
}

function draw_div_class($class, $innerhtml='', $args=false) {
	//convenience function specifically for DIVs, since they're so ubiquitous
	if (!$args) $args = array();
	$args['class'] = $class;
	if (empty($innerhtml)) $args['class'] .= ' empty';
	return draw_tag('div', $args, $innerhtml);
}

function draw_div_id($id, $innerhtml='', $args=false) {
	//convenience function specifically for DIVs, since they're so ubiquitous
	if (!$args) $args = array();
	$args['id'] = $id;
	return draw_tag('div', $args, $innerhtml);
}

function draw_dl($array, $class=false) {
	$return = '';
	foreach ($array as $key=>$value) {
		$return .= draw_container('dt', $key) . draw_container('dd', $value);
	}
	return draw_container('dl', $return, array('class'=>$class));
}

function draw_doctype() {
	return '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
		<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">';
}

function draw_favicon($location='/images/favicon.png') {
	//only accepts PNGs right now
	return draw_tag('link', array('rel'=>'shortcut icon', 'href'=>$location, 'type'=>'image/png')); 
}

function draw_focus($form_element) {
	global $_josh;
	if ($_josh['drawn']['focus']) return false;
	$_josh['drawn']['focus'] = $form_element;
	return draw_javascript('document.getElementById("' . $form_element . '").focus();');
}

function draw_form_button($text, $location=false, $class=false, $disabled=false, $javascript=false) {
	global $_josh;
	$class = ($class) ? $class . ' button' : 'button';
	$return  = '<input type="button" value="' . $text . '" id="' . $text . '" class="' . $class . '" onclick="';
	if ($location) {
		$return .= 'javascript:location.href=\'' . $location . '\'"';
	} else {
		$return .= 'javascript:' . $javascript . ';';
	}
	$return .= '"';
	if ($disabled) $return .= ' disabled';
	$return .= '>';
	return $return;
}

function draw_form_checkbox($name, $checked=false, $class=false, $javascript=false) {
	global $_josh;
	$class = ($class) ? $class . ' checkbox' : 'checkbox';
	$return  = '<input type="checkbox" name="' . $name . '" id="' . $name . '" class="' . $class . '"';
	if ($javascript) $return .= ' onclick="javascript:' . $javascript . '"';
	if ($checked) $return .= ' checked="checked"';
	$return .= '/>';
	return $return;
}

function draw_form_checkboxes($name, $linking_table=false, $object_col=false, $option_col=false, $id=false) {
	if (stristr($name, '_')) error_handle('draw_form_checkboxes()', 'an error occurred with the calling of this function; you can\'t have an underscore in the field name', __file__. __line__);
	if ($linking_table) {
		$result = db_query('SELECT o.id, o.value, (SELECT COUNT(*) FROM ' . $linking_table . ' l WHERE l.' . $option_col . ' = o.id AND ' . $object_col . ' = ' . $id . ') checked  FROM option_' . $name . ' o ORDER BY value');
	} else {
		$result = db_query('SELECT id, value, 0 checked FROM option_' . $name . ' ORDER BY value');
	}
	$return = '<table cellspacing="0" class="checkboxes">';
	while ($r = db_fetch($result)) {
		$return .= '<tr><td>' . draw_form_checkbox('chk_' . $name . '_' . $r['id'], $r['checked']) . '</td>';
		$return .= '<td>&nbsp;' . $r['value'] . '</td></tr>';
	}
	$return .= '</table>';
	return $return;
}

function draw_form_date($namePrefix, $timestamp=false, $withTime=false, $class=false, $required=true) {
	global $_josh;
	$class = ($class) ? $class . ' select' : 'select';

	//get time into proper format
	$nulled = (!$required && !$timestamp);
	if ($timestamp && !is_int($timestamp)) $timestamp = strToTime($timestamp);

	//required, default to today
	if (empty($timestamp) && $required) $timestamp = time();
	
	if ($timestamp) {
		$month  = date('n', $timestamp);
		$day    = date('j', $timestamp);
		$year   = date('Y', $timestamp);
		$hour   = date('g', $timestamp);
		$minute = date('i', $timestamp);
		$ampm   = date('A', $timestamp);
	} else {
		$month = $day = $year = $hour = $minute = $ampm = false;
	}
	
	//assemble date fields
	$months = array();
	foreach ($_josh['months'] as $key=>$value) $months[$key + 1] = $value;
	$return = draw_form_select($namePrefix . 'Month', $months, $month, $required, $class) .
	draw_form_select($namePrefix . 'Day', array_2d(array_range(1, 31)), $day, $required, $class) .
	draw_form_select($namePrefix . 'Year', array_2d(array_range(1920, 2015)), $year, $required, $class);
	if ($withTime) {
		$return .= '&nbsp;' . 
		draw_form_select($namePrefix . 'Hour', array_2d(array(12, 1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11)), $hour, $required, $class) .
		draw_form_select($namePrefix . 'Minute', array_2d(array('00', 15, 30, 45)), $minute, $required, $class) .
		draw_form_select($namePrefix . 'AMPM', array_2d(array('AM', 'PM')), $ampm, $required, $class);
	}
	return draw_container('nobr', $return);
}

function draw_form_date_cal($name, $value=false) {
	if ($value) $value = date('n/j/Y', strToTime($value));
	$return = draw_form_text($name, $value, 'date', 10);
	return $return;
}

function draw_form_file($name, $class=false, $onchange=false) {
	if (!$class) $class='file';
	$return  = '<input type="file" name="' . $name . '" class="' . $class . '"';
	if ($onchange) $return .= ' onchange="javascript:' . $onchange . '"';
	$return .= '>';
	return $return;
}

function draw_form_focus($name) {
	global $_josh;
	if (!$_josh['drawn']['focus']) {
		//only draw focus once -- don't want competition
		$_josh['drawn']['focus'] = $name;
		return draw_javascript('document.getElementById("' . $name . '").focus();');
	}
}

function draw_form_hidden($name, $value='') {
	return draw_tag('input', array('type'=>'hidden', 'name'=>$name, 'id'=>$name, 'value'=>$value));
}

function draw_form_img($url, $class='submit') {
	if (empty($class)) $class = 'submit';
	return draw_tag('input', array('type'=>'image', 'src'=>$url, 'class'=>$class));
}

function draw_form_password($name, $value='', $class=false, $maxlength=255) {
	global $_josh;
	$class = ($class) ? $class . ' password' : 'password';
	$return = '<input type="password" name="' . $name . '" id="' . $name . '" value="' . $value . '" class="' . $class . '" maxlength="' . $maxlength . '" class="' . $class . '">';
	return $return;
}

function draw_form_radio($name, $value='', $checked=false, $class=false) {
	global $_josh;
	$class = ($class) ? $class . ' radio' : 'radio';
	$return  = '<input type="radio" name="' . $name . '" value="' . $value . '"';
	if ($class) $return .= ' class="' . $class . '"';
	if ($checked) $return .= ' checked';
	$return .= '>';
	return $return;
}

function draw_form_select($name, $sql_options, $value=false, $required=true, $class=false, $action=false, $nullvalue='', $maxlength=false) {
	global $_josh;
	$class = ($class) ? $class . ' select' : 'select';
	$return  = '<select name="' . $name . '" id="' . $name . '" class="' . $class . '"';
	if ($action) $return .= ' onchange="javascript:' . $action . '"';
	$return .= '>';
	if (!$required) $return .= '<option value="">' . $nullvalue . '</option>';
	$lastgroup = '';
	if (is_array($sql_options)) {
		while (@list($key, $val, $group) = each($sql_options)) {
			if (is_array($val)) @list($key, $val, $group) = array_values($val); //possible db_table optgroup situation
			$val = format_string($val);
			
			//new optgroup code
			if (!isset($grouped)) {
				if ($group) {
					$grouped = true;
					//first group
					$lastgroup = $group;
					$return .= '<optgroup label="' . $lastgroup . '">';
				}
			} elseif ($grouped && ($lastgroup != $group)) {
				//subsequent group
				$lastgroup = $group;
				$return .= '</optgroup><optgroup label="' . $lastgroup . '">';
			}
			
			$return .= '<option value="' . $key . '" id="' . $name . $key . '"';
			if ($key == $value) $return .= ' selected="selected"';
			$return .= '>' . $val . '</option>';
		}
	} else {
		$result = db_query($sql_options);
		$key = false;
		while ($r = db_fetch($result)) {
			if (!$key) $key = array_keys($r);
			
			//new optgroup code
			if (!isset($grouped)) {
				$grouped = array_keys($r, 'optgroup');
				if ($grouped) {
					//first group
					$lastgroup = $r['optgroup'];
					$return .= '<optgroup label="' . $lastgroup . '">';
				}
			} elseif ($grouped && ($lastgroup != $r['optgroup'])) {
				//subsequent group
				$lastgroup = $r['optgroup'];
				$return .= '</optgroup><optgroup label="' . $lastgroup . '">';
			}
			$r[$key[1]] = format_string($r[$key[1]]);
			$return .= '<option value="' . $r[$key[0]] . '" id="' . $name . $r[$key[0]] . '"';
			if ($r[$key[0]] == $value) $return .= ' selected="selected"';
			$return .= '>' . $r[$key[1]] . '</option>';
		}
	}
	if (isset($grouped) && $grouped) $return .= '</optgroup>';
	$return .= '</select>';
	return $return;
}

function draw_form_select_month($name, $start, $default=false, $length=false, $class=false, $js=false, $nullable=false) {
	//select of months going back to $start mm/yyyy format
	global $_josh;
	$class = ($class) ? $class . ' select' : 'select';
	list($startMonth, $startYear) = explode('/', $start);
	$array = array();
	$break = false;
	while ($break == false) {
		$array[$startMonth . '/' . $startYear] = $_josh['months'][$startMonth - 1] . ' ' . $startYear;
		if (($startMonth == $_josh['month']) && ($startYear == $_josh['year'])) {
			$break = true;
		} elseif ($startMonth == 12) {
			$startMonth = 1;
			$startYear++;
		} else {
			$startMonth++;
		}
	}
	return draw_form_select($name, array_reverse($array), $default, !$nullable, $class, $js);
}

function draw_form_submit($message='Submit Form', $class=false) {
	global $_josh;
	$class = ($class) ? $class . ' submit' : 'submit';
	return draw_tag('input', array('type'=>'submit', 'value'=>$message, 'class'=>$class));
}

function draw_form_text($name, $value='', $class=false, $maxlength=255, $style=false) {
	$class			= ($class) ? $class . ' text' : 'text';
	$type			= 'text';
	$id				= $name;
	return draw_tag('input', compact('type', 'name', 'id', 'value', 'class', 'maxlength', 'style'));
}

function draw_form_textarea($name, $value='', $class=false) {
	error_debug('drawing textarea', __file__, __line__);
	global $_josh;
	if (!$value) $value = '';
	$class = ($class) ? $class . ' textarea' : 'textarea';
	return draw_container('textarea', $value, array('name'=>$name, 'id'=>$name, 'class'=>$class, 'rows'=>5, 'cols'=>50));
	//return '<textarea name='' . $name . '' id='' . $name . '' class='' . $class . ''>' . $value . '</textarea>';
}

function draw_google_chart($data, $type='line', $colors=false, $width=250, $height=100) {
	//example http://chart.apis.google.com/chart?cht=p3&chd=t:60,40&chs=250x100&chl=Hello|World
	//example http://chart.apis.google.com/chart?cht=p3&chd=t:60,40&chs=200x150&chl=Hello|World
	$types = array(
		'line'=>'ls',
		'bar'=>'bhs'
	);
	$parameters['cht']	= $types[$type];
	$parameters['chd']	= 't:' . implode(',', $data);
	$parameters['chs']	= $width . 'x' . $height;
	//$parameters['chl']	= implode('|', array_keys($data));
	$parameters['chco'] = $colors;
	$parameters['chm']	= 'B,efefef,0,0,0';
	$parameters['chls']	= '3';
	//$parameters['chxt']	= 'x,y';
	//$parameters['chxr']	= '0,0,30|1,0,4';
	$parameters['chds'] = '-1,11';
	$parameters['chma'] = '0,0,0,0';
	$pairs = array();
	foreach ($parameters as $key=>$value) $pairs[] = $key . '=' . $value;

	return '<img src="http://chart.apis.google.com/chart?' . implode('&', $pairs) . '" width="' . $width . '" height="' . $height . '" border="0" class="chart">';
	
}

function draw_google_map($markers, $center=false) {
	//haven't figured out the appropriate place to store all this stuff.  this calls a javascript function which should be local
	//markers must be an array with latitude, longitude, title, description, color
	global $_josh;
	$markerstr = '';
	$return = '
	function map_load() {
		if (GBrowserIsCompatible()) {
			var map = new GMap2(document.getElementById("map"));
			';
	$count = count($markers);
	$lat = 0;
	$lon = 0;
	foreach ($markers as $m) {
		$lat += $m['latitude'];
		$lon += $m['longitude'];
		$markerstr .= $_josh['newline'] . '
			var marker = draw_marker(' . $m['latitude'] . ', ' . $m['longitude'] . ', "' . $m['title'] . '", "' . $m['description'] . '", "' . $m['color'] . '");
			map.addOverlay(marker);
			';
	}
	if ($center) {
		list($lat, $lon) = $center;
	} else {
		$lat = $lat / $count;
		$lon = $lon / $count;
	}
	$zoom = 11;
	$return .= '
			map.setCenter(new GLatLng(' . $lat . ', ' . $lon . '), ' . $zoom . ');
			map.addControl(new GLargeMapControl());
			' . $markerstr . '
		}
		window.onunload = GUnload;
	}
	window.onload = map_load;
	';
	
	return draw_javascript_src('http://maps.google.com/maps?file=api&amp;v=2&amp;key=' . $_josh['google']['mapkey']) . draw_javascript($return) . '<div id="map"></div>';
}

function draw_google_tracker($id) {
	error_debug('drawing google tracker', __file__, __line__);
	//this is google's code, so restraining myself from draw_javascript and single-quote encapsulation
	return "
	<script type='text/javascript'>
	var gaJsHost = (('https:' == document.location.protocol) ? 'https://ssl.' : 'http://www.');
	document.write(unescape('%3Cscript src=\'' + gaJsHost + 'google-analytics.com/ga.js\' type=\'text/javascript\'%3E%3C/script%3E'));
	</script>
	<script type='text/javascript'>
	try {
	var pageTracker = _gat._getTracker('$id');
	pageTracker._setDomainName('none');
	pageTracker._trackPageview();
	} catch(err) {}</script>";
}

function draw_img($path, $link=false, $alt=false, $name=false, $linknewwindow=false) {
	//alt could also be an array of arguments
	global $_josh;
	
	//get width and height
	$image = @getimagesize($path);
	if (!$image) $image = @getimagesize($_josh['root'] . $path);
	//if (!$image) die('could not find' . $path);
	if (!$image) return '';
	
	//assemble tag
	$args = array('src'=>url_base() . $path, 'width'=>$image[0], 'height'=>$image[1], 'border'=>0);
	if (is_array($alt)) {
		//values of alt can overwrite width, height, border, even src
		$args = array_merge($args, $alt);
	} else {
		$args['alt'] = $alt;
		$args['name'] = $args['class'] = $args['id'] = $name;
		$alt = false; //this was passed as a string, needs to be nulled so it doesn't go to draw_link
	}
	
	//force alt text for w3 validation
	if (empty($args['alt'])) {
		list($name, $ext, $path) = file_name($path);
		$args['alt'] = format_text_human($name);
	}

	$image = draw_tag('img', $args);
	if ($link) return draw_link($link, $image, $linknewwindow, $alt);
	return $image;
}

function draw_javascript($javascript=false) {
	if (!$javascript) return draw_javascript_src();
	return 	'
	<script language="javascript" type="text/javascript">
		<!--
		' . $javascript . '
		//-->
	</script>
	';
}

function draw_javascript_ckeditor() {
	global $_josh;
	return draw_javascript_src($_josh['write_folder'] . '/lib/ckeditor/ckeditor.js');
}

function draw_javascript_lib() {
	global $_josh;
	return draw_javascript_src() .
		draw_javascript_src($_josh['write_folder'] . '/lib/prototype/prototype-1.5.0.js') .
		draw_javascript_src($_josh['write_folder'] . '/lib/scriptaculous/scriptaculous-1.6.5/scriptaculous.js');
}

function draw_javascript_link($target, $text, $id=false, $class=false) {
	//so as to avoid return false;
	$id = ($id) ? ' id="' . $id . '"' : '';
	$class = ($class) ? ' class="' . $class . '"' : '';
	if ($target) {
		if (stristr($target, '"')) $target = str_replace('"', "'", $target);
		$target = ' onclick="javascript:' . $target . ';"';
	} else {
		$target = '';
	}
	return '<a style="cursor:pointer;"' . $target . $id . $class . '>' . $text . '</a>';
}

function draw_javascript_tinymce($path_css='/styles/tinymce.css', $path_script='/_site/tiny_mce/tiny_mce.js') {
	//todo deprecated
	return draw_javascript_src() . draw_javascript_src($path_script) . draw_javascript('form_tinymce_init("' . $path_css . '");');
}

function draw_javascript_src($filename=false) {
	global $_josh;
	if (!$filename && isset($_josh['write_folder'])) {
		if ($_josh['drawn']['javascript']) return false; //only draw this file once per page
		$_josh['drawn']['javascript'] = true;
		$filename = $_josh['write_folder'] . '/javascript.js';
		$joshlibf = $_josh['joshlib_folder'] . '/javascript.js';
		if (!file_is($filename) || (filemtime($joshlibf) > filemtime($_josh['root'] . $filename))) {
			//either doesn't exist or is out-of-date
			if (!file_put($filename, file_get($joshlibf))) return error_handle(__FUNCTION__ . ' can\'t write the js file.', __file__, __line__);
		}
	} elseif (!$filename) {
		return error_handle(__FUNCTION__ . ' needs the variable _josh[\'write_folder\'] to be set.', __file__, __line__);
	}
	//$src = 'http://joshlib.joshreisner.com/javascript.js';
	return $_josh['newline'] . '<script language="javascript" src="' . $filename . '" type="text/javascript"></script>';
}

function draw_link($href=false, $str=false, $newwindow=false, $args=false) {
	if (!$args)	{
		$args = array();
	} elseif (!is_array($args)) {
		//if args is a string, make it the link's class
		$args = array('class'=>$args);
	}
	if (!$href) return $str;
	
	//obfuscate email
	if (format_text_starts('mailto:', $href)) {
		if (!$str) $str = format_ascii(format_string(str_replace('mailto:', '', $href), 60));
		$args['href'] = format_ascii($href);
	} else {
		if (!$str) $str = format_string($href, 60);
		$args['href'] = htmlentities($href);
	}
	if ($newwindow) $args['target'] = '_blank';
	
	return draw_tag('a', $args, $str);
}

function draw_link_ajax_set($table, $column, $id, $value, $str, $args=false) {
	if (!$id) $Id = 'session';
	return draw_link('javascript:ajax_set(\'' . $table . '\',\'' . $column . '\',\'' . $id . '\',\'' . $value . '\');', $str, false, $args);
}

function draw_list($options, $args_or_class=false, $type='ul', $selected=false) {
	//make a ul or an ol out of a one-dimensional array
	
	global $_josh;
	if (!is_array($options) || (!$count = count($options))) return false;
	if (!is_array($args_or_class)) $args_or_class = array('class'=>$args_or_class); //if args is a string, it's legacy class
		
	$counter = 1;
	for ($i = 0; $i < $count; $i++) {
		$liclass = 'option' . ($i + 1);
		if ($counter == 1) $liclass .= ' first';
		if ($counter == $count) $liclass .= ' last';
		if ($selected == ($i + 1)) $liclass .= ' selected';
		if ($options[$i] == false) $options[$i] = '';
		$options[$i] = draw_tag('li', array('class'=>$liclass), $options[$i]);
		$counter++;
	}
	return draw_tag($type, $args_or_class, implode($options,  "\t"));
}

function draw_list_db($table_or_sql, $linkprefix='', $args_or_class=false, $type='ul', $selected=false) {
	//experiment.  draw_list_table('blog_categories', '/c/?id=') will return a UL with a list of links
	//todo: add better behaviors like if id column dne, or something
	if (!stristr($table_or_sql, ' ')) $table_or_sql = 'SELECT id, title FROM ' . $table_or_sql . ' t WHERE t.is_active = 1 ORDER BY t.precedence';
	$result = db_table($table_or_sql);
	foreach ($result as &$r) $r = draw_link($linkprefix . $r['id'], $r['title']);
	return draw_list($result, $args_or_class, $type, $selected);
}

function draw_meta_description($string) {
	global $_josh;
	return draw_tag('meta', array('name'=>'description', 'content'=>$string)) . $_josh['newline'];
}

function draw_meta_utf8() {
	global $_josh;
	return draw_tag('meta', array('http-equiv'=>'Content-Type', 'content'=>'text/html; charset=utf-8')) . $_josh['newline'];
}

function draw_navigation($options, $match=false, $type='text', $class='navigation', $folder='/images/navigation/', $useid=false) {
	//useid is for rollover navigation -- use everything after id= instead of slashless url
	//type could be text, images or rollovers
	global $_josh;
	
	//this is so you can have several sets of rollovers in the same page eg the smarter toddler site
	if (!isset($_josh['drawn_navigation'])) $_josh['drawn_navigation'] = 0;
	$_josh['drawn_navigation']++;
	
	//skip if empty
	if (!is_array($options) || !count($options)) return false;
	
	//$return = $_josh['newline'] . $_josh['newline'] . '<!--start nav-->' . $_josh['newline'] . '<ul class='' . $class . ''>';
	$return = array();
	if ($match === false) {
		$match = $_josh['request']['path'];
	} elseif ($match === true) {
		$match = $_josh['request']['path_query'];
	} elseif ($match == '//') {
		//to take care of a common / . folder . / scenario
		$match = '/';
	}
	error_debug('<b>draw_navigation</b> match is ' . $match, __file__, __line__);
	$selected = false;
	$counter = 1;
	$javascript = $_josh['newline'];
	foreach ($options as $url=>$title) {
		$name = 'option_' . $_josh['drawn_navigation'] . '_' . $counter;
		$thisoption = '<a href="' . $url . '" class="' . $name;
		if (str_replace(url_base(), '', $url) == $match) {
			$img_state = '_on';
			$thisoption .= ' selected';
			$selected = $counter;
		} else {
			$img_state = '_off';
			if ($type == 'rollovers') {
				$thisoption .= '" onmouseover="javascript:img_roll(\'' . $name . '\',\'on\');" onmouseout="javascript:img_roll(\'' . $name . '\',\'off\');';
			}
		}
		$thisoption .= '">';
		if ($type == 'text') {
			$thisoption .= $title;
		} elseif (($type == 'images') || ($type == 'rollovers')) {
			if ($useid) {
				$img = substr($url, strpos($url, 'id=') + 3);
			} else {
				$img = str_replace('/', '', $url);
				if ($pos = strpos($img, '?')) $img = substr($img, 0, $pos);
				if (empty($img)) $img = 'home';
			}
			if ($type == 'rollovers') {
				$javascript .= $name . '_on		 = new Image;' . $_josh['newline'];
				$javascript .= $name . '_off	 = new Image;' . $_josh['newline'];
				$javascript .= $name . '_on.src	 = "' . $folder . $img . '_on.png";' . $_josh['newline'];
				$javascript .= $name . '_off.src = "' . $folder . $img . '_off.png";' . $_josh['newline'];
			}
			$thisoption .= draw_img($folder . $img . $img_state . '.png', false, false, $name);
		}
		$thisoption .= '</a>';
		$return[] = $thisoption;
		$counter++;
	}
	$return = 	draw_javascript_src() . draw_list($return, $class, 'ul', $selected);
	if ($type == 'rollovers') $return = draw_javascript('if (document.images) {' . $javascript . '}') . $return;
	return $return;
}

function draw_newline($count=1) {
	global $_josh;
	$return = '';
	for ($i = 0; $i < $count; $i++) {
		$return .= $_josh['newline'];
	}
	return $return;
}

function draw_page($title, $html) {
	//this is for joshserver and error handling, eventually for setup your site messages
	return '<html><head><title>' . strip_tags($title) . '</title></head>
			<body style="margin:0px;">
				<table width="100%" height="100%" cellpadding="0" cellspacing="0" border="0" style="background-color:#ddd; font-family:verdana, arial, sans-serif; font-size:13px; line-height:20px; color:#444;">
					<tr><td align="center">
					<div style="background-color:#fff;text-align:left;padding:10px 20px 10px 20px;width:360px;min-height:230px;position:relative;">
						<h1 style="color:#444; font-weight:normal; font-size:24px; line-height:30px;">' . $title . '</h1>' . 
						$html . '
					</div>
				</td></tr></table>
			</body>
		</html>';
}

function draw_rss_link($address) {
	return draw_tag('link', array('rel'=>'alternate', 'type'=>'application/rss+xml', 'title'=>'RSS', 'href'=>$address));
}

function draw_span($class, $inner) {
	//eg draw_span('title', $r) == draw_container('span', $r['title'], array('class'=>'title')) == '<span class="title">' . $r['title'] . '</span>'
	if (is_array($inner)) {
		if (isset($inner[$class])) {
			$inner = $inner[$class];
		} else {
			error_handle('$inner not set', __function__ . ' is looking for ' . $class . ' to be a key in the array passed'. __file__, __line__);
		}
	}
	return draw_container('span', $inner, array('class'=>$class));
}

function draw_swf($path, $width, $height, $border=0) {
	//standards-compliant satay method (http://www.alistapart.com/articles/flashsatay)
	return '<object type="application/x-shockwave-flash" data="' . $path . '" width="' . $width . '" height="' . $height . '"><param name="movie" value="' . $path . '" /></object>';

	//adobe method, deprecated
	return '<object classid="clsid:D27CDB6E-AE6D-11cf-96B8-444553540000" codebase="http://download.macromedia.com/pub/shockwave/cabs/flash/swflash.cab#version=9,0,28,0" width="' . $width . '" height="' . $height . '">
		<param name="movie" value="' . $path . '" />
		<param name="quality" value="high" />
		<embed src="' . $path . '" quality="high" pluginspage="http://www.adobe.com/shockwave/download/download.cgi?P1_Prod_Version=ShockwaveFlash" type="application/x-shockwave-flash" width="' . $width . '" height="' . $height . '" border="' . $border . '"></embed>
	  </object>';
}

function draw_tag($tag, $args=false, $innerhtml=false) {
	$tag = strToLower($tag);
	$return = '<' . $tag;
	$return .= (is_array($args)) ? draw_args($args) : draw_arg($args);
	if ($innerhtml === false) {
		$return .= '/>';
	} else {
		if (is_numeric($innerhtml) && ($innerhtml == 0)) $innerhtml = '&#48;';
		if (($tag == 'td') && empty($innerhtml)) $innerhtml = '&nbsp;';
		$return .= '>' . $innerhtml . '</' . $tag . '>';
	}
	$nonbreaking_tags = array('a', 'b', 'img', 'nobr', 'input');
	if (!in_array($tag, $nonbreaking_tags)) $return .= draw_newline();
	if ($tag == 'table') $return .= draw_newline(2);
	return $return;
}

?>