<?php
error_debug("including db.php", __file__, __line__);

function db_array($sql, $array=false, $prepend_id=false, $prepend_value=false) {
	//exec a sql query and return an associate array of the results
	//need more description of purpose for prepend_id, prepend_value
	//do we still need db_table?
	global $_josh;
	$result = db_query($sql);
	if (!$array) $array = array();
	$key = false;
	while ($r = db_fetch($result)) {
		if (!$key) $key = array_keys($r);
		if ($prepend_id) $r[$key[0]] = $prepend_id . $r[$key[0]];
		if ($prepend_value) $r[$key[1]] = $prepend_value . $r[$key[1]];
		$array[$r[$key[0]]] = $r[$key[1]];
	}
	return $array;
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
		error_handle("mssql not supported yet", "mssql is not yet supported for db_columns");
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
	global $_SESSION;
	if (!$id) {
		if (isset($_GET["id"])) {
			$id = $_GET["id"];
		} else {
			error_handle("expecting \$_GET[\"id\"]", "db_delete is expecting an id variable");
		}
	}
	db_query("UPDATE $table SET deleted_on = " . db_date() . ", deleted_by = {$_SESSION["user_id"]}, is_active = 0 WHERE id = " . $id);
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
	error_debug("<b>db_open</b> running");

	//skip if already connected
	if (isset($_josh["db"]["pointer"])) return;
	
	//reset variables if you're specifying which ones to use
	if ($location) $_josh["db"]["location"] = $location;
	if ($username) $_josh["db"]["username"] = $username;
	if ($password) $_josh["db"]["password"] = $password;
	if ($database) $_josh["db"]["database"] = $database;
	if ($language) $_josh["db"]["language"] = $language;
		
	//connect to db
	if (!isset($_josh["db"]["database"]) || !isset($_josh["db"]["username"]) || !isset($_josh["db"]["password"])) {
		error_handle("database variables error", "joshserver could not find the right database connection variables.  please fix this before proceeding.");
	} elseif ($_josh["db"]["language"] == "mysql") {
		error_debug("<b>db_open</b> trying to connect mysql on " . $_josh["db"]["location"]);
		if (!$_josh["db"]["pointer"] = @mysql_connect($_josh["db"]["location"], $_josh["db"]["username"], $_josh["db"]["password"])) {
			error_handle("database connection error", "this application is not able to connect its database.  we're sorry for the inconvenience, the administrator is attempting to fix the issue.");
		}
	} elseif ($_josh["db"]["language"] == "mssql") {
		error_debug("<b>db_open</b> trying to connect mssql on " . $_josh["db"]["location"] . " with username " . $_josh["db"]["username"]);
		if (!$_josh["db"]["pointer"] = @mssql_connect($_josh["db"]["location"], $_josh["db"]["username"], $_josh["db"]["password"])) {
			error_handle("database connection error", "this application is not able to connect its database.  we're sorry for the inconvenience, the administrator is attempting to fix the issue.");
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

function db_save($table, $id=false) {
	global $_SESSION;
	if (!$id && url_id()) $id = $_GET["id"];
	
	if (!isset($_SESSION["user_id"])) error_handle("session not set", "db_save needs a session user_id variable");
	$columns	= db_columns($table);
	$required	= array("id", "created_date", "created_user", "updated_date", "updated_user", "deleted_date", "deleted_user", "is_active");
	$query1		= array();
	$query2		= array();
	//debug();
	foreach ($columns as $c) {
		error_debug("<b>db_save</b> looking at column " . $c["name"]);
		if ($indexes = array_keys($required, $c["name"])) {
			error_debug("<b>db_save</b> unsetting " . $c["name"] . " from post query because it's a system field and handled specially");
			foreach ($indexes as $i) unset($required[$i]);
		} elseif (isset($_POST[$c["name"]])) {
			if ($c["type"] == "int") { //integer
				$value = format_null(format_numeric($_POST[$c["name"]], true));
			} elseif ($c["type"] == "mediumblob") { //password (what about file)
				$value = format_binary($_POST[$c["name"]]);
			} elseif ($c["type"] == "varchar") { //text
				$value = "'" . $_POST[$c["name"]] . "'";
			} elseif ($c["type"] == "text") { //textarea
				$value = "'" . format_html($_POST[$c["name"]] . "'");
			} elseif ($c["type"] == "tinyint") { //bit
				$value = format_boolean($_POST[$c["name"]], "1|0");
			} elseif ($c["type"] == "datetime") {
				$value = format_post_date($c["name"]);
			} elseif ($c["type"] == "decimal") {
				$value = format_null(format_numeric($_POST[$c["name"]], false));
			} else {
				error_handle("unhandled data type", "db_save hasn't been programmed yet to handle" . $c["type"]);
			}
			
			if ($id) {
				$query1[] = $c["name"] . " = " . $value;
			} else {
				$query1[] = $c["name"];
				$query2[] = $value;
			}
		} elseif (!$id && ($c["default"] != "")) {
			if ($id) {
				$query1[] = $c["name"] . " = " . $c["default"];
			} else {
				$query1[] = $c["name"];
				$query2[] = $c["default"];
			}
		} elseif (!$id && $c["required"]) {
			//fill values with admin defaults?  eg 0s for numeric and NOW()s for datetimes?
			//if ($type == "tinyint") $value = 0;
			echo $c["default"];
			error_handle("required value missing", "db_save is expecting a value for " . $c["name"]);
		}
	}
	if (count($required)) {
		error_handle("required fields missing", "the table $table needs columns for " . implode(", ", $required));
	}
	if ($id) {
		$query1[] = "updated_date = '" .  db_date() . "'";
		$query1[] = "updated_user = " . $_SESSION["user_id"];
		$query = "UPDATE $table SET " . implode(", ", $query1) . " WHERE id = " . $id;
	} else {
		$query1[] = "created_date";
		$query2[] = db_date();
		$query1[] = "created_user";
		$query2[] = $_SESSION["user_id"];
		$query1[] = "is_active";
		$query2[] = 1;
		$query = "INSERT INTO $table ( " . implode(", ", $query1) . " ) VALUES ( " . implode(", ", $query2) . " )";
	}
	//die($query);
	return db_query($query);
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
?>