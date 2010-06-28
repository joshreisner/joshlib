<?php
error_debug('including draw.php', __file__, __line__);

function draw_argument($name, $value=false) {
	if ($value === false) return;
	return ' ' . strToLower($name) . '="' . str_replace('"', format_ascii('"'), $value) . '"';
}

function draw_arguments($arguments=false) {
	$arguments = array_arguments($arguments);
	$return = '';
	foreach ($arguments as $key=>$value) $return .= draw_argument($key, $value);
	return $return;
}

function draw_array($array, $nice=false) {
	global $_josh;
	if (!is_array($array)) return false;
	$return = '<table cellspacing="1" style="background-color:#ccc;color:#333;border:0px;">';
	//if (!$nice) ksort($array);
	while(list($key, $value) = each($array)) {
		$key = urldecode($key);
		if ($nice && (strToLower($key) == 'j')) continue;
		$value = format_quotes($value);
		if (strToLower($key) == 'email') $value = '<a href="mailto:' . $value . '">' . $value . '</a>';
		if (is_array($value)) $value = draw_array($value, $nice);
		$return  .= '<tr><td style="background-color:#eee;"><b>';
		$return .= ($nice) ? format_text_human($key)  : $key;
		$return .= '&nbsp;</b></td><td style="background-color:#fff;">';
		$return .= is_object($value) ? 'object value' : $value;
		$return .= '</td></tr>';
	}
	$return .= '</table>';
	return $return;
}

function draw_audio_embed($src) {
	return '<embed src="' . $src . '" volume="50" width="314" height="60"><noembed><bgsound src="' . $src . '"/></noembed></embed>';
}

function draw_autorefresh($minutes=5) {
	return draw_tag('meta', array('http-equiv'=>'refresh', 'content'=>$minutes * 60));
}

function draw_body_open() {
	//draw the opening body tag, in, say, drawTop()
	global $_josh;
	$classes = array(url_folder());
	if ($_josh['editing']) $classes[] = 'query';
	if ($_josh['request']['subfolder']) $classes[] = $_josh['request']['subfolder'];
	$string = implode(' ', $classes);
	return '<body class="' . $string . '" id="' . $string . '">';
}

function draw_calendar($month=false, $year=false, $events=false, $divclass='calendar', $linknumbers=false, $type='div', $toggling=false) {
	/*
		for livingcities roundup & events page, soon intranet
		$events is an optional associative array that you can pass in (like via db_table) which can contain the following values:
			start		REQUIRED	string date
			title		REQUIRED	the event title
			end			optional	string date
			description	optional	should there be a description below the title?  eg used in a bubble
			link		optional	if the event should be linked
			color		optional	if there should be a background color to its div
			toggling	optional	mouseover event
	*/
	global $_josh;
	$id = 0;
	
	if (!function_exists('draw_event')) {
		function draw_event($e, $id, $toggling) {
			global $id;
			$id++;
			$e['title'] = format_string($e['title']);
			if (!isset($id)) $id = 'idempty'; //id should really be defined
			$toggling = ($toggling) ? 'javascript:toggleEvent(' . $id . ');' : false;
			if (!empty($e['link'])) $e['title'] = draw_link($e['link'], $e['title'], false, array('id'=>'calendar_link_' . $id, 'onmouseover'=>$toggling, 'onmouseout'=>$toggling));
			if (!empty($e['description'])) $e['title'] .= draw_div_class('description', $e['description'], array('id'=>'event_' . $id . '_description'));
			$style = (isset($e['color'])) ? 'background-color:#' . $e['color'] : false;
			return draw_div_class('event', $e['title'], array('style'=>$style, 'id'=>'event_' . $id));		
		}
	}

	//decide which month we're drawing
	if (!$month) $month = $_josh['month'];
	if (!$year) $year = $_josh['year'];
	
	//reprocess the $events array into $cal_events
	$cal_events = array(1=>'', 2=>'', 3=>'', 4=>'', 5=>'', 6=>'', 7=>'', 8=>'', 9=>'', 10=>'', 11=>'', 12=>'', 13=>'', 14=>'', 15=>'', 16=>'', 17=>'', 18=>'', 19=>'', 20=>'', 21=>'', 22=>'', 23=>'', 24=>'', 25=>'', 26=>'', 27=>'', 28=>'', 29=>'', 30=>'', 31=>'');
	if (is_array($events)) {
		$month_start	= mktime(0, 0, 0, $month, 1, $year);
		$month_end		= mktime(23, 59, 59, $month+1, 0, $year);
		foreach ($events as $e)	{
			if (!empty($e['start'])) {
				//parse start
				$start			= strToTime($e['start']);
				$start_day		= date('j', $start);
				
				if (empty($e['end'])) {
					//if the end is empty then the event doesn't span. we have to assume that it's just this day
					$cal_events[$start_day] .= draw_event($e, $id, $toggling);
				} else {
					//otherwise could be before or after
					$end			= strToTime($e['end']);
					$startAt		= ($month_start > $start) ? 1 : $start_day;
					$endAt			= ($month_end < $end) ? 31 : date('j', $end);
					for ($i = $startAt; $i <= $endAt; $i++) $cal_events[$i] .= draw_event($e, $id, $toggling);
				}
			}
		}
	}
	
	$firstday = date('w', mktime(0, 0, 0, $month, 1, $year));
	$lastday  = date('d', mktime(0, 0, 0, ($month + 1), 0, $year));
	
	$days_short = array('sun', 'mon', 'tue', 'wed', 'thu', 'fri', 'sat');
	$days_long = array('Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday');

	$return = '';	
	if ($type == 'table') $return .= '<tr>';
	for ($i = 0; $i < 7; $i++) {
		if ($type == 'table') {
			$return .= draw_container('td', $days_long[$i], 'header ' . $days_short[$i]);
		} else {
			$return .= draw_div_class('header ' . $days_short[$i], $days_long[$i]);
		}
	}
	if ($type == 'table') $return .= '</tr>';
	for ($week = 1, $thisday = 1; ($thisday < $lastday); $week++) {
		if ($type == 'table') $return .= '<tr>';
		for ($day = 1; $day <= 7; $day++) {
			$thisday = (((7 * ($week - 1)) + $day) - $firstday);
			if ($thisday > 0 && $thisday <= $lastday) {
				$class = 'day';
				if (($year == $_josh['year']) && ($month == $_josh['month']) && ($thisday == $_josh['today'])) $class .= ' today';
				if (!empty($cal_events[$thisday])) $class .= ' events';
				if ($type == 'table') {
					$return .= draw_container('td', 
						'<div class="number">' . 
						((isset($cal_events[$thisday]) && $linknumbers) ? draw_link('javascript:calendarNumberLink(' . $month . ',' . $thisday . ',' . $year . ');', $thisday) : $thisday) . '</div>' . @$cal_events[$thisday],
						 $class . ' ' . $days_short[$day-1]);
				} else {
					$return .= draw_div_class($class . ' ' . $days_short[$day-1], '<div class="number">' . ((isset($cal_events[$thisday]) && $linknumbers) ? draw_link('javascript:calendarNumberLink(' . $month . ',' . $thisday . ',' . $year . ');', $thisday) : $thisday) . '</div>' . @$cal_events[$thisday]);
				}
			} else {
				if ($type == 'table') {
					$return .= draw_container('td', '', 'blank ' . $days_short[$day-1]);
				} else {
					$return .= draw_div_class('blank ' . $days_short[$day-1]);
				}
			}
		}
		if ($type == 'table') $return .= '</tr>';
	}
	if ($type == 'table') {
		return draw_container('table', $return, array('class'=>$divclass, 'cellspacing'=>'0'));
	} else {
		return draw_div_class($divclass, $return);
	}
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

function draw_container($tag, $innerhtml, $arguments=false) {
	//convenience function for draw_tag if you're just writing a simple container tag
	return draw_tag($tag, $arguments, $innerhtml);
}

function draw_definition_list($array, $arguments=false) {
	error_deprecated('use draw_dl');
	$return = '';
	foreach ($array as $key=>$value) $return .= draw_container('dt', $key) . draw_container('dd', $value);
	return draw_container('dl', $return, $arguments);
}

function draw_div($id, $innerhtml=false, $arguments=false) {
	//convenience function specifically for DIVs, since they're so ubiquitous
	//todo deprecate this in favor of draw_div_id
	if (!$innerhtml) $innerhtml = '&nbsp;';
	return draw_div_id($id, $innerhtml, $arguments);
}

function draw_div_class($class, $innerhtml='', $arguments=false) {
	//convenience function specifically for DIVs, since they're so ubiquitous
	$arguments = array_arguments($arguments);
	$arguments['class'] = $class;
	if (empty($innerhtml)) $arguments['class'] .= ' empty';
	return draw_tag('div', $arguments, $innerhtml);
}

function draw_div_id($id, $innerhtml='', $arguments=false) {
	//convenience function specifically for DIVs, since they're so ubiquitous
	$arguments = array_arguments($arguments);
	$arguments['id'] = $id;
	return draw_tag('div', $arguments, $innerhtml);
}

function draw_div_open($id=false) {
	return draw_tag('div', array('id'=>$id), false, true); 
}

function draw_dl($array, $class=false) {
	$return = '';
	foreach ($array as $key=>$value) $return .= draw_container('dt', $key) . draw_container('dd', $value);
	return draw_container('dl', $return, array('class'=>$class));
}

function draw_doctype() {
	return '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd"><html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">';
}

function draw_favicon($location='/images/favicon.png') {
	//only accepts PNGs right now
	return draw_tag('link', array('rel'=>'shortcut icon', 'href'=>$location, 'type'=>'image/png')); 
}

function draw_file_icon($filename, $link=true) {
	return file_icon($filename, $link);
}

function draw_focus($form_element) {
	global $_josh;
	if (isset($_josh['drawn']['focus'])) return false;
	$_josh['drawn']['focus'] = $form_element;
	return draw_javascript('document.getElementById("' . $form_element . '").focus();');
}

function draw_form_button($text, $location=false, $class=false, $disabled=false, $javascript=false) {
	//todo deprecate
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

function draw_form_checkbox($name, $checked=false, $arguments=false, $javascript=false) {
	$arguments = array_arguments($arguments);
	$arguments['class'] = (empty($arguments['class'])) ? 'checkbox' : $arguments['class'] . ' checkbox';
	$arguments['type'] = 'checkbox';
	$arguments['name'] = $arguments['id'] = $name;
	if ($javascript) $arguments['onclick'] = 'javascript:' . $javascript;
	if ($checked) $arguments['checked'] = 'checked';
	return draw_tag('input', $arguments);
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
	if (!isset($_josh['drawn']['focus'])) {
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

function draw_form_select($name, $sql_options, $value=false, $not_nullable=true, $class=false, $onchange=false, $nullvalue='', $maxlength=60, $disabled=false) {
	//2010 04 04: changed $required to $not_nullable because it could still be required and have a null value at the top
	$return = ($not_nullable) ? '' : '<option value="">' . $nullvalue . '</option>';
	$lastgroup = '';
	if (is_array($sql_options)) {
		while (@list($key, $val, $group) = each($sql_options)) {
			if (is_array($val)) @list($key, $val, $group) = array_values($val); //possible db_table optgroup situation
			$val = format_string($val, $maxlength);
			
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
			$return .= draw_tag('option', array('value'=>$key, 'id'=>$name . $key, 'selected'=>($key == $value) ? 'selected' : false), $val);
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
			$return .= draw_tag('option', array('value'=>$r[$key[0]], 'id'=>$name . $r[$key[0]], 'selected'=>($r[$key[0]] == $value) ? 'selected' : false), format_string($r[$key[1]]));
		}
	}
	if (isset($grouped) && $grouped) $return .= '</optgroup>';

	$class = ($class) ? $class . ' select' : 'select';
	if ($onchange) $onchange = 'javascript:' . $onchange;
	return draw_tag('select', array('name'=>$name, 'id'=>$name, 'class'=>$class, 'onchange'=>$onchange, 'disabled'=>$disabled), $return);
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

function draw_google_analytics($id) {
	global $_josh;
	error_debug('drawing google tracker', __file__, __line__);
	return draw_javascript_src((($_josh['request']['protocol'] == 'https') ? 'https://ssl' : 'http://www') . '.google-analytics.com/ga.js') . draw_javascript('try {
			var pageTracker = _gat._getTracker("' . $id. '");
			pageTracker._setDomainName(".' . $_josh['request']['domain'] . '");
			pageTracker._setAllowLinker(true);
			pageTracker._setAllowHash(false);
			pageTracker._trackPageview(); 
		} catch(err) {}');
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

function draw_google_map($markers=false, $center=false, $zoom=false) {
	//haven't figured out the appropriate place to store all this stuff.  this calls a javascript function which should be local
	//markers must be an array with latitude, longitude, title, description, color
	global $_josh;
	
	if (!isset($_josh['google']['mapkey'])) error_handle(__function__ . ' requires a google maps api key', 'you can ' . draw_link('http://code.google.com/apis/maps/signup.html', 'go here') . ' to get one');
	
	//markers
	$markerstr = '';
	if ($markers) {
		$count = count($markers);
		$lat = 0;
		$lon = 0;
		foreach ($markers as $m) {
			$lat += $m['latitude'];
			$lon += $m['longitude'];
			$markerstr .= NEWLINE . '
				var marker = draw_marker(' . $m['latitude'] . ', ' . $m['longitude'] . ', "' . $m['title'] . '", "' . $m['description'] . '", "' . $m['color'] . '");
				map.addOverlay(marker);';
		}
	}
	
	//set an arbitrary center or center auto
	if ($center) {
		list($lat, $lon) = $center;
	} elseif (isset($count)) {
		$lat = $lat / $count;
		$lon = $lon / $count;
	} else {
		$lat = 37.4419;
		$lon = -122.1419;
	}

	//todo determine zoom automatically
	if (!$zoom) $zoom = 11;

	return '<div id="map"></div>' . draw_javascript_src('http://maps.google.com/maps?file=api&amp;v=2&amp;key=' . $_josh['google']['mapkey']) . draw_javascript('
		function map_load() {
			if (GBrowserIsCompatible()) {
				var map = new GMap2(document.getElementById("map"));
				map.setCenter(new GLatLng(' . $lat . ', ' . $lon . '), ' . $zoom . ');
				map.addControl(new GLargeMapControl());
				' . $markerstr . '
			}
			window.onunload = GUnload;
		}
		window.onload = map_load;
		');
}

function draw_google_tracker($id) {
	//todo deprecate
	return draw_google_analytics($id);
}

function draw_img($path, $link=false, $alt=false, $name=false, $linknewwindow=false) {
	//alt could also be an array of arguments
	global $_josh;
	
	//$path can be relative
	if (!$realpath = realpath($path)) $realpath = realpath(DIRECTORY_ROOT . $path);
	
	//get width and height
	$image = @getimagesize($realpath);
	if (!$image) return '';
	
	//assemble tag
	$arguments = array('src'=>url_base() . str_replace(DIRECTORY_ROOT, '', $realpath), 'width'=>$image[0], 'height'=>$image[1], 'border'=>0);
	if (is_array($alt)) {
		//values of alt can overwrite width, height, border, even src
		$arguments = array_merge($arguments, $alt);
	} else {
		$arguments['alt'] = $alt;
		$arguments['name'] = $arguments['class'] = $arguments['id'] = $name;
		$alt = false; //this was passed as a string, needs to be nulled so it doesn't go to draw_link
	}
	
	//fix slashes on windows
	$arguments['src'] = str_replace('\\', '/', $arguments['src']);
	
	//force alt text for w3 validation
	if (empty($arguments['alt'])) {
		list($name, $ext, $path) = file_name($path);
		$arguments['alt'] = format_text_human($name);
	}

	$image = draw_tag('img', $arguments);
	if ($link) return draw_link($link, $image, $linknewwindow, $alt);
	return $image;
}

function draw_img_random($folder, $link=false, $class=false) {
	return draw_img(array_random(file_folder('/images/random/', 'jpg,jpeg,gif,png', true)));
}

function draw_javascript($javascript=false) {
	if (!$javascript) return draw_javascript_src();
	return draw_tag('script', array('language'=>'javascript', 'type'=>'text/javascript'), $javascript);
}

function draw_javascript_lib() {
	error_deprecated(__FUNCTION__ . ' was deprecated on 4/5/2010 - use lib_get');
	return draw_javascript_src() . lib_get('scriptaculous');
}

function draw_javascript_link($target, $text, $id=false, $class=false) {
	error_deprecated(__FUNCTION__ . ' was deprecated on 4/10/2010 - you can use draw_link now instead');
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
	error_deprecated(__FUNCTION__ . ' is deprecated as of 3/11/2010 use lib_get');
	return draw_javascript_src() . draw_javascript_src($path_script) . draw_javascript('form_tinymce_init("' . $path_css . '");');
}

function draw_javascript_src($filename=false) {
	global $_josh;
	if (!$filename) {
		//default is to draw joshlib's own javascript library
		if (isset($_josh['drawn']['javascript'])) return false; //only draw this file once per page
		$_josh['drawn']['javascript'] = true;
		$filename = DIRECTORY_WRITE . '/javascript.js';
		$joshlibf = DIRECTORY_JOSHLIB . 'javascript.js';
		if (!file_check($filename) || (filemtime($joshlibf) > filemtime(DIRECTORY_ROOT . $filename))) {
			//either doesn't exist or is out-of-date
			if (!file_put($filename, file_get($joshlibf))) return error_handle(__FUNCTION__ . ' can\'t write the js file.', __file__, __line__);
		}
	}
	return draw_tag('script', array('language'=>'javascript', 'src'=>$filename, 'type'=>'text/javascript'), '');
}

function draw_link($href=false, $str=false, $newwindow=false, $arguments=false) {
	$arguments = array_arguments($arguments);
	if (format_text_starts('mailto:', $href)) {
		//obfuscate email
		if (!$str) $str = format_ascii(format_string(str_replace('mailto:', '', $href), 60));
		$arguments['href'] = format_ascii($href);
	} elseif (format_text_starts('javascript:', $href)) {
		//correct link for javascript
		$arguments['href'] = '#';
		$arguments['onclick'] = $href;
	} elseif ($href) {
		if (!$str) $str = format_string($href, 60);
		$arguments['href'] = htmlentities($href);
	} else {
		$arguments['class'] = (isset($arguments['class'])) ? $arguments['class'] . ' empty' : 'empty';
	}
	if ($newwindow) $arguments['target'] = '_blank'; //todo deprecate?
	return draw_tag('a', $arguments, $str);
}

function draw_link_ajax_set($table, $column, $id, $value, $str, $arguments=false) {
	//for setting single value ala show/hide intranet helptext ~ todo deprecate
	if (!$id) $id = 'session';
	return draw_link('javascript:ajax_set(\'' . $table . '\',\'' . $column . '\',\'' . $id . '\',\'' . $value . '\');', $str, false, $arguments);
}

function draw_list($options, $arguments=false, $type='ul', $selected=false) {
	//make a ul or an ol out of a one-dimensional array
	if (!is_array($options) || (!$count = count($options))) return false;
	//if (!is_array($arguments)) $arguments = array('class'=>$arguments); //if arguments is a string, it's legacy class
	$arguments = array_arguments($arguments);
		
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
	return draw_tag($type, $arguments, implode($options, ''));
}

function draw_list_columns($options, $columns=2, $arguments=false, $type='ul', $selected=false) {
	//experiment
	$return = array();
	$length = ceil(count($options) / $columns);
	for ($i = 0; $i < $columns; $i++) $return[] = draw_list(array_slice($options, $i * $length, $length));
	return draw_list($return, $arguments, $type, $selected);
}

function draw_list_db($table_or_sql, $linkprefix='', $arguments=false, $type='ul', $selected=false) {
	//experiment.  eg draw_list_table('blog_categories', '/c/?id=') will return a UL with a list of links
	//todo: add better behaviors like if id column dne, or something
	//03-11-2010 jr: interesting, is this being used?  example sounds like harvard
	if (!stristr($table_or_sql, ' ')) $table_or_sql = 'SELECT id, title FROM ' . $table_or_sql . ' t WHERE t.is_active = 1 ORDER BY t.precedence';
	$result = db_table($table_or_sql);
	foreach ($result as &$r) $r = draw_link($linkprefix . $r['id'], $r['title']);
	return draw_list($result, $arguments, $type, $selected);
}

function draw_list_sets($options, $length=2, $arguments=false, $type='ul', $selected=false) {
	//return a list broken into sublists by the number of options, eg draw_list_sets with 2 is 
	$return = array();
	$columns = ceil(count($options) / $length);
	for ($i = 0; $i < $columns; $i++) $return[] = draw_list(array_slice($options, $i * $length, $length));
	return draw_list($return, $arguments, $type, $selected);
}

function draw_meta_description($string) {
	return draw_tag('meta', array('name'=>'description', 'content'=>strip_tags($string)));
}

function draw_meta_keywords($string) {
	return draw_tag('meta', array('name'=>'keywords', 'content'=>$string));
}

function draw_meta_utf8() {
	return draw_tag('meta', array('http-equiv'=>'Content-Type', 'content'=>'text/html; charset=utf-8'));
}

function draw_nav($options, $type='text', $class='nav', $match='path', $sets=false, $add_home=false) {
	global $_josh;
	//2009 04 07 trying to come up with a simpler, better version of this function
	//type can be text, images or rollovers
	//match can be path, path_query or folder
	
	if (is_string($options)) $options = array_key_promote(db_table($options)); //might be sql	
	
	if ($add_home) $options = array_merge(array('/'=>'Home'), $options);
	
	if (!count($options)) return false; //skip if empty
	$return = array();
		
	//this is so you can have several sets of rollovers in the same page eg the smarter toddler site
	if (!isset($_josh['drawn']['navigation'])) $_josh['drawn']['navigation'] = 0;
	$_josh['drawn']['navigation']++;
	
	//determine matching target
	if ($match == 'path') {
		$match = $_josh['request']['path'];
	} elseif ($match == 'path_query') {
		$match = $_josh['request']['path_query'];
	} elseif (($match == '//') || ($match == '')) {
		$match = '/';
	}
	error_debug('<b>' . __function__ . '</b> match is ' . $match, __file__, __line__);
	
	$selected = false;
	$counter = 1;
	$javascript = NEWLINE;
	foreach ($options as $url=>$title) {
		$name = 'option_' . $_josh['drawn']['navigation'] . '_' . $counter;
		$args = array('name'=>$name, 'class'=>$name);
		
		if ($match == 'folder') {
			//eg /about/page1/ and /about/page2/ will match
			$urlparts = explode('/', $url);
			$matching = (@$urlparts[1] == $_josh['request']['folder']);
			if (substr($url, 0, 5) == 'http:') $matching = false;
		} else {
			$matching = (str_replace(url_base(), '', $url) == $match);
		}
		
		if ($matching) {
			$img_state = '_on';
			$args['class'] .= ' selected';
			$selected = $counter;
		} else {
			$img_state = '_off';
			if ($type == 'rollovers') {
				$args['onmouseover'] = 'javascript:img_roll(\'' . $name . '\',\'on\');';
				$args['onmouseout'] = 'javascript:img_roll(\'' . $name . '\',\'off\');';
			}
		}
		
		if (($type == 'images') || ($type == 'rollovers')) {
			$img = '/images/' . $class . '/' . format_text_code($title);
			if ($type == 'rollovers') $javascript .= $name . '_on		 = new Image;' . NEWLINE . $name . '_off	 = new Image;' . NEWLINE . $name . '_on.src	 = "' . $img . '_on.png";' . NEWLINE . $name . '_off.src = "' . $img . '_off.png";' . NEWLINE;
			$inner = draw_img($img . (($type == 'rollovers') ? $img_state : false) . '.png', false, $title, $name);
		} else { //type == text
			$inner = $title;		
		}
		$return[] = draw_link($url, $inner, false, $args);
		$counter++;
	}
	if ($sets) {
		$return = draw_list_sets($return, $sets, $class, 'ul', $selected);
	} else {
		$return = draw_list($return, $class, 'ul', $selected);
	}
	if ($type == 'rollovers') $return = draw_javascript_src() . draw_javascript('if (document.images) {' . $javascript . '}') . $return;
	return $return;
}

function draw_navigation($options, $match=false, $type='text', $class='navigation', $folder='/images/navigation/', $override=false) {
	//2010 04 07 deprecated
	error_deprecated(__function__ . ' was deprecated on 4/7/2010.  use draw_nav instead');
	
	//useid is for rollover navigation -- use everything after id= instead of slashless url
	//2010 03 15 jr: useid is changed now to override -- can be 'id' or 'folder'
	//type could be text, images or rollovers
	global $_josh;
	
	//this is so you can have several sets of rollovers in the same page eg the smarter toddler site
	if (!isset($_josh['drawn']['navigation'])) $_josh['drawn']['navigation'] = 0;
	$_josh['drawn']['navigation']++;
	
	//skip if empty
	if (!is_array($options) || !count($options)) return false;
		
	$return = array();
	if ($match === false) {
		$match = $_josh['request']['path'];
	} elseif ($match === true) {
		$match = $_josh['request']['path_query'];
	} elseif ($match == '//') {
		//to take care of a common / . folder . / scenario
		$match = '/';
	} elseif ($match == 'folder') {
		//new option
	}
	error_debug('<b>' . __function__ . '</b> match is ' . $match, __file__, __line__);
	$selected = false;
	$counter = 1;
	$javascript = NEWLINE;
	foreach ($options as $url=>$title) {
		$name = 'option_' . $_josh['drawn']['navigation'] . '_' . $counter;
		$args = array('name'=>$name, 'class'=>$name);
		
		if ($match == 'folder') {
			//eg /about/page1/ and /about/page2/ will match
			$urlparts = explode('/', $url);
			$matching = (@$urlparts[1] == $_josh['request']['folder']);
		} else {
			$matching = (str_replace(url_base(), '', $url) == $match);
		}
		
		if ($matching) {
			$img_state = '_on';
			$args['class'] .= ' selected';
			$selected = $counter;
		} else {
			$img_state = '_off';
			if ($type == 'rollovers') {
				$args['onmouseover'] = 'javascript:img_roll(\'' . $name . '\',\'on\');';
				$args['onmouseout'] = 'javascript:img_roll(\'' . $name . '\',\'off\');';
			}
		}
		if (($type == 'images') || ($type == 'rollovers')) {
			if ($override) {
				if ($override == 'id') {
					$img = substr($url, strpos($url, 'id=') + 3);
				} elseif ($override == 'folder') {
					$urlparts = explode('/', $url);
					$img = @$urlparts[1];
					//die('img is ~' . $img . '~');
				}
			} else {
				$img = str_replace('/', '', $url);
				if ($pos = strpos($img, '?')) $img = substr($img, 0, $pos);
				if (empty($img)) $img = 'home';
			}
			if ($type == 'rollovers') {
				$javascript .= $name . '_on		 = new Image;' . NEWLINE;
				$javascript .= $name . '_off	 = new Image;' . NEWLINE;
				$javascript .= $name . '_on.src	 = "' . $folder . $img . '_on.png";' . NEWLINE;
				$javascript .= $name . '_off.src = "' . $folder . $img . '_off.png";' . NEWLINE;
			}
			$inner = draw_img($folder . $img . $img_state . '.png', false, false, $name);
		} else { //type == text
			$inner = $title;		
		}
		$return[] = draw_link($url, $inner, false, $args);
		$counter++;
	}
	$return = draw_list($return, $class, 'ul', $selected);
	if ($type == 'rollovers') $return = draw_javascript_src() . draw_javascript('if (document.images) {' . $javascript . '}') . $return;
	return $return;
}

function draw_page($title, $html) {
	//this is for joshserver and error handling, eventually for setup your site messages
	return '<html><head>' . draw_meta_utf8() . '<title>' . strip_tags($title) . '</title></head>
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
}

function draw_table($array, $name='untitled_table', $css=false) {
	//array should be an array of associative arrays or a table name
	
	//if it's a table name, get table data	
	if (!is_array($array)) $array = db_table('SELECT * FROM ' . $array);
	
	//if it's still not an array, exit
	if (!isset($array[0]) || !is_array($array[0])) return false;

	//create a new table class, fill it with keys from the array
	$t = new table($name);
	$columns = array_keys($array[0]);
	foreach ($columns as $c) $t->set_column($c);
	$return = $t->draw($array);
	
	//if css is set, prepend css
	if ($css) $return = draw_css('
		table				{ font-family:Verdana; font-size:12px; color:#444; border:1px solid #bbb; }
		table td, table th	{ padding:4px 11px 4px 7px; border-bottom:1px solid #bbb; }
		th					{ text-align:left; font-weight:normal; background-color:#ddd; }
		table td.delete, table th.delete		{ width:20px; min-width:20px; }
		table td.icon, table th.icon			{ width:20px; min-width:20px; }
		table td.checkbox, table th.checkbox	{ width:20px; min-width:20px; }
		table td.group							{ height:40px; vertical-align:bottom; text-transform:uppercase; font-weight:bold; font-size:11px;	 }
		table td.empty							{ height:180px; background-color:#f3f3f3; text-align:center; font-style:italic; }
		table td.d								{ text-align:center; cursor:move; }
		
		div.dropmarker {
			height:2px;
			background-color:black;
			width:650px;
			color:transparent;
			z-index:1000;
			margin:0px;		      
		}
	') . $return;
	
	return $return;
}

function draw_table_rows($array, $columns=2) {
	//generates <tr><td>value</td><td>value</td><td>value</td></tr> blocks of $columns length from an $array
	$count	= count($array);
	$mod	= $count % $columns;
	$width	= round(100 / $columns);
	$return	= '';
	for ($i = 0; $i < $mod; $i++) $array[] = '&nbsp;'; //fill empties at end
	foreach ($array as &$a) $a = draw_container('td', $a, array('width'=>$width . '%')); //wrap all cells in TDs
	for ($i = 0; $i < $count; $i += $columns) $return .= draw_container('tr', implode('', array_slice($array, $i, $columns))); //set TRs
	return $return;
}

function draw_tag($tag, $arguments=false, $innerhtml=false, $open=false) {
	$tag = strToLower($tag);
	$return = '<' . $tag;
	$return .= (is_array($arguments)) ? draw_arguments($arguments) : draw_argument('class', $arguments);
	if ($innerhtml === false) {
		$return .= ($open) ? '>' : '/>';
	} else {
		if (is_numeric($innerhtml) && ($innerhtml == 0)) $innerhtml = '&#48;';
		if (($tag == 'td') && empty($innerhtml)) $innerhtml = '&nbsp;';
		$return .= '>' . $innerhtml . '</' . $tag . '>';
	}
	return $return;
}

?>