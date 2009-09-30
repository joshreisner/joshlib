<?php
/* 
WELCOME TO JOSHLIB!
	http://code.google.com/p/joshlib/ (wiki / documentation / report issues here)
	http://joshlib.joshreisner.com/ (eventual website)

LICENSE
	all files other than lib.zip are authored by josh reisner and provided to the public under the terms of the LGPL
	
THIRD PARTY SOFTWARE
	included in lib.zip.  thank you so much to each of the contributors for these excellent packages
	
	--title-------------lang----version---------url---------------------------------------------license-------------------------
	> codepress			(js)	version 0.9.6	http://sourceforge.net/projects/codepress/		LGPL
	> fpdf				(php)	version 1.6		http://www.fpdf.org/							no license
	> prototype			(js)	version 1.6.0.3	http://prototypejs.org/							MIT
	> lightbox			(js)	version 2.04	http://www.lokeshdhakar.com/projects/lightbox2/	Creative Commons Attribution 2.5
	> scriptaculous		(js)	version 1.8.2	http://script.aculo.us/							MIT (I think)
	> simple_html_dom	(php)	version 1.11	http://sourceforge.net/projects/simplehtmldom/	MIT
	> tinymce			(js)	version 3.2.5	http://tinymce.moxiecode.com/					LGPL

VARIABLES THAT JOSHLIB TRIES TO GET FROM THE WEBSERVER -- IF YOU'RE RUNNING FROM THE COMMAND LINE YOU MIGHT NEED TO PASS THEM
	$_josh['request']			this is an array, easiest way to set this is doing url_parse(http://www.yoursite.com/yourfolder/yourpage.php?query=whatever)
	$_josh['referrer']			same as request
	$_josh['folder']			/ or \
	$_josh['newline']			\n or \r\n
	$_josh['root']				path to the site, eg /Users/yourusername/Sites/thissite
	$_josh['slow']				true or false; whether to use javascript when redirecting (true) or header variables (false)
	$_josh['mobile']			true or false

USING THE DEBUGGER
	you can run the debug() function after joshlib has been included to see output of various processes
	to debug the loading of the joshlib itself, set $_josh['debug'] = true before you include it

MISC FUNCTIONS DEFINED ON THIS PAGE
	cookie
	cookie_get
	daysInMonth
	debug
	geocode
	
MISC CLASSES DEFINED HERE
	form
	table
	
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
	
//set static variables
	//todo, limit these
	$_josh['drawn']['javascript'] 	= false;	//only include javascript.js once
	$_josh['drawn']['focus']		= false;	//only autofocus on one form element
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
	$_josh['month']					= date('n');
	$_josh['months']				= array('January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December');
	$_josh['mos']					= array('Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec');
	$_josh['numbers']				= array('zero','one','two','three','four','five','six','seven','eight','nine');
	$_josh['queries']				= 0;	//for counting trips to the database
	$_josh['system_columns']		= array('id', 'created_date', 'created_user', 'updated_date', 'updated_user', 'deleted_date', 'deleted_user', 'is_active');
	$_josh['today']					= date('j');
	$_josh['year']					= date('Y');

//get includes
	require($_josh['joshlib_folder'] . '/array.php');
	require($_josh['joshlib_folder'] . '/cache.php');
	require($_josh['joshlib_folder'] . '/db.php');
	require($_josh['joshlib_folder'] . '/draw.php');
	require($_josh['joshlib_folder'] . '/email.php');
	require($_josh['joshlib_folder'] . '/file.php');
	require($_josh['joshlib_folder'] . '/format.php');
	require($_josh['joshlib_folder'] . '/url.php');

//parse environment variables
	if (isset($_SERVER) && isset($_SERVER['HTTP_HOST']) && isset($_SERVER['SCRIPT_NAME'])) { //this could not be set if this were running from the command line (eg by a cron)
		//build request as string, then set it to array with url_parse
		$_josh['request'] = (isset($_SERVER['HTTPS']) && ($_SERVER['HTTPS'] == 'on')) ? 'https' : 'http';
		$_josh['request'] .= '://' . $_SERVER['HTTP_HOST'];
		if (isset($_SERVER['REDIRECT_URL'])) {
			//we are using mod_rewrite, probably as part of a CMS system
			$_josh['request'] .= $_SERVER['REQUEST_URI'];
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
			$_josh['folder']			= '\\';
			$_josh['newline']			= "\r\n";
			//$_josh['root']				= str_replace(str_replace('/', '\\', $_josh['request']['path']), '', str_replace('\\\\', '\\foo', $_SERVER['PATH_TRANSLATED']));
			$_josh['root']				= str_replace($_josh['request']['path'], '', $_SERVER['PATH_TRANSLATED']);
			$_josh['slow']				= true;
		} else { //platform is UNIX or Mac
			$_josh['folder']			= '/';
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
		if (!isset($_josh['folder']))	$_josh['folder']	= '/';
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
	if (!isset($_josh['config'])) $_josh['config'] = $_josh['write_folder'] . $_josh['folder'] . 'config.php'; //eg /_example.com/config.php
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
	
//ensure lib exists
	if (!is_dir($_josh['root'] . $_josh['write_folder'] . $_josh['folder'] . 'lib')) {
		file_write_folder($_josh['write_folder'] . $_josh['folder'] . 'dynamic');	//used by file_dynamic
		file_write_folder($_josh['write_folder'] . $_josh['folder'] . 'files');		//used by tinymce
		file_write_folder($_josh['write_folder'] . $_josh['folder'] . 'images');	//used my tinymce
		file_write_folder($_josh['write_folder'] . $_josh['folder'] . 'rss');		//common -- eventually used by file_rss
		file_unzip($_josh['joshlib_folder'] . $_josh['folder'] . 'lib.zip', $_josh['write_folder']);
	}		

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
	if ($_josh['posting']) foreach($_POST as $key=>$value) $_POST[$key] = format_quotes($value);
	
	$_josh['editing']	= url_id();
	
//handle system url calls
	if (url_action('ajax_delete,ajax_reorder,ajax_set,flushcache,debug,phpinfo') && (($_josh['mode'] == 'dev') || !empty($_SESSION['user_id']))) {
		//try to handle the following ajax calls automatically -- requires the session for security
		
		$array = array_ajax();
		
		//quick thing for sessions -- might make it sliiiightly more secure (but it shouldn't)
		if (isset($array['id']) && ($array['id'] == 'session')) $array['id'] = $_SESSION['user_id'];
		
		switch ($_GET['action']) {
			case 'ajax_delete':
				exit;
			case 'ajax_reorder':
				exit;
			case 'ajax_draw_select':
			//draw_form_select($name, $sql_options, $value=false, $required=true, $class=false, $action=false, $nullvalue='', $maxlength=false) {

				echo draw_form_select($array['name'], 'SELECT id, value FROM ' . $array['table'] . ' WHERE is_active = 1', $array['value'], $array['required']);
				exit;
			case 'ajax_set':
				//todo, better column type sensing
				if (stristr($array['column'], 'date')) $array['value'] = format_date($array['value'], 'NULL', 'SQL');
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
	}

//special functions that don't yet fit into a category

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


//form class
class form { 
	var $name	= false;
	var $fields = array();
	var $title	= false;
	var $title_prefix = false;
	var $id		= false;
	var $table	= false;
	var $values = array();
	var $submit = false;
	
	function __construct($name, $id=false, $submit=true, $cancel=false) {
		//$table is the db table you're referencing.  good for putting up a quick form scaffolding
		//$id is the id column of the table -- you can add values to the form (say if you are editing)
		//$submit is a boolean, and indicates whether you should auto-add a submit button at the bottom
		//if you pass $submit as a string, it will title use the text you passed, and title the form that
		
		$this->name = $name;
		$this->submit = $submit;
		$this->cancel = $cancel;
		$this->id = $id;
		if ($name) $this->set_table($name);
		if ($submit === true) {
			$this->title = (($id) ? 'Edit ' : 'Add New ') . format_singular(format_text_human($name));
		} else {
			$this->title = $submit;
		}
		if ($this->table && $id) $this->set_values(db_grab('SELECT * FROM ' . $this->table . ' WHERE id = ' . $id));
	}

	function draw($values=false) {
		global $_josh, $_GET;
		
		if ($values) $this->set_values($values);
		
		//add submit?
		if ($this->submit) {
			$additional = false;
			
			//add cancel?
			if (isset($_GET['return_to'])) {
				if ($this->cancel) $additional = 'or ' . draw_link($_GET['return_to'], 'cancel');
				$this->set_field(array('type'=>'hidden', 'name'=>'return_to', 'value'=>$_GET['return_to']));
			} elseif (isset($_josh['referrer']['url'])) {
				if ($this->cancel) $additional = 'or ' . draw_link($_josh['referrer']['url'], 'cancel');
				$this->set_field(array('type'=>'hidden', 'name'=>'return_to', 'value'=>$_josh['referrer']['url']));
			}
			
			//add submit
			$this->set_field(array('type'=>'submit', 'value'=>strip_tags($this->title), 'additional'=>$additional));
		}
				
		//start output
		if (!$this->title) $this->title = ''; //legend is showing up <legend/>
		$return = draw_container('legend', $this->title_prefix . $this->title);

		//add fields
		foreach ($this->fields as $field) $return .= $this->draw_row($field);

		//wrap in unnecessary fieldset
		$return = draw_tag('fieldset', false, $return);
		
		//wrap in form
		$return = draw_tag('form', array('method'=>'post', 'enctype'=>'multipart/form-data', 'accept-charset'=>'UTF-8', 'action'=>$_josh['request']['path_query'], 'name'=>$this->name, 'class'=>$this->name, 'onsubmit'=>'javascript:return form_validate(this);'), $return);
		
		//focus on first element
		reset($this->fields);
		if ($this->fields[key($this->fields)]['name']) $return .= draw_form_focus($this->fields[key($this->fields)]['name']);

		return $return;
	}
	
	function draw_row($field) {
		global $_josh;
		extract($field);
		$return = '';
		
		//value is being set manually		
		if (!$value && isset($this->values[$name])) $value = $this->values[$name];
		
		//values has default
		if (!$value && $default) $value = $default;
		
		//wrap additional
		if (isset($additional) && $additional) $additional = draw_tag('span', array('class'=>'additional'), $additional);
		
		//draw the field
		if ($type == 'hidden') {
			$return .= draw_form_hidden($name, $value);
		} else {
			if ($label) {
				if ($additional && (($type == 'checkboxes') || ($type == 'textarea'))) $label .= $additional;
				$return .= draw_tag('label', array('for'=>$name), $label);
			}
			switch ($type) {
				case 'checkbox':	
					$return .= draw_div_class('checkbox_option', draw_form_checkbox($name, $value) . '<span class="option_name" onclick="javascript:form_checkbox_toggle(\'' . $name . '\');">' . $additional . '</span>');
					break;
				case 'checkboxes':
					if (!$option_title) {
						//$option_title = 'title';
						$options_columns = db_columns($options_table);
						$option_title = $options_columns[1]['name'];
					}
					$options = ($value) ? db_table('SELECT o.id, o.' . $option_title . ', (SELECT COUNT(*) FROM ' . $linking_table . ' l WHERE l.' . $option_id . ' = o.id AND l.' . $object_id . ' = ' . $value . ') checked FROM ' . $options_table . ' o WHERE o.is_active = 1 ORDER BY o.' . $option_title) : db_table('SELECT id, ' . $option_title . ', 0 checked FROM ' . $options_table . ' WHERE o.is_active = 1 ORDER BY ' . $option_title);
					foreach ($options as &$o) {
						$name = 'chk-' . $options_table . '-' . $o['id'];
						$o = draw_form_checkbox($name, $o['checked']) . '<span class="option_name" onclick="javascript:form_checkbox_toggle(\'' . $name . '\');">' . $o[$option_title] . '</span>';
					}
					if ($allow_changes) $options[] = '<a class="option_add" href="javascript:form_checkbox_add(\'' . $options_table . '\', \'' . $allow_changes . '\');">add new</a>';
					$return .= draw_list($options, array('id'=>$options_table));
					break;
				case 'date':
					$return .= draw_form_date($name, $value, false) . $additional;
					break;
				case 'datetime':
					$return .= draw_form_date($name, $value, true) . $additional;
					break;
				case 'file':
					$return .= draw_form_file($name, $class, $onchange) . $additional;
					//todo -- this is wonky -- presupposes it's a jpg
					if ($value) $return .= draw_img(file_dynamic($this->table, $name, $this->id, 'jpg'));
					break;
				case 'group':
					$return .= $value;
					break;
				case 'note':
					$return .= '<div class="note">' . $additional . '</div>';
					break;
				case 'password':
					$return .= draw_form_password($name, $value, $class, 255, false) . $additional;
					break;
				case 'radio':
					if (!$options) {
						if (!$sql) $sql = 'SELECT id, name FROM options_' . str_replace('_id', '', $name);
						$options = db_array($sql);
					}
					$return .= '<div class="radio">';
					if ($append) while (list($addkey, $addval) = each($append)) $options[$addkey] = $addval;
					foreach ($options as $id=>$description) {
						$return .= '<div class="radio_option">' . draw_form_radio($name, $id, ($value == $id), $class) . $description . '</div>';
					}
					$return .= '</div>';
					break;
				case 'readonly':
					$return .= $value . ' ' . $additional;
					break;
				case 'select':
					if (!$options) {
						if (!$sql) $sql = 'SELECT id, name FROM options_' . str_replace('_id', '', $name);
						$options = db_array($sql);
					}
					if ($append) while (list($addkey, $addval) = each($append)) $options[$addkey] = $addval;
					$return .= draw_form_select($name, $options, $value, $required, $class, $action) . $additional;
					break;
				case 'submit':
					$return .= draw_form_submit($value, $class) . $additional;
					break;
				case 'text':
					$return .= draw_form_text($name, $value, $class, $maxlength, false, false) . $additional;
					break;
				case 'textarea':
					$return .= draw_form_textarea($name, $value, $class) . $additional;
					break;
			}
						
			//wrap it up
			$return = draw_div_class('field ' . $type . ' ' . $name, $return);
		}
		return $return;
	}
	
	function set_field($array) {
		//defaults
		$type = $value = $class = $default = $name = $label = $required = $append = $allow_changes = $sql = $action = $onchange = $additional = $maxlength = $options_table = $option_id = $option_title = $object_id = $options = $linking_table = false;
		
		//load inputs
		if (!is_array($array)) return error_handle('array not set');
		extract($array);
		
		//type is required
		if (!$type) return error_handle('type not set');

		if ((($type == 'text') || ($type == 'password')) && !isset($array['additional']) && $required) $additional = '(required)';

		error_debug('adding field ' . $label, __file__, __line__);

		if (!$name)	$name	= format_text_code($label);
		if ($label === false) $label = format_text_human($name);
		if (!$value) $value	= (isset($this->values[$name])) ? $this->values[$name] : false;
		if (!$class) $class	= '';
		if (!$option_id) $option_id	= 'option_id';
		if (!$object_id) $object_id	= 'object_id';
		
		if ($type == 'checkbox') {
			$additional = $label;
			$label = '&nbsp;';
		}
		
		//package and save
		$this->fields[$name] = compact('name', 'type', 'label', 'value', 'default', 'append', 'required', 'allow_changes', 'sql', 'class', 'action', 'onchange', 'additional', 'options_table', 'option_id', 'option_title', 'object_id', 'options', 'linking_table', 'maxlength');
	}
	
	function set_group($string='') {
		$this->set_field(array('name'=>'group', 'type'=>'group', 'value'=>$string));
	}
	
	function set_table($table) {
		$this->table = false;
		if ($cols = db_columns($table, true)) {
		
			//preload foreign keys
			$foreign_keys = array();
			if ($keys = db_keys_from($table)) foreach ($keys as $key) $foreign_keys[$key['name']] = $key;

			$this->table = $table;
			foreach ($cols as $c) {
				if ($c['type'] == 'varchar') {
					if ($c['name'] == 'password') {
						$this->set_field(array('type'=>'password', 'name'=>$c['name'], 'additional'=>$c['comments'], 'required'=>$c['required']));
					} elseif ($c['name'] == 'secret_key') {
						//hide this field
					} else {
						$this->set_field(array('type'=>'text', 'name'=>$c['name'], 'additional'=>$c['comments'], 'required'=>$c['required']));
					}
				} elseif ($c['type'] == 'text') {
					$this->set_field(array('type'=>'textarea', 'name'=>$c['name'], 'class'=>'mceEditor'));
				} elseif (($c['type'] == 'bit') || ($c['type'] == 'tinyint')) {
					$this->set_field(array('type'=>'checkbox', 'name'=>$c['name']));
				} elseif ($c['type'] == 'date') {
					$this->set_field(array('type'=>'date', 'name'=>$c['name'], 'additional'=>$c['comments']));
				} elseif ($c['type'] == 'datetime') {
					$this->set_field(array('type'=>'datetime', 'name'=>$c['name'], 'additional'=>$c['comments']));
				} elseif (($c['type'] == 'image') || ($c['type'] == 'mediumblob')) {
					$this->set_field(array('type'=>'file', 'name'=>$c['name'], 'additional'=>$c['comments']));
				} elseif ($c['type'] == 'int') {
					if (isset($foreign_keys[$c['name']])) {
						$this->set_field(array('type'=>'select', 'name'=>$key['name'], 'label'=>$key['label'], 'sql'=>'SELECT * FROM ' . $key['ref_table'], 'additional'=>$c['comments'], 'required'=>$c['required']));
					}
				}
			}
			
			//load checkboxes tables
			if ($keys = db_keys_to($table)) {
				foreach ($keys as $key) {
					if (isset($key['ref_table'])) {
						$this->set_field(array(
							'type'=>'checkboxes', 
							'value'=>$this->id, //this doesn't feel like the right place to set this
							'name'=>$key['ref_table'], //todo make this unnecessary: make it the linking table instead of the options table
							'label'=>$key['constraint_name'],
							//'option_title'=>'name',
							//"additional"=>$additional, 
							//"allow_changes"=>$allow_changes, 
							'options_table'=>$key['ref_table'], 
							'linking_table'=>$key['table_name'], 
							'option_id'=>$key['name'], 
							'object_id'=>$key['column_name']
							)
						);
					}
				}
			}
		}
		
	}
	
	function set_title($title=false) {
		//title should be a string, and indicates you want a title dd at the top of your form
		//if you don't pass a $title, there had better be a title already set via the constructor
		if ($title) $this->title = $title;
		array_unshift($this->fields, array('type'=>'title', 'name'=>'','class'=>'title', 'value'=>$this->title, 'label'=>false));
	}
	
	function set_title_prefix($prefix=false) {
		$this->title_prefix = $prefix;
	}
	
	function set_values($values=false) {
		//if you want to do a custom select and pass in the associative array
		if (!is_array($values)) return false;
		foreach ($values as $key=>$value) {
			$this->values[$key] =  $value;
		}
	}
	
	function unset_fields($fieldnames) {
		$fields = array_post_fields($fieldnames);
		foreach ($fields as $f) unset($this->fields[$f]);
	}
}

//table class
class table {
	var $columns	= array();
	var $draggable	= false;
	var $name		= false;
	var $title		= false;

	public function __construct($name='table', $title=false) {
		$this->name = format_text_code($name);
		$this->title = $title;
	}
	
	function draw($values, $errmsg='Sorry, no results!', $hover=false, $total=false) {
		global $_josh;
		$class			= $this->name;
		$count_columns	= count($this->columns);
		$count_rows		= count($values);
		$counter		= 1; //to determine first_row and last_row
		$totals			= array(); //var to hold totals, if applicable
		$return			= ''; //hold the output
		if (!$count_columns) {
			//there were no columns defined.  no columns, no table
			$return .= $this->draw_header(false) . $this->draw_empty('Sorry, no columns defined!');
		} elseif (!$count_rows) {
			//no rows, return errmsg
			$return .= $this->draw_header(false) . $this->draw_empty($errmsg);
		} else {
			$row	= 'odd';
			$group	= '';
			$return .= $this->draw_header() . '<tbody id="' . $this->name . '">';
			
			foreach ($values as $v) {
				if (isset($v['group']) && ($group != $v['group'])) {
					$return .= draw_container('tr', draw_container('td', $v['group'], array('colspan'=>$count_columns, 'class'=>'group')));
					$row = 'odd'; //reset even/odd at the beginning of groups
					$group = $v['group'];
				}
				if ($total) {
					//must be array
					foreach ($total as $t) {
						if (isset($v[$t])) {
							if (!isset($totals[$t])) $totals[$t] = 0;
							$totals[$t] += format_numeric($v[$t]);
						}
					}
				}
				
				//row and arguments
				$return .= '<tr';
				if (isset($v['id'])) $return .= ' id="item_' . $v['id'] . '"';				
				$return .= ' class="' . $row;
				if (isset($v['class']) && !empty($v['class'])) $return .= ' ' . $v['class'] . ' ' . $row . '_' . $v['class'];
				if ($counter == 1) $return .= ' first_row';
				if ($counter == $count_rows) $return .= ' last_row';
				$return .= '"';
				if ($hover) {
					if (isset($v['link'])) $return .= ' onclick="location.href=\'' . $v['link'] . '\';"';
					//hover class must exist
					$return .= ' onmouseover="css_add(this, \'hover\');"';
					$return .= ' onmouseout="css_remove(this, \'hover\');"';
				}
				$return .= '>' . $_josh['newline'];
				
				foreach ($this->columns as $c) $return .= draw_tag('td', array('class'=>$c['name'] . ' ' . $c['class']), $v[$c['name']]);
				
				$return .= '</tr>' . $_josh['newline'];
				
				$row = ($row == 'even') ? 'odd' : 'even';
			}
			$return .= '</tbody>';
			$counter++;
		}
		if ($total) {
			$return .= '<tr class="total"><tfoot>';
			foreach ($this->columns as $c) {
				$return .= '<td class="' . $c['name'];
				if ($c['class']) $this->return .= ' ' . $c['class'];
				if (isset($totals[$c['name']])) {
					$return .= $totals[$c['name']];
				} else {
					$return .= '&nbsp;';
				}
				$return .= '</td>' . $_josh['newline'];
			}
			$return .= '</tr></tfoot>' . $_josh['newline'];
		}
		
		$class .= ' table'; //temp for intranet
		$return = draw_container('table', $return, array('cellspacing'=>0, 'class'=>$class));
		
		//drag and drop table
		if ($this->draggable && $count_rows) {
			$return .= draw_javascript('
				function reorder() {
					var ampcharcode= "%26";
					var serializeOpts = Sortable.serialize("' . $this->name . '") + unescape(ampcharcode) + "key=' . $this->name . '" + unescape(ampcharcode) + "update=' . $this->name . '";
					var options = { method:"post", parameters:serializeOpts, onSuccess:function(transport) {
						//alert(transport.responseText);
					} };
					new Ajax.Request("' . $this->target . '", options);
				}
				Sortable.create("' . $this->name . '", { tag:"tr", ' . (($this->draghandle) ? 'handle:"' . $this->draghandle . '", ' : '') . 'ghosting:true, constraint:"vertical", onUpdate:reorder, tree:true });
				');
		}
		return $return;
	}

	function draw_column($c) {
		$class = $c['name'];
		if ($c['class']) $class .= ' ' . $c['class'];
		$style = ($c['width']) ? 'width:' . $c['width'] . 'px;': false;
		$content = ($c['title']) ? $c['title'] : format_text_human($c['name']);
		return draw_container('th', $content, array('style'=>$style, 'class'=>$c['class']));
	}

	function draw_columns() {
		$return = '';
		foreach ($this->columns as $c) $return .= $this->draw_column($c);
		return draw_container('tr', $return);
	}
	
	function draw_empty($string) {
		return draw_container('tr', draw_container('td', $string, array('class'=>'empty')));
	}
	
	
	function draw_header($show_columns=true) {
		return draw_container('thead', $this->draw_title() . (($show_columns) ? $this->draw_columns() : ''));
	}
	
	function draw_title() {
		return ($this->title) ? draw_container('tr', draw_container('th', $this->title, array('class'=>'title', 'colspan'=>count($this->columns)))) : '';
	}
	
	function col($name, $class=false, $title=false, $width=false) {
		//legacy alias, todo ~ deprecate
		$this->set_column($name, $class, $title, $width);
	}
	
	function set_column($name, $class=false, $title=false, $width=false) {
		$this->columns[] = compact('name', 'class', 'title', 'width');
	}
	
	function set_draggable($target, $draghandle=false) {
		$this->draggable = true;
		$this->target = $target;
		$this->draghandle = $draghandle;
	}
	
	function set_title($html) {
		$this->title = $html;
	}
}

if ($_josh['debug']) error_debug('joshlib has finished loading and self-debugging in ' . format_time_exec(), __file__, __line__);
?>