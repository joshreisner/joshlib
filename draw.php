<?php
error_debug("~ including draw.php");

function draw_array($array, $nice=false) {
	global $_josh;
	if (!is_array($array)) return false;
	$return = '<table width="100%" cellpadding="3" cellspacing="1" border="0" bgcolor="' . $_josh["colors"]["grey2"] . '">';
	if (!$nice) ksort($array);
	while(list($key, $value) = each($array)) {
		if ($nice && (strToLower($key) == "j")) continue;
		if (is_int($key)) continue;
		$value = format_quotes($value);
		if (strToLower($key) == "email") $value = "<a href='mailto:" . $value . "'>" . $value . "</a>";
		if (is_array($value)) {
			$return2 = "";
			foreach ($value as $key2 => $value2) {
				$return2 .= "&#183; " . $value2 . "<br>";
			}
			$value = $return2;
		}
		$return  .= '
			<tr bgcolor="' . $_josh["colors"]["white"] . '" style="font-family: verdana; font-size:11px; padding:6px; line-height:16px; width:100%;" valign="top"';
		if (strToLower($key) == "message") $return .= ' height="160"';
		$return .= '><td bgcolor="' . $_josh["colors"]["grey1"] . '" width="21%"><nobr>';
		$return .= ($nice) ? format_text_human($key)  : $key;
		$return .= '&nbsp;</nobr></td><td width="79%">' . nl2br($value) . '</td></tr>';
	}
	$return .= '</table>';
	return $return;
}

function draw_autorefresh($minutes) {
	global $_josh;
	return '<meta http-equiv="refresh" content="' . $minutes * 60 . '">' . $_josh["newline"];
}

function draw_css($keepalive=false) {
	global $_josh;
	error_debug("drawing css");
	if (!isset($_josh["drawn"]["css"]) || !$_josh["drawn"]["css"]) {
		if (!$keepalive) $_josh["drawn"]["css"] = true;
		return ('<style type="text/css">
			.josh_body			{ margin:0px; background-color:' . $_josh["colors"]["grey3"] . '; font-family:verdana, arial, sans-serif; }
			.josh_title			{ font-family:verdana, arial, sans-serif; font-size:20px; font-weight:bold; }
			.josh_heading		{ font-family:verdana, arial, sans-serif; font-size:14px; }
			.josh_heading_large	{ font-family:verdana, arial, sans-serif; font-size:17px; }
			.josh_manage_site	{ font-family:verdana, arial, sans-serif; font-size:20px; font-weight:bold; color:' . $_josh["colors"]["red2"] . '; background-color:' . $_josh["colors"]["yellow"] . '; text-decoration:none; }
			.josh_message		{ font-family:verdana, arial, sans-serif; font-size:12px; line-height:18px; }
			.josh_smaller		{ font-family:verdana, arial, sans-serif; font-size:10px; }
			.josh_small			{ font-family:verdana, arial, sans-serif; font-size:9px; }
			.josh_field			{ background-color:' . $_josh["colors"]["grey2"] . '; font-family:verdana, arial, sans-serif; font-size:11px; border-width:1px; padding:2px;  }
			.josh_textarea		{ background-color:' . $_josh["colors"]["grey2"] . '; font-family:verdana, arial, sans-serif; font-size:11px; border-width:1px; padding:2px; width:200px; height:100px; }
			.josh_button		{ font-family:verdana, arial, sans-serif; background-color:' . $_josh["colors"]["grey4"] . '; font-size:10px; width:130px; border-width:1px; color: ' . $_josh["colors"]["white"] . ';}
			.josh_button_prompt	{ font-family:verdana, arial, sans-serif; background-color:' . $_josh["colors"]["grey2"] . '; font-size:10px; width:130px; border-width:1px; color: ' . $_josh["colors"]["grey5"] . ';}
			.josh_code          { font-family:droid sans mono, monaco, courier new, courier; font-size:12px; line-height:18px; }
			.josh_drawer_open	{ background-color:' . $_josh["colors"]["grey5"] . '; margin:0px; margin-top:13px; }
			.josh_drawer_closed { background-color:' . $_josh["colors"]["grey5"] . '; margin:0px; margin-top:13px; }
			a.josh_dark:link	{ color:' . $_josh["colors"]["red2"] . '; }
			a.josh_dark:active	{ color:' . $_josh["colors"]["red2"] . '; }
			a.josh_dark:visited	{ color:' . $_josh["colors"]["red2"] . '; }
			a.josh_dark:hover	{ color:' . $_josh["colors"]["red1"] . '; }
			a.josh_lite:link	{ color:' . $_josh["colors"]["white"] . '; text-decoration:none; }
			a.josh_lite:active	{ color:' . $_josh["colors"]["white"] . '; text-decoration:none; }
			a.josh_lite:visited	{ color:' . $_josh["colors"]["white"] . '; text-decoration:none; }
			a.josh_lite:hover	{ color:' . $_josh["colors"]["white"] . '; text-decoration:underline; }
			span.clickable:hover	{ background-color:#f3f3f3; cursor:hand; cursor:pointer; }
		</style>');
	}
}

function draw_focus($form_element) {
	return draw_javascript('document.getElementById("' . $form_element . '").focus();');
}

function draw_form_button($text, $location=false, $class=false, $disabled=false, $javascript=false) {
	global $_josh;
	if (!$class) $class = $_josh["styles"]["button"];
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
	if (!$class) $class = (isset($_josh["styles"]["checkbox"])) ? $_josh["styles"]["checkbox"] : "checkbox";
	$return  = '<input type="checkbox" name="' . $name . '" id="' . $name . '" class="' . $class . '"';
	if ($javascript) $return .= ' onclick="javascript:' . $javascript . '"';
	if ($checked) $return .= ' checked';
	$return .= '>';
	return $return;
}

function draw_form_checkboxes($name, $linking_table=false, $object_col=false, $option_col=false, $id=false) {
	if (stristr($name, "_")) error_handle("draw_form_checkboxes()", "an error occurred with the calling of this function; you can't have an underscore in the field name", true);
	if ($linking_table) {
		$result = db_query("SELECT o.id, o.value, (SELECT COUNT(*) FROM " . $linking_table . " l WHERE l." . $option_col . " = o.id AND " . $object_col . " = " . $id . ") checked  FROM option_" . $name . " o ORDER BY value");
	} else {
		$result = db_query("SELECT id, value, 0 checked FROM option_" . $name . " ORDER BY value");
	}
	$return = '<table cellspacing="0" class="checkboxes">';
	while ($r = db_fetch($result)) {
		$return .= '<tr><td>' . draw_form_checkbox("chk_" . $name . "_" . $r["id"], $r["checked"]) . '</td>';
		$return .= '<td>&nbsp;' . $r["value"] . '</td></tr>';
	}
	$return .= '</table>';
	return $return;
}

function draw_form_date($namePrefix, $timestamp=false, $withTime=false, $class=false, $required=true) {
	global $_josh;
	if (!$class) $class = $_josh["styles"]["select"];

	//get time into proper format
	$nulled = (!$required && !$timestamp);
	if (!$timestamp) $timestamp = time();
	if (!is_int($timestamp)) $timestamp = strToTime($timestamp);
	$month  = date("n", $timestamp);
	$day    = date("j", $timestamp);
	$year   = date("Y", $timestamp);
	$hour   = date("g", $timestamp);
	$minute = date("i", $timestamp);
	$ampm   = date("A", $timestamp);

	//assemble date fields
	$return  = '<nobr><select name="' . $namePrefix . 'Month" class="' . $class . '">';
	if (!$required) {
		$return .= "<option";
		if ($nulled) $return .= " selected";
		$return .= "></option>";
	}
	for ($i = 1; $i < 13; $i++) {
		$return .= '<option value="' . $i . '"';
		if (!$nulled && ($i == $month)) $return .= ' selected';
		$return .= '>' . $_josh["months"][$i-1] . '</option>';
	}
	$return .= '</select>';
	$return .= '&nbsp;<select name="' . $namePrefix . 'Day" class="' . $class . '">';
	if (!$required) {
		$return .= "<option";
		if ($nulled) $return .= " selected";
		$return .= "></option>";
	}
	for ($i = 1; $i < 32; $i++) {
		$return .= '<option value="' . $i . '"';
		if (!$nulled && ($i == $day)) $return .= ' selected';
		$return .= '>' . $i . '</option>';
	}
	$return .= '</select>';
	$return .= '&nbsp;<select name="' . $namePrefix . 'Year" class="' . $class . '">';
	if (!$required) {
		$return .= "<option";
		if ($nulled) $return .= " selected";
		$return .= "></option>";
	}
	for ($i = 1910; $i < 2030; $i++) {
		$return .= '<option value="' . $i . '"';
		if (!$nulled && ($i == $year)) $return .= ' selected';
		$return .='>' . $i . '</option>';
	}
	$return .= '</select></nobr>';
	
	if ($withTime) {
		//todo -- make time nullable (!$required)
		$return .= '&nbsp;&nbsp;<select name="' . $namePrefix . 'Hour" class="' . $class . '">';
		$return .= '<option value="12"';
		if ($hour == 12) $return .= ' selected';
		$return .= '>12</option>';
		for ($i = 1; $i < 12; $i++) {
			$return .= '<option value="' . $i . '"';
			if ($hour == $i) $return .= ' selected';
			$return .= '>' . $i . '</option>';
		}
		$return .= '</select>&nbsp;<select name="' . $namePrefix . 'Minute" class="' . $class . '">';
			$return .= '<option value="00"';
			if ($minute == 0) $return .= ' selected';
			$return .='>00</option>';
			for ($i = 1; $i < 60; $i++) {
				$return .= '<option value="' . $i . '"';
				if ($minute == $i) $return .= ' selected';
				$return .= '>' . sprintf("%02d", $i) . '</option>';
			}
			/*$return .= '<option value="15"';
			if ($minute == 15) $return .= ' selected';
			$return .='>15</option>';
			$return .= '<option value="30"';
			if ($minute == 30) $return .= ' selected';
			$return .='>30</option>';
			$return .= '<option value="45"';
			if ($minute == 45) $return .= ' selected';
			$return .='>45</option>';*/
		$return .= '</select>&nbsp;<select name="' . $namePrefix . 'AMPM" class="' . $class . '">';
			$return .= '<option value="AM"';
			if ($ampm == "AM") $return .= ' selected';
			$return .='>AM</option><option value="PM"';
			if ($ampm == "PM") $return .= ' selected';
			$return .='>PM</option></select>';
	}
	
	//return string
	return $return;
}

function draw_form_file($name, $class="file", $onchange=false) {
	$return  = '<input type="file" name="' . $name . '" class="' . $class . '"';
	if ($onchange) $return .= ' onchange="javascript:' . $onchange . '"';
	$return .= '>';
	return $return;
}

function draw_form_focus($name) {
	return '<script lanugage="javascript">
		<!--
			document.getElementById("' . $name . '").focus();
		//-->
		</script>';
}

function draw_form_hidden($name, $value="") {
	return '<input type="hidden" name="' . $name . '" value="' . $value . '">';
}

function draw_form_password($name, $value="", $class=false, $maxlength=255, $autocomplete=true) {
	global $_josh;
	if (!$class) $class = $_josh["styles"]["field"];
	$return = '<input type="password" name="' . $name . '" id="' . $name . '" value="' . $value . '" class="' . $class . '" maxlength="' . $maxlength . '" class="' . $class . '"';
	if (!$autocomplete) $return .= ' autocomplete="off"';
	$return .= '>';
	return $return;
}

function draw_form_radio($name, $value="", $checked=false, $class=false) {
	global $_josh;
	$return  = '<input type="radio" name="' . $name . '" value="' . $value . '"';
	if ($class) $return .= ' class="' . $class . '"';
	if ($checked) $return .= ' checked';
	$return .= '>';
	return $return;
}

function draw_form_select($name, $sql_options, $value=false, $required=true, $class=false, $action=false, $nullvalue="", $maxlength=false) {
	global $_josh;
	if (!$class) $class = $_josh["styles"]["select"];
	$return  = '<select name="' . $name . '" id="' . $name . '" class="' . $class . '"';
	if ($action) $return .= ' onchange="javascript:' . $action . '"';
	$return .= '>';
	if (!$required) $return .= "<option value=''>" . $nullvalue . "</option>";
	if (is_array($sql_options)) {
		while (list($key, $val) = each($sql_options)) {
			$val = format_text_shorten($val);
			$return .= "<option value='" . $key . "' id='" . $name . $key . "'";
			if ($key == $value) $return .= " selected";
			$return .= ">" . $val . "</option>";
		}
	} else {
		$result = db_query($sql_options);
		$key = false;
		while ($r = db_fetch($result)) {
			if (!$key) $key = array_keys($r);
			$r[$key[1]] = format_text_shorten($r[$key[1]]);
			$return .= '<option value="' . $r[$key[0]] . '" id="' . $name . $r[$key[0]] . '"';
			if ($r[$key[0]] == $value) $return .= ' selected';
			$return .= '>' . $r[$key[1]] . '</option>';
		}
	}
	$return .= '</select>';
	return $return;
}

function draw_form_select_month($name, $start, $default=false, $length=false, $class=false, $js=false, $nullable=false) {
	//select of months going back to $start mm/yyyy format
	global $_josh;
	if (!$class) $class = $_josh["styles"]["field"];
	list($startMonth, $startYear) = explode("/", $start);
	$array = array();
	$break = false;
	while ($break == false) {
		$array[$startMonth . "/" . $startYear] = $_josh["months"][$startMonth - 1] . " " . $startYear;
		if (($startMonth == $_josh["month"]) && ($startYear == $_josh["year"])) {
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

function draw_form_submit($message="Submit Form", $class=false) {
	global $_josh;
	if (!$class) $class = $_josh["styles"]["button"];
	return '<input type="submit" value="   ' . $message . '   " class="' . $class . '">';
}

function draw_form_text($name, $value="", $class=false, $maxlength=255, $style=false, $autocomplete=true) {
	global $_josh;
	if (!$class) $class = $_josh["styles"]["field"];
	$return  = '<input type="text" name="' . $name . '" id="' . $name . '" value="' . $value . '" class="' . $class . '" maxlength="' . $maxlength . '"';
	if ($style) $return .= ' style="' . $style . '"';
	if (!$autocomplete) $return .= ' autocomplete="off"';
	$return .= '>';
	return $return;
}

function draw_form_textarea($name, $value="", $class=false) {
	error_debug("drawing textarea");
	global $_josh;
	if (!$class) $class = $_josh["styles"]["textarea"];
	return '<textarea name="' . $name . '" id="' . $name . '" class="' . $class . '">' . $value . '</textarea>';
}

function draw_google_chart($data, $type="line", $colors=false, $width=250, $height=100) {
	//example http://chart.apis.google.com/chart?cht=p3&chd=t:60,40&chs=250x100&chl=Hello|World
	//example http://chart.apis.google.com/chart?cht=p3&chd=t:60,40&chs=200x150&chl=Hello|World
	$types = array(
		"line"=>"ls",
		"bar"=>"bhs"
	);
	$parameters["cht"]	= $types[$type];
	$parameters["chd"]	= "t:" . implode(",", $data);
	$parameters["chs"]	= $width . "x" . $height;
	//$parameters["chl"]	= implode("|", array_keys($data));
	$parameters["chco"] = $colors;
	$parameters["chm"]	= "B,efefef,0,0,0";
	$parameters["chls"]	= "3";
	//$parameters["chxt"]	= "x,y";
	//$parameters["chxr"]	= "0,0,30|1,0,4";
	$parameters["chds"] = "-1,11";
	$parameters["chma"] = "0,0,0,0";
	$pairs = array();
	foreach ($parameters as $key=>$value) $pairs[] = $key . "=" . $value;

	return '<img src="http://chart.apis.google.com/chart?' . implode("&", $pairs) . '" width="' . $width . '" height="' . $height . '" border="0" class="chart">';
	
}

function draw_google_map($markers, $center=false) {
	//haven't figured out the appropriate place to store all this stuff.  this calls a javascript function which should be local
	//markers must be an array with latitude, longitude, title, description, color
	global $_josh;
	$markerstr = "";
	$return = '
	function map_load() {
		if (GBrowserIsCompatible()) {
			var map = new GMap2(document.getElementById("map"));
			';
	$count = count($markers);
	$lat = 0;
	$lon = 0;
	foreach ($markers as $m) {
		$lat += $m["latitude"];
		$lon += $m["longitude"];
		$markerstr .= $_josh["newline"] . '
			var marker = draw_marker(' . $m["latitude"] . ', ' . $m["longitude"] . ', "' . $m["title"] . '", "' . $m["description"] . '", "' . $m["color"] . '");
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
	
	return draw_javascript_src("http://maps.google.com/maps?file=api&amp;v=2&amp;key=" . $_josh["google"]["mapkey"]) . draw_javascript($return) . '<div id="map"></div>';
}

function draw_google_tracker($id) {
	error_debug("drawing google tracker");
	return '
	<script type="text/javascript">
	var gaJsHost = (("https:" == document.location.protocol) ? "https://ssl." : "http://www.");
	document.write(unescape("%3Cscript src=\'" + gaJsHost + "google-analytics.com/ga.js\' type=\'text/javascript\'%3E%3C/script%3E"));
	</script>
	<script type="text/javascript">
	try {
	var pageTracker = _gat._getTracker("' . $id . '");
	pageTracker._trackPageview();
	} catch(err) {}</script>';
}

function draw_img($path, $link=false, $alt=false, $name=false) {
	global $_josh;
	$image = @getimagesize($path);
	if (!$image) $image = @getimagesize($_josh["root"] . $path);
	if (!$image) return false;
	
	$width	= $image[0];
	$height	= $image[1];
	
	$return = '<img src="' . url_base() . $path . '" width="' . $width . '" height="' . $height . '" border="0"';
	if ($alt)	$return .= ' alt="' . $alt . '"';
	if ($name)	$return .= ' name="' . $name . '" id="' . $name . '"';
	$return .= '/>';
	if ($link)	$return = '<a href="' . $link . '">' . $return . '</a>';
	return $return;
}

function draw_javascript($javascript=false) {
	if (!$javascript) return draw_javascript_src();
	return 	'
	<script language="javascript">
		<!--
		' . $javascript . '
		//-->
	</script>
	';
}

function draw_javascript_src($src=false) {
	global $_josh;
	if (!$src && isset($_josh["javascript"])) {
		$src = $_josh["javascript"];
		if (!file_exists($_josh["root"] . $_josh["javascript"]) || (filemtime($_josh["joshlib"] . "javascript.js") > filemtime($_josh["root"] . $_josh["javascript"]))) {
			if ($content = file_get($_josh["joshlib"] . "javascript.js")) {
				file_put($_josh["javascript"], $content);
			} else {
				error_handle("couldn't get javascript", "system needs that file");
			}
		}
	} elseif (!$src && !isset($_josh["javascript"])) {
		return false;
	}
	//$src = "http://joshlib.joshreisner.com/javascript.js";
	return $_josh["newline"] . '<script language="javascript" src="' . $src . '" type="text/javascript"></script>';
}

function draw_navigation($options, $match=false, $type="text", $class="navigation") {
	//type could be text, images or rollovers
	global $_josh;
	//debug();
	$return = $_josh["newline"] . $_josh["newline"] . "<!--start nav-->" . $_josh["newline"] . "<ul class='" . $class . "'>";
	if ($match === false) {
		$match = $_josh["request"]["path"];
	} elseif ($match === true) {
		$match = $_josh["request"]["path_query"];
	} elseif ($match == "//") {
		//to take care of a common / . folder . / scenario
		$match = "/";
	}
	error_debug("<b>draw_navigation</b> match is " . $match);
	$counter = 1;
	$javascript = $_josh["newline"];
	foreach ($options as $title=>$url) {
		$name = 'option' . $counter;
		$return .= $_josh["newline"] . '<li><a href="' . $url . '" class="' . $name;
		if (str_replace(url_base(), "", $url) == $match) {
			$img_state = "_on";
			$return .= ' selected';
		} else {
			$img_state = "_off";
			if ($type == "rollovers") {
				$return .= '" onmouseover="javascript:roll(\'' . $name . '\',\'on\');" onmouseout="javascript:roll(\'' . $name . '\',\'off\');';
			}
		}
		$return .= '">';
		if ($type == "text") {
			$return .= $title;
		} elseif (($type == "images") || ($type == "rollovers")) {
			$img = str_replace("/", "", $url);
			if (empty($img)) $img = "home";
			$img = "/images/navigation/" . $img;
			if ($type == "rollovers") {
				$javascript .= $name . "_on		 = new Image;" . $_josh["newline"];
				$javascript .= $name . "_off	 = new Image;" . $_josh["newline"];
				$javascript .= $name . "_on.src	 = '" . $img . "_on.png';" . $_josh["newline"];
				$javascript .= $name . "_off.src = '" . $img . "_off.png';" . $_josh["newline"];
			}
			$img .= $img_state . ".png";
			$return .= draw_img($img, false, false, $name);
		}
		$return .= '</a></li>';
		$counter++;
	}
	$return .= $_josh["newline"] . "</ul>";
	if ($type == "rollovers") $return = draw_javascript('if (document.images) {' . $javascript . '}
		function roll(what, how) { eval("document." + what + ".src = " + what + "_" + how + ".src;"); }') . $return;
	return $return;
}

function draw_page($title, $html, $severe=false, $keepalive=false) {
	global $_josh;
	error_debug("drawing page");
	$_josh["drawn"]["css"] = false;
	if ($severe) $title = "<font color='" . $_josh["colors"]["red2"] . "'>" . $title . "</font>";
	$return = "<html>
		<head>
			<title>" . strip_tags($title) . "</title>
			" . draw_css($keepalive) . "
			<script language='javascript'>
				<!--
				function josh_confirm(action, message, id) {
					var url = '/j/' + action + '/';
					if (id) url += '/' + id + '/';
					if (confirm('Are you sure you want to ' + message + '?')) location.href = url;
				}
				//-->
			</script>
		</head>
		<body class='josh_body' bgcolor='" . $_josh["colors"]["grey3"] . "'>
			<table width='100%' height='100%' cellpadding='0' cellspacing='0' border='0'>
				<tr height='90%'>
					<td align='center' height='350'>
						<table width='400' height='250' cellpadding='20' cellspacing='0' border='0' bgcolor='" . $_josh["colors"]["white"] . "'>
							<tr>
								<td valign='top'>
								<div class='josh_title'>" . $title . "</div>
								<br>
								<div class='josh_message'>" . $html . "</div>
								</td>
							</tr>
						</table>
					</td>
				</tr>
			</table>
		</body>
	</html>";
	if ($keepalive) return $return;
	echo $return;
	db_close();
}

function draw_rss_link($address) {
	return '<link rel="alternate" type="application/rss+xml" title="RSS" href="' . $address . '">';
}

function draw_swf($path, $width, $height, $border=0) {
	$return = '<object classid="clsid:D27CDB6E-AE6D-11cf-96B8-444553540000" codebase="http://download.macromedia.com/pub/shockwave/cabs/flash/swflash.cab#version=9,0,28,0" width="' . $width . '" height="' . $height . '">
		<param name="movie" value="' . $path . '" />
		<param name="quality" value="high" />
		<embed src="' . $path . '" quality="high" pluginspage="http://www.adobe.com/shockwave/download/download.cgi?P1_Prod_Version=ShockwaveFlash" type="application/x-shockwave-flash" width="' . $width . '" height="' . $height . '" border="' . $border . '"></embed>
	  </object>';
	return $return;
}

?>