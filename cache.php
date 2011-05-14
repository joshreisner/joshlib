<?php
//cache features are under construction
//code needs review

error_debug('including cache.php', __file__, __line__);

function cache_clear($match=false) {
	global $_josh;
	if ($files = file_folder(DIRECTORY_WRITE . '/caches/')) {
		foreach ($files as $f) {
			if ($match) {
				//delete only certain files
			} else {
				//delete everything
				file_delete($f['path_name']);
			}
		}
	}
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
	$filename = format_text_starts('/', $filename);
	
	//append user id (if set) as query argument
	if ($userID = user()) $filename .= ((stristr('?', $filename)) ? '?' : '&') . 'user_id=' . $userID;
	
	//finalize
	$filename = DIRECTORY_WRITE . '/caches/' . urlencode($filename) . '.html';

	if (file_check($filename)) {
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