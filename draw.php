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
	foreach ($array as $key=>$value) {
		$key = urldecode($key);
		if ($nice && (strToLower($key) == 'j')) continue;
		//$value = format_quotes($value);
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

function draw_article($content='', $arguments=false) {
	return draw_tag('article', $arguments, $content);
}

function draw_aside($content='', $arguments=false) {
	return draw_tag('aside', $arguments, $content);
}

function draw_audio_embed($src) {
	return '<embed src="' . $src . '" volume="50" width="314" height="60"><noembed><bgsound src="' . $src . '"/></noembed></embed>';
}

function draw_autorefresh($minutes=5) {
	return draw_tag('meta', array('http-equiv'=>'refresh', 'content'=>$minutes * 60));
}

function draw_b($inner, $arguments=false) {
	return draw_strong($inner, $arguments);
}

function draw_body_open($class=false) {
	//draw the opening body tag, in, say, drawTop()
	global $_josh;
	$args = array('class'=>$class);
	array_argument($args, url_folder());
	array_argument($args, url_subfolder());
	if ($_josh['editing']) array_argument($args, 'query');
	return '<body' . draw_arguments($args) . '>';
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
			
		todo deprecate table-based
	*/
	global $_josh;
	$id = 0;
	
	if (is_string($events)) $events = db_table($events);
	
	if (!function_exists('draw_event')) {
		function draw_event($e, $id, $toggling) {
			global $id;
			$id++;
			$e['title'] = format_string($e['title']);
			if (!isset($id)) $id = 'idempty'; //id should really be defined
			$toggling = ($toggling) ? 'javascript:toggleEvent(' . $id . ');' : false;
			if (!empty($e['link'])) $e['title'] = draw_link($e['link'], $e['title'], false, array('id'=>'calendar_link_' . $id, 'onmouseover'=>$toggling, 'onmouseout'=>$toggling));
			if (!empty($e['description'])) $e['title'] .= draw_div(array('id'=>'event_' . $id . '_description', 'class'=>'description'), $e['description']);
			$style = (isset($e['color'])) ? 'background-color:#' . $e['color'] : false;
			return draw_div_class(array('style'=>$style, 'id'=>'event_' . $id, 'class'=>'event'), $e['title']);		
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
			//allow for _date
			if (empty($e['start']) && !empty($e['start_date'])) $e['start'] = $e['start_date'];
			if (empty($e['end']) && !empty($e['end_date'])) $e['end'] = $e['end_date'];
			
			if (!empty($e['start'])) {
				//parse start
				$start			= strToTime($e['start']);
				$start_day		= date('j', $start);
				
				if (empty($e['end'])) {
					//if the end is empty then the event doesn't span. we have to assume that it's just this day
					if (date('n', $start) == $month) $cal_events[$start_day] .= draw_event($e, $id, $toggling);
				} else {
					//otherwise could be before or after, draw a series of events on each day in the span
					$end			= strToTime($e['end']);
					if (($month_start > $end) || ($month_end < $start)) continue;
					if ($month_start > $start) $start = $month_start;
					if ($month_end < $end) $end = $month_end;
					$start_day		= date('j', $start);
					$end_day		= date('j', $start);
					for ($i = $start_day; $i <= $end_day; $i++) $cal_events[$i] .= draw_event($e, $id, $toggling);
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
			$return .= draw_div('header ' . $days_short[$i], $days_long[$i]);
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
					$return .= draw_div($class . ' ' . $days_short[$day-1], '<div class="number">' . ((isset($cal_events[$thisday]) && $linknumbers) ? draw_link('javascript:calendarNumberLink(' . $month . ',' . $thisday . ',' . $year . ');', $thisday) : $thisday) . '</div>' . @$cal_events[$thisday]);
				}
			} else {
				if ($type == 'table') {
					$return .= draw_container('td', '', 'blank ' . $days_short[$day-1]);
				} else {
					$return .= draw_div('day blank ' . $days_short[$day-1]);
				}
			}
		}
		if ($type == 'table') $return .= '</tr>';
	}
	if ($type == 'table') {
		return draw_container('table', $return, array('class'=>$divclass, 'cellspacing'=>'0'));
	} else {
		return draw_div($divclass, $return);
	}
}

function draw_chrome_frame() {
	//should be inserted into the head element
	global $_josh;
	return '<meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
		<!--[if lt IE 7]>
			<script type="text/javascript" src="http://ajax.googleapis.com/ajax/libs/chrome-frame/1/CFInstall.min.js"></script>
			<script>
			window.attachEvent("onload", function() {
				CFInstall.check({ mode: "overlay", destination: "' . $_josh['request']['url'] . '" });
			});
			</script>
		<![endif]-->';	
}

function draw_comment($str) {
	return NEWLINE . NEWLINE . '<!-- ' . $str . ' -->' . NEWLINE . NEWLINE;
}

function draw_css($content) {
	return draw_tag('style', array('type'=>'text/css'), $content);
}

function draw_css_src($location='/css/global.css', $media=false, $manage_caching=true) {
	//special 'ie' mode for internet explorer
	$ie = ($media == 'ie');
	if ($ie) $media = 'screen';
	
	//refresh browser cache when the stylesheet is updated
	if ($manage_caching && ($filemtime = @filemtime(DIRECTORY_ROOT . $location))) $location .= '?' . $filemtime;
	
	$return = draw_tag('link', array('rel'=>'stylesheet', 'type'=>'text/css', 'media'=>$media, 'href'=>$location));
	if ($ie) $return = '<!--[if IE]>' . $return . '<![endif]-->';
	return $return;
}

function draw_csv($array, $separator=',') {
	//make a csv out of an associative $array
	$columns = array_keys($array[0]);
	$return = implode($separator, $columns) . NEWLINE;
	foreach ($array as $a) $return .= implode($separator, array_values($a)) . NEWLINE;
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

function draw_div($arguments=false, $inner='', $dbinfo=false) {
	//convenience function specifically for DIVs, since they're so ubiquitous
	$arguments = array_arguments($arguments);

	//makes a contenteditable field out of the DIV
	if ($dbinfo && (@list($table, $column, $dbid) = explode('.', $dbinfo))) { //expects dbinfo in this particular format
		$arguments['contenteditable'] = 'true';
		$arguments['data-table'] = $table;
		$arguments['data-column'] = $column;
		$arguments['data-id'] = $dbid;
	}
	return draw_tag('div', $arguments, $inner);
}

function draw_div_class($class, $innerhtml='', $arguments=false) {
	//todo deprecate
	$arguments = array_arguments($arguments);
	array_argument($arguments, $class);
	if (empty($innerhtml)) $arguments['class'] .= ' empty';
	return draw_tag('div', $arguments, $innerhtml);
}

function draw_div_class_open($class=false) {
	//todo deprecate
	return draw_div_open($class);
}

function draw_div_id($id, $innerhtml='', $arguments=false) {
	error_deprecated('draw_div_id has been deprecated on 5/18/2012.  please use draw_div instead, eg draw_div(\'#container\', \'this is a really nice container.\')');
	//convenience function specifically for DIVs, since they're so ubiquitous
	$arguments = array_arguments($arguments);
	$arguments['id'] = $id;
	return draw_tag('div', $arguments, $innerhtml);
}

function draw_div_open($arguments=false) {
	$arguments = array_arguments($arguments);
	return '<div' . draw_arguments($arguments) . '>';
}

function draw_dl($array, $class=false) {
	$return = '';
	foreach ($array as $key=>$value) $return .= draw_container('dt', $key) . draw_container('dd', $value);
	return draw_container('dl', $return, array('class'=>$class));
}

function draw_doctype($lang='en', $manifest=false) {
	if ($manifest) $manifest = ' manifest="' . $manifest . '"';
	return '<!DOCTYPE html>
        <!--[if IEMobile 7 ]><html class="no-js ie iem7" lang="' . $lang . '"' . $manifest . '><![endif]-->
        <!--[if lt IE 7 ]><html class="no-js ie ie6" lang="' . $lang . '"' . $manifest . '><![endif]-->
        <!--[if IE 7 ]><html class="no-js ie ie7" lang="' . $lang . '"' . $manifest . '><![endif]-->
        <!--[if IE 8 ]><html class="no-js ie ie8" lang="' . $lang . '"' . $manifest . '><![endif]-->
        <!--[if IE 9 ]><html class="no-js ie ie9" lang="' . $lang . '"' . $manifest . '><![endif]-->
        <!--[if (gt IE 9)|(gt IEMobile 7)|!(IEMobile)|!(IE)]><!--><html class="no-js" lang="' . $lang . '"' . $manifest . '><!--<![endif]-->';
}

function draw_dump($var, $forceType='', $bCollapsed=false) {
	//use $forceType='xml' for xml otherwise it will be recognized as a string
	//use $bCollapsed=true for collapsed view
	//get dBug class
	lib_get('dbug');
	
	new dBug($var, $forceType, $bCollapsed);
}

function draw_em($string) {
	return draw_tag('em', false, $string);
}

function draw_exit($var, $forceType='', $bCollapsed=false) {
	//use $forceType='xml' for xml otherwise it will be recognized as a string
	//use $bCollapsed=true for collapsed view
	//get dBug class
	lib_get('dbug');
	
	new dBug($var, $forceType, $bCollapsed);
	exit();
}

function draw_favicon($location='/images/favicon.png') {
	//only accepts PNGs right now
	return draw_tag('link', array('rel'=>'shortcut icon', 'href'=>$location, 'type'=>'image/png')); 
}

function draw_file_icon($filename, $link=true) {
	return file_icon($filename, $link);
}

function draw_firebug() {
	global $_josh;
	if ($_josh['mode'] == 'dev') return draw_javascript_src('https://getfirebug.com/firebug-lite.js');
	return false;
}

function draw_focus($form_element) {
	global $_josh;
	if (isset($_josh['drawn']['focus'])) return false;
	$_josh['drawn']['focus'] = $form_element;
	return draw_javascript('document.getElementById("' . $form_element . '").focus();');
}

function draw_footer($content=false, $arguments=false) {
	return draw_tag('footer', $arguments, $content);
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

function draw_form_checkbox($name, $checked=false, $arguments=false, $javascript=false, $label=false) {
	$arguments = array_arguments($arguments);
	$arguments['class'] = (empty($arguments['class'])) ? 'checkbox' : $arguments['class'] . ' checkbox';
	$arguments['type'] = 'checkbox';
	$arguments['name'] = $arguments['id'] = $name;
	if ($javascript) $arguments['onclick'] = 'javascript:' . $javascript;
	if ($checked) $arguments['checked'] = 'checked';
	return draw_tag('input', $arguments) . ($label ? draw_form_label($name, $label, $arguments['class']) : false);
}

function draw_form_checkboxes($name, $linking_table=false, $object_col=false, $option_col=false, $id=false) {
	if (stristr($name, '_')) error_handle('draw_form_checkboxes()', 'an error occurred with the calling of this function; you can\'t have an underscore in the field name', __file__, __line__);
	if ($linking_table) {
		$result = db_query('SELECT o.id, o.value, (SELECT COUNT(*) FROM ' . $linking_table . ' l WHERE l.' . $option_col . ' = o.id AND ' . $object_col . ' = ' . $id . ') checked  FROM option_' . $name . ' o ORDER BY value');
	} else {
		$result = db_query('SELECT id, value, 0 checked FROM option_' . $name . ' ORDER BY value');
	}
	$return = '<table cellspacing="0" class="checkboxes">';
	while ($r = db_fetch($result)) {
		$return .= '<tr><td>' . draw_form_checkbox('chk-' . $name . '-' . $r['id'], $r['checked']) . '</td>';
		$return .= '<td>&nbsp;<label for="' . 'chk-' . $name . '-' . $r['id'] . '">' . $r['value'] . '</label></td></tr>';
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
	
	$return = draw_div('date',
		draw_form_select($namePrefix . 'Month', $months, $month, $required, $class) .
		draw_form_select($namePrefix . 'Day', array_2d(array_range(1, 31)), $day, $required, $class) .
		draw_form_select($namePrefix . 'Year', array_2d(array_range($_josh['year'] - 90, $_josh['year'] + 10)), $year, $required, $class)
	);
	
	if ($withTime) {
		$return .= draw_div('time',  
			draw_form_select($namePrefix . 'Hour', array_2d(array(12, 1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11)), $hour, $required, $class) .
			draw_form_select($namePrefix . 'Minute', array_2d(array('00', 15, 30, 45)), $minute, $required, $class) .
			draw_form_select($namePrefix . 'AMPM', array_2d(array('AM', 'PM')), $ampm, $required, $class)
		);
	}
	return draw_div('date', $return);
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
	if (!empty($_josh['drawn']['focus'])) return false; //only draw focus once
	$_josh['drawn']['focus'] = $name;
	return draw_javascript('document.getElementById("' . $name . '").focus();');
}

function draw_form_hidden($name, $value='') {
	return draw_tag('input', array('type'=>'hidden', 'name'=>$name, 'id'=>$name, 'value'=>$value));
}

function draw_form_img($url, $class='submit') {
	if (empty($class)) $class = 'submit';
	return draw_tag('input', array('type'=>'image', 'src'=>$url, 'class'=>$class));
}

function draw_form_label($for, $content, $class=false) {
	return draw_tag('label', array('for'=>$for, 'class'=>$class), $content);
}

function draw_form_password($name, $value='', $class=false, $maxlength=255) {
	$class = ($class) ? $class . ' password' : 'password';
	$return = '<input type="password" name="' . $name . '" id="' . $name . '" value="' . $value . '" maxlength="' . $maxlength . '" class="' . $class . '"/>';
	return $return;
}

function draw_form_radio($name, $value='', $checked=false, $args=false, $label=false) {
	$args = array_arguments($args);
	array_argument($args, 'radio');
	array_argument($args, 'radio', 'type');
	if ($checked) array_argument($args, 'checked', 'checked');
	array_argument($args, $name . '-' . $value, 'id');
	array_argument($args, $name, 'name');
	array_argument($args, $value, 'value');
	return draw_tag('input', $args) . ($label ? draw_tag('label', array('for'=>$name . '-' . $value), $label) : '');
}

function draw_form_radio_set($name, $sql_options, $value=false) {
	$options = array();
	if (!is_array($sql_options)) $sql_options = array_key_promote(db_table($sql_options));
	foreach ($sql_options as $key=>$val) {
		$options[] = draw_form_radio($name, $key, ($key == $value), false, $val);
	}
	return draw_list($options);
}

function draw_form_select($name, $sql_options, $value=false, $not_nullable=true, $class=false, $onchange=false, $nullvalue='', $maxlength=60, $disabled=false) {
	//2010 04 04: changed $required to $not_nullable because it could still be required and have a null value at the top
	$return = ($not_nullable) ? '' : '<option value="">' . $nullvalue . '</option>';
	$lastgroup = '';
	if (!is_array($sql_options)) $sql_options = db_table($sql_options);
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

function draw_form_text($name, $value='', $args=false, $maxlength=255, $style=false) {
	$args = array_arguments($args);
	//array_argument($args, 'text');
	array_argument($args, 'text', 'type');
	array_argument($args, $name, 'id');
	array_argument($args, $name, 'name');
	array_argument($args, $value, 'value');
	array_argument($args, $maxlength, 'maxlength'); //todo deprecate?
	array_argument($args, $style, 'style'); //todo deprecate?
	return draw_tag('input', $args);
}

function draw_form_textarea($name, $value='', $args=false) {
	$args = array_arguments($args);
	array_argument($args, 'textarea');
	array_argument($args, $name, 'name');
	array_argument($args, $name, 'id');
	
	//validator requires these values
	if (empty($args['rows'])) $args['rows'] = 5;
	if (empty($args['cols'])) $args['cols'] = 50;
	
	if (!$value) $value = '';

	return draw_tag('textarea', $args, $value);
}

function draw_google_analytics($id) {
	global $_josh;
	error_debug('drawing google tracker', __file__, __line__);
	if ($_josh['mode'] != 'live') return '<!-- google analytics hidden because running in dev mode -->';
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

function draw_google_chrome_frame() {
	//alias
	return draw_chrome_frame();
}

function draw_google_map($markers=false, $center=false, $zoom=false, $control=true) {
	//haven't figured out the appropriate place to store all this stuff.  this calls a javascript function which should be local
	//markers must be an array with latitude, longitude, title, description, color
	global $_josh;
	
	if (!isset($_josh['google']['mapkey'])) error_handle(__function__ . ' requires a google maps api key', 'you can ' . draw_link('http://code.google.com/apis/maps/signup.html', 'go here') . ' to get one', __file__, __line__);
	
	//markers
	$markerstr = '';
	if ($markers) {
		$count = count($markers);
		$lat = 0;
		$lon = 0;
		foreach ($markers as $m) {
			$m['auto'] = (isset($m['auto']) && $m['auto']) ? ', true' : false;
			if (!isset($m['color'])) $m['color'] = 'red';
			$lat += $m['latitude'];
			$lon += $m['longitude'];
			$m['description'] = str_replace("\r\n", '', nl2br($m['description']));
			$markerstr .= NEWLINE . '
				var marker = map_marker(' . $m['latitude'] . ', ' . $m['longitude'] . ', "<h2 style=\"font-weight:bold;font-size:120%;margin:0;padding:5px 0 2px;font-family: Arial, Helvetica; \">' . $m['title'] . '</h2><p style=\"line-height:16px; font-size: 100%; font-family: Arial, Helvetica; margin: 0; padding: 0 0 20px; vertical-align:top; letter-spacing: .02em;\">' . $m['description'] . '</p>", "' . $m['color'] . '"' . $m['auto'] . ');
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
		//wha?  rock dove?
		$lat = 37.4419;
		$lon = -122.1419;
	}
	
	//tweak centering for maps with an automarker.  todo: tweak for different levels of zoom
	if ($markers && ($count == 1) && isset($markers[0]['auto']) && $markers[0]['auto']) {
		$lat += .07;
		$lon += .03;
	}
	
	//todo determine zoom automatically
	if (!$zoom) $zoom = 11;

	lib_get('modernizr'); //requires modernizr
	
	return draw_javascript_src() . draw_javascript_src('http://maps.google.com/maps?file=api&amp;v=2&amp;key=' . $_josh['google']['mapkey']) . '<div id="map"></div>' . 	
	draw_css('
		div#map img { max-height: inherit; max-width: inherit; }
		.ie div#map img { max-height: none; max-width: none; }
		
	') . 
	draw_javascript('
		function map_load() {
			if (GBrowserIsCompatible()) {
				var map = new GMap2(document.getElementById("map"));
				map.setCenter(new GLatLng(' . $lat . ', ' . $lon . '), ' . $zoom . ');
				' . ($control ? 'map.addControl(new GLargeMapControl());' : '') . '
				' . $markerstr . '
			}
			window.onunload = GUnload;
		}
		window.onload = map_load;
		');
}

function draw_google_search($class='search') {
	global $_josh;
	return draw_tag('form', array('method'=>'get', 'action'=>'http://www.google.com/search', 'class'=>$class),  
		draw_container('span', draw_form_text('q'), 'text') . 
		draw_form_hidden('sitesearch', $_josh['request']['host']) . 
		draw_container('span', draw_form_submit('Search'), 'submit')
	);
}

function draw_google_tracker($id) {
	error_deprecated(__FUNCTION__ . ' was deprecated on 10/11/2010 - you can use draw_google_analytics instead');
	return draw_google_analytics($id);
}

// usage is generally going to be include(draw_haml('page')); so that scope
// is preserved
// it's also possible to draw_haml('page', false) to render just a single template
function draw_haml($template, $compile_only=true, $layout='layout', $path=false) {
  global $_josh;
  lib_get('phamlp');
  file_dir_writable('haml');

  $haml = new HamlParser();  
  $compiled_path = DIRECTORY_ROOT . DIRECTORY_WRITE . '/haml/';
  if(!$path) $path = $_josh['haml_path'];
  
  // we collect last mod times and existence for files
  $layout_mtime = 0;
          $exists = file_exists($compiled_path . "/_$template.php");
  if($exists) $compiled_mtime = filemtime($compiled_path . "/_$template.php");
  if($layout) $layout_mtime = filemtime($path . "$layout.haml");
  $template_mtime = filemtime($path . "$template.haml");
  
  // don't recompile if nothing has changed
  if(!$exists || $layout_mtime > $compiled_mtime || $template_mtime > $compiled_mtime) {
    if($layout) {
      $layout = $haml->parse($path . "$layout.haml");
      $result = str_replace('<?php echo draw_yield(); ?>', $haml->parse($path . "$template.haml"), $layout);
    } else {
      $result = $haml->parse($path . "$template.haml");
    }
    file_put($compiled_path . "/_$template.php", $result);
  }
  
  if(!$compile_only) {
    ob_start();
    include($compiled_path . "_$template.php");
    return ob_get_clean();
  }
  
  return $compiled_path . "_$template.php";
}

function draw_h1($content, $arguments=false) {
	return draw_tag('h1', $arguments, $content);
}

function draw_h2($content, $arguments=false) {
	return draw_tag('h2', $arguments, $content);
}

function draw_h3($content, $arguments=false) {
	return draw_tag('h3', $arguments, $content);
}

function draw_h4($content, $arguments=false) {
	return draw_tag('h4', $arguments, $content);
}

function draw_h5($content, $arguments=false) {
	return draw_tag('h5', $arguments, $content);
}

function draw_header($content=false, $arguments=false) {
	return draw_tag('header', $arguments, $content);
}

function draw_img($path, $link=false, $alt=false, $name=false, $linknewwindow=false) {
	//alt could also be an array of arguments
	global $_josh;
	
	//$path can be relative
	if (!$realpath = realpath($path)) $realpath = realpath(DIRECTORY_ROOT . $path);
	
	$filemtime = filemtime($realpath);

	//get width and height
	$image = @getimagesize($realpath);
	if (!$image) return '';
	
	//debuggy!
	error_debug('<b>draw_img</b> realpath is ' . $realpath, __file__, __line__);
	error_debug('<b>draw_img</b> root is ' . DIRECTORY_ROOT, __file__, __line__);
	
	$src = url_base() . str_replace(DIRECTORY_ROOT, '', $realpath);
	$src = $path; //because of aplus situation
	
	//assemble tag
	$arguments = array('src'=>$src, 'width'=>$image[0], 'height'=>$image[1]);
	//$arguments = array('src'=>$src, 'width'=>$image[0], 'height'=>$image[1], 'border'=>0);
	if (is_array($alt)) {
		//values of alt can overwrite width, height, border, even src
		if (isset($alt['maxwidth']) && !isset($alt['maxheight'])) {
			//simple horiz resize if necessary
			if ($alt['maxwidth'] < $arguments['width']) {
				$arguments['height'] *= $alt['maxwidth'] / $arguments['width'];
				$arguments['width'] = $alt['maxwidth'];
			}
			unset($alt['maxwidth']);
		} elseif (!isset($alt['maxwidth']) && isset($alt['maxheight'])) {
			//todo simple vert resize if necessary
		} elseif (isset($alt['maxwidth']) && isset($alt['maxheight'])) {
			//todo more complex resize both
		}
		$arguments = array_merge($arguments, $alt);
	} else {
		$arguments['alt'] = $alt;
		$arguments['name'] = $arguments['class'] = $arguments['id'] = $name;
		$alt = false; //this was passed as a string, needs to be nulled so it doesn't go to draw_link
	}
	
	//fix slashes on windows
	$arguments['src'] = str_replace('\\', '/', $arguments['src']);

	//manage caching
	$arguments['src'] .= '?' . $filemtime;
	
	//force alt text for w3 validation
	if (empty($arguments['alt'])) {
		list($name, $ext, $path) = file_name($path);
		$arguments['alt'] = format_text_human($name);
	}
	
	$image = draw_tag('img', $arguments);
	array_argument($alt, 'image');
	if ($link) return draw_link($link, $image, $linknewwindow, $alt);
	return $image;
}

function draw_img_random($folder, $link=false, $class=false) {
	//tweak for godaddy, DIRECTORY_ROOT is sometimes inconsistent
	$file = array_random(file_folder($folder, 'jpg,jpeg,gif,png'));
	return draw_img($folder . $file['name']);
}

function draw_img_thumbnail($path, $link, $max) {
	//$max is maximum width or height
	if($max == '') $max = 80;
	
	//$path can be relative
	if (!$realpath = realpath($path)) $realpath = realpath(DIRECTORY_ROOT . $path);
	
	//get width and height
	if (!@list($width, $height, $type, $attr) = @getimagesize($realpath)) return '';
		
	if ($width >= $height) {
		$args = array('width'=>$max, 'height'=>$height*($max/$width));
	} else {
		$args = array('width'=>$width*($max/$height), 'height'=>$max);
	}
		
	return draw_img($path, $link, $args);
}

function draw_javascript($javascript=false) {
	if (!$javascript) return false; //draw_javascript_src();
	return draw_tag('script', array('type'=>'text/javascript'), $javascript);
	//return draw_tag('script', array('language'=>'javascript', 'type'=>'text/javascript'), $javascript);
}

function draw_javascript_ready($javascript=false) {
	return draw_javascript('$(function(){' . $javascript . '});');
}

/* function draw_javascript_lib() {
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
} */

function draw_javascript_folder_src() {
	//conditionally include a javascript file based on the current folder
	$filename = '/js/' . url_folder() . '.js';
	if (file_check($filename)) return draw_javascript_src($filename);
}

function draw_javascript_src($filename=false) {
	global $_josh;
	$return = '';
	if (!$filename) {
		//default is to draw joshlib's own javascript library
		if (isset($_josh['drawn']['javascript'])) return false; //only draw this file once per page
		$_josh['drawn']['javascript'] = true;
		$return = lib_get('jquery'); //javascript.js requires jquery now
		$filename = DIRECTORY_WRITE . '/javascript.js';
		$joshlibf = DIRECTORY_JOSHLIB . 'javascript.js';
		if (!file_check($filename) || (filemtime($joshlibf) > filemtime(DIRECTORY_ROOT . $filename))) {
			//either doesn't exist or is out-of-date
			if (!file_put($filename, file_get($joshlibf))) return error_handle('JS Write Error', __FUNCTION__ . ' can\'t write the js file.', __file__, __line__);
		}
	}
	return $return . draw_tag('script', array('src'=>$filename, 'type'=>'text/javascript'), '');
	//return $return . draw_tag('script', array('language'=>'javascript', 'src'=>$filename, 'type'=>'text/javascript'), '');
}

function draw_li($content='', $arguments=false) {
	return draw_tag('li', $arguments, $content);
}

function draw_link($href=false, $str=false, $newwindow=false, $arguments=false, $maxlen=60) {
	$arguments = array_arguments($arguments);
	if (format_text_starts('mailto:', $href)) {
		//obfuscate email
		if (!$str) $str = format_ascii(substr(str_replace('mailto:', '', $href), 0, $maxlen));
		$arguments['href'] = format_ascii($href);
	} elseif (format_text_starts('javascript:', $href)) {
		//correct link for javascript
		$arguments['href'] = '#';
		$arguments['onclick'] = $href;
	} elseif ($href) {
		/* if (!$str) {
			$str = $href;
			if (strlen($str) > $maxlen) $str = substr($str, 0, $maxlen) . '&hellip;';
		} */
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

function draw_list($options, $arguments=false, $parent='ul', $selected=false, $classes=false, $child='li', $separator=false) {
	//make a ul or an ol out of a one-dimensional array
	if (!is_array($options) || (!$count = count($options))) return false;
	if ($separator !== false) $separator = draw_tag($child, 'separator divider-vertical', $separator);
	$arguments = array_arguments($arguments);
	$counter = 1;
	for ($i = 0; $i < $count; $i++) {
		$li_args = array('class'=>'option' . $counter);
		if (isset($classes[$i])) {
			if (is_array($classes[$i])) {
				foreach ($classes[$i] as $key=>$value) array_argument($li_args, $value, $key);
			} else {
				array_argument($li_args, $classes[$i]);
			}
			//array_argument($li_args, $classes[$i], 'id'); //also setting id now for sortable list in cms
		}
		if ($counter == 1)			array_argument($li_args, 'first');
		if ($counter % 2)			{ array_argument($li_args, 'odd'); } else { array_argument($li_args, 'even'); }
		if ($counter == $count)		array_argument($li_args, 'last');
		if ($selected == ($i + 1))	array_argument($li_args, 'selected active');
		if (empty($options[$i]))	array_argument($li_args, 'empty');
		if ($options[$i] == false) {
			$options[$i] = ''; //don't pass a false value, li must be a container
		} elseif (is_array($options[$i])) {
			$options[$i] = draw_list($options[$i]);
		}
		
		
		$options[$i] = draw_tag($child, $li_args, $options[$i]);
		$counter++;
	}
	return draw_tag($parent, $arguments, implode($options, $separator));
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
	$sets = array_sets($options, $length);
	foreach ($sets as &$s) $s = draw_list($s);
	return draw_list($sets, $arguments, $type, $selected);
}

function draw_meta_description($string='') {
	if (!$string) return false;
	//this strips tags and sets length at 150, which is the max
	$string = html_entity_decode(format_string($string, 150, ''), ENT_QUOTES, 'UTF-8');
	if (!strlen($string)) return false;
	return draw_tag('meta', array('name'=>'description', 'content'=>$string));
}

function draw_meta_keywords($string='') {
	if (!$string) return false;
	return draw_tag('meta', array('name'=>'keywords', 'content'=>$string));
}

function draw_meta_utf8() {
	if (html() == 5) {
		return draw_tag('meta', array('charset'=>'utf-8'));
	} else {
		return draw_tag('meta', array('http-equiv'=>'Content-Type', 'content'=>'text/html; charset=utf-8'));
	}
	
}

function draw_nav($options, $type='text', $class='nav', $match='path', $sets=false, $add_home=false, $use_nav_tag=true, $separator=false) {	
	global $_josh;

	//type can be text only now be text.  images and rollover modes are now deprecated
	if ($type != 'text') error_deprecated(__FUNCTION__ . ' can only accept "text" as a $type parameter as of 3/23/2012');
	
	//match can be path, path_query, folder, or array
	//you can pass a SQL string instead of options
	if (is_string($options)) $options = array_key_promote(db_table($options)); 
	
	if ($add_home) $options = array_merge(array('/'=>'Home'), $options);
	
	if (!count($options)) return false; //skip if empty
	$return = $classes = array();
		
	//this is so you can have several sets of rollovers in the same page eg the smarter toddler site
	/* deprecated
	if (!isset($_josh['drawn']['navigation'])) $_josh['drawn']['navigation'] = 0;
	$_josh['drawn']['navigation']++;
	*/
	
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
		$classes[] = format_class($url);
		
		//$name = 'option_' . $_josh['drawn']['navigation'] . '_' . $counter; //deprecated
		$args = array(/* 'name'=>$name,  'class'=>$name*/);
		
		if ($match == 'folder') {
			//so eg /about/page1/ and /about/page2/ will match
			$urlparts = explode('/', $url);
			$matching = (@$urlparts[1] == $_josh['request']['folder']);
			if (substr($url, 0, 5) == 'http:') $matching = false;
		} elseif (is_array($match)) {
			//new mode.  you can match values against the query string
			$querystring = url_query_parse($url);
			//echo draw_array($querystring);
			$matching = true;
			foreach ($match as $key=>$value) {
				if (!isset($querystring[$key]) || ($querystring[$key] != $value)) $matching = false;
			}
		} else {
			$matching = (str_replace(url_base(), '', $url) == $match);
		}
		
		if ($matching) {
			/* $img_state = '_on'; */
			$args['class'] = ' selected active';
			$selected = $counter;
		} else {
/*
			$img_state = '_off';
			if ($type == 'rollovers') {
				$args['onmouseover'] = 'javascript:img_roll(\'' . $name . '\',\'on\');';
				$args['onmouseout'] = 'javascript:img_roll(\'' . $name . '\',\'off\');';
			}
*/
		}
		
/*
		if (($type == 'images') || ($type == 'rollovers')) {
			$img = '/images/' . $class . '/' . format_text_code($title);
			if ($type == 'rollovers') $javascript .= $name . '_on		 = new Image;' . NEWLINE . $name . '_off	 = new Image;' . NEWLINE . $name . '_on.src	 = "' . $img . '_on.png";' . NEWLINE . $name . '_off.src = "' . $img . '_off.png";' . NEWLINE;
			$inner = draw_img($img . (($type == 'rollovers') ? $img_state : false) . '.png', false, $title, $name);
		} else { //type == text
*/
			$inner = $title;		
//		}
		$return[] = draw_link($url, $inner, false, $args);
		$counter++;
	}
	
	$return = ($sets) ? draw_list_sets($return, $sets, $class, 'ul', $selected) : draw_list($return, $class, 'ul', $selected, $classes, 'li', $separator);
	
	if ($type == 'rollovers') $return = draw_javascript_src() . draw_javascript('if (document.images) {' . $javascript . '}') . $return;
	if ($use_nav_tag) $return = draw_tag('nav', array('class'=>$class), $return);
	return $return;
}

function draw_nav_nested($pages, $class='nav', $current_depth=1, $match='path') {
	global $request;
	$selected = false;
	$li_items = $classes = array();
	foreach ($pages as $p) {
		//draw li for each page
		$li_class = '';
		
		//get selected
		if ($request[$match] == $p['url']) {
			$li_class = 'selected';
			$selected = true;
		}
		
		$return = draw_link($p['url'], $p['title']);
		
		if (count($p['children'])) {
			list($str, $descendant_selected) = draw_nav_nested($p['children'], false, $current_depth + 1, $match);
			$return .= $str;
			if ($descendant_selected) {
				$li_class = 'descendant-selected';
				$selected = true;
			}
		}
		
		$classes[] = $li_class;
		$li_items[] = $return;
	}
		
	$return = draw_list($li_items, $class, 'ul', false, $classes);
	if ($current_depth == 1) return $return;
	return array($return, $selected); //have to pass the fact that there was a selected item up the chain
}

function draw_p($inner, $arguments=false) {
	return draw_tag('p', $arguments, $inner);
}

function draw_page($title, $html, $tab=false) {
	//this is for joshserver and error handling, eventually for setup your site messages
	if ($tab) {
		$tab = '<div style="width:400px;overflow:auto;"><div style="background-color:#59c;color:#fff;height:36px;line-height:36px;font-size:24px;padding:0px 20px 0px 20px;float:left;">' . $tab . '</div></div>';
	}
	return '
		<html>
			<head>' . draw_meta_utf8() . draw_title($title) . '</head>
			<body style="margin:0;">
				<table width="100%" height="100%" cellpadding="30" cellspacing="0" border="0" style="background-color:#ddd; font-family:verdana, arial, sans-serif; font-size:13px; line-height:20px; color:#444;">
					<tr>
						<td align="center">
							' . $tab . '
							<div style="background-color:#fff;text-align:left;padding:10px 20px 10px 20px;width:360px;min-height:230px;">
								<h1 style="color:#444; font-weight:normal; font-size:24px; line-height:30px;">' . $title . '</h1>' . 
								$html . '
							</div>
						</td>
					</tr>
				</table>
			</body>
		</html>';
}

function draw_rss_link($address) {
	return draw_tag('link', array('rel'=>'alternate', 'type'=>'application/rss+xml', 'title'=>'RSS', 'href'=>$address));
}

function draw_section($content='', $arguments=false) {
	return draw_tag('section', $arguments, $content);
}

function draw_small($inner, $arguments=false) {
	return draw_tag('small', $arguments, $inner);
}

function draw_span($class, $inner='') {
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

function draw_strong($inner, $arguments=false) {
	return draw_tag('strong', $arguments, $inner);
}

function draw_swf($path, $width, $height, $alternate='') {
	//standards-compliant satay method (http://www.alistapart.com/articles/flashsatay)
	return '<object type="application/x-shockwave-flash" data="' . $path . '" width="' . $width . '" height="' . $height . '"><param name="movie" value="' . $path . '" /><param name="wmode" value="opaque">' . $alternate . '</object>';
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
	foreach ($columns as $c) if ($c != 'group') $t->set_column($c);
	$return = $t->draw($array);
	
	//if css is set, prepend css
	if ($css) $return = draw_css('
		table				{ font-family:Verdana; font-size:12px; color:#444; border:0; }
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
	$tag = trim(strToLower($tag));
	$return = '<' . $tag;
	$return .= (is_array($arguments)) ? draw_arguments($arguments) : draw_argument('class', $arguments);
	
	$containers = array('div', 'span', 'b', 'strong', 'em', 'i', 'header', 'footer', 'h1', 'h2', 'h3', 'h4', 'h5', 'title');
	if (($innerhtml === false) && !in_array($tag, $containers)) {
		$return .= '/>';
	} else {
		if (($tag == 'td') && empty($innerhtml)) $innerhtml = '&nbsp;';
		$return .= '>' . $innerhtml . '</' . $tag . '>';
	}
	return $return;
}

function draw_time($timestamp, $format=false, $is_pubdate=false) {
	if (!$format) $format = '%b %d, %Y';
	return '<time datetime="' . format_date_iso8601($timestamp) . '"' . (($is_pubdate) ? ' pubdate' : '') . '>' . format_date($timestamp, '', $format) . '</time>';
}

function draw_title($title=false) {
	global $_josh;
	if ((empty($title) || strToLower($title) == 'home') && !empty($_josh['app_name'])) $title = $_josh['app_name'];
	return draw_tag('title', false, strip_tags($title));
}

function draw_typekit($key='yxt2eld') {
	return draw_javascript_src('http://use.typekit.com/' . $key . '.js') . draw_javascript('try{Typekit.load();}catch(e){}');
}

function draw_video($url, $width=false, $height=false) {
	//takes a url and gives you back a video

	if (!$width && !$height) {
		$width = 560;
		$height = 315;
	} elseif (!$width) {
		$width = ceil((560 / 315) * $height);
	} elseif (!$height) {
		$height = ceil((315 / 560) * $width);
	}

	$url = url_parse($url);
	if ($url['host'] == 'youtu.be') {
		return '<iframe width="' . $width . '" height="' . $height . '" src="http://www.youtube.com/embed' . $url['path'] . '" frameborder="0" webkitAllowFullScreen mozallowfullscreen allowfullscreen></iframe>';
	} elseif ($url['host'] == 'vimeo.com') {
		return '<iframe width="' . $width . '" height="' . $height . '" src="http://player.vimeo.com/video' . $url['path'] . '" frameborder="0" webkitAllowFullScreen mozallowfullscreen allowfullscreen></iframe>';
	}
	
}