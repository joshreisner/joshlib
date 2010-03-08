<?php
/* 
WELCOME TO JOSHLIB!
	http://code.google.com/p/joshlib/ (wiki / documentation / report issues here)
	http://joshlib.joshreisner.com/ (eventual website)

LICENSE
	all files other than lib.zip are authored by josh reisner and provided to the public under the terms of the LGPL
	
THIRD PARTY SOFTWARE
	included in lib.zip.  thank you so much to each of the contributors for these excellent packages
	
	--title-------------lang----url---------------------------------------------license-------------------------
	> ckeditor			(js)	http://ckeditor.com/							GPL, LGPL and MPL
	> codepress			(js)	http://sourceforge.net/projects/codepress/		LGPL
	> fpdf				(php)	http://www.fpdf.org/							no license
	> lightbox2			(js)	http://www.lokeshdhakar.com/projects/lightbox2/	Creative Commons Attribution 2.5
	> prototype			(js)	http://prototypejs.org/							MIT
	> salesforce		(php)	http://developer.force.com/						~ not sure.  this is just an API
	> scriptaculous		(js)	http://script.aculo.us/							MIT (I think)
	> simple_html_dom	(php)	http://sourceforge.net/projects/simplehtmldom/	MIT
	> tinymce			(js)	http://tinymce.moxiecode.com/					LGPL

VARIABLES THAT JOSHLIB TRIES TO GET FROM THE WEBSERVER -- IF YOU'RE RUNNING FROM THE COMMAND LINE YOU MIGHT NEED TO PASS THEM
	$_josh['request']			this is an array, easiest way to set this is doing url_parse(http://www.yoursite.com/yourfolder/yourpage.php?query=whatever)
	$_josh['referrer']			same as request
	$_josh['newline']			\n or \r\n
	$_josh['root']				path to the site, eg /Users/yourusername/Sites/thissite
	$_josh['slow']				true or false; whether to use javascript when redirecting (true) or header variables (false)
	$_josh['mobile']			true or false

USING THE DEBUGGER
	you can run the debug() function after joshlib has been included to see output of various processes
	to debug the loading of the joshlib itself, set $_josh['debug'] = true before you include it

RUNNING ON THE CLI
	joshlib depends on certain $SERVER variables being present.  add these lines before including joshlib:
	$_SERVER['HTTP_HOST']		= 'backend.livingcities.org';
	$_SERVER['SCRIPT_NAME']		= '/salesforce/index.php';
	$_SERVER['DOCUMENT_ROOT']	= '/home/livingcities/www/backend';
	
*/
$_josh['time_start'] = microtime(true);	//start the processing time stopwatch -- use format_time_exec() to access this

//set up error handling.  needs to go first to handle subsequent errors
	error_reporting(E_ALL);
	ini_set('display_errors', TRUE);
	ini_set('display_startup_errors', TRUE);
	$_josh['joshlib_folder'] = dirname(__file__);
	if (!isset($_josh['debug'])) $_josh['debug'] = false;
	require($_josh['joshlib_folder'] . '/error.php');
	set_error_handler('error_handle_php');
	set_exception_handler('error_handle_exception');
	error_debug('<b>index</b> error handling is set up', __file__, __line__);
	
//suddenly this is an issue
	//todo, make settable
	date_default_timezone_set('America/New_York');
	
//set static variables todo, limit these

	//date
	$_josh['days']					= array('Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday');
	$_josh['month']					= date('n');
	$_josh['months']				= array('January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December');
	$_josh['mos']					= array('Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec');
	$_josh['today']					= date('j');
	$_josh['year']					= date('Y');
	$_josh['date']['strings']		= array('Yesterday', 'Today', 'Tomorrow');

	//status
	$_josh['drawn']['ckeditor']		= false;	//only include ckeditor js once
	$_josh['drawn']['focus']		= false;	//only autofocus on one form element
	$_josh['drawn']['javascript'] 	= false;	//only include javascript.js once
	$_josh['drawn']['tinymce']		= false;	//only include tinymce js once

	//search -- perhaps make this local to search function?
	$_josh['ignored_words']			= array('1','2','3','4','5','6','7','8','9','0','about','after','all','also','an','and','another','any','are',
									'as','at','be','because','been','before','being','between','both','but','by','came','can','come',
									'could','did','do','does','each','else','for','from','get','got','has','had','he','have','her','here',
									'him','himself','his','how','if','in','into','is','it','its','just','like','make','many','me','might',
									'more','most','much','must','my','never','now','of','on','only','or','other','our','out','over','re',
									'said','same','see','should','since','so','some','still','such','take','than','that','the','their',
									'them','then','there','these','they','this','those','through','to','too','under','up','use','very',
									'want','was','way','we','well','were','what','when','where','which','while','who','will','with',
									'would','you','your','a','b','c','d','e','f','g','h','i','j','k','l','m','n','o','p','q','r','s',
									't','u','v','w','x','y','z',''); //ignore these words when making search indexes

	//etc
	$_josh['newline']				= "\n"; //default
	$_josh['numbers']				= array('zero','one','two','three','four','five','six','seven','eight','nine');
	$_josh['queries']				= 0;	//for counting trips to the database
	
	//used by cms, db_column_add, and form::set_field
	$_josh['field_types']			= array(
										'checkbox'=>'Checkbox',
										'date'=>'Date',
										'select'=>'Dropdown',
										'file'=>'File',
										'image'=>'Image',
										'int'=>'Integer',
										'image-alt'=>'Image (Alt)',
										'text'=>'Text',
										'textarea'=>'Textarea',
										'url'=>'URL'
									);
	//used by cms and db_save
	$_josh['system_columns']		= array('id', 'created_date', 'created_user', 'updated_date', 'updated_user', 'deleted_date', 'deleted_user', 'is_active');

//get includes
	require($_josh['joshlib_folder'] . '/array.php');
	require($_josh['joshlib_folder'] . '/cache.php');
	require($_josh['joshlib_folder'] . '/db.php');
	require($_josh['joshlib_folder'] . '/draw.php');
	require($_josh['joshlib_folder'] . '/email.php');
	require($_josh['joshlib_folder'] . '/file.php');
	require($_josh['joshlib_folder'] . '/form.php');
	require($_josh['joshlib_folder'] . '/format.php');
	require($_josh['joshlib_folder'] . '/table.php');
	require($_josh['joshlib_folder'] . '/url.php');

//parse environment variables
	if (isset($_SERVER) && isset($_SERVER['HTTP_HOST']) && isset($_SERVER['SCRIPT_NAME'])) { //this would not be set if this were running from the command line (eg by a cron)
		//build request as string, then set it to array with url_parse
		$_josh['request'] = (isset($_SERVER['HTTPS']) && ($_SERVER['HTTPS'] == 'on')) ? 'https' : 'http';
		$_josh['request'] .= '://' . $_SERVER['HTTP_HOST'];
		
		//sometimes getting this error http://www.example.com./
		if (substr($_josh['request'], -1) == '.') $_josh['request'] = substr($_josh['request'], 0, -1);
		
		//check if we're using mod_rewrite
		if (isset($_SERVER['REDIRECT_URL'])) {
			$_josh['request'] .= str_ireplace($_josh['request'], '', $_SERVER['REQUEST_URI']); //sometimes REQUEST_URI contains full http://www.example.com when it should not, due (i think) to redirection with url_query_add() or the like
			$_GET = array_merge($_GET, url_query_parse($_josh['request']));
		} else {
			$_josh['request'] .= $_SERVER['SCRIPT_NAME'];
			if (isset($_SERVER['QUERY_STRING'])) $_josh['request'] .= '?' . $_SERVER['QUERY_STRING'];
		}
		
		$_josh['request'] = url_parse($_josh['request']);
				
		//special set $_GET['id']
		if ($_josh['request']['id'] && !isset($_GET['id'])) $_GET['id'] = $_josh['request']['id'];
			
		//platform-specific info
		if (isset($_SERVER['SERVER_SOFTWARE']) && strstr($_SERVER['SERVER_SOFTWARE'], 'Microsoft')) { //platform is PC
			$_josh['newline']			= "\r\n";
			//$_josh['root']				= str_replace(str_replace('/', '\\', $_josh['request']['path']), '', str_replace('\\\\', '\\foo', $_SERVER['PATH_TRANSLATED']));
			$_josh['root']				= str_replace($_josh['request']['path'], '', $_SERVER['PATH_TRANSLATED']);
			$_josh['slow']				= true;
		} else { //platform is UNIX or Mac
			$_josh['newline']			= "\n"; //has to be double-quotes for some reason
			$_josh['root']				= $_SERVER['DOCUMENT_ROOT'];
			if (!isset($_josh['slow']))	$_josh['slow'] = false;
		}
		
		//only checking for iphone right now
		$_josh['request']['mobile']		= (isset($_SERVER['HTTP_USER_AGENT']) && strstr($_SERVER['HTTP_USER_AGENT'], 'iPhone'));
	 	$_josh['referrer']				= (isset($_SERVER['HTTP_REFERER']))	? url_parse($_SERVER['HTTP_REFERER']) : false;
	} else {
		//probably running from command line -- set defaults
		if (!isset($_josh['debug']))	$_josh['debug']		= false;
		if (!isset($_josh['request']))	$_josh['request']	= false;
		if (!isset($_josh['newline']))	$_josh['newline']	= '\n';
		if (!isset($_josh['root']))		$_josh['root']		= false;
		if (!isset($_josh['slow']))		$_josh['slow']		= false;
		if (!isset($_josh['mobile']))	$_josh['mobile']	= false;
		if (!isset($_josh['referrer']))	$_josh['referrer']	= false;
	}
	
//set defaults for configuration for variables
	if (!isset($_josh['db']['location']))	$_josh['db']['location']	= 'localhost';
	if (!isset($_josh['db']['language']))	$_josh['db']['language']	= 'mysql';
	if (!isset($_josh['db']['database']))	$_josh['db']['database']	= '';
	if (!isset($_josh['db']['username']))	$_josh['db']['username']	= '';
	if (!isset($_josh['db']['password']))	$_josh['db']['password']	= '';
	if (!isset($_josh['basedblanguage']))	$_josh['basedblanguage']	= $_josh['db']['language'];
	if (!isset($_josh['is_secure']))		$_josh['is_secure']			= false;
	if (!isset($_josh['email_admin']))		$_josh['email_admin']		= 'josh@joshreisner.com';
	if (!isset($_josh['email_default']))	$_josh['email_default']		= ((empty($_josh['request']['subdomain'])) ? 'www' : $_josh['request']['subdomain']) . '@' . $_josh['request']['domain'];
	if (!isset($_josh['error_log_api']))	$_josh['error_log_api']		= false;
		
//get configuration variables
	if (!isset($_josh['write_folder'])) $_josh['write_folder'] = '/_' . $_josh['request']['sanswww']; //eg /_example.com
	if (!isset($_josh['config'])) $_josh['config'] = $_josh['write_folder'] . DIRECTORY_SEPARATOR . 'config.php'; //eg /_example.com/config.php
	if (file_is($_josh['config'])) {
		error_debug('<b>configure</b> found file', __file__, __line__);
		require($_josh['root'] . $_josh['config']);
	} else {
		//if it doesn't exist, create one
		error_debug('couldn\'t find config file, attempting to create one', __file__, __line__);
		file_write_folder();
		file_put_config();
		require($_josh['root'] . $_josh['config']);
	}
	
//ensure lib exists--todo autogen lib folder when lib.zip has been updated
	if (!is_dir($_josh['root'] . $_josh['write_folder'] . DIRECTORY_SEPARATOR . 'lib')) file_unzip($_josh['joshlib_folder'] . DIRECTORY_SEPARATOR . 'lib.zip', $_josh['write_folder']);

//set error reporting level by determining whether this is a dev or live situation
	if (isset($_SERVER['HTTP_HOST']) && (format_text_starts('dev-', $_SERVER['HTTP_HOST']) || format_text_starts('beta.', $_SERVER['HTTP_HOST']) || format_text_ends('.site', $_SERVER['HTTP_HOST']))) {
		$_josh['mode'] = 'dev';
		//error reporting already set above
	} else {
		$_josh['mode'] = 'live';
		error_reporting(0);
	}

//handle https forwarding
	if (isset($_josh['request']['protocol'])) {
		if ($_josh['is_secure'] && ($_josh['request']['protocol'] != 'https')) {
			url_change('https://' . $_josh['request']['host'] . $_josh['request']['path_query']);
		} elseif (!$_josh['is_secure'] && ($_josh['request']['protocol'] != 'http')) {
			url_change('http://' . $_josh['request']['host'] . $_josh['request']['path_query']);
		}
	}

//set convenience state variabless and escape quotes if necessary
	$_josh['getting']	= !empty($_GET);
	if ($_josh['getting']) foreach($_GET as $key=>$value) $_GET[$key] = format_quotes($value);
	
	$_josh['uploading'] = false;
	if (!empty($_FILES)) {
		foreach($_FILES as &$file) {
			if (!empty($file['name'])) {
				$file['name'] = format_quotes($file['name']);
				$_josh['uploading'] = true;
			}
		}
	}
	
	$_josh['posting']	= !empty($_POST);
	if ($_josh['posting']) foreach($_POST as $key=>$value) $_POST[$key] = format_quotes(str_replace('& ', '&amp; ', $value));
	
	$_josh['editing']	= url_id();
	
//handle some ajax calls automatically -- requires user to be logged in
	if (user() && url_action('ajax_delete,ajax_reorder,ajax_set,flushcache,debug,phpinfo')) {
		$array = array_ajax();
		
		//quick thing for sessions -- why do we need this?
		if (isset($array['id']) && ($array['id'] == 'session')) $array['id'] = user();
		
		switch ($_GET['action']) {
			case 'ajax_delete':
				exit;
			case 'ajax_reorder':
				db_query('UPDATE ' . $array['table'] . ' SET ' . $array['column'] . ' = NULL');
				
				//email('josh@joshreisner.com', draw_array($array));
				
				foreach ($array as $key=>$value) {
					$key = urldecode($key);
					if (format_text_starts($array['table'], $key)) db_query('UPDATE ' . $array['table'] . ' SET ' . $array['column'] . ' = ' . (format_numeric($key, true) + 1) . ' WHERE id = ' . $value);
				}
				
				echo 'reordered';
				exit;
			case 'ajax_draw_select':
			//draw_form_select($name, $sql_options, $value=false, $required=true, $class=false, $action=false, $nullvalue='', $maxlength=false) {

				echo draw_form_select($array['name'], 'SELECT id, value FROM ' . $array['table'] . ' WHERE is_active = 1', $array['value'], $array['required']);
				exit;
			case 'ajax_set':
				//todo, better column type sensing
				if (stristr($array['column'], 'date')) $array['value'] = format_date($array['value'], 'NULL', 'SQL');
				
				//special exception for #panel in cms on object list and form pages
				if (($array['table'] == 'app_objects')) $array['value'] = str_replace("\n", '<br/>', $array['value']); //nl2br($array['value'])
				
				if (db_query('UPDATE ' . $array['table'] . ' SET ' . $array['column'] . ' = \'' . $array['value'] . '\' WHERE id = ' . $array['id'])) {
					echo (stristr($array['column'], 'date')) ? format_date($array['value']) : $array['value'];
				} else {
					echo 'ERROR';
				}
				exit;
			case 'debug':
				debug();
				break;
			case 'flushcache':
				cache_clear();
				echo 'caches cleared';
				exit;
			case 'phpinfo':
				phpinfo();
				exit;
		}
	} elseif (url_action('validate')) {
		//validate redirect
		url_change('http://validator.w3.org/check?uri=' . urlencode(url_query_drop('action', false)));
	}

//special functions that don't yet fit into a category

function browser_gzip() {
    return in_array('gzip', array_separated($_SERVER['HTTP_ACCEPT_ENCODING']));
}

function browser_output($html) {
	//one easy way to employ this is with ob_start('browser_output')
    if (!browser_gzip() || headers_sent()) return $html;
    header('Content-Encoding: gzip');
    return gzencode($html);
}

function cookie($name=false, $value=false, $session=false) {
	global $_josh, $_COOKIE, $_SERVER;

	//need to output something to page for cookie to take -- if it's a redirect do it with javascript
	$_josh['slow'] = true;

	if ($_josh['debug']) {
		error_debug('not actually setting cookie (' . $name . ', ' . $value . ') because buffer is already broken by debugger.', __file__, __line__);
		return false;
	}

	if ($name) {
		$time = ($value) ? mktime(0, 0, 0, 1, 1, 2030) : time()-3600;
		if ($session) $time = 0; //expire at the end of the session
		if (!$value) $value = '';
		$_COOKIE[$name] = $value;
		setcookie($name, $value, $time, '/', '.' . $_josh['request']['domain']);
	} elseif (isset($_SERVER['HTTP_COOKIE'])) {
	    $cookies = explode(';', $_SERVER['HTTP_COOKIE']);
	    foreach($cookies as $cookie) {
	        $parts = explode('=', $cookie);
	        $name = trim($parts[0]);
	        setcookie($name, '', time()-1000);
	        setcookie($name, '', time()-1000, '/');
	    }
	}
}

function cookie_get($key) {
	//return a cookie value and format its quotes -- important for security!
	//started for bb cms
	global $_COOKIE;
	if (!isset($_COOKIE[$key]) || empty($_COOKIE[$key])) return false;
	return format_quotes($_COOKIE[$key]);
}

function daysInMonth($month, $year=false) {
	global $_josh;
	if (!$year) $year = $_josh['year'];
	return date('d', mktime(0,0,0, $month + 1, 0, $year));
}

function debug() {
	global $_josh;
	$_josh['debug'] = true;
}

function geocode($address, $zip) {
	global $_josh;
	$result = file('http://maps.google.com/maps/geo?q=' . urlencode($address . ', ' . $zip) . '&output=csv&oe=utf8&sensor=false&key=' . $_josh['google']['mapkey']);
	if (list($status, $accuracy, $latitude, $longitude) = explode(',', utf8_decode($result[0]))) return array($latitude, $longitude);
	return false;
}

function language_translate($string, $from, $to) {
	global $_josh;
	
	//make sure there's something to translate
	if (empty($string)) return '';
	
	//unescape in case of post
	$string = str_replace("''", "'", $string);
	
	//todo figure out how to do this with POST since the limit is higher
	//todo figure out exactly what the limit is
	
	$chunks = array_chunk_html($string, 1450);
	$string = '';
	foreach ($chunks as $c) {
		error_debug('<b>lanuage_translate</b> running query for a string that is ' . strlen($c) . ' characters long', __file__, __line__);
	
		if (!isset($_josh['google_search_api_key'])) error_handle('api key not set', 'this script needs a google search api key');
		
		$ch = curl_init();
		$url = 'http://ajax.googleapis.com/ajax/services/language/translate?v=1.0&q=' . urlencode($c) . '&key=' . $_josh['google_search_api_key'] . '&langpair=' . $from . '%7C' . $to;
		
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_REFERER, $_josh['request']['url']);
		$body = curl_exec($ch);
		curl_close($ch);
		
		// now, process the JSON string
		$json = json_decode($body, true);
		if ($json['responseStatus'] != '200') error_handle('google translate bad result', 'the text string was ' . strlen($c) . '.  ' . draw_array($json));	
		$string .= $json['responseData']['translatedText'];
	}
	return format_quotes($string);
}

function lib_location($string) {
	global $_josh;
	
	//i'm thinking i'll start a lib.php file
	switch ($string) {
		case 'fpdf' :
		return $_josh['write_folder'] . '/lib/fpdf/fpdf-1.6.php';
		
		case 'prototype' :
		return $_josh['write_folder'] . '/lib/prototype/prototype-1.5.0.js';
		
		case 'scriptaculous' :
		return $_josh['write_folder'] . '/lib/scriptaculous/scriptaculous-1.6.5/scriptaculous.js';
		
		case 'simple_html_dom' :
		return $_josh['root'] . $_josh['write_folder'] . '/lib/simple_html_dom/simple_html_dom-1.11.php';

		case 'tinymce' :
		return $_josh['write_folder'] . '/lib/tinymce/tinymce-3.2.7/tiny_mce.js';
	}
}

function user($return=false) {
	//shortcut to say if a session exists and what the id is, or return $return
	if (empty($_SESSION['user_id'])) return $return;
	return $_SESSION['user_id'];
}

function var_name(&$iVar, &$aDefinedVars) {
	//get the name of the variable you are passing in
	//via http://mach13.com/how-to-get-a-variable-name-as-a-string-in-php
	//was considering it for auto-classing draw_list
	foreach ($aDefinedVars as $k=>$v) $aDefinedVars_0[$k] = $v;
	$iVarSave	= $iVar;
	$iVar		= !$iVar;
	$aDiffKeys	= array_keys(array_diff_assoc($aDefinedVars_0, $aDefinedVars));
	$iVar		= $iVarSave;
	return $aDiffKeys[0];
}

if ($_josh['debug']) error_debug('joshlib has finished loading and self-debugging in ' . format_time_exec(), __file__, __line__);
?>