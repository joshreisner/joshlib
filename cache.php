<?php
error_debug('including cache.php', __file__, __line__);

function cache_clear() {

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
}

function cache_start($page=false) {
	global $_josh;
	
	//determine what filename we should use--defaults to path_query
	$filename = $_josh['write_folder'] . '/caches/';
	if (!empty($_SESSION['user_id'])) $filename .= $_SESSION['user_id'] . '/';
	$filename .= urlencode((($page) ? $page : $_josh['request']['path_query']));

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