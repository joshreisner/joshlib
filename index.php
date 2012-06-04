<?php
//start the processing time stopwatch -- use format_time_exec() to access this
	define('TIME_START', microtime(true));

//set up error handling.  this needs to go first to handle any subsequent errors
	error_reporting(E_ALL); //this gets turned off on live sites below
	ini_set('display_errors', true);
	ini_set('display_startup_errors', true);
	if (!isset($_josh['mode'])) $_josh['mode'] = 'live'; //assume live until we can parse the url
	define('DIRECTORY_JOSHLIB', dirname(__file__) . DIRECTORY_SEPARATOR);
	require(DIRECTORY_JOSHLIB . 'error.php');
	set_error_handler('error_handle_php', E_ALL);
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
	$_josh['error_mode_html']		= true; //show errors in html (set to false for ajax or cli)
	$_josh['path_imagemagick']		= '/usr/local/bin/'; // it's /opt/local/bin/ on a mac, specify that in your config
	
//constants
	define('BR', '<br/>');
	define('TAB', "\t");
	$_josh['month']					= date('n');
	$_josh['today']					= date('j');
	$_josh['year']					= date('Y');
	$_josh['html']					= 4; //default
	
//page draw status
	$_josh['drawn']					= array();	//array for including javascript only once, eg $_josh['drawn']['tinymce'] = true;

//template paths
  $_josh['haml_path'] = $_SERVER['DOCUMENT_ROOT'] . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR;
  
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
										'color'=>'Color',
										'date'=>'Date',
										'datetime'=>'Date & Time',
										'email'=>'Email',
										'select'=>'Dropdown',
										'file'=>'File',
										'file-size'=>'File Size',
										'file-type'=>'File Type',
										'image'=>'Image',
										'image-alt'=>'Image (Alt)',
										'int'=>'Integer',
										'latlon'=>'Lat/Lon Coords',
										'text'=>'Text',
										'textarea'=>'Textarea',
										'textarea-plain'=>'Textarea (Plain)',
										'url'=>'URL',
										'url-local'=>'URL (local)'
									);
	$_josh['system_columns']		= array('id', 'created_date', 'created_user', 'updated_date', 'updated_user', 'publish_date', 'publish_user', 'is_published', 'deleted_date', 'deleted_user', 'is_active', 'precedence', 'subsequence');

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
			
	//set error reporting level by determining whether this is a dev or live situation
	if ($_josh['mode'] != 'debug') {
		if (in_array($_josh['request']['tld'], array('dev', 'diego', 'john', 'josh', 'localhost', 'michael', 'site'))) {
			$_josh['mode'] = 'dev';
		} else {
			$_josh['mode'] = 'live';
			error_reporting(0);
		}
	}

	//special set $_GET['id'] - todo deprecate?
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
	
	//only checking for iOS
	$_josh['request']['mobile']		= (isset($_SERVER['HTTP_USER_AGENT']) && (strstr($_SERVER['HTTP_USER_AGENT'], 'iPhone') || strstr($_SERVER['HTTP_USER_AGENT'], 'iPad')));
 	
 	//referring page
 	$_josh['referrer']				= (isset($_SERVER['HTTP_REFERER']))	? url_parse($_SERVER['HTTP_REFERER']) : false;
	
//set defaults for configuration for variables
	if (!isset($_josh['app_name']))			$_josh['app_name']			= 'Untitled Application';
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
		
//establish write directory
	if (!defined('DIRECTORY_WRITE')) define('DIRECTORY_WRITE', '/_' . $_josh['request']['sanswww']);
	if (!defined('DIRECTORY_LIB')) define('DIRECTORY_LIB', DIRECTORY_ROOT . DIRECTORY_WRITE . DIRECTORY_SEPARATOR . 'lib');
	if (!is_dir(DIRECTORY_ROOT . DIRECTORY_WRITE)) file_dir_writable();

//get configuration variables from config file
	if (!isset($_josh['config'])) $_josh['config'] = DIRECTORY_WRITE . DIRECTORY_SEPARATOR . 'config.php'; //eg /_example.com/config.php
	if (!file_check($_josh['config'])) {
		//if config file doesn't exist, create one
		error_debug('couldn\'t find config file, attempting to create one', __file__, __line__);
		file_put_config();
	}
	if (!include(DIRECTORY_ROOT . $_josh['config'])) die('Could not create config file, please check the permissions on ' . DIRECTORY_ROOT . '.');
	
//check to make sure we're on the correct domain, might have read host variable from config file
	if (isset($_josh['host']) && ($_josh['host'] != $_josh['request']['host'])) url_change($_josh['request']['protocol'] . '://' . $_josh['host'] . $_josh['request']['path_query']);

//ensure lib exists--todo autodelete these old lib folders
	if (is_dir(DIRECTORY_LIB) && (filemtime(DIRECTORY_JOSHLIB . 'lib.zip') > filemtime(DIRECTORY_LIB))) {
		error_debug('going to rename ' . DIRECTORY_LIB . ' because it is older (' . format_date_time(filemtime(DIRECTORY_LIB)) . ') than lib.zip (' . format_date_time(filemtime(DIRECTORY_JOSHLIB . 'lib.zip')) . ').', __file__, __line__);	
		rename(DIRECTORY_LIB, DIRECTORY_LIB . '-old-' . time());
	}
	if (!is_dir(DIRECTORY_LIB) && !file_unzip(DIRECTORY_JOSHLIB . 'lib.zip', DIRECTORY_WRITE, true)) {
		error_handle('Could not unzip library.  Please manually unzip it.', __file__, __line__);
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
	
	$_josh['editing']	= url_id(); //necessary?
	
//handle some ajax calls automatically -- requires user to be logged in
	if (url_action('ajax_delete,ajax_publish,ajax_reorder,ajax_set,flushcache,db_check,db_fix,debug,indexes,lib_refresh,phpinfo')) {
		if (!user()) die('user not logged in ' . SESSION_USER_ID);
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
					} elseif (empty($array['value']) && (!$c['required'])) {
						$array['value'] = 'NULL';
					} else {
						$array['value'] = "'" . $array['value'] . "'";
					}
					if (db_query('UPDATE ' . $array['table'] . ' SET ' . $array['column'] . ' = ' . $array['value'] . ', updated_date = NOW(), updated_user = ' . user() . ' WHERE id = ' . $array['id'])) {
						echo db_grab('SELECT ' . $array['column'] . ' FROM ' . $array['table'] . ' WHERE id = ' . $array['id']);
					} else {
						echo 'ERROR';
					}
					//delete from write_folder?
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

	if (url_action('file_download') && url_id('file_id')) {
		if (!isset($_GET['file_table']) || !isset($_GET['file_id']) || !db_table_exists($_GET['file_table'])) exit; //table not defined or does not exist
		if (!isset($_GET['col_title']))	$_GET['col_title']	= 'title';
		if (!isset($_GET['col_file']))	$_GET['col_file']	= 'file';
		if (!isset($_GET['col_type']))	$_GET['col_type']	= 'type';
		if ($file = db_grab('SELECT ' . $_GET['col_title'] . ' title, ' . $_GET['col_file'] . ' file, ' . $_GET['col_type'] . ' type FROM ' . $_GET['file_table'] . ' WHERE is_active = 1 AND id = ' . $_GET['file_id'])) {
			file_download($file['file'], $file['title'], $file['type']);
		} else {
			echo 'could not get file';
		}
		exit;
	}

	$_josh['finished_loading'] = true;
	error_debug('joshlib finished loading in ' . format_time_exec(), __file__, __line__, '#def');


//special (misc) functions that don't yet fit into a category

function _div($args=false, $content='') {
	$args = array_arguments($args);
	return draw_tag('div', $args, $content);
}

function _h1($args=false, $content='') {
	$args = array_arguments($args);
	return draw_tag('h1', $args, $content);
}

function admin($key='is_admin') {
	//shortcut to say if a session has the is_admin bit set	
	return (isset($_SESSION[$key]) && $_SESSION[$key]);
}

function browser_output($html) {
	//todo rename url.php to http.php and make this http_output 
	//one easy way to employ this is with ob_start('browser_output')
    if (!in_array('gzip', array_separated($_SERVER['HTTP_ACCEPT_ENCODING'])) || headers_sent()) return $html;
    header('Content-Encoding: gzip');
    return gzencode($html);
}

function cms_bar($width='100%') {
	global $_josh;
	//works with the CMS (code.google.com/p/bb-login) -- gives you an admin bar that controls the site you're on.  
	//should be called right before </body>
	//links is an associative array like draw_nav

	if (cookie_get('cms_key')) {
		//log in
		if (!user(false, 'cms_user_id') && $r = db_grab('SELECT id, firstname, lastname, email, secret_key, role FROM app_users WHERE secret_key = "' . $_COOKIE['cms_key'] . '" AND is_active = 1')) {
			$_SESSION['cms_user_id']	= $r['id'];
			$_SESSION['show_deleted']	= false;
			$_SESSION['cms_name']		= $r['firstname'];
			$_SESSION['full_name']		= $r['firstname'] . ' ' . $r['lastname'];
			$_SESSION['email']			= $r['email'];
			$_SESSION['role']			= $r['role'];
			$_SESSION['isLoggedIn']		= true;
			db_query('UPDATE app_users SET last_login = NOW() WHERE id = ' . $r['id']);		
		}
		
		if (user(false, 'cms_user_id')) {
			if (!isset($_josh['cms_links'])) $_josh['cms_links'] = array();
			$_josh['cms_links'] = array_merge(array('/login/'=>'CMS Home'), $_josh['cms_links']);
			$_josh['cms_links']['/login/?action=logout&return_to=' . urlencode($_josh['request']['path_query'])] = '&times';
			return draw_css('
				body { margin-top: 31px; overflow: visible; position: relative; } 
				body #cms-bar { border-bottom: 1px solid rgba(0,0,0,0.4); color: #333; font: 14px Verdana; padding: 0; position: fixed; text-align: left; top: 0; left: 0; width: 100%; z-index: 10000;
				  background-color: #ffa114;
				  background-image: -webkit-gradient(linear, left top, left bottom, from(#ffa114), to(#ffaf14)); 
				  background-image: -webkit-linear-gradient(top, #ffa114, #ffaf14); 
				  background-image:    -moz-linear-gradient(top, #ffa114, #ffaf14); 
				  background-image:     -ms-linear-gradient(top, #ffa114, #ffaf14); 
				  background-image:      -o-linear-gradient(top, #ffa114, #ffaf14); 
				  background-image:         linear-gradient(top, #ffa114, #ffaf14);
				  
					-webkit-box-shadow: 0px 0px 5px #333, inset 0 1px 1px 0 rgba(255,255,255,0.5); 
					   -moz-box-shadow: 0px 0px 5px #333, inset 0 1px 1px 0 rgba(255,255,255,0.5); 
					        box-shadow: 0px 0px 5px #333, inset 0 1px 1px 0 rgba(255,255,255,0.5);
				}
				body #cms-bar div.cms-wrapper { width: ' . $width . '; margin: 0 auto; }
				body #cms-bar div.cms-wrapper span.cms-message { color: rgba(0,0,0,0.5); float: left; /* font-style: italic; */ height: 30px; line-height: 30px; margin-left: 1em; text-shadow: 0 1px 0 rgba(255,255,255,0.3); } 
				body #cms-bar div.cms-wrapper nav { float: right; }
				body #cms-bar div.cms-wrapper nav ul.cms-bar-nav { list-style-type: none; float: right; margin: 0; padding: 0; }
				body #cms-bar div.cms-wrapper nav ul.cms-bar-nav li { float: left; /* margin-left: 10px; */ border-left: 1px solid rgba(0,0,0,0.3); }
				body #cms-bar div.cms-wrapper nav ul.cms-bar-nav li a { border-left: 1px solid rgba(255,255,255,0.4); color: #333; display: inline-block; line-height: 26px; padding: 2px 10px; text-decoration: none; text-shadow: 0 1px 0 rgba(255,255,255,0.3); }
				body #cms-bar div.cms-wrapper nav ul.cms-bar-nav li a:hover { background: rgba(0,0,0,0.15); color: #fff; text-shadow: 0 -1px 0 rgba(0,0,0,0.4); }
				body #cms-bar div.cms-wrapper nav ul.cms-bar-nav li a:active { padding: 3px 9px 1px 11px; border-color: transparent; 
					-webkit-box-shadow: inset 1px 1px 1px 0 rgba(0,0,0,0.4); 
					   -moz-box-shadow: inset 1px 1px 1px 0 rgba(0,0,0,0.4); 
					        box-shadow: inset 1px 1px 1px 0 rgba(0,0,0,0.4);
				}
				' . (($width != '100%') ? '
				body #cms-bar div.cms-wrapper nav ul.cms-bar-nav li.last { border-right: 1px solid rgba(255,255,255,0.4);  } 
				body #cms-bar div.cms-wrapper nav ul.cms-bar-nav li.last a { border-right: 1px solid rgba(0,0,0,0.3); }' : '') . '
				body #cms-bar div.cms-wrapper nav ul.cms-bar-nav li.last a { color: rgba(0,0,0,0.3); font-weight: bold; text-shadow: 0 1px 0 rgba(255,255,255,0.3); } 
				body #cms-bar div.cms-wrapper nav ul.cms-bar-nav li.last a:hover { color: #fff; text-shadow: 0 -1px 0 rgba(0,0,0,0.9); } 
			') . 
			draw_div('#cms-bar',
				draw_div('cms-wrapper',
					draw_span('cms-message', 'Welcome back ' . $_SESSION['cms_name']) . 
					draw_nav($_josh['cms_links'], 'text', 'cms-bar-nav')
				)
			);
		}
	}
}

function cms_bar_link($url, $title) {
	global $_josh;
	$_josh['cms_links'][$url] = $title;
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

/*
function date_holiday($date=false, $country='us') {
	//return the name of the holiday on $date, or false
	//todo refactor (currently designed to get all holidays for a month because it's copied from the intranet)
	if (!$date) {
		$date = time();
	} elseif (!is_int($date) {
		if (!$date = strtotime($date)) error_handle('could not convert date', __function__ . ' encountered a problem with the date', __file__, __line__);
	}
	$month		= date('n', $date);
	$year		= date('Y', $date);
	$day		= date('j', $date);
	$holidays	= array();
	
	if ($month == 1) {
		//new year's day
		$holidays[1] = 'New Year\'s Day';
		if (date('w', mktime(0,0,0,1,1,$year)) == 0) $holidays[2] = 'New Year\'s';
	
		//martin luther king day -- 3rd monday in jan
		for ($i = 1; $i < 32; $i++) {
			if (date('w', mktime(0,0,0,1,$i,$year)) == 1) $count++;
			if ($count == 3) {
				$holidays[$i] = 'Martin Luther King Day';
				break;
			}
		}
	} elseif ($month == 2) {
		//president's day -- 3rd monday in feb
		for ($i = 1; $i <= $lastday; $i++) {
			if (date('w', mktime(0,0,0,2,$i,$year)) == 1) $count++;
			if ($count == 3) {
				$holidays[$i] = 'President\'s Day';
				break;
			}
		}
	} elseif ($month == 5) {
		//memorial day -- last monday in may
		for ($i = 31; $i > 0; $i--) {
			if (date('w', mktime(0,0,0,5,$i,$year)) == 1) {
				$holidays[$i] = 'Memorial Day';
				break;
			}
		}
	} elseif ($month == 7) {
		//fourth of july
		if (date('w', mktime(0,0,0,7,4,$year)) == 6) $holidays[3] = 'Independence Day';
		if (date('w', mktime(0,0,0,7,4,$year)) == 0) $holidays[5] = 'Independence Day';
		$holidays[4] = 'Independence Day';
	} elseif ($month == 9) {
		//labor day -- first monday in sept
		for ($i = 1; $i < 31; $i++) {
			if (date('w', mktime(0,0,0,9,$i,$year)) == 1) {
				$holidays[$i] = 'Labor Day';
				break;
			}
		}
	} elseif ($month == 10) {
		//columbus day -- second monday in oct
		for ($i = 1; $i < 32; $i++) {
			if (date('w', mktime(0,0,0,10,$i,$year)) == 1) $count++;
			if ($count == 2) {
				$holidays[$i] = 'Columbus Day';
				break;
			}
		}
	} elseif ($month == 11) {
		//thanksgiving -- 4th thursday in nov
		for ($i = 1; $i < 31; $i++) {
			if (date('w', mktime(0,0,0,11,$i,$year)) == 4) $count++;
			if ($count == 4) {
				$holidays[$i] = 'Thanksgiving';
				$holidays[$i+1] = 'Day After Thanksgiving';
				break;
			}
		}
	} elseif ($month == 12) {
		//obscure possibility that friday after thanksgiving is 12/1
		for ($i = 1; $i < 31; $i++) {
			if (date('w', mktime(0,0,0,11,$i,$year)) == 4) $count++;
			if ($count == 4) {
				if ($i == 30) $holidays[1] = 'Day After Thanksgiving';
				break;
			}
		}
	
		//christmas
		$holidays[25] = 'Christmas Day';
		if (date('w', mktime(0,0,0,12,25,$year)) == 6) $holidays[24] = 'Christmas';
		if (date('w', mktime(0,0,0,12,25,$year)) == 0) $holidays[26] = 'Christmas';
	
		//obscure possibility that new year's is on a saturday; friday becomes a holiday
		if (date('w', mktime(0,0,0,12,31,$year)) == 5) $holidays[31] = 'New Year\'s';
	}
	
	if (isset($holidays[$day])) return $holidays[$day];
	return false;	
}
*/

function api_stock($symbol) {
	if ($file = array_rss('http://www.google.com/ig/api?stock=' . $symbol)) {
		$array = array();
		foreach ($file['finance'] as $key=>$value) {
			if ($key == '@attributes') continue;
			$array[$key] = $value['@attributes']['data'];
		}
		return $array;
	}
	return false;
}

function daysInMonth($month=false, $year=false) {
	/*todo rename*/
	global $_josh;
	if (!$month) $month = $_josh['month'];
	if (!$year) $year = $_josh['year'];
	return date('d', mktime(0,0,0, $month + 1, 0, $year));
}

function debug($html=true) {
	global $_josh;
	$_josh['error_mode_html'] = $html;
	$_josh['mode'] = 'debug';
}

function geocode($address, $zip=false) {
	global $_josh;
	if (!isset($_josh['google']['mapkey'])) error_handle('need google maps api key', 'put it in the config file');
	if ($zip) $address = $address . ', ' . $zip;
	$result = file('http://maps.google.com/maps/geo?q=' . urlencode($address) . '&output=csv&oe=utf8&sensor=false&key=' . $_josh['google']['mapkey']);
	if (list($status, $accuracy, $latitude, $longitude) = explode(',', utf8_decode($result[0]))) {
		if ($latitude || $longitude) return array($latitude, $longitude);
	}
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

function language_detect() {
	//return en, es, fr, ru from browser settings
	if (empty($_SERVER['HTTP_ACCEPT_LANGUAGE'])) return false;
    $code = explode(';', $_SERVER['HTTP_ACCEPT_LANGUAGE']);
    $code = explode(',', $code[0]);
    return substr($code[0], 0, 2);
}

function language_translate($string, $source, $target) {
	global $_josh;
	
	//make sure there's something to translate
	if (empty($string)) return '';
	
	if (empty($_josh['google_translate_api_key'])) error_handle('translate API key required', 'Google translate is now a paid service.  Provide an ' . draw_link('http://code.google.com/apis/language/translate/overview.html', 'API key') . ' in the config file.');
	
	//unescape in case of post
	$string = str_replace("''", "'", $string);
	
	//todo figure out how to do this with POST since the limit is higher
	//todo figure out exactly what the limit is
	//todo investigate implied possibility of doing multiple translations in a single pass
	
	$chunks = array_chunk_html($string, 1450);
	$string = '';
	foreach ($chunks as $c) {
		error_debug('<b>lanuage_translate</b> running query for a string that is ' . strlen($c) . ' characters long', __file__, __line__);
	
		if (!isset($_josh['google_search_api_key'])) error_handle('api key not set', __function__ . ' needs a ' . draw_link('http://code.google.com/apis/ajaxsearch/signup.html', 'Google AJAX search API key'), __file__, __line__);
		
		$body = url_get('https://www.googleapis.com/language/translate/v2?key=' . $_josh['google_translate_api_key'] . '&q=' . urlencode($c) . '&key=' . $_josh['google_search_api_key'] . '&source=' . $source . '&target=' . $target);
		
		// now, process the JSON string
		$json = json_decode($body, true);
		//die(draw_array($json));
		
		//new google translate uses different error messages
		//if ($json['responseStatus'] != '200') error_handle('google translate bad result', 'the text string length was ' . strlen($c) . BR . BR . $c . BR . BR . '.  json was ' . $json . draw_array($json), __file__, __line__);

		$string .= $json['data']['translations'][0]['translatedText'];
	}
	return format_quotes($string);
}

function lib_get($string) {
	global $_josh;
	
	//string can also be comma-separated or array
	if (stristr($string, ',')) $string = array_separated($string);
	if (is_array($string)) {
		$return = '';
		foreach ($string as $s) $return .= lib_get($s);
		return $return;
	}
	
	$string = strtolower($string);
	
	//shortcuts
	if ($string == 'prettify') $string = 'google-code-prettify';
	
	switch ($string) {
		//php libraries
		case 'dbug' :
		case 'fpdf' :
		case 'salesforce' :
		case 'simple_html_dom' :
		case 'swiftmailer' :
		return @include_once(lib_location($string));
		
		case 'phphaml' :
		include_once(lib_location($string));
		//phphaml\Library::autoload();
		return;
        
		//javascript/css libraries
		case 'bootstrap' :
		case 'fancybox' :
		case 'google-code-prettify' : 
		case 'innershiv' :
		case 'jquery' :
		case 'jquery-latest' :
		case 'jscolor' :
		case 'jscrollpane' :
		case 'latlon-picker' :
		case 'lorem_ipsum' :
		case 'modernizr' :
		case 'swfobject' : 
		case 'tablednd' :
		case 'tinymce' :
		case 'validate' :
		case 'wysihtml5' : 
		case 'uploadify' :
		if (isset($_josh['drawn'][$string])) return false;
		
		$_josh['drawn'][$string] = true;
		$return = draw_javascript_src(lib_location($string));

		if ($string == 'tinymce') {
			//whether to show full button set or not
			$tinymce_mode = (user() ? 'advanced' : 'simple');
			if (isset($_josh['tinymce_mode'])) $tinymce_mode = $_josh['tinymce_mode'];
			
			//special statements for tinymce
			$return = lib_get('jquery') . draw_javascript_ready('
					$("textarea.tinymce").each(function(){
						$(this).tinymce({
							// Location of TinyMCE script
							content_css : "/css/tinymce.css?" + new Date().getTime(),
							custom_shortcuts : 0,
							extended_valid_elements : "a[href|target|rel|name|class],caption,dd[class],dl[class],dt[class],img[class|src|border=0|alt|title|hspace|vspace|width|height|align|style],dir,hr[class|width|size|noshade],iframe[src|width|height|frameborder|webkitAllowFullScreen|allowFullScreen],font[face|size|color|style],span[align|class|style],p[align|class|style],table[cellspacing|align|border|cellpadding|class],tbody,td[width|align|class],th[class],tr[class]",
							plugins : "' . (($tinymce_mode == 'advanced') ? 'imagemanager,filemanager,' : '') . 'paste",
							relative_urls : false,
							remove_script_host : false,
							onchange_callback: function() { tinyMCE.triggerSave(); },
							script_url : "' . str_replace('jquery.tinymce.js', 'tiny_mce.js', lib_location('tinymce')) . '",
							theme : "advanced",
							theme_advanced_buttons1 : "' . (($tinymce_mode == 'advanced') ? 
								'styleselect,|,bold,italic,underline,strikethrough,|,justifyleft,justifycenter,blockquote,|,bullist,numlist,outdent,indent,|,link,unlink,image,hr,|,code' : 
								'bold,italic,underline,strikethrough,|,justifyleft,justifycenter,blockquote,|,bullist,numlist,|,link,unlink'
							) . '",
							theme_advanced_buttons2 : "",
							theme_advanced_toolbar_location : "top"
						});
					});
			') . $return;
			file_dir_writable('images');
			file_dir_writable('files');
		} elseif (in_array($string, array('bootstrap', 'google-code-prettify', 'latlon-picker', 'tablednd', 'validate', 'wysihtml5', 'fancybox', 'jscrollpane', 'uploadify'))) {
			$return = lib_get('jquery') . $return;
			if ($string == 'bootstrap') {
				$return = draw_css_src(DIRECTORY_WRITE . '/lib/bootstrap/css/bootstrap.min.css') . $return;
			} elseif ($string == 'fancybox') {
				$return .= draw_javascript_src(DIRECTORY_WRITE . '/lib/jquery/jquery.mousewheel-3.0.6.pack.js') . 
					draw_css_src(DIRECTORY_WRITE . '/lib/jquery/fancybox/jquery.fancybox.css');
			} elseif ($string == 'google-code-prettify') {
				$return = /* draw_css_src(DIRECTORY_WRITE . '/lib/google-code-prettify/prettify.css') .  */$return . draw_javascript_ready('prettyPrint()');
			} elseif ($string == 'jscrollpane') {
				$return = draw_css_src(DIRECTORY_WRITE . '/lib/jquery/jscrollpane/jquery.jscrollpane.css') . 
				$return . draw_javascript_src(DIRECTORY_WRITE . '/lib/jquery/jscrollpane/jquery.mousewheel.js');			
			} elseif ($string == 'latlon-picker') {
				$return = draw_javascript_src('http://maps.googleapis.com/maps/api/js?sensor=false') . draw_css_src(DIRECTORY_WRITE . '/lib/jquery/jquery.gmaps-latlon-picker.css') . $return;			  
			} elseif ($string == 'wysihtml5') {
				$return = draw_javascript_src(DIRECTORY_WRITE . '/lib/wysihtml5/parser_rules/advanced.js') . $return;
			} elseif ($string == 'uploadify') {
				file_dir_writable('uploads');
				$return .= draw_css_src(DIRECTORY_WRITE . '/lib/uploadify/uploadify.css') . 
					lib_get('swfobject') . 
					draw_javascript_ready('
						$("a.uploadify").each(function(){
							//alert($(this).attr("href"));
							$(this).uploadify({
								"swf"  			: "' . DIRECTORY_WRITE . '/lib/uploadify/uploadify.swf",
								"uploader"		: $(this).attr("href"),
								"checkExisting"	: "' . DIRECTORY_WRITE . '/lib/uploadify/uploadify-check-exists.php",
								"cancelImage"	: "' . DIRECTORY_WRITE . '/lib/uploadify/uploadify-cancel.png",
								"auto"      	: true,
								"onError"		: function(event, ID, fileObj, errorObj) { alert(errorObj.type + "::" + errorObj.info); }
							});
						});
					');
			}
		}
		
		return $return;
	}
}

function lib_location($string) {

	$lib = DIRECTORY_WRITE . '/lib/' . $string . '/';

	switch ($string) {
		case 'bootstrap' : 
		return DIRECTORY_WRITE . '/lib/bootstrap/js/bootstrap.min.js';
		
		case 'dbug' : 
		return DIRECTORY_ROOT . $lib . 'dbug.php';
		
		case 'fancybox' : 
		return DIRECTORY_WRITE . '/lib/jquery/fancybox/jquery.fancybox.pack.js';
		
		case 'fpdf' :
		return DIRECTORY_ROOT . $lib . 'fpdf-1.6.php';
		
		case 'google-code-prettify' : 
		return DIRECTORY_WRITE . '/lib/google-code-prettify/prettify.js';
		
		case 'innershiv' :
		return $lib . 'innershiv.min.js';
		
		case 'jquery' :
		return $lib . 'jquery-1.7.1.min.js';
		
		case 'jquery-hosted' :
		return 'http://code.jquery.com/jquery-1.5.2.min.js';
		
		case 'jscolor' :
		return DIRECTORY_WRITE . '/lib/jscolor/jscolor.js';

		case 'jscrollpane' :
		return DIRECTORY_WRITE . '/lib/jquery/jscrollpane/jquery.jscrollpane.min.js';

		case 'lorem_ipsum' :
		return $lib . 'lorem_ipsum.js';
		
		case 'latlon-picker' :
		return DIRECTORY_WRITE . '/lib/jquery/jquery.gmaps-latlon-picker.js';
    		
		case 'modernizr' :
		return $lib . 'modernizr-2.0.min.js';
		
		case 'phphaml' :
		return DIRECTORY_ROOT . $lib . 'library.php';
    
		case 'salesforce' :
		return DIRECTORY_ROOT . $lib . 'phptoolkit-13_1/soapclient/SforceEnterpriseClient.php';
		
		case 'simple_html_dom' :
		return DIRECTORY_ROOT . $lib . 'simple_html_dom-1.11.php';

		case 'swiftmailer' :
		return DIRECTORY_ROOT . $lib . 'swift_required.php';
		
		case 'swfobject' :
		return $lib . 'swfobject.js';
		
		case 'tablednd' :
		return DIRECTORY_WRITE . '/lib/jquery/jquery.tablednd_0_5.js';
		
		case 'tinymce' :
		return $lib . 'jscripts/tiny_mce/jquery.tinymce.js';

		case 'uploadify' :
		return DIRECTORY_WRITE . '/lib/uploadify/jquery.uploadify.min.js';
		
		case 'validate' :
		return DIRECTORY_WRITE . '/lib/jquery/jquery.validate.min.js';
		
		case 'wysihtml5' : 
		return $lib . 'dist/wysihtml5-0.3.0.min.js';
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

function user($return_if_empty=false, $key=false) {
	//shortcut to say if a session exists and what the id is, or return $return eg NULL for sql
	if (!$key) $key = (defined('SESSION_USER_ID'))	? SESSION_USER_ID : 'user_id';
	if (!isset($_SESSION)) {
		if (headers_sent()) return false;
		session_start();
	}
	if (empty($_SESSION[$key]) || ($_SESSION[$key] === false)) return $return_if_empty;
	return $_SESSION[$key];
}