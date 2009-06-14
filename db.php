<?php
error_debug("including db.php", __file__, __line__);

function db_array($sql, $array=false, $prepend_id=false, $prepend_value=false, $limit=false) {
	//exec a sql query and return an array of the results
	//need more description of purpose for prepend_id, prepend_value
	//what's the difference between this and db_table?
	global $_josh;
	$result = db_query($sql, $limit);
	if (!$array) $array = array();
	$key = false;
	
	if (stristr($sql, ",")) {
		//todo ~ need more elegant way of determining multi-column results
		//selecting more than one column, return associative array (no point in selecting more than two columns)
		while ($r = db_fetch($result)) {
			if (!$key) $key = array_keys($r);
			if ($prepend_id) $r[$key[0]] = $prepend_id . $r[$key[0]];
			if ($prepend_value) $r[$key[1]] = $prepend_value . $r[$key[1]];
			$array[$r[$key[0]]] = $r[$key[1]];
		}		
	} else {
		while ($r = db_fetch($result)) {
			if (!$key) $key = array_keys($r);
			if ($prepend_id) $r[$key[0]] = $prepend_id . $r[$key[0]];
			//if ($prepend_value) $r[$key[1]] = $prepend_value . $r[$key[1]];
			$array[] = $r[$key[0]];
		}		
	}
	
	return $array;
}

function db_backup($limit=false) {
	//outputs a gzip backup of the current database
	global $_josh;
	
	//default filename is /_site/backups/YYYY-MM-DD.sql -- delete any existing file of that name
	$folder = $_josh["write_folder"] . "/backups/";
	$target = $folder . date("Y-m-d") . ".gz";
	file_delete($target);
	
	if ($limit) {
		//limit the number of backup files that live in this directory
		if ($files = file_folder($folder, ".gz")) {
			$delete = count($files) - $limit + 1;
			for ($i = 0; $i < $delete; $i++) file_delete($files[$i]["path_name"]);
		}
	}
	
	//only works with mysql right now
	extract($_josh["db"]);
	if ($language != "mysql") error_handle("only mysql supported", "db_backup is a mysql-only function right now");

	//build command, socket hack, execute
	$command = "mysqldump --opt --host='$location' --user='$username' --password='$password' $database | gzip > " . $_josh["root"] . $target;
	if (isset($_josh["mysqldump_path"])) $command = $_josh["mysqldump_path"] . $command;
	$command = str_replace(":", "' --socket='", $command);
	system($command);
	
	if (file_check($target) > 200) { //sometimes when it fails, mysqldump creates a file that's 20B
		//export was a success.  return the path for linking.
		return $target;
	} else {
		//export failed
		file_delete($target);
		return false;
	}
}

function db_check($table, $column=false) {
	global $_josh;
	db_switch("information_schema");
	if ($column) {
		$column = " AND column_name = '$column'";
		$target = "columns";
	} else {
		$target = "tables";
	}
	$return = db_grab("SELECT * FROM $target WHERE table_schema = '{$_josh["db"]["database"]}' AND table_name = '$table'" . $column);
	db_switch();
	return $return;
}

//db_checkboxes("doc", "documents_to_categories", "documentID", "categoryID", $_GET["id"]);
function db_checkboxes($name, $linking_table, $object_col, $option_col, $id) {
	db_query("DELETE FROM $linking_table WHERE $object_col = " . $id);
	foreach ($_POST as $key => $value) {
		error_debug("<b>db_checkboxes</b> checking " . $key);
		@list($control, $field_name, $categoryID) = explode("_", $key);
		if (($control == "chk") && ($field_name == $name)) {
			db_query("INSERT INTO $linking_table ( $object_col, $option_col ) VALUES ( $id, $categoryID )");
		}
	}
}

function db_clear($tables=false) { //cant find where this is called from.  obsolete?
	global $_josh;
	$sql = ($_josh["db"]["language"] == "mssql") ? "SELECT name FROM sysobjects WHERE type='u' AND status > 0" : "SHOW TABLES FROM " . $_josh["db"]["database"];
	$tables = ($tables) ? explode(",", $tables) : db_array($sql);
	foreach ($tables as $table) db_query("DELETE FROM " . $table);
}

function db_close($keepalive=false) { //close connection and quit
	global $_josh;
	error_debug("<b>db_close</b> there were a total of " . count($_josh["queries"]) . " queries.");
	if (isset($_josh["db"]["pointer"])) {
		if ($_josh["db"]["language"] == "mysql") {
			@mysql_close($_josh["db"]["pointer"]);
		} elseif ($_josh["db"]["language"] == "mssql") {
			@mssql_close($_josh["db"]["pointer"]);
		}
		unset($_josh["db"]["pointer"]);
	}
	
	//new imap thing for work.josh
	if (isset($_josh["imap"]["pointer"])) {
		@imap_close($_josh["imap"]["pointer"]);
		unset($_josh["imap"]["pointer"]);
	}

	if (!$keepalive) exit;
}

function db_columns($tablename) {
	global $_josh;
	error_debug("<b>db_columns</b> running");
	$return = array();
	if ($_josh["db"]["language"] == "mysql") {
		$result = db_query("DESCRIBE " . $tablename);
		while ($r = db_fetch($result)) {
			$name = $r["Field"];
			@list($type, $length) = explode("(", str_replace(")", "", $r["Type"]));
			$required = ($r["Null"] == "YES") ? false : true;
			$default = $r["Default"];
			$return[] = compact("name","type","required","default");
		}
	} else {
		$return = db_table("SELECT 
				c.name, 
				t.name type, 
				CASE WHEN c.isnullable = 0 THEN 1 ELSE 0 END required, 
				m.text [default]
			FROM syscolumns c
			JOIN sysobjects o ON o.id = c.id
			JOIN systypes t on c.xtype = t.xtype
			LEFT JOIN syscomments m on c.cdefault = m.id
			WHERE o.name = '$tablename'
			ORDER BY c.colorder
			");
		foreach ($return as &$c) {
			//remove parens from default
			if ($c["default"]) $c["default"] = substr($c["default"], 1, strlen($c["default"]) - 2);
		}
	}
	return $return;
}

function db_date() {
	global $_josh;
	return ($_josh["db"]["language"] == "mssql") ? "GETDATE()" : "NOW()";
}

function db_datediff($date1=false, $date2=false) {
	global $_josh;
	if ($_josh["db"]["language"] == "mssql") {
		if (!$date1) $date1 = "GETDATE()";
		if (!$date2) $date2 = "GETDATE()";
		return "DATEDIFF(dd, " . $date1 . ", " . $date2 . ")";
	} elseif ($_josh["db"]["language"] == "mysql") {
		if (!$date1) $date1 = "NOW()";
		if (!$date2) $date2 = "NOW()";
		return "DATEDIFF(" . $date2 . ", " . $date1 . ")";
	}
}

function db_delete($table, $id=false) {
	//deleting an object does not update it
	global $_SESSION, $_GET;
	if (!$id) {
		if (isset($_GET["id"])) {
			$id = $_GET["id"];
		} else {
			error_handle("expecting \$_GET[\"id\"]", "db_delete is expecting an id variable");
		}
	}
	db_query("UPDATE $table SET 
		deleted_date = " . db_date() . ", 
		deleted_user = {$_SESSION["user_id"]}, 
		is_active = 0 
		WHERE id = " . $id);
}

function db_fetch($result) {
	global $_josh;
	if ($_josh["db"]["language"] == "mysql") {
		return mysql_fetch_assoc($result);
	} elseif ($_josh["db"]["language"] == "mssql") {
		return mssql_fetch_assoc($result);
	}
}

function db_fetch_field($result, $i) {
	global $_josh;
	if ($_josh["db"]["language"] == "mysql") {
		return mysql_fetch_field($result, $i);
	} elseif ($_josh["db"]["language"] == "mssql") {
		return mssql_fetch_field($result, $i);
	}
}

function db_field_type($result, $i) {
	global $_josh;
	if ($_josh["db"]["language"] == "mysql") {
		return mysql_field_type($result, $i);
	} elseif ($_josh["db"]["language"] == "mssql") {
		return mssql_field_type($result, $i);
	}
}

function db_found($result) {
	global $_josh;
	if ($_josh["db"]["language"] == "mysql") {
		return @mysql_num_rows($result);
	} elseif ($_josh["db"]["language"] == "mssql") {
		return @mssql_num_rows($result);
	}
}

function db_grab($query, $checking=false) {
	global $_josh;
	error_debug("<b>db_grab</b> running");
	$result = db_query($query, 1, $checking);
	if (!db_found($result)) {
		error_debug("grabbing value");
		return false;
	} else {
		$r = db_fetch($result);
		if (count($r) == 1) {
			$key = array_keys($r);
			$r = $r[$key[0]]; //if returning just one value, make it scalar
		}
		return $r;
	}
}

function db_id() {
	global $_josh;
	if ($_josh["db"]["language"] == "mysql") {
		return mysql_insert_id();
	} elseif ($_josh["db"]["language"] == "mssql") {
		return db_grab("SELECT @@IDENTITY");
	}
}

function db_key() {
	global $_josh;
	if ($_josh["db"]["language"] == "mssql") {
		return ""; //todo: not yet implemented for mssql
	} elseif ($_josh["db"]["language"] == "mysql") {
		return "REPLACE(REPLACE(ENCRYPT(UUID()), '/', '|'), '.', '!')";
	}
}

function db_num_fields($result) {
	global $_josh;
	if ($_josh["db"]["language"] == "mysql") {
		return mysql_num_fields($result);
	} elseif ($_josh["db"]["language"] == "mssql") {
		return mssql_num_fields($result);
	}
}

function db_open($location=false, $username=false, $password=false, $database=false, $language=false) {
	global $_josh;
	
	//skip if already connected
	if (isset($_josh["db"]["pointer"])) return;
	
	error_debug("<b>db_open</b> running");

	//reset variables if you're specifying which ones to use
	if ($location) $_josh["db"]["location"] = $location;
	if ($username) $_josh["db"]["username"] = $username;
	if ($password) $_josh["db"]["password"] = $password;
	if ($database) $_josh["db"]["database"] = $database;
	if ($language) $_josh["db"]["language"] = $language;
		
	//connect to db
	if (!isset($_josh["db"]["database"]) || !isset($_josh["db"]["username"]) || !isset($_josh["db"]["password"])) {
		error_handle("database variables error", "joshserver could not find the right database connection variables.  please fix this before proceeding.");
		exit;
	} elseif ($_josh["db"]["language"] == "mysql") {
		error_debug("<b>db_open</b> trying to connect mysql on " . $_josh["db"]["location"]);
		if (!$_josh["db"]["pointer"] = @mysql_connect($_josh["db"]["location"], $_josh["db"]["username"], $_josh["db"]["password"])) {
			error_handle("database connection error", "this application is not able to connect its database.  we're sorry for the inconvenience, the administrator is attempting to fix the issue.");
			exit;
		}
		mysql_set_charset("utf8", $_josh["db"]["pointer"]);
	} elseif ($_josh["db"]["language"] == "mssql") {
		error_debug("<b>db_open</b> trying to connect mssql on " . $_josh["db"]["location"] . " with username " . $_josh["db"]["username"]);
		if (!$_josh["db"]["pointer"] = @mssql_connect($_josh["db"]["location"], $_josh["db"]["username"], $_josh["db"]["password"])) {
			error_handle("database connection error", "this application is not able to connect its database.  we're sorry for the inconvenience, the administrator is attempting to fix the issue.");
			exit;
		}
	}
	
	//select db
	db_switch();
}

function db_pwdcompare($string, $field) {
	global $_josh;
	error_debug("<b>db_pwdcompare</b> running");
	if ($_josh["db"]["language"] == "mssql") {
		return "PWDCOMPARE('" . $string . "', " . $field . ")";
	} else {
		return "IF (" . $field . " = PASSWORD('" . $string . "'), 1, 0)";
	}
}

function db_query($query, $limit=false, $suppress_error=false) {
	global $_josh;
	db_open();
	$query = trim($query);
	if (isset($_josh["basedblanguage"]) && ($_josh["basedblanguage"] != $_josh["db"]["language"])) $query = db_translate($query, $_josh["basedblanguage"], $_josh["db"]["language"]);
	$_josh["queries"][] = $query;
	if ($_josh["db"]["language"] == "mysql") {
		if ($limit) $query .= " LIMIT " . $limit;
		$result = @mysql_query($query, $_josh["db"]["pointer"]);
		$error = mysql_error();
		if (!$error) {
			if (strlen($query) > 2000) $query = substr($query, 0, 2000);
			error_debug("<b>db_query</b> <i>" . $query . "</i>, " . db_found($result) . " results returned");
			if (format_text_starts("insert", $query)) return db_id();
			return $result;
		} else {
			if (strlen($query) > 2000) $query = substr($query, 0, 2000);
			error_debug("<b>db_query</b> failed <i>" . $query . "</i>");
			if ($suppress_error) return false;
			error_handle("mysql error", format_code($query) . $error);
			//error_handle("mysql error", $error);
		}
	} elseif ($_josh["db"]["language"] == "mssql") {
		//echo $_josh["db"]["location"]. " db";
		if ($limit) $query = "SELECT TOP " . $limit . substr($query, 6);

		if ($result = @mssql_query($query, $_josh["db"]["pointer"])) {
			if (strlen($query) > 2000) $query = substr($query, 0, 2000);
			error_debug("<b>db_query</b> <i>" . $query . "</i>, " . db_found($result) . " results returned");
			if (format_text_starts("insert", $query)) return db_id();
			return $result;
		} else {
			if (strlen($query) > 2000) $query = substr($query, 0, 2000);
			if ($suppress_error) return false;
			error_handle("mssql error", format_code($query) . mssql_get_last_message());
		}
	}
}

function db_save($table, $id="get", $array=false) {
	global $_SESSION, $_POST;
	
	//default behavior is to use $_GET["id"] as the id number to deal with
	if ($id == "get") $id = url_id();
	if ($id == "id") {
		//this happened once
		error_handle("db_save can't process", "a value of 'id' was set for the ID");
		exit;
	}
	
	if (!isset($_SESSION["user_id"])) error_handle("session not set", "db_save needs a session user_id variable");
	if (!$array) $array = $_POST;
	
	$columns	= db_columns($table);
	$required	= array("id", "created_date", "created_user", "updated_date", "updated_user", "deleted_date", "deleted_user", "is_active");
	$query1		= array();
	$query2		= array();
	
	//debug();
	
	foreach ($columns as $c) {
		error_debug("<b>db_save</b> looking at column " . $c["name"] . ", of type " . $c["type"]);
		if ($indexes = array_keys($required, $c["name"])) {
			error_debug("<b>db_save</b> unsetting " . $c["name"] . " from array because it's a system field and handled specially");
			foreach ($indexes as $i) unset($required[$i]);
		} elseif (isset($array[$c["name"]])) {
			if (($c["type"] == "decimal") || ($c["type"] == "float")) {
				$value = format_null(format_numeric($array[$c["name"]], false));
			} elseif ($c["type"] == "int") { //integer
				$value = format_null(format_numeric($array[$c["name"]], true));
			} elseif (($c["type"] == "mediumblob") || ($c["type"] == "image")) { //document
				$value = format_binary($array[$c["name"]]);
			} elseif ($c["type"] == "varchar") { //text
				$value = "'" . format_html_entities($array[$c["name"]]) . "'";
				if (($value == "''") && (!$c["required"])) $value = "NULL"; //special null
			} elseif ($c["type"] == "text") { //textarea
				$value = "'" . format_html($array[$c["name"]] . "'");
			} elseif (($c["type"] == "tinyint") || ($c["type"] == "bit")) { //bit
				$value = format_boolean($array[$c["name"]], "1|0");
			} elseif ($c["type"] == "datetime") {
				$value = "'" . format_date($array[$c["name"]], "", "sql") . "'";
			} else {
				error_handle("unhandled data type", "db_save hasn't been programmed yet to handle " . $c["type"]);
			}
			
			if ($id) {
				$query1[] = $c["name"] . " = " . $value;
			} else {
				$query1[] = $c["name"];
				$query2[] = $value;
			}
		} elseif (isset($array[$c["name"] . "Month"])) {
			//this could be a date or datetime
			if ($c["type"] == "datetime") {
				$value = format_post_date($c["name"], $array);
			} elseif ($c["type"] == "date") {
				$value = format_post_date($c["name"], $array);
			}
			if ($id) {
				$query1[] = $c["name"] . " = " . $value;
			} else {
				$query1[] = $c["name"];
				$query2[] = $value;
			}
		} elseif ($c["type"] == "tinyint") { 
			//checkbox that's not set
			//if the checkbox field isn't present in the form, we maybe have a problem
			$value = 0;
			if ($id) {
				$query1[] = $c["name"] . " = " . $value;
			} else {
				$query1[] = $c["name"];
				$query2[] = $value;
			}
		} elseif ($id) {
			//we're ok, because we should already have it
			//echo $c["name"];
		} elseif ($c["default"] != "") {
			if ($id) {
				$query1[] = $c["name"] . " = " . $c["default"];
			} else {
				$query1[] = $c["name"];
				$query2[] = $c["default"];
			}
		} elseif (!$id && $c["required"]) {
			//fill values with admin defaults?  eg 0s for numeric and NOW()s for datetimes?
			//if ($type == "tinyint") $value = 0;
			if ($c["name"] == "secret_key") {
				if ($id) {
					//keep existing keys
					//$query1[] = $c["name"] . " = " . $c["default"];
				} else {
					$query1[] = $c["name"];
					$query2[] = db_key();
				}
			} else {
				//echo $c["default"];
				error_handle("required value missing", "db_save is expecting a value for " . $c["name"]);
			}
		}
	}
	if (count($required)) error_handle("required fields missing", "the table $table needs columns for " . implode(", ", $required));
	//serious fucking vulnerability: UPDATE tasks SET client_id = 16, status_id = 3, hours = NULL, rate = 75, closed_date = '2009-05-18 00:00:00', closed_user = 15, is_urgent = 0, updated_date = NOW(), updated_user = 15 WHERE id = id
	if ($id) {
		$query1[] = "updated_date = " .  db_date();
		$query1[] = "updated_user = " . ((isset($array["updated_user"])) ? $array["updated_user"] : $_SESSION["user_id"]);
		if (db_query("UPDATE $table SET " . implode(", ", $query1) . " WHERE id = " . $id)) return $id;
		return false;
	} else {
		$query1[] = "created_date";
		$query2[] = db_date();
		$query1[] = "created_user";
		$query2[] = ((isset($array["created_user"])) ? $array["created_user"] : $_SESSION["user_id"]);
		$query1[] = "is_active";
		$query2[] = 1;
		$query = "INSERT INTO $table ( " . implode(", ", $query1) . " ) VALUES ( " . implode(", ", $query2) . " )";
		return db_query($query);
	}
}

function db_switch($target=false) {
	global $_josh;
	db_open();
	if (!$target) $target = $_josh["db"]["database"];
	if ($_josh["db"]["language"] == "mssql") {
		mssql_select_db($target, $_josh["db"]["pointer"]);
	} elseif ($_josh["db"]["language"] == "mysql") {
		mysql_select_db($target, $_josh["db"]["pointer"]);
	}
	$_josh["db"]["switched"] = ($target == $_josh["db"]["database"]) ? false : true;
}

function db_table($sql, $limit=false, $suppress_error=false) {
	$return = array();
	$result = db_query($sql, $limit, $suppress_error);
	while ($r = db_fetch($result)) $return[] = $r;
	return $return;
}

function db_translate($sql, $from, $to) {
	if (($from == "mssql") && ($to == "mysql")) {
		$sql = str_replace("PWDENCRYPT(", "PASSWORD(", $sql);
		$sql = str_replace("GETDATE(", "NOW(", $sql);
		$sql = str_replace("ISNULL(", "IFNULL(", $sql);
		$sql = str_replace("NEWID(", "RAND(", $sql);
	} elseif (($from == "mysql") && ($to == "mssql")) {
		$sql = str_replace("PASSWORD(", "PWDENCRYPT(", $sql);
		$sql = str_replace("NOW(", "GETDATE(", $sql);
		$sql = str_replace("IFNULL(", "ISNULL(", $sql);
		$sql = str_replace("RAND(", "NEWID(", $sql);
	}
	return $sql;
}

function db_undelete($table, $id=false) {
	//undeleting an object does not update it
	global $_SESSION;
	if (!$id) {
		if (isset($_GET["id"])) {
			$id = $_GET["id"];
		} else {
			error_handle("expecting \$_GET[\"id\"]", "db_delete is expecting an id variable");
		}
	}
	db_query("UPDATE $table SET 
		deleted_date = NULL, 
		deleted_user = NULL, 
		is_active = 1
		WHERE id = " . $id);
}

?>