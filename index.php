<?php
/* 
WELCOME TO JOSHLIB!
	http://code.google.com/p/joshlib/ (wiki / documentation / report issues here)

LICENSE
	all files other than lib.zip are written by josh reisner and are available to the public under the terms of the LGPL
	
THIRD PARTY SOFTWARE
	included in lib.zip.  thank you so much to each of the contributors for these excellent packages
	
	~~TITLE~~~~~~~~~~~~~LANG~~~~DEVELOPER~URL~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~LICENSE~~~~~~~~~~~~~~~~~~~~
	> ckeditor			js		http://ckeditor.com/								GPL, LGPL and MPL
	> codepress			js		http://sourceforge.net/projects/codepress/			LGPL
	> fpdf				php		http://www.fpdf.org/								
	> lightbox2			js		http://www.lokeshdhakar.com/projects/lightbox2/		CC Attribution 2.5
	> lorem_ipsum		js		http://tinyurl.com/yjrmlcy
	> prototype			js		http://prototypejs.org/								MIT
	> salesforce		php		http://developer.force.com/							
	> sasl				php		http://www.phpclasses.org/browse/package/1888.html	BSD
	> scriptaculous		js		http://script.aculo.us/								MIT
	> simple_html_dom	php		http://sourceforge.net/projects/simplehtmldom/		MIT
	> smtp				php		http://www.phpclasses.org/browse/package/14.html	BSD
	> tinymce			js		http://tinymce.moxiecode.com/						LGPL

USING THE DEBUGGER
	you can run the debug() function after joshlib has been included to see output of various processes
	to debug the loading of the joshlib itself, set $_josh['debug'] = true before you include it

RUNNING ON THE CLI
	joshlib depends on certain $SERVER variables being present.  add these lines before including joshlib:
	$_SERVER['HTTP_HOST']		= 'backend.livingcities.org';
	$_SERVER['SCRIPT_NAME']		= '/salesforce/index.php';
	$_SERVER['DOCUMENT_ROOT']	= '/home/livingcities/www/backend';
	
*/
define('TIME_START', microtime(true));	//start the processing time stopwatch -- use format_time_exec() to access this

//set up error handling.  this needs to go first to handle any subsequent errors
	error_reporting(E_ALL);
	ini_set('display_errors', true);
	ini_set('display_startup_errors', true);
	if (!isset($_josh['debug'])) $_josh['debug'] = false;
	define('DIRECTORY_JOSHLIB', dirname(__file__) . DIRECTORY_SEPARATOR);
	require(DIRECTORY_JOSHLIB . 'error.php');
	set_error_handler('error_handle_php');
	set_exception_handler('error_handle_exception');
	error_debug('<b>index</b> error handling is set up', __file__, __line__);
	
//strings
	date_default_timezone_set('America/New_York');
	$_josh['days']					= array('Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday');
	$_josh['months']				= array('January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December');
	$_josh['mos']					= array('Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec');
	$_josh['numbers']				= array('zero','one','two','three','four','five','six','seven','eight','nine');
	$_josh['date']['strings']		= array('Yesterday', 'Today', 'Tomorrow');

//constants
	define('TAB', "\t");
	$_josh['month']					= date('n');
	$_josh['today']					= date('j');
	$_josh['year']					= date('Y');

//page draw status
	$_josh['drawn']					= array();	//array for including javascript only once, eg $_josh['drawn']['tinymce'] = true;

//ignore these words when making search indexes | todo make this local to search function
	$_josh['ignored_words']			= array('1','2','3','4','5','6','7','8','9','0','about','after','all','also','an','and','another','any','are',
										'as','at','be','because','been','before','being','between','both','but','by','came','can','come',
										'could','did','do','does','each','else','for','from','get','got','has','had','he','have','her','here',
										'him','himself','his','how','if','in','into','is','it','its','just','like','make','many','me','might',
										'more','most','much','must','my','never','now','of','on','only','or','other','our','out','over','re',
										'said','same','see','should','since','so','some','still','such','take','than','that','the','their',
										'them','then','there','these','they','this','those','through','to','too','under','up','use','very',
										'want','was','way','we','well','were','what','when','where','which','while','who','will','with',
										'would','you','your','a','b','c','d','e','f','g','h','i','j','k','l','m','n','o','p','q','r','s',
										't','u','v','w','x','y','z',''
									);
	
//used by cms, db_column_add, and form::set_field, db_save
	$_josh['field_types']			= array(
										'checkbox'=>'Checkbox',
										'checkboxes'=>'Checkboxes',
										'date'=>'Date',
										'datetime'=>'Date & Time',
										'select'=>'Dropdown',
										'file'=>'File',
										'file-type'=>'File (Type)',
										'image'=>'Image',
										'int'=>'Integer',
										'image-alt'=>'Image (Alt)',
										'text'=>'Text',
										'textarea'=>'Textarea',
										'textarea-plain'=>'Textarea (Plain)',
										'url'=>'URL'
									);
	$_josh['system_columns']		= array('id', 'created_date', 'created_user', 'updated_date', 'updated_user', 'publish_date', 'publish_user', 'is_published', 'deleted_date', 'deleted_user', 'is_active', 'precedence');

//get the rest of the library
	require(DIRECTORY_JOSHLIB . 'array.php');
	require(DIRECTORY_JOSHLIB . 'cache.php');
	require(DIRECTORY_JOSHLIB . 'db.php');
	require(DIRECTORY_JOSHLIB . 'draw.php');
	require(DIRECTORY_JOSHLIB . 'email.php');
	require(DIRECTORY_JOSHLIB . 'file.php');
	require(DIRECTORY_JOSHLIB . 'form.php');
	require(DIRECTORY_JOSHLIB . 'format.php');
	require(DIRECTORY_JOSHLIB . 'table.php');
	require(DIRECTORY_JOSHLIB . 'url.php');

//parse environment variables
	if (!isset($_SERVER['DOCUMENT_ROOT']) || !isset($_SERVER['HTTP_HOST']) || !isset($_SERVER['SCRIPT_NAME'])) error_handle('environment variables not set', 'joshlib requires $_SERVER[\'DOCUMENT_ROOT\'], $_SERVER[\'HTTP_HOST\'] and $_SERVER[\'SCRIPT_NAME\'] to function properly.  please define these before proceeding.');

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
	if (isset($_SERVER['SERVER_SOFTWARE']) && strstr($_SERVER['SERVER_SOFTWARE'], 'Microsoft')) {
		define('PLATFORM', 'win');
		define('NEWLINE', "\r\n");
		define('DIRECTORY_ROOT', str_replace($_josh['request']['path'], '', $_SERVER['PATH_TRANSLATED']));
		$_josh['slow']				= true;
	} else { //platform is UNIX or Mac
		define('PLATFORM', 'unix');
		define('NEWLINE', "\n");
		define('DIRECTORY_ROOT', $_SERVER['DOCUMENT_ROOT']);
		if (!isset($_josh['slow']))	$_josh['slow'] = false;
	}
	
	//only checking for iphone right now
	$_josh['request']['mobile']		= (isset($_SERVER['HTTP_USER_AGENT']) && strstr($_SERVER['HTTP_USER_AGENT'], 'iPhone'));
 	$_josh['referrer']				= (isset($_SERVER['HTTP_REFERER']))	? url_parse($_SERVER['HTTP_REFERER']) : false;
	
//set defaults for configuration for variables
	if (!isset($_josh['db']['location']))	$_josh['db']['location']	= 'localhost';
	if (!isset($_josh['db']['language']))	$_josh['db']['language']	= 'mysql';
	if (!isset($_josh['db']['database']))	$_josh['db']['database']	= $_josh['request']['domainname'];
	if (!isset($_josh['db']['username']))	$_josh['db']['username']	= '';
	if (!isset($_josh['db']['password']))	$_josh['db']['password']	= '';
	if (!isset($_josh['basedblanguage']))	$_josh['basedblanguage']	= $_josh['db']['language'];
	if (!isset($_josh['is_secure']))		$_josh['is_secure']			= false;
	if (!isset($_josh['email_admin']))		$_josh['email_admin']		= 'josh@joshreisner.com';
	if (!isset($_josh['email_default']))	$_josh['email_default']		= ((empty($_josh['request']['subdomain'])) ? 'www' : $_josh['request']['subdomain']) . '@' . $_josh['request']['domain'];
	if (!isset($_josh['error_log_api']))	$_josh['error_log_api']		= false;
		
//get configuration variables
	if (!defined('DIRECTORY_WRITE')) define('DIRECTORY_WRITE', '/_' . $_josh['request']['sanswww']);
	if (!isset($_josh['config'])) $_josh['config'] = DIRECTORY_WRITE . DIRECTORY_SEPARATOR . 'config.php'; //eg /_example.com/config.php
	if (!file_check($_josh['config'])) {
		//if config file doesn't exist, create one
		error_debug('couldn\'t find config file, attempting to create one', __file__, __line__);
		file_dir_writable();
		file_put_config();
	}
	require(DIRECTORY_ROOT . $_josh['config']);
	
//ensure lib exists--todo autogen lib folder when lib.zip has been updated
	if (!is_dir(DIRECTORY_ROOT . DIRECTORY_WRITE . DIRECTORY_SEPARATOR . 'lib')) file_unzip(DIRECTORY_JOSHLIB . DIRECTORY_SEPARATOR . 'lib.zip', DIRECTORY_WRITE);

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
	if (user() && url_action('ajax_delete,ajax_publish,ajax_reorder,ajax_set,flushcache,db_check,db_fix,debug,lib_refresh,phpinfo')) {
		$array = array_ajax();
		
		//quick thing for sessions -- why do we need this?
		if (isset($array['id']) && ($array['id'] == 'session')) $array['id'] = user();
		
		switch ($_GET['action']) {
			case 'ajax_delete':
				//todo implement
				exit;
			case 'ajax_publish':
				//todo implement
				if ($array['checked']) {
					db_query('UPDATE ' . $array['table'] . ' SET is_published = 1, publish_user = ' . user() . ', publish_date = ' . db_date() . ' WHERE id = ' . $array['id']);
				} else {
					db_query('UPDATE ' . $array['table'] . ' SET is_published = 0, publish_user = NULL, publish_date = NULL WHERE id = ' . $array['id']);
				}
				exit;
			case 'ajax_reorder':
				db_query('UPDATE ' . $array['table'] . ' SET ' . $array['column'] . ' = NULL');
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
			case 'db_check':
				$tables = db_tables();
				foreach ($tables as &$t) {
					//debug();
					error_debug('looking at ' . $t, __file__, __line__);
					$lookingfor = $_josh['system_columns'];
					$columns = db_columns($t);
					foreach ($columns as $c) $lookingfor = array_remove($c['name'], $lookingfor);
					$t = array('name'=>$t);
					$t['missing']	= ($count = count($lookingfor)) ? $count : 0;
					$t['details']	= ($count) ? implode(' &amp; ', $lookingfor) : 'All Good';
					$t['fix']		= ($count) ? draw_link(url_query_add(array('action'=>'db_fix', 'table'=>$t['name']), false), ' FIX ') : '';
				}
				echo draw_table($tables, 'name', true);
				echo draw_link(url_action_add(false), 'Exit');
				exit;
			case 'db_fix':
				$lookingfor = $_josh['system_columns'];
				$columns = db_columns($_GET['table']);
				foreach ($columns as $c) $lookingfor = array_remove($c['name'], $lookingfor);
				foreach ($lookingfor as $l) {
					$type = false;
					if (($l == 'created_date') || ($l == 'updated_date') || ($l == 'deleted_date') || ($l == 'publish_date')) {
						$type = 'datetime';
					} elseif (($l == 'created_user') || ($l == 'updated_user') || ($l == 'deleted_user') || ($l == 'publish_user') || ($l == 'precedence')) {
						$type = 'int';
					} elseif (($l == 'is_published') || ($l == 'is_active')) {
						$type = 'checkbox';
					}
					
					//todo handle id
					
					if ($type) db_column_add($_GET['table'], $l, $type);
					
					//todo handle deprecated names such as createdOn or updatedOn etc
				}
				//echo 'ok!';
				url_query_add(array('action'=>'db_check', 'table'=>false));
				exit;
			case 'debug':
				debug();
				break;
			case 'flushcache':
				cache_clear();
				echo 'caches cleared';
				exit;
			case 'lib_refresh':
				lib_refresh();
				url_drop('action');
				exit;
			case 'phpinfo':
				phpinfo();
				exit;
		}
	}

//special functions that don't yet fit into a category

function admin() {
	//shortcut to say if a session has the is_admin bit set	
	return (isset($_SESSION['is_admin']) && $_SESSION['is_admin']);
}

function browser_output($html) {
	//todo rename url.php to http.php and make this http_output 
	//one easy way to employ this is with ob_start('browser_output')
    if (!in_array('gzip', array_separated($_SERVER['HTTP_ACCEPT_ENCODING'])) || headers_sent()) return $html;
    header('Content-Encoding: gzip');
    return gzencode($html);
}

function cookie($name=false, $value=false, $session=false) {
	global $_josh;

	//in order for the cookie to take, there needs to be page output.  don't allow fast redirecting.
	$_josh['slow'] = true;

	if ($_josh['debug']) {
		error_debug('not actually setting cookie (' . $name . ', ' . $value . ') because buffer is already broken by debugger.', __file__, __line__);
	} elseif ($name) {
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

function lib_get($string) {
	global $_josh;
	
	switch ($string) {
		//php libraries
		case 'fpdf' :
		case 'salesforce' :
		case 'sasl' :
		case 'simple_html_dom' :
		case 'smtp' :
		return include_once(lib_location($string));
		
		//javascript libraries
		case 'ckeditor' :
		case 'lorem_ipsum' :
		case 'prototype' :
		case 'scriptaculous' :
		case 'tinymce' :
		if (isset($_josh['drawn'][$string])) return false;
		
		$_josh['drawn'][$string] = true;
		$return = draw_javascript_src(lib_location($string));
		
		if ($string == 'tinymce') {
			//special statements for tinymce
			file_dir_writable('images');
			file_dir_writable('files');
			$return .= draw_javascript_src(lib_location('tinymce')) . draw_javascript('form_tinymce_init("/styles/tinymce.css", ' . (user() ? 'true' : 'false') . ')');
		}
		
		return $return;
	}
}

function lib_location($string) {
	switch ($string) {
		case 'ckeditor' :
		return DIRECTORY_WRITE . '/lib/ckeditor/ckeditor.js';
		
		case 'fpdf' :
		return DIRECTORY_ROOT . DIRECTORY_WRITE . '/lib/fpdf/fpdf-1.6.php';
		
		case 'lorem_ipsum' :
		return DIRECTORY_WRITE . '/lib/lorem_ipsum/lorem_ipsum.js';
		
		case 'prototype' :
		return DIRECTORY_WRITE . '/lib/prototype/prototype-1.5.0.js';
		
		case 'sasl' :
		return DIRECTORY_ROOT . DIRECTORY_WRITE . '/lib/sasl/sasl-2005-10-31/sasl.php';
		
		case 'scriptaculous' :
		return DIRECTORY_WRITE . '/lib/scriptaculous/scriptaculous-1.6.5/scriptaculous.js';

		case 'salesforce' :
		return DIRECTORY_ROOT . DIRECTORY_WRITE . '/lib/salesforce/phptoolkit-13_1/soapclient/SforceEnterpriseClient.php';
		
		case 'simple_html_dom' :
		return DIRECTORY_ROOT . DIRECTORY_WRITE . '/lib/simple_html_dom/simple_html_dom-1.11.php';

		case 'smtp' :
		return DIRECTORY_ROOT . DIRECTORY_WRITE . '/lib/smtp/smtpclass-2009-04-11/smtp.php';
		
		case 'tinymce' :
		return DIRECTORY_WRITE . '/lib/tinymce/tinymce-3.3rc1/tiny_mce.js';
	}
}

function lib_refresh() {
	//this doesn't work yet, unfortunately
	file_delete(DIRECTORY_WRITE . '/lib');
	exit;
}

function user($return=false) {
	//shortcut to say if a session exists and what the id is, or return $return eg NULL for sql
	if (empty($_SESSION['user_id'])) return $return;
	return $_SESSION['user_id'];
}

if ($_josh['debug']) error_debug('joshlib finished loading and self-debugging in ' . format_time_exec(), __file__, __line__, '#def');
?>