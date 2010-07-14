<?php
error_debug('including html.php', __file__, __line__);

function html($version=false) {
	//set or get html version
	global $_josh;
	if ($version) {
		$_josh['html'] = $version;
	} else {
		$version = $_josh['html'];
	}
	return $version;
}

function arg(&$array, $value, $key='class', $separator=' ') {
	if (empty($array[$key])) {
		$array[$key] = $value;
	} else { 
		$array[$key] .= $separator . $value;
	}
}

function html_nav($options=false, $match='path', $class_args=false) {
	global $_josh;
	if (!$count = count($options)) return false;
	$counter = 1;
	$nav = array();
	foreach ($options as $url=>$title) {
		$args = array();
		if ($counter == 1) arg($args, 'first');
		if ($counter == $count) arg($args, 'last');
		if ($match == 'path') {
			if ($_josh['request']['path'] == $url) arg($args, 'selected');
		} elseif ($match == 'folders') {
			//folder match
		} elseif ($match == 'path_query') {
			if ($_josh['request']['path_query'] == $url) arg($args, 'selected');
		}
		$nav[] = draw_tag('li', $args, draw_link($url, $title));
		$counter++;
	}
	return draw_tag('nav', $class_args, implode('', $nav));
}

?>