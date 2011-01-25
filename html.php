<?php
error_debug('including email.php', __file__, __line__);
//i'm going to very carefully rewrite most of the draw.php in here

function html_a($link=false, $href='#', $arguments=false, $new_window=false, $max_length=60) {
	//$max_length is the maximum length of the $link if it's empty, eg <a href="http://www.areallylongaddress.com/foo/bar/">http://www.areallylong...</a>
	if (!$link) {
		$link = $href;
		if (strlen($link) > $max_length) $link = substr($str, 0, $max_length) . '&hellip;';
	}
	$arguments = array_arguments($arguments);
	if (format_text_starts('mailto:', $href)) {
		//obfuscate the address to fight spam
		$link = format_ascii(str_replace('mailto:', '', $href));
		$arguments['href'] = format_ascii($href);
	} elseif (format_text_starts('javascript:', $href)) {
		//fix link for javascript
		$arguments['href'] = '#';
		$arguments['onclick'] = $href;
	} elseif ($href) {
		$arguments['href'] = htmlentities($href);
	} else {
		array_argument($arguments, 'empty');
	}
	if ($new_window) $arguments['target'] = '_blank';
	return draw_tag('a', $arguments, $link);
}

?>