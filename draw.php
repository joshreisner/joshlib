<?php
error_debug("including draw.php", __file__, __line__);

function draw_arg($name, $value=false) {
	if ($value === false) return;
	return ' ' . strToLower($name) . '="' . str_replace('"', '&quot;', $value) . '"';
}

function draw_args($array) {
	$return = "";
	foreach ($array as $key=>$value) $return .= draw_arg($key, $value);
	return $return;
}

function draw_array($array, $nice=false) {
	global $_josh;
	if (!is_array($array)) return false;
	$return = '<table width="100%" cellpadding="3" cellspacing="1" border="0" style="background-color:#eee;">';
	//if (!$nice) ksort($array);
	while(list($key, $value) = each($array)) {
		if ($nice && (strToLower($key) == "j")) continue;
		$value = format_quotes($value);
		if (strToLower($key) == "email") $value = "<a href='mailto:" . $value . "'>" . $value . "</a>";
		if (is_array($value)) {
			$return2 = "";
			foreach ($value as $key2 => $value2) {
				$return2 .= "&#8226; " . $value2 . "<br>";
			}
			$value = $return2;
		}
		$return  .= '
			<tr style="background-color:#fff; font-family: verdana; font-size:11px; padding:6px; line-height:16px; width:100%;" valign="top"';
		if (strToLower($key) == "message") $return .= ' height="160"';
		$return .= '><td style="background-color:#eee;" width="21%"><nobr>';
		$return .= ($nice) ? format_text_human($key)  : $key;
		$return .= '&nbsp;</nobr></td><td width="79%">';
		$return .= is_object($value) ? "object value" : nl2br($value);
		$return .= '</td></tr>';
	}
	$return .= '</table>';
	return $return;
}

function draw_autorefresh($minutes=5) {
	return draw_tag("meta", array("http-equiv"=>"refresh", "content"=>$minutes * 60));
}

function draw_css($content) {
	return draw_tag("style", array("type"=>"text/css"), $content);
}

function draw_css_src($location="/styles/screen.css", $media=false) {
	//special "ie" mode for internet explorer
	$ie = ($media == "ie");
	if ($ie) $media = "screen";
	$return = draw_tag("link", array("rel"=>"stylesheet", "type"=>"text/css", "media"=>$media, "href"=>$location));
	if ($ie) $return = '<!--[if IE]>' . $return . '<![endif]-->';
	return $return;
}

function draw_container($tag, $innerhtml, $args=false) {
	//convenience function for draw_tag if you're just writing a simple container tag
	return draw_tag($tag, $args, $innerhtml);
}

function draw_div($id, $innerhtml="", $args=false) {
	//convenience function specifically for DIVs, since they're so ubiquitous
	if (!$args) $args = array();
	$args["id"] = $id;
	return draw_tag("div", $args, $innerhtml);
}

function draw_dl($array, $class=false) {
	$return = "";
	foreach ($array as $key=>$value) {
		$return .= draw_container("dt", $key) . draw_container("dd", $value);
	}
	return draw_container("dl", $return, array("class"=>$class));
}

function draw_favicon($location="/images/favicon.png") {
	//only accepts PNGs right now
	return draw_tag("link", array("rel"=>"shortcut icon", "href"=>$location, "type"=>"image/png")); 
}

function draw_focus($form_element) {
	return draw_javascript('document.getElementById("' . $form_element . '").focus();');
}

function draw_form_button($text, $location=false, $class=false, $disabled=false, $javascript=false) {
	global $_josh;
	$class = ($class) ? $class . " button" : "button";
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
	$class = ($class) ? $class . " checkbox" : "checkbox";
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
	$class = ($class) ? $class . " select" : "select";

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
	return draw_javascript('document.getElementById("' . $name . '").focus();');
}

function draw_form_hidden($name, $value="") {
	return draw_tag("input", array("type"=>"hidden", "name"=>$name, "id"=>$name, "value"=>$value));
}

function draw_form_password($name, $value="", $class=false, $maxlength=255, $autocomplete=true) {
	global $_josh;
	$class = ($class) ? $class . " password" : "password";
	$return = '<input type="password" name="' . $name . '" id="' . $name . '" value="' . $value . '" class="' . $class . '" maxlength="' . $maxlength . '" class="' . $class . '"';
	if (!$autocomplete) $return .= ' autocomplete="off"';
	$return .= '>';
	return $return;
}

function draw_form_radio($name, $value="", $checked=false, $class=false) {
	global $_josh;
	$class = ($class) ? $class . " radio" : "radio";
	$return  = '<input type="radio" name="' . $name . '" value="' . $value . '"';
	if ($class) $return .= ' class="' . $class . '"';
	if ($checked) $return .= ' checked';
	$return .= '>';
	return $return;
}

function draw_form_select($name, $sql_options, $value=false, $required=true, $class=false, $action=false, $nullvalue="", $maxlength=false) {
	global $_josh;
	$class = ($class) ? $class . " select" : "select";
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
	$class = ($class) ? $class . " select" : "select";
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
	$class = ($class) ? $class . " button" : "button";
	return draw_tag("input", array("type"=>"submit", "value"=>$message, "class"=>$class));
	//return '<input type="submit" value="   ' . $message . '   " class="' . $class . '">';
}

function draw_form_text($name, $value="", $class=false, $maxlength=255, $style=false, $autocomplete=true) {
	$class			= ($class) ? $class . " text" : "text";
	$autocomplete	= format_boolean($autocomplete, "on|off");
	$type			= "text";
	$id				= $name;
	return draw_tag("input", compact("type", "name", "id", "value", "class", "maxlength", "style", "autocomplete"));
}

function draw_form_textarea($name, $value="", $class=false) {
	error_debug("drawing textarea");
	global $_josh;
	if (!$value) $value = "";
	$class = ($class) ? $class . " textarea" : "textarea";
	return draw_container("textarea", $value, array("name"=>$name, "id"=>$name, "class"=>$class));
	//return '<textarea name="' . $name . '" id="' . $name . '" class="' . $class . '">' . $value . '</textarea>';
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
	//not going to bother doing draw_javascript on this since it's google's code
	return '
	<script type="text/javascript">
	var gaJsHost = (("https:" == document.location.protocol) ? "https://ssl." : "http://www.");
	document.write(unescape("%3Cscript src=\'" + gaJsHost + "google-analytics.com/ga.js\' type=\'text/javascript\'%3E%3C/script%3E"));
	</script>
	<script type="text/javascript">
	try {
	var pageTracker = _gat._getTracker("' . $id . '");
	pageTracker._setDomainName("none");
	pageTracker._trackPageview();
	} catch(err) {}</script>';
}

function draw_img($path, $link=false, $alt=false, $name=false, $linknewwindow=false) {
	//alt could also be an array of arguments
	global $_josh;
	
	//get width and height
	$image = @getimagesize($path);
	if (!$image) $image = @getimagesize($_josh["root"] . $path);
	if (!$image) return false;
	
	//assemble tag
	$args = array("src"=>url_base() . $path, "width"=>$image[0], "height"=>$image[1], "border"=>0);
	if (is_array($alt)) {
		//values of alt can overwrite width, height, border, even src but that could get ugly -- maybe i should prevent that?
		$args = array_merge($args, $alt);
	} else {
		$args["alt"] = $alt;
		$args["name"] = $args["class"] = $args["id"] = $name;
	}
	$return = draw_tag("img", $args);
	if ($link) $return = draw_link($link, $return, $linknewwindow);
	return $return;
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

function draw_javascript_tinymce($path_css="/styles/tinymce.css", $path_script="/_site/tinymce/jscripts/tiny_mce/tiny_mce.js") {
	return draw_javascript_src() . draw_javascript_src($path_script) . draw_javascript("form_tinymce_init('" . $path_css . "');");
}

function draw_javascript_src($filename=false) {
	global $_josh;
	if (!$filename && isset($_josh["write_folder"])) {
		if ($_josh["drawn"]["javascript"]) return false; //only draw this file once per page
		$_josh["drawn"]["javascript"] = true;
		$filename = $_josh["write_folder"] . "/javascript.js";
		$joshlibf = $_josh["joshlib_folder"] . "/javascript.js";
		if (!file_is($filename) || (filemtime($joshlibf) > filemtime($_josh["root"] . $filename))) {
			//either doesn't exist or is out-of-date
			if (!file_put($filename, file_get($joshlibf))) return error_handle(__FUNCTION__ . " can't write the js file.", __file__, __line__);
		}
	} elseif (!$filename) {
		return error_handle(__FUNCTION__ . " needs the variable _josh[\"write_folder\"] to be set.", __file__, __line__);
	}
	//$src = "http://joshlib.joshreisner.com/javascript.js";
	return $_josh["newline"] . '<script language="javascript" src="' . $filename . '" type="text/javascript"></script>';
}

function draw_link($href, $str=false, $newwindow=false, $args=false) {
	if (!$args)	$args = array();
	
	//obfuscate email
	if (format_text_starts("mailto:", $href)) {
		if (!$str)	$str = format_ascii(format_string(str_replace("mailto:", "", $href), 60));
		$href = format_ascii($href);
	} elseif (!$str) {
		if (!$str)	$str = format_string($href, 60);
	}
	
	$args["href"]	= $href;	
	if ($newwindow) $args["target"] = "_blank";

	return draw_container("a", $str, $args);
}

function draw_list($options, $class=false, $type="ul", $selected=false) {
	//make a ul or an ol out of a one-dimensional array
	global $_josh;
	if (!is_array($options) || (!$count = count($options))) return false;
	for ($i = 0; $i < $count; $i++) {
		$liclass = "option" . ($i + 1);
		if ($selected == ($i + 1)) $liclass .= " selected";
		$options[$i] = draw_tag("li", array("class"=>$liclass), $options[$i]);
	}
	return draw_tag($type, array("class"=>$class), implode($options,  $_josh["newline"] . "\t"));
}

function draw_meta_description($string) {
	global $_josh;
	return draw_tag("meta", array("name"=>"description", "content"=>$string)) . $_josh["newline"];
}

function draw_meta_utf8() {
	global $_josh;
	return draw_tag("meta", array("http-equiv"=>"Content-Type", "content"=>"text/html; charset=utf-8")) . $_josh["newline"];
}

function draw_navigation($options, $match=false, $type="text", $class="navigation", $folder="/images/navigation/") {
	//type could be text, images or rollovers
	global $_josh;
	
	//this is so you can have several sets of rollovers in the same page eg the smarter toddler site
	if (!isset($_josh["drawn_navigation"])) $_josh["drawn_navigation"] = 0;
	$_josh["drawn_navigation"]++;
	
	//skip if empty
	if (!is_array($options) || !count($options)) return false;
	
	//$return = $_josh["newline"] . $_josh["newline"] . "<!--start nav-->" . $_josh["newline"] . "<ul class='" . $class . "'>";
	$return = array();
	if ($match === false) {
		$match = $_josh["request"]["path"];
	} elseif ($match === true) {
		$match = $_josh["request"]["path_query"];
	} elseif ($match == "//") {
		//to take care of a common / . folder . / scenario
		$match = "/";
	}
	error_debug("<b>draw_navigation</b> match is " . $match);
	$selected = false;
	$counter = 1;
	$javascript = $_josh["newline"];
	foreach ($options as $title=>$url) {
		$name = 'option_' . $_josh["drawn_navigation"] . '_' . $counter;
		$thisoption = '<a href="' . $url . '" class="' . $name;
		if (str_replace(url_base(), "", $url) == $match) {
			$img_state = "_on";
			$thisoption .= ' selected';
			$selected = $counter;
		} else {
			$img_state = "_off";
			if ($type == "rollovers") {
				$thisoption .= '" onmouseover="javascript:img_roll(\'' . $name . '\',\'on\');" onmouseout="javascript:img_roll(\'' . $name . '\',\'off\');';
			}
		}
		$thisoption .= '">';
		if ($type == "text") {
			$thisoption .= $title;
		} elseif (($type == "images") || ($type == "rollovers")) {
			$img = str_replace("/", "", $url);
			if (empty($img)) $img = "home";
			$img = $folder . $img;
			if ($type == "rollovers") {
				$javascript .= $name . "_on		 = new Image;" . $_josh["newline"];
				$javascript .= $name . "_off	 = new Image;" . $_josh["newline"];
				$javascript .= $name . "_on.src	 = '" . $img . "_on.png';" . $_josh["newline"];
				$javascript .= $name . "_off.src = '" . $img . "_off.png';" . $_josh["newline"];
			}
			$img .= $img_state . ".png";
			$thisoption .= draw_img($img, false, false, $name);
		}
		$thisoption .= '</a>';
		$return[] = $thisoption;
		$counter++;
	}
	$return = 	draw_javascript_src() . draw_list($return, $class, "ul", $selected);
	if ($type == "rollovers") $return = draw_javascript('if (document.images) {' . $javascript . '}') . $return;
	return $return;
}

function draw_newline($count=1) {
	global $_josh;
	$return = "";
	for ($i = 0; $i < $count; $i++) {
		$return .= $_josh["newline"];
	}
	return $return;
}

function draw_rss_link($address) {
	return draw_tag("link", array("rel"=>"alternate", "type"=>"application/rss+xml", "title"=>"RSS", "href"=>$address));
}

function draw_swf($path, $width, $height, $border=0) {
	$return = '<object classid="clsid:D27CDB6E-AE6D-11cf-96B8-444553540000" codebase="http://download.macromedia.com/pub/shockwave/cabs/flash/swflash.cab#version=9,0,28,0" width="' . $width . '" height="' . $height . '">
		<param name="movie" value="' . $path . '" />
		<param name="quality" value="high" />
		<embed src="' . $path . '" quality="high" pluginspage="http://www.adobe.com/shockwave/download/download.cgi?P1_Prod_Version=ShockwaveFlash" type="application/x-shockwave-flash" width="' . $width . '" height="' . $height . '" border="' . $border . '"></embed>
	  </object>';
	return $return;
}

function draw_tag($tag, $args=false, $innerhtml=false) {
	$tag = strToLower($tag);
	$return = '<' . $tag;
	$return .= (is_array($args)) ? draw_args($args) : draw_arg($args);
	if ($innerhtml === false) {
		$return .= '/>';
	} else {
		if (($tag == "td") && empty($innerhtml)) $innerhtml = "&nbsp;";
		$return .= '>' . $innerhtml . '</' . $tag . '>';
	}
	if ($tag == "td") $return .= draw_newline();
	if ($tag == "tr") $return .= draw_newline();
	if ($tag == "table") $return .= draw_newline(2);
	return $return;
}

?>