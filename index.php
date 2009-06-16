<?php
/* 
WELCOME TO JOSHLIB
	http://code.google.com/p/joshlib/ (wiki / documentation / tracking)
	http://joshlib.joshreisner.com/ (eventual website)

ACKNOWLEDGEMENTS
	> simple_html_dom	-- included as html.php.  more info there.  wonderful.  thank you sirs.  format_html() & form class references this.
	> moxiecode tinymce	-- not included, but frequently used with this library
	> fpdf				-- you are magic

the purpose of this page (index.php) is to set a bunch of variables that joshlib is going to need, 
and define some functions that don't fit anywhere else

variables you can pass joshlib that it won't overwrite are:

	$_josh["config"]			the file location of the configuration file

	$_josh["debug"]				true / false.  turn this on to get detailed error logging output to the browser
	
	$_josh["db"]["location"]	the server location of the configuration file
	$_josh["db"]["username"]
	$_josh["db"]["password"]
	$_josh["db"]["language"]	the lanugage to use -- currently mssql or mysql
	$_josh["db"]["database"]	the database it should connect to
	
	$_josh["email_default"]		the address general message should come from
	$_josh["email_admin"]		the address of the site administrator, for error reporting
	
	$_josh["javascript"]		the location of where to put javascript.js, if you're going to use the javascript functions
	$_josh["basedblanguage"]	mysql or mssql -- if it doesn't match db language, will try to translate
	$_josh["is_secure"]			true (use https) or false (don't)
	$_josh["error_log_api"]		post hook -- should be full urL, eg http://work.joshreisner.com/api/hook.php

in addition, there are some variables you can pass that will be overwritten if there is $_SERVER info to be had.  
so if joshlib is run from the command line, these variables would be good to have

	$_josh["request"]			this is an array, easiest way to set this is doing url_parse(http://www.yoursite.com/yourfolder/yourpage.php?query=whatever)
	$_josh["referrer"]			same as request
	$_josh["folder"]			/ or \
	$_josh["newline"]			\n or \r\n
	$_josh["root"]				path to the site, eg /Users/yourusername/Sites/thissite
	$_josh["slow"]				true or false; whether to use javascript when redirecting (true) or header variables (false)
	$_josh["mobile"]			true or false

misc functions defined here:
	cookie
	daysInMonth
	debug
	geocode
	
misc classes defined here:
	table
	form
	
*/
$_josh["time_start"] = microtime(true);	//start the processing time stopwatch -- use format_time_exec() to access this

//set up error handling.  needs to go first to handle subsequent errors
	error_reporting(E_ALL);
	ini_set('display_errors', TRUE);
	ini_set('display_startup_errors', TRUE);
	$_josh["joshlib_folder"]	= dirname(__file__);
	//$_josh["debug"]	= true;
	if (!isset($_josh["debug"])) $_josh["debug"] = false;
	require($_josh["joshlib_folder"] . "/error.php");
	set_error_handler("error_handle_php");
	set_exception_handler("error_handle_exception");
	error_debug("error handling set up", __file__, __line__);
	
//set static variables
	$_josh["debug_log"]				= array();	//for holding execution messages
	$_josh["drawn"]["javascript"] 	= false;	//only include javascript.js once
	$_josh["drawn"]["focus"]		= false;	//only autofocus on one form element
	$_josh["ignored_words"]			= array("1","2","3","4","5","6","7","8","9","0","about","after","all","also","an","and","another","any","are",
									"as","at","be","because","been","before","being","between","both","but","by","came","can","come",
									"could","did","do","does","each","else","for","from","get","got","has","had","he","have","her","here",
									"him","himself","his","how","if","in","into","is","it","its","just","like","make","many","me","might",
									"more","most","much","must","my","never","now","of","on","only","or","other","our","out","over","re",
									"said","same","see","should","since","so","some","still","such","take","than","that","the","their",
									"them","then","there","these","they","this","those","through","to","too","under","up","use","very",
									"want","was","way","we","well","were","what","when","where","which","while","who","will","with",
									"would","you","your","a","b","c","d","e","f","g","h","i","j","k","l","m","n","o","p","q","r","s",
									"t","u","v","w","x","y","z",""); //ignore these words when making search indexes
	$_josh["month"]					= date("n");
	$_josh["months"]				= array("January", "February", "March", "April", "May", "June", "July", "August", "September", "October", "November", "December");
	$_josh["mos"]					= array("Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sep", "Oct", "Nov", "Dec");
	$_josh["numbers"]				= array("zero","one","two","three","four","five","six","seven","eight","nine");
	$_josh["queries"]				= array();	//for counting trips to the database
	$_josh["today"]					= date("j");
	$_josh["year"]					= date("Y");

//get includes
	require($_josh["joshlib_folder"] . "/array.php");
	require($_josh["joshlib_folder"] . "/db.php");
	require($_josh["joshlib_folder"] . "/draw.php");
	require($_josh["joshlib_folder"] . "/email.php");
	require($_josh["joshlib_folder"] . "/file.php");
	require($_josh["joshlib_folder"] . "/format.php");
	require($_josh["joshlib_folder"] . "/html.php");
	require($_josh["joshlib_folder"] . "/url.php");

//parse environment variables
	if (isset($_SERVER) && isset($_SERVER["HTTP_HOST"]) && isset($_SERVER["SCRIPT_NAME"])) { //this could not be set if this were running from the command line (eg by a cron)
		//build request as string, then set it to array with url_parse
		$_josh["request"] = (isset($_SERVER["HTTPS"]) && ($_SERVER["HTTPS"] == "on")) ? "https" : "http";
		$_josh["request"] .= "://" . $_SERVER["HTTP_HOST"];
		if (isset($_SERVER["REDIRECT_URL"])) { //we are using mod_rewrite
			$_josh["request"] .= $_SERVER["REQUEST_URI"];
			$_GET = array_merge($_GET, url_query_parse($_josh["request"]));
		} else {
			$_josh["request"] .= $_SERVER["SCRIPT_NAME"];
			if (isset($_SERVER["QUERY_STRING"])) $_josh["request"] .= "?" . $_SERVER["QUERY_STRING"];
		}
		$_josh["request"] = url_parse($_josh["request"]);
			
		//platform-specific info
		if (isset($_SERVER["SERVER_SOFTWARE"]) && strstr($_SERVER["SERVER_SOFTWARE"], "Microsoft")) { //platform is PC
			$_josh["folder"]			= "\\";
			$_josh["newline"]			= "\r\n";
			//$_josh["root"]				= str_replace(str_replace("/", "\\", $_josh["request"]["path"]), "", str_replace("\\\\", "\\foo", $_SERVER["PATH_TRANSLATED"]));
			$_josh["root"]				= str_replace($_josh["request"]["path"], "", $_SERVER["PATH_TRANSLATED"]);
			$_josh["slow"]				= true;
		} else { //platform is UNIX or Mac
			$_josh["folder"]			= "/";
			$_josh["newline"]			= "\n";
			$_josh["root"]				= $_SERVER["DOCUMENT_ROOT"];
			if (!isset($_josh["slow"]))	$_josh["slow"] = false;
		}
		
		//only checking for iphone right now
		$_josh["request"]["mobile"]		= (isset($_SERVER["HTTP_USER_AGENT"]) && strstr($_SERVER["HTTP_USER_AGENT"], "iPhone"));
	 	$_josh["referrer"]				= (isset($_SERVER["HTTP_REFERER"]))	? url_parse($_SERVER["HTTP_REFERER"]) : false;
	} else {
		//set defaults and hope for the best
		if (!isset($_josh["debug"]))	$_josh["debug"]		= false;
		if (!isset($_josh["request"]))	$_josh["request"]	= false;
		if (!isset($_josh["folder"]))	$_josh["folder"]	= "/";
		if (!isset($_josh["newline"]))	$_josh["newline"]	= "\n";
		if (!isset($_josh["root"]))		$_josh["root"]		= false;
		if (!isset($_josh["slow"]))		$_josh["slow"]		= false;
		if (!isset($_josh["mobile"]))	$_josh["mobile"]	= false;
		if (!isset($_josh["referrer"]))	$_josh["referrer"]	= false;
	}
	
	
//get configuration variables
	if (!isset($_josh["write_folder"])) $_josh["write_folder"] = "/_site";
	if (!isset($_josh["config"])) $_josh["config"] = $_josh["write_folder"] . "/config-" . $_josh["request"]["sanswww"] . ".php";
	if (file_exists($_josh["config"])) {
		error_debug("<b>configure</b> found file", __file__, __line__);
		require($_josh["config"]);
	} elseif (file_exists($_josh["root"] . $_josh["config"])) {
		error_debug("<b>configure</b> found file", __file__, __line__);
		require($_josh["root"] . $_josh["config"]);
	} else {
		error_debug("<b>configure</b> couldn't find config file", __file__, __line__);
	}


//set defaults for configuration for variables it didn't find
	if (!isset($_josh["db"]["location"]))	$_josh["db"]["location"]	= "localhost";
	if (!isset($_josh["db"]["language"]))	$_josh["db"]["language"]	= "mysql";
	if (!isset($_josh["basedblanguage"]))	$_josh["basedblanguage"]	= $_josh["db"]["language"];
	if (!isset($_josh["is_secure"]))		$_josh["is_secure"]			= false;
	if (!isset($_josh["email_admin"]))		$_josh["email_admin"]		= "josh@joshreisner.com";
	if (!isset($_josh["email_default"]))	$_josh["email_default"]		= "josh@joshreisner.com";
	if (!isset($_josh["error_log_api"]))	$_josh["error_log_api"]		= false;
		
	
//set error reporting level by determining whether this is a dev or live situation
	if (isset($_SERVER["HTTP_HOST"]) && (format_text_starts("dev-", $_SERVER["HTTP_HOST"]) || format_text_starts("beta.", $_SERVER["HTTP_HOST"]) || format_text_ends(".site", $_SERVER["HTTP_HOST"]))) {
		$_josh["mode"] = "dev";
		//error reporting already set above
	} else {
		$_josh["mode"] = "live";
		error_reporting(0);
	}


//handle https forwarding
	if (isset($_josh["request"]["protocol"])) {
		if ($_josh["is_secure"] && ($_josh["request"]["protocol"] != "https")) {
			url_change("https://" . $_josh["request"]["host"] . $_josh["request"]["path_query"]);
		} elseif (!$_josh["is_secure"] && ($_josh["request"]["protocol"] != "http")) {
			url_change("http://" . $_josh["request"]["host"] . $_josh["request"]["path_query"]);
		}
	}


//set convenience state variables and escape quotes if necessary
	$_josh["getting"]	= !empty($_GET);
	if ($_josh["getting"]) foreach($_GET as $key=>$value) $_GET[$key] = format_quotes($value);
	
	$_josh["uploading"] = false;
	if (!empty($_FILES)) {
		foreach($_FILES as &$file) {
			if (!empty($file["name"])) {
				$file["name"] = format_quotes($file["name"]);
				$_josh["uploading"] = true;
			}
		}
	}
	
	
	$_josh["posting"]	= !empty($_POST);
	if ($_josh["posting"]) foreach($_POST as $key=>$value) $_POST[$key] = format_quotes($value);
	
	$_josh["editing"]	= url_id();
	
	
//special functions that don't yet fit into a category

function cookie($name=false, $value=false, $session=false) {
	global $_josh, $_COOKIE, $_SERVER;

	if ($_josh["debug"]) {
		error_debug("not actually setting cookie because buffer is already broken by debugging", __file__, __line__);
		return false;
	}

	if ($name) {
		$time = ($value) ? mktime(0, 0, 0, 1, 1, 2030) : time()-3600;
		if ($session) $time = 0; //expire at the end of the session
		if (!$value) $value = "";
		$_COOKIE[$name] = $value;
		setcookie($name, $value, $time, "/", "." . $_josh["request"]["domain"]);
	} elseif (isset($_SERVER['HTTP_COOKIE'])) {
	    $cookies = explode(';', $_SERVER['HTTP_COOKIE']);
	    foreach($cookies as $cookie) {
	        $parts = explode('=', $cookie);
	        $name = trim($parts[0]);
	        setcookie($name, '', time()-1000);
	        setcookie($name, '', time()-1000, '/');
	    }
	}
	$_josh["slow"] = true;
}

function daysInMonth($month, $year=false) {
	global $_josh;
	if (!$year) $year = $_josh["year"];
	return date("d", mktime(0,0,0, $month + 1, 0, $year));
}

function debug() {
	global $_josh;
	$_josh["debug"] = true;
}

function geocode($address, $zip) {
	global $_josh;
	$result = file("http://maps.google.com/maps/geo?q=" . urlencode($address . ", " . $zip) . "&output=csv&oe=utf8&sensor=false&key=" . $_josh["google"]["mapkey"]);
	if (list($status, $accuracy, $latitude, $longitude) = explode(",", utf8_decode($result[0]))) return array($latitude, $longitude);
	return false;
}

//table class
class table {
	var $columns	= array();
	var $return		= "";
	var $draggable	= false;

	public function __construct($name="table") {
		$this->name = $name;
	}
	
	function col($name, $class=false, $title=false, $width=false) {
		$this->columns[] = compact("name", "class", "title", "width");
	}

	function draggable($target, $draghandle=false) {
		$this->draggable = true;
		$this->target = $target;
		$this->draghandle = $draghandle;
	}
	
	function drawHeader() {
		$this->return .= '<thead><tr>';
		foreach ($this->columns as $c) {
			$this->return .= '<th class="' . $c["name"];
			if ($c["class"]) $this->return .= " " . $c["class"];
			$this->return .= '"';
			if ($c["width"]) $this->return .= " style='width:" . $c["width"] . "; min-width:" . $c["width"] . "px'";
			$this->return .= '>';
			$this->return .= ($c["title"]) ? $c["title"] : format_text_human($c["name"]);
			$this->return .= '</th>';
		}
		$this->return .= '</tr></thead>';
	}
	
	function draw($values, $errmsg="Sorry, no results!", $hover=false, $total=false) {
		global $_josh;
		$colspan = count($this->columns);
		$totals = array();
		$this->return = $_josh["newline"] . '<!--table start-->' . $_josh["newline"] . '<table cellspacing="0" class="' . $this->name . '">' . $_josh["newline"];
		if (!$colspan) {
			//there were no columns defined.  no columns, no table
			$this->return .= '<tr><td class="empty">Sorry, no columns defined!</td></tr>' . $_josh["newline"];
		} elseif (!count($values)) {
			//no rows, return errmsg
			$this->return .= '<tr><td class="empty">' . $errmsg . '</td></tr>' . $_josh["newline"];
		} else {
			$row	= "odd";
			$group	= "";
			$this->return .= $this->drawHeader() . '<tbody id="' . $this->name . '">';
			
			foreach ($values as $v) {
				if (isset($v["group"]) && ($group != $v["group"])) {
					$this->return .= '<tr><td colspan="' . $colspan . '" class="group">' . $v["group"] . '</td></tr>' . $_josh["newline"];
					$row = "odd";
					$group = $v["group"];
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
				$this->return .= '<tr class="' . $row;
				if (isset($v["class"]) && !empty($v["class"])) $this->return .= " " . $v["class"] . " " . $row . "-" . $v["class"];
				$this->return .= '"';
				if ($hover) {
					if (isset($v["link"])) $this->return .= ' onclick="location.href=\'' . $v["link"] . '\';"';
					//hover class must exist
					$this->return .= ' onmouseover="css_add(this, \'hover\');"';
					$this->return .= ' onmouseout="css_remove(this, \'hover\');"';
				}
				if ($this->draggable) {
					//draggable forces an id to be set
					$this->return .= ' id="item_' . $v["id"] . '"';
				}
				$this->return .= '>' . $_josh["newline"];
				foreach ($this->columns as $c) {
					if (!strlen(trim($v[$c["name"]]))) $v[$c["name"]] = "&nbsp;";
					$this->return .= '<td class="' . $c["name"];
					if ($c["class"]) $this->return .= " " . $c["class"];
					$this->return .= '">' . $v[$c["name"]] . '</td>' . $_josh["newline"];
				}
				$this->return .= '</tr>' . $_josh["newline"];
				$row = ($row == "even") ? "odd" : "even";
			}
			$this->return .= '</tbody>';
		}
		if ($total) {
			$this->return .= "<tr class='total'><tfoot>";
			foreach ($this->columns as $c) {
				$this->return .= '<td class="' . $c["name"];
				if ($c["class"]) $this->return .= " " . $c["class"];
				$this->return .= '">';
				if (isset($totals[$c["name"]])) {
					$this->return .= $totals[$c["name"]];
				} else {
					$this->return .= "&nbsp;";
				}
				$this->return .= '</td>' . $_josh["newline"];
			}
			$this->return .= "</tr></tfoot>" . $_josh["newline"];
		}
		$this->return .= '</table>';
		
		//drag and drop table
		if ($this->draggable) {
		$this->return .= draw_javascript("
			function reorder() {
				var ampcharcode= '%26';
				var serializeOpts = Sortable.serialize('" . $this->name . "') + unescape(ampcharcode) + 'key=" . $this->name . "' + unescape(ampcharcode) + 'update=" . $this->name . "';
				var options = { method:'post', parameters:serializeOpts };
				new Ajax.Request('" . $this->target . "', options);
				newOrder = Sortable.sequence('" . $this->name . "');
				var state = 'odd';
				for (var i = 0; i < newOrder.length; i++) {
					document.getElementById('item_' + newOrder[i]).className = state;
					state = (state == 'odd') ? 'even' : 'odd';
				}
			}
			Sortable.create('" . $this->name . "', { tag:'tr', " . (($this->draghandle) ? "handle:'" . $this->draghandle . "', " : "") . "ghosting:true, constraint:'vertical', onUpdate:reorder, tree:true });
			");
		}
		return $this->return;
	}

}


//form class
class form { 
	var $table = false;
	var $fields = array();
	var $values = array();
		
	function addField($array) {
		//defaults
		$type = $value = $class = $name = $label = $required = $append = $sql = $action = $onchange = $additional = $maxlength = $options_table = $option_id = $object_id = $options = $linking_table = false;
		
		//load inputs
		if (!is_array($array)) return error_handle("array not set");
		extract($array);
		
		//type is required
		if (!$type) return error_handle("type not set");
		
		if ((($type == "text") || ($type == "password")) && !isset($array["additional"]) && $required) $additional = "(required)";

		error_debug("adding field " . $label, __file__, __line__);
		
		if (!$name)	$name	= format_text_code($label);
		if (!$label) $label = format_text_human($name);
		if (!$value) $value	= (isset($this->values[$name])) ? $this->values[$name] : false;
		if (!$class) $class	= "";
		if (!$option_id) $option_id	= "option_id";
		if (!$object_id) $object_id	= "object_id";
		
		if ($additional) $additional = "<span class='additional'>" . $additional . "</span>";
		
		if ($type == "checkbox") {
			$additional = $label;
			$label = "&nbsp;";
		}
		
		//package and save
		$this->fields[] = compact("name", "type", "label", "value", "append", "required", "sql", "class", "action", "onchange", "additional", "options_table", "option_id", "object_id", "options", "linking_table", "maxlength");
	}
	
	function addGroup($string="") {
		$this->addField(array("name"=>"group", "type"=>"group", "value"=>$string));
	}
	
	function addRow($field) {
		global $_josh;
		extract($field);
		$return = "";
		if ($type == "group") {
			$return .= '<dt class="group">' . $value . '</dt>';
		} elseif ($type == "hidden") {
			$return .= draw_form_hidden($name, $value);
		} else {
			if ($label) {
				$return .= '<dt class="' . $type . '">' . $label;
				if ($additional && ($type == "checkboxes")) $return .= $additional;
				$return .= '</dt>' . $_josh["newline"];
			}
			$return .= '<dd class="' . $type . '">';
			if ($type == "checkbox") {
				$return .= '<div class="checkbox_option">' . draw_form_checkbox($name, $value) . '<span class="option_name" onclick="javascript:form_checkbox_toggle(\'' . $name . '\');">' . $additional . '</span></div>';
			} elseif ($type == "checkboxes") {
				if ($value) {
					$options = db_query("SELECT o.id, o.title, (SELECT COUNT(*) FROM $linking_table l WHERE l.$option_id = o.id AND l.$object_id = $value) checked FROM $options_table o ORDER BY o.title");
				} else {
					$options = db_query("SELECT id, title, 0 checked FROM $options_table ORDER BY title");
				}
				while ($o = db_fetch($options)) {
					$name = "chk_" . str_replace("_", "-", $options_table) . "_" . $o["id"];
					$return .= '<div class="checkbox_option">' . draw_form_checkbox($name, $o["checked"]) . '<span class="option_name" onclick="javascript:form_checkbox_toggle(\'' . $name . '\');">' . $o["title"] . '</span></div>';
				}
			} elseif ($type == "date") {
				$return .= draw_form_date($name, $value, false) . $additional;
			} elseif ($type == "datetime") {
				$return .= draw_form_date($name, $value, true) . $additional;
			} elseif ($type == "file") {
				$return .= draw_form_file($name, $class, $onchange) . $additional;
			} elseif ($type == "note") {
				$return .= "<div class='note'>" . $additional . "</div>";
			} elseif ($type == "password") {
				$return .= draw_form_password($name, $value, $class, 255, false) . $additional;
			} elseif ($type == "radio") {
				if (!$options) {
					if (!$sql) $sql = "SELECT id, name FROM options_" . str_replace("_id", "", $name);
					$options = db_array($sql);
				}
				if ($append) while (list($addkey, $addval) = each($append)) $options[$addkey] = $addval;
				foreach ($options as $id=>$description) {
					$return .= '<div class="radio_option">' . draw_form_radio($name, $id, ($value == $id), $class) . $description . '</div>';
				}
			} elseif ($type == "readonly") {
				$return .= $value . " " . $additional;
			} elseif ($type == "select") {
				if (!$options) {
					if (!$sql) $sql = "SELECT id, name FROM options_" . str_replace("_id", "", $name);
					$options = db_array($sql);
				}
				if ($append) while (list($addkey, $addval) = each($append)) $options[$addkey] = $addval;
				$return .= draw_form_select($name, $options, $value, $required, $class, $action) . $additional;
			} elseif ($type == "submit") {
				$return .= draw_form_submit($value, $class) . $additional;
			} elseif ($type == "text") {
				$return .= draw_form_text($name, $value, $class, $maxlength, false, false) . $additional;
			} elseif ($type == "textarea") {
				$return .= draw_form_textarea($name, $value, $class) . $additional;
			} 
			$return .= '</dd>' . $_josh["newline"];
		}
		return $return;
	}
	
	function draw($validate=false, $values=false) {
		global $_josh, $_GET;
		
		//sometimes you can get to a form from multiple places.  you might want to return the way you came.
		if (isset($_josh["referrer"]["path_query"])) {
			$this->addField(array("type"=>"hidden", "name"=>"return_to", "value"=>$_josh["referrer"]["url"]));
		} elseif (isset($_GET["return_to"])) {
			$this->addField(array("type"=>"hidden", "name"=>"return_to", "value"=>$_GET["return_to"]));
		}
		
		$return = '<form method="post" enctype="multipart/form-data" accept-charset="UTF-8" action="' . $_josh["request"]["path_query"] . '"';
		if ($validate) $return .= ' name="' . $validate . '" onsubmit="javascript:return validate_' . $validate . '(this);"';
		$return .= '>
			<dl class="' . $validate . '">';

		//add fields
		foreach ($this->fields as $field) {
			//values needs to be an associative array
			if ($values && isset($values[$field["name"]])) $field["value"] = $values[$field["name"]];
			$return .= $this->addRow($field);
		}
		
		$return .= '</dl></form>';
		return $return;
	}
}

?>