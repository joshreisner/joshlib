<?php
error_debug('including cache.php', __file__, __line__);

function cache_clear($match=false) {
	
}

function cache_end() {
	global $_josh;

	//stop buffering
	$contents = ob_get_contents();
	ob_end_clean();

	//echo contents
	echo $contents;

	//write the cache file
	file_put($_josh['cache'], $contents);
	
	//unset the filename variable because we don't need it anymore
	unset($_josh['cache']);
}

function cache_start($filename=false) {
	global $_josh;
	
	//determine what filename we should use--defaults to path_query
	if (!$filename) $filename = $_josh['request']['path_query'];
	
	//strip front slash for easier matching later
	$filename = format_string_starts('/', $filename);
	
	//append user_id (if set) as query argument
	if (!empty($_SESSION['user_id'])) $filename .= ((stristr('?', $filename)) ? '?' : '&') . 'user_id=' $_SESSION['user_id'];
	
	//finalize
	$filename = $_josh['write_folder'] . '/caches/' . urlencode($filename);

	if (file_is($filename)) {
		//get cache file
		echo file_get($filename);
		return false; //meaning you don't need to continue processing, because i've got a cache here
	} else {
		//or create cache file
		$_josh['cache'] = $filename;
		ob_start();
		return true; //meaning you do need to process
	}
}

?>