<?php
error_debug("~ including array.php");

function array_key_compare_asc($a, $b) {
	global $_josh;
	error_debug("<b>arrayKeyCompare</b> comparing" . $a[$_josh["sort_key"]]);
	return strcmp($a[$_josh["sort_key"]], $b[$_josh["sort_key"]]);
}

function array_key_compare_desc($a, $b) {
	global $_josh;
	error_debug("<b>arrayKeyCompare</b> comparing" . $a[$_josh["sort_key"]]);
	return strcmp($b[$_josh["sort_key"]], $a[$_josh["sort_key"]]);
}

function array_key_filter($array, $key, $value) {
	$return = array();
	foreach ($array as $element) {
		if ($element[$key] == $value) $return[] = $element;
	}
	return $return;
}

function array_post_fields($fieldnames, $delimiter=",") {
	//array is a delimited string, possibly with spaces
	$return = array();
	$fields = explode($delimiter, $fieldnames);
	foreach ($fields as $f) $return[] = trim($f);
	return $return;
}

function array_remove($needle, $haystack) {
	$return = array();
	foreach ($haystack as $value) if ($value != $needle) $return[] = $value;
	return $return;
}

function array_sort($array, $direction="asc", $key=false) {
	global $_josh;
	$_josh["sort_key"] = ($key) ? $key : array_shift(array_keys($array[0]));
	error_debug("<b>arraySort</b> running for $key");
	usort($array, "arrayKeyCompare" . format_title($direction));
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
?>