<?php
/*
should this be called http instead of url?  i could include cookie, and url_header would make more sense.
*/

error_debug('including url.php', __file__, __line__);

function url_action($matches, $key='action') {
	//don't know whether this is any good.  matches possible $_GET['action'] values
	global $_GET;
	if (isset($_GET[$key])) {
		error_debug('<b>' . __function__ . '</b> checking $_GET[\'' . $key . '\'] for ' . $matches, __file__, __line__);
		$matches = explode(',', $matches);
		foreach ($matches as $m) {
			if ($_GET[$key] == trim($m)) {
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
		if ($_josh['debug']) db_close(); //exit early so you can see log
		echo draw_javascript('location.href="' . $target . '"');
	} else {
		error_debug('<b>url_change</b> (fast) to ' . $target, __file__, __line__);
		if ($_josh['debug']) db_close(); //exit early so you can see log
		header('Location: ' . $target);
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

function url_drop($deletes=false, $go=true) {
	//alias for url_query_drop
	return url_query_drop($deletes, $go);
}

function url_header_utf8() {
	header('Content-Type: text/html; charset=utf-8');
}

function url_id($index='id') {
	global $_GET;
	//check to see whether there's an id and if so, if it's an integer
	if (isset($_GET[$index]) && format_check($_GET[$index])) return $_GET[$index];
	unset($_GET[$index]);
	return false;
}

function url_parse($url) {
	global $_josh;
	error_debug('<b>url_parse</b> running for  ' . $url, __file__, __line__);
	
	$gtlds = array('aero','biz','com','coop','info','jobs','museum','name','net','org','pro','travel','gov','edu','mil','int','site');

	$ctlds = array('ac','ad','ae','af','ag','ai','al','am','an','ao','aq','ar','as','at','au','aw','az','ax','ba','bb','bd','be','bf',
	'bg','bh','bi','bj','bm','bn','bo','br','bs','bt','bv','bw','by','bz','ca','cc','cd','cf','cg','ch','ci','ck','cl','cm','cn','co',
	'cr','cs','cu','cv','cx','cy','cz','de','dj','dk','dm','do','dz','ec','ee','eg','eh','er','es','et','eu','fi','fj','fk','fm','fo',
	'fr','ga','gb','gd','ge','gf','gg','gh','gi','gl','gm','gn','gp','gq','gr','gs','gt','gu','gw','gy','hk','hm','hn','hr','ht','hu',
	'id','ie','il','im','in','io','iq','ir','is','it','je','jm','jo','jp','ke','kg','kh','ki','km','kn','kp','kr','kw','ky','kz','la',
	'lb','lc','li','lk','lr','ls','lt','lu','lv','ly','ma','mc','md','mg','mh','mk','ml','mm','mn','mo','mp','mq','mr','ms','mt','mu',
	'mv','mw','mx','my','mz','na','nc','ne','nf','ng','ni','nl','no','np','nr','nu','nz','om','pa','pe','pf','pg','ph','pk','pl','pm',
	'pn','pr','ps','pt','pw','py','qa','re','ro','ru','rw','sa','sb','sc','sd','se','sg','sh','si','sj','sk','sl','sm','sn','so','sr',
	'st','sv','sy','sz','tc','td','tf','tg','th','tj','tk','tl','tm','tn','to','tp','tr','tt','tv','tw','tz','ua','ug','uk','um','us',
	'uy','uz','va','vc','ve','vg','vi','vn','vu','wf','ws','ye','yt','yu','za','zm','zw');

	//add protocol if missing.  when would this be missing?
	if (!strstr($url, 'http://') && !strstr($url, 'https://')) $url = 'http://' . $url; 

	$subs			= ''; 
	$domainname		= ''; 
	$tld			= ''; 
	$tldarray		= array_merge($gtlds, $ctlds); 
	$tld_isReady	= false;
	$return			= parse_url(trim($url));
	$return['host']	= strtolower($return['host']); //fixing errors i'm getting on livingcities finding config
	$domainarray	= explode('.', $return['host']);
	$top			= count($domainarray);
	
	for ($i = 0; $i < $top; $i++) {
		$_domainPart = array_pop($domainarray);
		if (!$tld_isReady) {
			if (in_array($_domainPart, $tldarray)) {
				$tld = '.' . $_domainPart . $tld;
			} else {
				$domainname = $_domainPart;
				$tld_isReady = 1;
			}
		} else {
			$subs = '.' . $_domainPart . $subs;
		}
	}

	if (!isset($return['path'])) $return['path'] = '';
	$return['domainname']	= $domainname;
	$return['tld']			= str_replace('.', '', $tld);
	$return['domain']		= $domainname . $tld;
	$return['usingwww']		= (substr($return['host'], 0, 4) == 'www.') ? 1 : 0;
	$return['sanswww']		= ($return['usingwww']) ? substr($return['host'], 4) : $return['host'];
	$return['subdomain']	= substr($subs, 1);
	$return['path']			= str_replace('index.php', '', $return['path']);
	
	//get folder, subfolder, subsubfolder (there must be a smoother way)
	$urlparts = explode('/', $return['path']);
	$urlcount = count($urlparts);

	$return['folder']		= (empty($urlparts[1])) ? false : $urlparts[1];
	$return['subfolder']	= (empty($urlparts[2])) ? false : $urlparts[2];
	$return['subsubfolder']	= (empty($urlparts[3])) ? false : $urlparts[3];

	//special $_GET['id']
	$return['id'] = (format_check($urlparts[$urlcount-1])) ? $urlparts[$urlcount-1] : false;

	//add query string to path_query
	$return['path_query']	= $return['path'];
	if (isset($return['query'])) {
		$return['path_query'] .= '?' . $return['query'];
	} else {
		$return['query'] = false;
	}
	
	//protocol is a better word than scheme
	$return['protocol'] = $return['scheme'];
	
	//get full browser address
	$return['url'] = $return['protocol'] . '://' . $return['host'] . $return['path_query'];
	
	/*handle possible mod_rewrite slots (i don't think we need this anymore 8/22/09
	if (isset($_GET['slot1'])) {
		$return['folder'] = $_GET['slot1'];
		$return['path'] = '/' . $_GET['slot1'] . '/';
		if (isset($_GET['slot2'])) {
			$return['subfolder'] = $_GET['slot2'];
			$return['path'] .= $_GET['slot2'] . '/';
			if (isset($_GET['slot3'])) {
				$return['subsubfolder'] = $_GET['slot3'];
				$return['path'] .= $_GET['slot3'] . '/';
			}
		}
		$return['path_query'] = $return['path'];
	}*/
	
	//output for debugging ~ testing for debug since it takes some processing
	if ($_josh['debug']) {
		ksort($return);
		error_debug('<b>url_parse</b> is returning ' . draw_array($return), __file__, __line__);
	}
	
	return $return;
}

function url_query_action($value, $go=true) {
	//quick method to add a query_query_add for action
	return url_query_add(array("action"=>$value), $go);
}

function url_query_add($adds, $go=true, $path=false) { //add specified query arguments
	global $_josh, $_GET;
	$target = url_base() . (($path) ? $path : $_josh['request']['path']);
	//$adds = array_unique(array_merge($_GET, $adds));
	$adds = array_merge($_GET, $adds);
	if (count($adds)) foreach ($adds as $key=>$value) if ($value) $pairs[] = $key . '=' . urlencode($value);
	sort($pairs);
	if (count($pairs)) $target .= '?' . implode('&', $pairs);
	if ($go) url_change($target);
	return $target;
}

function url_query_drop($deletes=false, $go=true) {
	//purpose: clear specified query arguments, or clear everything if unspecified
	//called by: lots of pages on the intranet, eg /staff/view.php
	//accepts: $deletes is a one-dimensional array of keys, eg array('id', 'action', 'chicken'), $go is boolean
	//deletes could also be a comma-separated list
	global $_josh, $_GET;
	$get = $_GET;
	$target = url_base() . $_josh['request']['path'];
	if ($deletes) {
		if (!is_array($deletes)) $deletes = explode(',', $deletes);
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

?>