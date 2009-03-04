<?php
/* welcome to joshlib
http://code.google.com/p/joshlib/ (wiki / documentation / tracking)
http://joshlib.joshreisner.com/ (eventual website)

this hmac thing is drivi
*/
$_josh["time_start"] = microtime(true);	//start the processing time stopwatch -- use format_time_exec() to access this

//parse environment variables
	/* 
	because these can change by platform, $_josh["server"] variables are supposed to be direct representations
	of particular variables.  they're not really for script use, although they can be.  $_josh["request"] and $_josh["referrer"]
	are better for those purposes
	*/
	$_josh["server"]["host"]		= (isset($_SERVER["HTTP_HOST"])) ? $_SERVER["HTTP_HOST"] : false;
	$_josh["server"]["mobile"]		= (isset($_SERVER["HTTP_HOST"]) && strstr($_SERVER["HTTP_USER_AGENT"], "iPhone"));
	$_josh["server"]["refer"]		= (isset($_SERVER["HTTP_REFERER"])) ? $_SERVER["HTTP_REFERER"] : false;
	$_josh["server"]["request"]		= $_SERVER["SCRIPT_NAME"];
	$_josh["server"]["protocol"]	= (isset($_SERVER["HTTPS"]) && ($_SERVER["HTTPS"] == "on")) ? "https" : "http";
	$_josh["server"]["query"]		= (isset($_SERVER["QUERY_STRING"])) ? $_SERVER["QUERY_STRING"] : false;
	
	if (isset($_SERVER["SERVER_SOFTWARE"]) && strstr($_SERVER["SERVER_SOFTWARE"], "Microsoft")) {
		//iis
		$_josh["server"]["isunix"]	= false;
		$_josh["folder"]			= "\\";
		$_josh["newline"]			= "\r\n";
		$_josh["root"]				= str_replace(str_replace("/", "\\", $_josh["server"]["request"]), "", str_replace("\\\\", "\\", $_SERVER["PATH_TRANSLATED"]));
		$_josh["slow"]				= true;
	} else {
		//apache
		$_josh["server"]["isunix"]	= true;
		$_josh["folder"]			= "/";
		$_josh["newline"]			= "\n";
		$_josh["root"]				= $_SERVER["DOCUMENT_ROOT"];
		if (!isset($_josh["slow"]))	$_josh["slow"] = false;
	}
	
	//echo $_josh["root"];
	//phpinfo();


//set possibly-already-set variables
	if (!isset($_josh["debug"]))	$_josh["debug"]		= false;
	if (!isset($_josh["styles"]))	$_josh["styles"]	= array("field"=>"josh_field", "select"=>"josh_field", "button"=>"josh_button", "textarea"=>"josh_textarea");


//set static variables
	$_josh["today"]				= date("j");		//useful date info.  todo -- combine these into an array
	$_josh["year"]				= date("Y");
	$_josh["month"]				= date("n");
	$_josh["colors"]			= array(	//all the colors of the joshserver rainbow
									"white"	=>"#ffffff",
									"grey1"	=>"#f0f0f0",
									"grey2"	=>"#e6e6e6", 
									"grey3"	=>"#cccccc",
									"grey4" =>"#aaaaaa",
									"grey5"	=>"#777777",
									"red1"	=>"#cc9999",
									"red2"	=>"#cc5555",
									"yellow"=>"#ffffcc"
									);
	$_josh["drawn"]["bottom"]	= false;
	$_josh["drawn"]["css"]		= false;	//only run josh_draw_css() once
	$_josh["drawn"]["js"]		= false;	//only run josh_draw_javascript() once
	$_josh["drawn"]["focus"]	= false;	//only autofocus on one form
	$_josh["drawn"]["top"]		= false;
	$_josh["forms"]				= array();	//for handling multiple forms in a page (eg which one gets autofocus?)
	$_josh["ignored_words"]		= array("1","2","3","4","5","6","7","8","9","0","about","after","all","also","an","and","another","any","are",
									"as","at","be","because","been","before","being","between","both","but","by","came","can","come",
									"could","did","do","does","each","else","for","from","get","got","has","had","he","have","her","here",
									"him","himself","his","how","if","in","into","is","it","its","just","like","make","many","me","might",
									"more","most","much","must","my","never","now","of","on","only","or","other","our","out","over","re",
									"said","same","see","should","since","so","some","still","such","take","than","that","the","their",
									"them","then","there","these","they","this","those","through","to","too","under","up","use","very",
									"want","was","way","we","well","were","what","when","where","which","while","who","will","with",
									"would","you","your","a","b","c","d","e","f","g","h","i","j","k","l","m","n","o","p","q","r","s",
									"t","u","v","w","x","y","z",""); //ignore these words when making search indexes
	$_josh["months"]			= array("January", "February", "March", "April", "May", "June", "July", "August", "September", "October", "November", "December");
	$_josh["mos"]				= array("Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sep", "Oct", "Nov", "Dec");
	$_josh["numbers"]			= array("zero","one","two","three","four","five","six","seven","eight","nine");
	$_josh["queries"]			= array();	//for counting trips to the database
	

//get library files
	require("error.php");
	require("array.php");
	require("db.php");
	require("draw.php");
	require("email.php");
	require("file.php");
	require("format.php");
	require("htmlawed.php");
	require("url.php");


//hook up error.php
	set_error_handler("error_handle_php");
	
//find out about environment (you can use draw_array($array, false) to display these arrays for debugging -- see line 75 below)
	$_josh["request"]	= ($_josh["server"]["host"])	? url_parse($_josh["server"]["protocol"] . "://" . $_josh["server"]["host"] . $_josh["server"]["request"] . "?" . $_josh["server"]["query"]) : false;
	$_josh["referrer"]	= ($_josh["server"]["refer"])	? url_parse($_josh["server"]["refer"]) : false;
	
	
//get configuration variables
	configure();
	
	
//set error reporting level by determining whether this is a dev or live situation
	if (isset($_josh["mode"]) && ($_josh["mode"] == "dev")) {
		//1: you can set the option manually
		error_reporting(E_ALL);
	} elseif (format_text_starts("dev-", $_SERVER["HTTP_HOST"]) || format_text_starts("beta.", $_SERVER["HTTP_HOST"]) || format_text_ends(".site", $_SERVER["HTTP_HOST"])) {
		//2: urls start with dev- or end with .site are automatically considered dev sites
		$_josh["mode"] = "dev";
		error_reporting(E_ALL);
	} else {
		$_josh["mode"] = "live";
		error_reporting(0);
	}


//handle https
	if (isset($_josh["request"]["protocol"])) {
		if ($_josh["is_secure"] && ($_josh["request"]["protocol"] != "https")) {
			url_change("https://" . $_josh["request"]["host"] . $_josh["request"]["path_query"]);
		} elseif (!$_josh["is_secure"] && ($_josh["request"]["protocol"] != "http")) {
			url_change("http://" . $_josh["request"]["host"] . $_josh["request"]["path_query"]);
		}
	}


//escape quotes if necessary
	$_josh["getting"] = !empty($_GET);
	if ($_josh["getting"]) foreach($_GET as $key=>$value) $_GET[$key] = format_quotes($value);
	
	$_josh["posting"] = !empty($_POST);
	if ($_josh["posting"]) foreach($_POST as $key=>$value) $_POST[$key] = format_quotes($value);


//extract for easier accessibility
	extract($_josh);
	
	
//special functions that don't fit into a category (yet)
function configure() {
	global $_josh;
	error_debug("<b>configure</b> running");
	$filename = isset($_josh["config"]) ? $_josh["config"] : "/_site/config-" . $_josh["request"]["sanswww"] . ".php";
	if (file_exists($filename)) {
		error_debug("<b>configure</b> found file");
		require($filename);
	} elseif (file_exists($_josh["root"] . $filename)) {
		error_debug("<b>configure</b> found file");
		require($_josh["root"] . $filename);
	} else {
		error_debug("<b>configure</b> couldn't find config file");
		$_josh["mode"] = "dev";
		error_handle("config file missing", "joshserver couldn't find the config file which should be at <span class='josh_code'>" . $_josh["root"] . $filename . "</span>.  please create a file there and put db connection info in it.");
	}

	//set defaults
	if (!isset($_josh["db"]["location"]))	$_josh["db"]["location"]	= "localhost";
	if (!isset($_josh["db"]["language"]))	$_josh["db"]["language"]	= "mysql";
	if (!isset($_josh["basedblanguage"]))	$_josh["basedblanguage"]	= $_josh["db"]["language"];
	if (!isset($_josh["is_secure"]))		$_josh["is_secure"]			= false;
	if (!isset($_josh["email_admin"]))		$_josh["email_admin"]		= "josh@joshreisner.com";
	if (!isset($_josh["email_default"]))	$_josh["email_default"]		= "josh@joshreisner.com";
	
	//required variables
	if (!isset($_josh["db"]["username"]) || 
		!isset($_josh["db"]["password"]) || 
		!isset($_josh["db"]["database"])) {
		error_handle("config variables missing", "joshserver found a db file but was missing a username, password or database variable.");
	}
	return true;
}

function cookie($name=false, $value=false) {
	global $_josh, $_COOKIE, $_SERVER;
	//if you don't specify name, it will try to delete all the cookies
	if ($name) {
		$time = ($value) ? mktime(0, 0, 0, 1, 1, 2030) : time()-3600;
		$_COOKIE[$name] = $value;
		if (!$value) $value = "";
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
	var $columns = array();
	var $return  = "";
	
	function col($name, $class=false, $title=false, $width=false) {
		$this->columns[] = compact("name", "class", "title", "width");
	}

	function drawHeader() {
		$this->return .= '<tr>';
		foreach ($this->columns as $c) {
			$this->return .= '<th class="' . $c["name"];
			if ($c["class"]) $this->return .= " " . $c["class"];
			$this->return .= '"';
			if ($c["width"]) $this->return .= " width='" . $c["width"] . "'";
			$this->return .= '>';
			$this->return .= ($c["title"]) ? $c["title"] : format_text_human($c["name"]);
			$this->return .= '</th>';
		}
		$this->return .= '</tr>';
	}
	
	function draw($values, $errmsg="Sorry, no results!", $hover=false, $total=false) {
		global $_josh;
		$colspan = count($this->columns);
		$totals = array();
		$this->return = $_josh["newline"] . '<!--table start-->' . $_josh["newline"] . '<table cellspacing="0">' . $_josh["newline"];
		if (!$colspan) {
			$this->return .= '<tr><td class="empty">Sorry, no columns defined!</td></tr>' . $_josh["newline"];
		} elseif (!count($values)) {
			$this->return .= '<tr><td class="empty">' . $errmsg . '</td></tr>' . $_josh["newline"];
		} else {
			$row	= "odd";
			$group	= "";
			$this->return .= $this->drawHeader();
			
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
							if (isset($totals[$t])) {
								$totals[$t] += $v[$t];
							} else {
								$totals[$t] = $v[$t];							
							}
						}
					}
				}
				$this->return .= '<tr class="' . $row;
				if (isset($v["class"]) && !empty($v["class"])) $this->return .= " " . $v["class"] . " " . $row . "-" . $v["class"];
				$this->return .= '"';
				if ($hover) {
					if (isset($v["link"])) $this->return .= ' onclick="location.href=\'' . $v["link"] . '\';"';
					//hover class must exist
					$this->return .= ' onmouseover="cssAdd(this, \'hover\');"';
					$this->return .= ' onmouseout="cssRemove(this, \'hover\');"';
				}
				$this->return .= '>';
				foreach ($this->columns as $c) {
					//if (!strlen($v[$c["name"]])) $v[$c["name"]] = "&nbsp;";
					$this->return .= '<td class="' . $c["name"];
					if ($c["class"]) $this->return .= " " . $c["class"];
					$this->return .= '">' . $v[$c["name"]] . '</td>';
				}
				$this->return .= '</tr>' . $_josh["newline"];
				$row = ($row == "even") ? "odd" : "even";
			}
		}
		if ($total) {
			$this->return .= "<tr class='total'>";
			foreach ($this->columns as $c) {
				$this->return .= '<td class="' . $c["name"];
				if ($c["class"]) $this->return .= " " . $c["class"];
				$this->return .= '">';
				if (isset($totals[$c["name"]])) {
					$this->return .= $totals[$c["name"]];
				} else {
					$this->return .= "&nbsp;";
				}
				$this->return .= '</td>';
			}
			$this->return .= "</tr>";
		}
		$this->return .= '</table>';
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
		$type = $value = $class = $name = $label = $required = $append = $sql = $action = $additional = $maxlength = $options_table = $options = $linking_table = false;
		
		//load inputs
		if (!is_array($array)) return error_handle("array not set");
		extract($array);
		
		//type is required
		if (!$type) return error_handle("type not set");
		
		if ((($type == "text") || ($type == "password")) && !isset($array["additional"]) && $required) $additional = "(required)";

		error_debug("adding field " . $label);
		
		if (!$name)	$name	= format_text_code($label);
		if (!$label) $label	= format_text_human($name);
		if (!$value) $value	= (isset($this->values[$name])) ? $this->values[$name] : false;
		if (!$class) $class	= $type;
		
		if ($additional) $additional = "<span class='additional'>" . $additional . "</span>";
		
		if ($type == "checkbox") {
			$additional = $label;
			$label = false;
		}
		
		//package and save
		$this->fields[] = compact("name", "type", "label", "value", "append", "required", "sql", "class", "action", "additional", "options_table", "options", "linking_table", "maxlength");
	}
	
	function addRow($field) {
		global $_josh;
		extract($field);
		$return = "";
		if ($type == "hidden") {
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
					$options = db_query("SELECT o.id, o.name, (SELECT COUNT(*) FROM $linking_table l WHERE l.option_id = o.id AND l.object_id = $value) checked FROM $options_table o ORDER BY o.name");
				} else {
					$options = db_query("SELECT id, name, 0 checked FROM $options_table ORDER BY name");
				}
				while ($o = db_fetch($options)) {
					$name = "chk_" . str_replace("_", "-", $options_table) . "_" . $o["id"];
					$return .= '<div class="checkbox_option">' . draw_form_checkbox($name, $o["checked"]) . '<span class="option_name" onclick="javascript:form_checkbox_toggle(\'' . $name . '\');">' . $o["name"] . '</span></div>';
				}
			} elseif ($type == "date") {
				$return .= draw_form_date($name, $value, false) . $additional;
			} elseif ($type == "datetime") {
				$return .= draw_form_date($name, $value, true) . $additional;
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
				$return .= draw_form_select($name, $options, $value, $required, $class, $action);
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
	
	function draw($validate=false) {
		global $_josh, $_GET;
		//sometimes you can get to a form from multiple places.  you might want to return the way you came.
		if (isset($_josh["referrer"]["path_query"])) {
			$this->addField(array("type"=>"hidden", "name"=>"return_to", "value"=>$_josh["referrer"]["path_query"]));
		} elseif (isset($_GET["return_to"])) {
			$this->addField(array("type"=>"hidden", "name"=>"return_to", "value"=>$_GET["return_to"]));
		}
		
		$return = '<form method="post" action="' . $_josh["request"]["path_query"] . '"';
		if ($validate) $return .= ' name="' . $validate . '" onsubmit="javascript:return validate_' . $validate . '(this);"';
		$return .= '>
			<dl class="' . $validate . '">';

		//add fields
		foreach ($this->fields as $field) $return .= $this->addRow($field);
		
		$return .= '</dl></form>';
		return $return;
	}
}

?>