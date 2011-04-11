<?php
/* 
WELCOME TO JOSHLIB!
	http://code.google.com/p/joshlib/ (wiki / documentation / report issues here)

LICENSE
	all files other than lib.zip are written by josh reisner and are available to the public under the terms of the LGPL
	
THIRD PARTY SOFTWARE
	included in lib.zip.  thank you so much to each of the contributors for these excellent packages
	
	~~TITLE~~~~~~~~~~~~~LANG~~~~LICENSE~VERSION~UPDATED~~~~~DEVELOPER~URL~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
	> codepress			js		LGPL						http://sourceforge.net/projects/codepress/
	> file_icons
	> fpdf				php									http://www.fpdf.org/
	> jquery			js		MIT		1.4.2	2010-08-09	http://jquery.com/
	> lorem_ipsum		js									http://develobert.blogspot.com/2007/11/automated-lorem-ipsum-generator.html
	> salesforce		php									http://developer.force.com/							
	> simple_html_dom	php		MIT							http://sourceforge.net/projects/simplehtmldom/
	> swiftmailer		php		LGPL	4.0.6	2010-08-09	http://swiftmailer.org/
	> tinymce			js		LGPL	3.3.8	2010-08-09	http://tinymce.moxiecode.com/

	JQUERY EXTENSIONS (in the jquery folder)
	> jeditable									2010-08-10	http://www.appelsiini.net/projects/jeditable
	> table drag and drop		LGPL	0.5		2010-08-09	http://www.isocra.com/2008/02/table-drag-and-drop-jquery-plugin/		

USING THE DEBUGGER
	you can run the debug() function after joshlib has been included to see output of various processes
	to debug the loading of the joshlib itself, set $_josh['mode'] = 'debug' before you include it

RUNNING ON THE COMMAND LINE
	joshlib depends on certain $_SERVER variables being present.  add these lines before including joshlib:
	$_SERVER['HTTP_HOST']		= 'backend.livingcities.org';
	$_SERVER['SCRIPT_NAME']		= '/salesforce/index.php';
	$_SERVER['DOCUMENT_ROOT']	= '/home/livingcities/www/backend';
	
GETTING STARTED
	note that error messages will not be thrown unless you specify this as a dev server.
	use a .dev or .site TLD when developing, like www.yoursite.dev
	
*/
define('TIME_START', microtime(true));	//start the processing time stopwatch -- use format_time_exec() to access this

//set up error handling.  this needs to go first to handle any subsequent errors
	error_reporting(E_ALL);
	ini_set('display_errors', true);
	ini_set('display_startup_errors', true);
	if (!isset($_josh['mode'])) $_josh['mode'] = 'live'; //assume live until we can parse the url
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
	$_josh['date']['format']		= '%b %d, %Y'; //default date Oct 06, 2010 http://php.net/manual/en/function.strftime.php
	$_josh['date']['strings']		= array('Yesterday', 'Today', 'Tomorrow');
	
//constants
	define('BR', '<br/>');
	define('TAB', "\t");
	$_josh['month']					= date('n');
	$_josh['today']					= date('j');
	$_josh['year']					= date('Y');
	$_josh['html']					= 4; //default
	
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
										'image-alt'=>'Image (Alt)',
										'int'=>'Integer',
										//'object'=>'Ordered Object',
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
	require(DIRECTORY_JOSHLIB . 'html.php');
	require(DIRECTORY_JOSHLIB . 'table.php');
	require(DIRECTORY_JOSHLIB . 'url.php');

//parse environment variables
	if (!isset($_SERVER['DOCUMENT_ROOT']) || !isset($_SERVER['HTTP_HOST']) || !isset($_SERVER['SCRIPT_NAME'])) error_handle('environment variables not set', 'joshlib requires $_SERVER[\'DOCUMENT_ROOT\'], $_SERVER[\'HTTP_HOST\'] and $_SERVER[\'SCRIPT_NAME\'] to function properly.  please define these before proceeding.', __file__, __line__);

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
			
	//die(draw_array($_josh['request']));

	//special set $_GET['id']
	if ($_josh['request']['id'] && !isset($_GET['id'])) $_GET['id'] = $_josh['request']['id'];
			
	//cwd
	define('DIRECTORY', dirname($_SERVER['SCRIPT_FILENAME']));

	//platform-specific info
	if (isset($_SERVER['SERVER_SOFTWARE']) && strstr($_SERVER['SERVER_SOFTWARE'], 'Microsoft')) {
		define('PLATFORM', 'win');
		define('NEWLINE', "\r\n");
		define('DIRECTORY_ROOT', str_replace($_josh['request']['path'], '', $_SERVER['PATH_TRANSLATED']));
		$_josh['slow']				= true;

		//IIS handles 404s differently than apache--not sure if this should go above the request-path
		if (!empty($_SERVER['QUERY_STRING']) && ($url = format_text_starts('404;', $_SERVER['QUERY_STRING']))) $_josh['request'] = url_parse(str_replace(':80', '', $url));

	} else { //platform is UNIX or Mac
		define('PLATFORM', 'unix');
		define('NEWLINE', "\n");
		define('DIRECTORY_ROOT', $_SERVER['DOCUMENT_ROOT']); //eg 
		if (!isset($_josh['slow']))	$_josh['slow'] = false;
	}
	
	//only checking for iphone right now
	$_josh['request']['mobile']		= (isset($_SERVER['HTTP_USER_AGENT']) && strstr($_SERVER['HTTP_USER_AGENT'], 'iPhone'));
 	$_josh['referrer']				= (isset($_SERVER['HTTP_REFERER']))	? url_parse($_SERVER['HTTP_REFERER']) : false;
	
//set defaults for configuration for variables
	if (!isset($_josh['db']['location']))	$_josh['db']['location']	= 'localhost';
	if (!isset($_josh['db']['language']))	$_josh['db']['language']	= 'mysql';
	if (!isset($_josh['db']['database']))	$_josh['db']['database']	= $_josh['request']['domainname'];
	if (!isset($_josh['db']['username']))	$_josh['db']['username']	= 'root';
	if (!isset($_josh['db']['password']))	$_josh['db']['password']	= '';
	if (!isset($_josh['basedblanguage']))	$_josh['basedblanguage']	= $_josh['db']['language'];
	if (!isset($_josh['is_secure']))		$_josh['is_secure']			= false;
	if (!isset($_josh['email_admin']))		$_josh['email_admin']		= false;
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
	if (!include(DIRECTORY_ROOT . $_josh['config'])) die('Could not create config file, please check the permissions on ' . DIRECTORY_ROOT . '.');
	
//check to make sure we're on the correct domain, might have read host variable from config file
	if (isset($_josh['host']) && ($_josh['host'] != $_josh['request']['host'])) url_change($_josh['request']['protocol'] . '://' . $_josh['host'] . $_josh['request']['path_query']);

//ensure lib exists--todo autogen lib folder when lib.zip has been updated
	if (!is_dir(DIRECTORY_ROOT . DIRECTORY_WRITE . DIRECTORY_SEPARATOR . 'lib')) file_unzip(DIRECTORY_JOSHLIB . 'lib.zip', DIRECTORY_WRITE);

//set error reporting level by determining whether this is a dev or live situation
	if (format_text_starts('dev-', $_josh['request']['host']) || format_text_ends('.site', $_josh['request']['host']) || format_text_ends('.dev', $_josh['request']['host'])) {
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
		$_josh['uploading'] = true;
		error_debug('uploading ' . count($_FILES) . ' files', __file__, __line__);
		while (list($key, $vars) = each($_FILES)) {
			error_debug('vars for this file were ' . draw_array($vars), __file__, __line__);
			//escape quotes in filename for db entry
			if (!empty($_FILES[$key]['name'])) $_FILES[$key]['name'] = format_quotes($_FILES[$key]['name']);
		}
	}
	$_josh['posting']	= !empty($_POST);
	if ($_josh['posting']) foreach($_POST as $key=>$value) $_POST[$key] = format_quotes(str_replace('& ', '&amp; ', $value));
	
	$_josh['editing']	= url_id();
	
//handle some ajax calls automatically -- requires user to be logged in
	if (user() && url_action('ajax_delete,ajax_publish,ajax_reorder,ajax_set,flushcache,db_check,db_fix,debug,indexes,lib_refresh,phpinfo')) {
		$array = array_ajax();
		
		//quick thing for sessions -- why do we need this?
		if (isset($array['id']) && ($array['id'] == 'session')) $array['id'] = user();
		
		switch ($_GET['action']) {
			case 'ajax_delete':
				//todo implement
				exit;
			case 'ajax_publish':
				//todo implement
				if ($array['checked'] == 'true') {
					db_query('UPDATE ' . $array['table'] . ' SET is_published = 1, publish_user = ' . user() . ', publish_date = ' . db_date() . ' WHERE id = ' . $array['id']);
					echo 'published';
				} elseif ($array['checked'] == 'false') {
					db_query('UPDATE ' . $array['table'] . ' SET is_published = 0, publish_user = NULL, publish_date = NULL WHERE id = ' . $array['id']);
					echo 'unpublished';
				}
				exit;
			case 'ajax_reorder':
				//db_query('UPDATE ' . $array['table'] . ' SET ' . $array['column'] . ' = NULL');
				if (!empty($array['table']) && !empty($array['column'])) {
					$counter = 1;
					foreach ($_REQUEST[$array['table']] as $value) {
						if ($value) {
							db_query('UPDATE ' . $array['table'] . ' SET ' . $array['column'] . ' = ' . $counter . ' WHERE id = ' . $value);
							$counter++;
						}
					}
					echo 'reordered ' . $counter . ' rows';
				}
				exit;
			case 'ajax_draw_select':
				//when would this happen?  kthxbai
				echo draw_form_select($array['name'], 'SELECT id, value FROM ' . $array['table'] . ' WHERE is_active = 1', $array['value'], $array['required']);
				exit;
			case 'ajax_set':
				if ($c = db_column($array['table'], $array['column'])) {
					if (($c['type'] == 'date') || ($c['type'] == 'datetime')) {
						$array['value'] = format_date($array['value'], 'NULL', 'SQL');
					//} elseif (($c['type'] == 'mediumblob')) {
						//if it's an image, be sure to clear the dynamic version (eg clearing img from CMS)
						//file_delete('/dynamic/' . $array['table'] . '-' . $array['column'] . '-' . $array['id'] . '.jpg');
					} elseif (empty($array['value']) && (!$c['required'])) {
						$array['value'] = 'NULL';
					} else {
						$array['value'] = '"' . $array['value'] . '"';
					}
					
					if (db_query('UPDATE ' . $array['table'] . ' SET ' . $array['column'] . ' = ' . $array['value'] . ', updated_date = NOW(), updated_user = ' . user() . ' WHERE id = ' . $array['id'])) {
						echo db_grab('SELECT ' . $array['column'] . ' FROM ' . $array['table'] . ' WHERE id = ' . $array['id']);
					} else {
						echo 'ERROR';
					}
				} else {
					//column does not exist
				}
				
				//special exception for #panel in cms on object list and form pages
				//if (($array['table'] == 'app_objects')) $array['value'] = str_replace("\n", '<br/>', $array['value']); //nl2br($array['value'])
				//echo (stristr($array['column'], 'date')) ? format_date($array['value']) : str_ireplace("\n", '<br/>', format_quotes($array['value'], true));

				exit;
			case 'db_check':
				$result = db_tables();
				//refactoring for php4
				$tables = array();
				foreach ($result as $r) {
					//debug();
					error_debug('looking at ' . $r, __file__, __line__);
					$lookingfor = $_josh['system_columns'];
					$columns = db_columns($r);
					foreach ($columns as $c) $lookingfor = array_remove($c['name'], $lookingfor);
					$t = array('name'=>$t);
					$t['missing']	= ($count = count($lookingfor)) ? $count : 0;
					$t['details']	= ($count) ? implode(' &amp; ', $lookingfor) : 'All Good';
					$t['fix']		= ($count) ? draw_link(url_query_add(array('action'=>'db_fix', 'table'=>$t['name']), false), ' FIX ') : '';
					$tables[] = $t;
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
			case 'indexes':
				db_words_refresh();
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

$_josh['finished_loading'] = true;
error_debug('joshlib finished loading in ' . format_time_exec(), __file__, __line__, '#def');


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

	if ($_josh['mode'] == 'debug') {
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

function daysInMonth($month=false, $year=false) {
	global $_josh;
	if (!$month) $month = $_josh['month'];
	if (!$year) $year = $_josh['year'];
	return date('d', mktime(0,0,0, $month + 1, 0, $year));
}

function debug() {
	global $_josh;
	$_josh['mode'] = 'debug';
}

function geocode($address, $zip) {
	global $_josh;
	$result = file('http://maps.google.com/maps/geo?q=' . urlencode($address . ', ' . $zip) . '&output=csv&oe=utf8&sensor=false&key=' . $_josh['google']['mapkey']);
	if (list($status, $accuracy, $latitude, $longitude) = explode(',', utf8_decode($result[0]))) return array($latitude, $longitude);
	return false;
}

function home($match='/') {
	global $_josh;
	return ($_josh['request']['path'] == $match);
}

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

function increment() {
	global $_josh;
	if (!isset($_josh['increment'])) $_josh['increment'] = 0;
	$_josh['increment']++;
	return $_josh['increment'];
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
	
		if (!isset($_josh['google_search_api_key'])) error_handle('api key not set', __function__ . ' needs a ' . draw_link('http://code.google.com/apis/ajaxsearch/signup.html', 'Google AJAX search API key'), __file__, __line__);
		
		$ch = curl_init();
		$url = 'http://ajax.googleapis.com/ajax/services/language/translate?v=1.0&q=' . urlencode($c) . '&key=' . $_josh['google_search_api_key'] . '&langpair=' . $from . '%7C' . $to;
		
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_REFERER, $_josh['request']['url']);
		$body = curl_exec($ch);
		curl_close($ch);
		
		// now, process the JSON string
		$json = json_decode($body, true);
		if ($json['responseStatus'] != '200') error_handle('google translate bad result', 'the text string length was ' . strlen($c) . BR . BR . $c . BR . BR . '.  json was ' . $json . draw_array($json), __file__, __line__);
		$string .= $json['responseData']['translatedText'];
	}
	return format_quotes($string);
}

function lib_get($string) {
	global $_josh;
	
	switch ($string) {
		//php libraries
		case 'dBug' :
		case 'fpdf' :
		case 'salesforce' :
		case 'simple_html_dom' :
		case 'swiftmailer' :
		return include_once(lib_location($string));
		
		//javascript libraries
		case 'fancybox' :
		case 'innershiv' :
		case 'jeditable' :
		case 'jquery' :
		case 'jquery-latest' :
		case 'jscrollpane' :
		case 'lorem_ipsum' :
		case 'modernizr' :
		case 'tablednd' :
		case 'tinymce' :
		if (isset($_josh['drawn'][$string])) return false;
		
		$_josh['drawn'][$string] = true;
		$return = draw_javascript_src(lib_location($string));

		if ($string == 'tinymce') {
			//special statements for tinymce
			$return = lib_get('jquery') . draw_javascript('
				$(function(){
					$("textarea.tinymce").each(function(){
						$(this).tinymce({
							// Location of TinyMCE script
							script_url : "' . str_replace('jquery.tinymce.js', 'tiny_mce.js', lib_location('tinymce')) . '",
					
							//legacy code
							theme : "advanced",
							
							theme_advanced_buttons1 : "' . (user() ? 
								'styleselect,|,bold,italic,underline,strikethrough,|,justifyleft,justifycenter,blockquote,|,bullist,numlist,outdent,indent,|,link,unlink,image,|,code' : 
								'bold,italic,underline,strikethrough,|,justifyleft,justifycenter,blockquote,|,bullist,numlist,|,link,unlink') . '",
							theme_advanced_buttons2 : "",
							theme_advanced_resizing : true,		
							theme_advanced_toolbar_location : "top",
							
							valid_elements : "@[id|class|style|title|dir<ltr?rtl|lang|xml::lang|onclick|ondblclick|"
								+ "onmousedown|onmouseup|onmouseover|onmousemove|onmouseout|onkeypress|"
								+ "onkeydown|onkeyup],a[rel|rev|charset|hreflang|tabindex|accesskey|type|"
								+ "name|href|target|title|class|onfocus|onblur],strong/b,em/i,strike,u,"
								+ "#p,-ol[type|compact],-ul[type|compact],-li,br,img[longdesc|usemap|"
								+ "src|border|alt=|title|hspace|vspace|width|height|align],-sub,-sup,"
								+ "-blockquote,-table[border=0|cellspacing|cellpadding|width|frame|rules|"
								+ "height|align|summary|bgcolor|background|bordercolor],-tr[rowspan|width|"
								+ "height|align|valign|bgcolor|background|bordercolor],tbody,thead,tfoot,"
								+ "#td[colspan|rowspan|width|height|align|valign|bgcolor|background|bordercolor"
								+ "|scope],#th[colspan|rowspan|width|height|align|valign|scope],caption,-div,"
								+ "-span,-code,-pre,address,-h1,-h2,-h3,-h4,-h5,-h6,hr[size|noshade],-font[face"
								+ "|size|color],dd,dl,dt,cite,abbr,acronym,del[datetime|cite],ins[datetime|cite],"
								+ "object[classid|width|height|codebase|*],param[name|value|_value],embed[type|width"
								+ "|height|src|*],script[src|type],map[name],area[shape|coords|href|alt|target],bdo,"
								+ "button,col[align|char|charoff|span|valign|width],colgroup[align|char|charoff|span|"
								+ "valign|width],dfn,fieldset,form[action|accept|accept-charset|enctype|method],"
								+ "input[accept|alt|checked|disabled|maxlength|name|readonly|size|src|type|value],"
								+ "kbd,label[for],legend,noscript,optgroup[label|disabled],option[disabled|label|selected|value],"
								+ "q[cite],samp,select[disabled|multiple|name|size],small,"
								+ "textarea[cols|rows|disabled|name|readonly],tt,var,big",
	
							extended_valid_elements : "a[href|target|rel|name],img[class|src|border=0|alt|title|hspace|vspace|width|height|align|style],dir,hr[class|width|size|noshade],font[face|size|color|style],span[align|class|style],p[align|class|style],table[cellspacing,align,border,cellpadding,class],tr[class],td[width,align,class]",
							content_css : "/styles/tinymce.css?" + new Date().getTime(),
							plugins : "' . (user() ? 'imagemanager,filemanager,' : '') . 'paste",
							
							
							//remove shortcuts
							custom_shortcuts : 0,
							
							setup : function(ed) {
								//ed.addShortcut("ctrl+1", "Some description", function() { alert("Whee!"); });
					        },

							
							relative_urls : false,
							remove_script_host : false /*we need this again for lc backend */
					
							/* new stuff in this version
							// Drop lists for link/image/media/template dialogs
							template_external_list_url : "lists/template_list.js",
							external_link_list_url : "lists/link_list.js",
							external_image_list_url : "lists/image_list.js",
							media_external_list_url : "lists/media_list.js",
					
							// Replace values for the template plugin
							template_replace_values : {
								username : "Some User",
								staffid : "991234"
							}*/
						});
						//tinyMCE.get($(this).attr("id")).addShortcut("ctrl+alt+shift+o", "Some description", function() { alert("Whee!"); });
					});
					
					

				});
			') . $return;
			file_dir_writable('images');
			file_dir_writable('files');
			//$return .= draw_javascript('form_tinymce_init("/styles/tinymce.css", ' . (user() ? 'true' : 'false') . ')');
		} elseif (($string == 'tablednd') || ($string == 'jeditable') || ($string == 'fancybox') || ($string == 'jscrollpane')) {
			$return = lib_get('jquery') . $return;
			if ($string == 'fancybox') {
				$return .= draw_javascript_src(DIRECTORY_WRITE . '/lib/jquery/fancybox/jquery.mousewheel-3.0.2.pack.js') . 
					draw_css_src(DIRECTORY_WRITE . '/lib/jquery/fancybox/jquery.fancybox-1.3.1.css');
			} elseif ($string == 'jscrollpane') {
				$return = draw_css_src(DIRECTORY_WRITE . '/lib/jquery/jscrollpane/jquery.jscrollpane.css') . 
					$return . 
					draw_javascript_src(DIRECTORY_WRITE . '/lib/jquery/jscrollpane/jquery.mousewheel.js');
			}
		}
		
		return $return;

		//deprecated
		case 'prototype' :
		case 'scriptaculous' :
		return error_handle('Prototype and Scriptaculous are deprecated', 'As of August 9th 2010, jQuery is the official Javascript framework of Joshlib', __file__, __line__);
	}
}

function lib_location($string) {

	$lib = DIRECTORY_WRITE . '/lib/' . $string . '/';

	switch ($string) {
		case 'dBug' : 
		return DIRECTORY_ROOT . $lib . 'dBug.php';
		
		case 'fancybox' : 
		return DIRECTORY_WRITE . '/lib/jquery/fancybox/jquery.fancybox-1.3.1.js';
		
		case 'fpdf' :
		return DIRECTORY_ROOT . $lib . 'fpdf-1.6.php';
		
		case 'innershiv' :
		return $lib . 'innershiv.min.js';
		
		case 'jeditable' :
		return DIRECTORY_WRITE . '/lib/jquery/jquery.jeditable.mini.js';

		case 'jquery' :
		return $lib . 'jquery-1.5.min.js';
		
		case 'jquery-hosted' :
		return 'http://code.jquery.com/jquery-1.5.min.js';
		
		case 'jscrollpane' :
		return DIRECTORY_WRITE . '/lib/jquery/jscrollpane/jquery.jscrollpane.min.js';

		case 'lorem_ipsum' :
		return $lib . 'lorem_ipsum.js';
				
		case 'modernizr' :
		return $lib . 'modernizr-1.7.min.js';
				
		case 'salesforce' :
		return DIRECTORY_ROOT . $lib . 'phptoolkit-13_1/soapclient/SforceEnterpriseClient.php';
		
		case 'simple_html_dom' :
		return DIRECTORY_ROOT . $lib . 'simple_html_dom-1.11.php';

		case 'swiftmailer' :
		return DIRECTORY_ROOT . $lib . 'swift_required.php';
		
		case 'tablednd' :
		return DIRECTORY_WRITE . '/lib/jquery/jquery.tablednd_0_5.js';
		
		case 'tinymce' :
		return $lib . 'tinymce_3_3_9/jquery.tinymce.js';
	}
}

function lib_refresh() {
	//this doesn't work yet, unfortunately
	file_delete(DIRECTORY_WRITE . '/lib');
	exit;
}

function max_num($number=false) {
	global $_josh;
	//method for getting max in a loop
	if (!isset($_josh['max'])) $_josh['max'] = 0;
	if (!$number) {
		error_debug('<b>' . __function__ . '</b> getting max, which is ' . $_josh['max'], __file__, __line__);
		return $_josh['max'];
	} elseif (is_numeric($number) && ($number > $_josh['max'])) {
		error_debug('<b>' . __function__ . '</b> setting max for ' . $number, __file__, __line__);
		$_josh['max'] = $number;
	}
}

function posting($form_id=false) {
	//tell if POST variables are present, and if specified, whether it's a particular form
	if (!$form_id) return (!empty($_POST));
	if (!isset($_POST['form_id'])) return false;
	return ($_POST['form_id'] == $form_id);
}

function user($return=false) {
	//shortcut to say if a session exists and what the id is, or return $return eg NULL for sql
	if (!isset($_SESSION)) session_start();
	if (empty($_SESSION['user_id'])) return $return;
	return $_SESSION['user_id'];
}

/* deprecating for php4 support
function var_name(-$var, -$defined_vars) {
	//replacing apersands with hyphens
	//-$defined_vars should be get_defined_vars()
	//adapted from http://mach13.com/how-to-get-a-variable-name-as-a-string-in-php (thank you)
    foreach ($defined_vars as $key=>$value) $defined_vars_0[$key] = $value;
    $save		= $var;
    $var		= !$var;
    $diff_keys	= array_keys(array_diff_assoc($defined_vars_0, $defined_vars));
    $var		= $save;
    return $diff_keys[0];
}
*/
?>