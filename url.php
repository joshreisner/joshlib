<?php
/*
should this be called http instead of url?  i could include cookie, and url_header would make more sense.
potentially just need to add http.php
*/
error_debug('including url.php', __file__, __line__);

function url($string) {
	//boolean test whether string is valid URL
	//todo make robust
	return (substr($string, 0, 4) == 'http');
}

function url_action($matches, $key='action') {
	//don't know whether this is any good.  matches possible $_GET['action'] values
	if (isset($_GET[$key])) {
		error_debug('<b>' . __function__ . '</b> checking $_GET[\'' . $key . '\'] for ' . $matches, __file__, __line__);
		$matches = array_separated($matches);
		foreach ($matches as $m) {
			if ($_GET[$key] == $m) {
				error_debug('<b>' . __function__ . '</b> found a match!', __file__, __line__);
				return true;
			}
		}
	}
	error_debug('<b>' . __function__ . '</b> didn\'t find any matches', __file__, __line__);
	return false;
}

function url_action_add($action=false, $go=false) {
	return url_query_add(array('action'=>$action), $go);
}

function url_array($array, $separator='&') {
	//use array_url() to reverse this
	//must be key=>value array pairs
	$pairs = array();
	foreach ($array as $key=>$value) $pairs[] = rawurlencode($key) . '=' . rawurlencode($value);
	return implode($separator, $pairs);
}

function url_base() {
	global $_josh;
	return $_josh['request']['protocol'] . '://' . $_josh['request']['host'];
}

function url_change($target='') {
	global $_josh;
	
	if ($target === false) { //if redirect is really set to FALSE, send to site home
		$target = '/';
	} elseif (empty($target)) { //if redirect is an empty string, refresh the page by sending back to same URL
		$target = $_josh['request']['path_query'];
	}
	if ($_josh['slow']) {
		error_debug('<b>url_change</b> (slow) to ' . $target, __file__, __line__);
		if ($_josh['mode'] != 'debug') echo draw_javascript('location.href="' . $target . '"');
	} else {
		error_debug('<b>url_change</b> (fast) to ' . $target, __file__, __line__);
		if ($_josh['mode'] != 'debug') header('Location: ' . $target);
	}
	db_close();
}

function url_change_get($target='') {
	global $_GET;
	if (isset($_GET['return_to']) && !empty($_GET['return_to'])) $target = $_GET['return_to'];
	url_change($target);
}

function url_change_post($target='') {
	global $_POST;
	if (isset($_POST['return_to']) && !empty($_POST['return_to'])) $target = $_POST['return_to'];
	url_change($target);
}

function url_domain($url=false) {
	if ($url) {
		$url = url_parse($url);
	} else {
		global $_josh;
		$url = $_josh['request'];
	}
	return $url['domain'];
}

function url_domainname($url=false) {
	if ($url) {
		$url = url_parse($url);
	} else {
		global $_josh;
		$url = $_josh['request'];
	}
	return $url['domainname'];
}

function url_file_link($file_table, $file_id, $col_title='title', $col_file='file', $col_type='type') {
	return url_query_add(array('action'=>'file_download', 'file_table'=>$file_table, 'file_id'=>$file_id, 'col_title'=>$col_title, 'col_file'=>$col_file, 'col_type'=>$col_type), false);
}

function url_drop($deletes=false, $go=true) {
	//alias for url_query_drop
	return url_query_drop($deletes, $go);
}

function url_folder($empty='home') {
	global $_josh;
	return (empty($_josh['request']['folder'])) ? $empty : $_josh['request']['folder'];
}

function url_get($url, $username=false, $password=false) {
	global $_josh;
	
	//retrieve remote page contemts
	
	if (!url($url)) return false;
		
	//curl_init is the preferred method.  if it's not available, try file()
	if (!function_exists('curl_init')) return @implode('', @file($url));

	//run curl
	$ch = curl_init($url);
	if (format_text_starts('https://', $url)) curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	if ($username && $password) curl_setopt($ch, CURLOPT_HTTPHEADER, array('Authorization: Basic ' . base64_encode($username . ':' . $password)));
	curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.0)');
	curl_setopt($ch, CURLOPT_HTTPHEADER, array(
		'GET / HTTP/1.1',
		'Accept: text/xml,application/xml,application/xhtml+xml,text/html;q=0.9,text/plain;q=0.8,image/png,*/*;q=0.5', 
		'Cache-Control: max-age=0',
		'Connection: keep-alive',
		'Keep-Alive: 300',
		'Accept-Charset: ISO-8859-1,utf-8;q=0.7,*;q=0.7',
		'Accept-Language: en-us,en;q=0.5',
		'Pragma: ' // browsers keep this blank. 
	)); 
	curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10); 
	curl_setopt($ch, CURLOPT_REFERER, 'http://www.google.com/');
	//curl_setopt($ch, CURLOPT_REFERER, $_josh['request']['url']);
	$return = trim(curl_exec($ch));
	
	//don't report couldn't connect errors, generates too much email
	if ($error = curl_errno($ch) && ($error != CURLE_COULDNT_CONNECT)) error_handle('CURL Error', curl_error($ch), __file__, __line__);

	curl_close($ch);
	return $return;
}

function url_header_utf8() {
	//todo rename http_header_utf8
	if (!headers_sent()) header('Content-Type: text/html; charset=utf-8');
}

function url_id($index='id') {
	//check to see whether there's an id and if so, if it's an integer
	if (isset($_GET[$index]) && format_check($_GET[$index])) return $_GET[$index];
	unset($_GET[$index]);
	return false;
}

function url_id_add($id=false, $go=false) {
	return url_query_add(array('id'=>$id), $go);
}

function url_parse($url) {
	//todo rename array_url
	global $_josh;
	error_debug('<b>url_parse</b> running for  ' . $url, __file__, __line__);

	//hacking fix
	$url = str_replace('"', '', $url);
	

	//add protocol if missing.  todo add substr
	if (!strstr($url, 'http://') && !strstr($url, 'https://')) $url = 'http://' . $url; 

	//todo test with ?query=string and #hash

	//start with php parsed url
	$return			= parse_url(trim($url));
	$return['host']	= strtolower($return['host']);
	if (!isset($return['path'])) $return['path'] = '';

	$cc_tlds = array('ac','ad','ae','af','ag','ai','al','am','an','ao','aq','ar','as','at','au','aw','az','ax','ba','bb','bd','be','bf','bg','bh',
		'bi','bj','bm','bn','bo','br','bs','bt','bv','bw','by','bz','ca','cc','cd','cf','cg','ch','ci','ck','cl','cm','cn','co','cr','cs','cu','cv',
		'cx','cy','cz','de','dj','dk','dm','do','dz','ec','ee','eg','eh','er','es','et','eu','fi','fj','fk','fm','fo','fr','ga','gb','gd','ge','gf',
		'gg','gh','gi','gl','gm','gn','gp','gq','gr','gs','gt','gu','gw','gy','hk','hm','hn','hr','ht','hu','id','ie','il','im','in','io','iq','ir',
		'is','it','je','jm','jo','jp','ke','kg','kh','ki','km','kn','kp','kr','kw','ky','kz','la','lb','lc','li','lk','lr','ls','lt','lu','lv','ly',
		'ma','mc','md','mg','mh','mk','ml','mm','mn','mo','mp','mq','mr','ms','mt','mu','mv','mw','mx','my','mz','na','nc','ne','nf','ng','ni','nl',
		'no','np','nr','nu','nz','om','pa','pe','pf','pg','ph','pk','pl','pm','pn','pr','ps','pt','pw','py','qa','re','ro','ru','rw','sa','sb','sc',
		'sd','se','sg','sh','si','sj','sk','sl','sm','sn','so','sr','st','sv','sy','sz','tc','td','tf','tg','th','tj','tk','tl','tm','tn','to','tp',
		'tr','tt','tv','tw','tz','ua','ug','uk','um','us','uy','uz','va','vc','ve','vg','vi','vn','vu','wf','ws','ye','yt','yu','za','zm','zw');

	$domain_parts	= explode('.', $return['host']);
	$domain_count	= count($domain_parts);

	$return['subdomain']	= '';
	$return['usingwww']		= ($domain_parts[0] == 'www');
	$return['sanswww']		= ($return['usingwww']) ? implode('.', array_slice($domain_parts, 1)) : $return['host'];

	if (($domain_count > 2) && in_array($domain_parts[$domain_count-1], $cc_tlds)) { //using cctld
		$return['tld']			= $domain_parts[$domain_count-2] . '.' . $domain_parts[$domain_count-1];
		$return['domainname']	= $domain_parts[$domain_count-3];
		if ($domain_count > 3) $return['subdomain'] = implode('.', array_slice($domain_parts, 0, -3));
	} else { //not using cctld
		$return['tld']			= $domain_parts[$domain_count-1];
		$return['domainname']	= @$domain_parts[$domain_count-2];
		if ($domain_count > 2) $return['subdomain'] = implode('.', array_slice($domain_parts, 0, -2));
	}
	
	$return['domain']		= $return['domainname'] . '.' . $return['tld'];

	//clean out directory index when possible
	if ($remainder = format_text_ends(DIRECTORY_INDEX, $return['path'])) $return['path'] = $remainder;
	
	//fix request for situations where you're doing folder.php to represent /folder/
	if ($_SERVER['PHP_SELF'] == $return['path'] . '/') $return['path'] = str_replace('.php', '/', $return['path']);
	
	//get folder, subfolder, subsubfolder (there must be a smoother way)
	$urlparts = explode('/', $return['path']);
	$urlcount = count($urlparts);

	$return['folder']		= (empty($urlparts[1])) ? false : $urlparts[1];
	$return['subfolder']	= (empty($urlparts[2])) ? false : $urlparts[2];
	$return['subsubfolder']	= (empty($urlparts[3])) ? false : $urlparts[3];
	
	if (stristr($return['subfolder'], '.')) $return['subfolder'] = false;

	$return['id'] = $return['page'] = false;
	if (!empty($urlparts[$urlcount-1])) {
		$return['page'] = $urlparts[$urlcount-1];

		//special GET for mod_rewrite pages -- id == 13 if http://foo.com/bar/13
		$return['id'] = (format_check($urlparts[$urlcount-1])) ? $urlparts[$urlcount-1] : false;
	}
	
	//add query string to path_query
	$return['path_query']	= $return['path'];
	if (isset($return['query'])) {
		$return['path_query'] .= '?' . $return['query'];
	} else {
		$return['query'] = false;
	}
	
	//protocol is a better word than scheme
	//die(draw_array($return));
	$return['protocol'] = $return['scheme'];
	
	//get socket
	//todo ~ handle http://www.example.com:80/
	if ($return ['protocol'] == 'http') {
		$return['socket'] = 80;
	} elseif ($return ['protocol'] == 'https') {
		$return['socket'] = 443;
	}
	
	//get full browser address
	$return['base'] = $return['protocol'] . '://' . $return['host'];
	$return['url'] = $return['base'] . $return['path_query'];
		
	//output for debugging ~ testing for debug since it takes some processing
	if ($_josh['mode'] == 'debug') {
		ksort($return);
		error_debug('<b>url_parse</b> is returning ' . draw_array($return), __file__, __line__);
	}
	
	//die(draw_array($return));

	return $return;
}

function url_query_action($value, $go=true) {
	//quick method to add a query_query_add for action
	return url_query_add(array("action"=>$value), $go);
}

function url_query_add($adds=false, $go=true, $path=false) { //add specified query arguments
	global $_josh;
	if (!$adds) $adds = array();
	//$target = url_base() . (($path) ? $path : $_josh['request']['path']);
	if (!$path) {
		$path = $_josh['request']['path'];
		$adds = array_merge($_GET, $adds);
	}
	$pairs = array();
	if (count($adds)) foreach ($adds as $key=>$value) if ($value !== false) $pairs[] = $key . '=' . urlencode($value);
	if (count($pairs)) {
		sort($pairs);
		$path .= '?' . implode('&', $pairs);
	}
	if ($go) url_change($path);
	return $path;
}

function url_query_drop($deletes=false, $go=true) {
	//clear specified query arguments, or clear everything if unspecified
	//accepts: $deletes is a one-dimensional array of keys, eg array('id', 'action', 'chicken'), $go is boolean
	//deletes could also be a comma-separated list
	global $_josh;
	$get = $_GET;
	$target = $_josh['request']['path'];
	if ($deletes) {
		if (!is_array($deletes)) $deletes = array_separated($deletes);
		foreach ($deletes as $key) {
			$key = trim($key);
			if (array_key_exists($key, $get)) unset($get[$key]);
		}
		$pairs = array();
		reset($get);
		while (list($key, $value) = each($get)) if ($value) $pairs[] = $key . '=' . $value;
		sort($pairs);
		if (count($pairs)) $target .= '?' . implode('&', $pairs);
	}
	if ($go) url_change($target);
	return $target;
}

function url_query_require($target='./', $index='id') {
	//requires a _GET variable to be defined or eject page
	if (!url_id($index)) url_change($target);
}

function url_query_parse($querystring) {
	if (strstr($querystring, '?')) $querystring = substr($querystring, strpos($querystring, '?') + 1);
	$return = array();
	if (strstr($querystring, '=')) {
		$pairs = explode('&', $querystring);
		foreach ($pairs as $pair) {
			@list($key, $value) = explode('=', $pair);
			$return[$key] = urldecode($value);
		}
	}
	return $return;
}

function url_sanswww($url=false) {
	if ($url) {
		$url = url_parse($url);
	} else {
		global $_josh;
		$url = $_josh['request'];
	}
	return $url['sanswww'];
}

function url_subfolder($empty=false) {
	global $_josh;
	return (empty($_josh['request']['subfolder'])) ? $empty : $_josh['request']['subfolder'];
}

function url_thumbnail($url, $width=300, $output=false) {
	//return (or output) a thumbnail of the specified $url using the thumbalizr api
	//authored Jan 17, 2013 for Brad Ascalon (included in BB Login)
	global $_josh;
	
	if (empty($_josh['thumbalizr']['api_key'])) error_handle('Thumbalizr Key Needed', 'Please go to ' . draw_link('http://www.thumbalizr.com/', 'Thumbalizr') . ' and sign up for an api key and enter it in your config as $_josh[\'thumbalizr\'][\'api_key\'].', __function__, __line__);
	
	//get img from thumbalizr
	$img = file_get_contents('http://api.thumbalizr.com/?api_key=' . $_josh['thumbalizr']['api_key'] . '&quality=90&width=' . $width . '&encoding=jpg&delay=8&mode=screen&bwidth=1280&bheight=1024&url=' . $url);
	
	//get headers
	$headers='';
	foreach ($http_response_header as $tmp) {
 		if (strpos($tmp,'X-Thumbalizr-') !== false) { 
 			$tmp1 = explode('X-Thumbalizr-', $tmp);
 			$tmp2 = explode(': ', $tmp1[1]);
 			$headers[$tmp2[0]] = $tmp2[1]; 
 		}
	}	
	//die(draw_array($headers));

	//output
	if ($headers['Status'] != 'OK') {
		error_handle('Thumbalizr Fail', 'An image could not be generated from URL: ' . $url . draw_array($headers), __function__, __line__);
		return false;
	}
		
	if (!$output) return $img;
	
	//output as image
	header('Content-type: image/jpeg');
	foreach($headers as $key=>$value) header('X-Thumbalizr-'. $key . ': ' . $value);
	echo $img;
}

function url_tld($empty=false) {
	global $_josh;
	return (empty($_josh['request']['tld'])) ? $empty : $_josh['request']['tld'];
}