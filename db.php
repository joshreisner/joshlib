<?php
error_debug('including db.php', __file__, __line__);

function db_array($sql, $array=false, $prepend_id=false, $prepend_value=false, $limit=false) {
	//exec a sql query and return an array of the results
	//need more description of purpose for prepend_id, prepend_value
	//what's the difference between this and db_table?
	
	//db_table returns resultsets like $array[0] = array('name'=>'josh', 'role'=>'coder', 'gender'=>'m');
	//db_array can return either $array = array('foo', 'bar', 'baz') or $array = array('name'=>'josh', 'role'=>'coder', 'gender'=>'m');
	
	global $_josh;
	$result = db_query($sql, $limit);
	if (!$array) $array = array();
	$key = false;
	
	//multiple rows
	if (db_num_fields($result) == 1) {
		//single d array
		while ($r = db_fetch($result)) {
			if (!$key) $key = array_keys($r);
			if ($prepend_id) $r[$key[0]] = $prepend_id . $r[$key[0]];
			$array[] = $r[$key[0]];
		}		
	} else {
		//multidimensional array
		while ($r = db_fetch($result)) {
			if (!$key) $key = array_keys($r);
			if ($prepend_id) $r[$key[0]] = $prepend_id . $r[$key[0]];
			if ($prepend_value) $r[$key[1]] = $prepend_value . $r[$key[1]];
			$array[$r[$key[0]]] = $r[$key[1]];
		}		
	}
	
	return $array;
}

function db_backup($limit=false) {
	//outputs a gzip backup of the current database
	global $_josh;
	
	//default filename is /_site/backups/YYYY-MM-DD.sql -- delete any existing file of that name
	$folder = $_josh['write_folder'] . '/backups/';
	$target = $folder . date('Y-m-d') . '.gz';
	file_delete($target);
	
	if ($limit) {
		//limit the number of backup files that live in this directory
		if ($files = file_folder($folder, '.gz')) {
			$delete = count($files) - $limit + 1;
			for ($i = 0; $i < $delete; $i++) file_delete($files[$i]['path_name']);
		}
	}
	
	//only works with mysql right now
	extract($_josh['db']);
	if ($language != 'mysql') error_handle('only mysql supported', 'db_backup is a mysql-only function right now');

	//build command, socket hack, execute
	$command = 'mysqldump --opt --host="' . $location . '" --user="' . $username . '" --password="' . $password . '" "' . $database . '" | gzip > ' . $_josh['root'] . $target;
	if (isset($_josh['mysqldump_path'])) $command = $_josh['mysqldump_path'] . $command;
	$command = str_replace(':', '" --socket="', $command);
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
	db_switch('information_schema');
	if ($column) {
		$column = ' AND column_name = "' . $column . '"';
		$target = 'columns';
	} else {
		$target = 'tables';
	}
	$return = db_grab('SELECT * FROM ' . $target . ' WHERE table_schema = "' . $_josh['db']['database'] . '" AND table_name = "' . $table . '"' . $column);
	db_switch();
	return $return;
}

//db_checkboxes('doc', 'documents_to_categories', 'documentID', 'categoryID', $_GET['id']);
function db_checkboxes($name, $linking_table, $object_col, $option_col, $object_id) {
	db_query('DELETE FROM ' . $linking_table . ' WHERE ' . $object_col . ' = ' . $object_id);
	$categories = array_post_checkboxes($name);
	foreach ($categories as $category_id) db_query('INSERT INTO ' . $linking_table . ' ( ' . $object_col . ', ' . $option_col . ' ) VALUES ( ' . $object_id . ', ' . $category_id . ' )');
}

function db_clear($tables=false) { //cant find where this is called from.  obsolete?
	global $_josh;
	$sql = ($_josh['db']['language'] == 'mssql') ? 'SELECT name FROM sysobjects WHERE type="u" AND status > 0' : 'SHOW TABLES FROM ' . $_josh['db']['database'];
	$tables = ($tables) ? explode(',', $tables) : db_array($sql);
	foreach ($tables as $table) db_query('DELETE FROM ' . $table);
}

function db_close($keepalive=false) { //close connection and quit
	global $_josh;
	error_debug('<b>db_close</b> there were a total of ' . $_josh['queries'] . ' queries.', __file__, __line__);
	if (db_connected()) {
		if ($_josh['db']['language'] == 'mysql') {
			@mysql_close($_josh['db']['pointer']);
		} elseif ($_josh['db']['language'] == 'mssql') {
			@mssql_close($_josh['db']['pointer']);
		}
		unset($_josh['db']['pointer']);
	}
	
	//new imap thing for work.josh
	if (isset($_josh['imap']['pointer'])) {
		@imap_close($_josh['imap']['pointer']);
		unset($_josh['imap']['pointer']);
	}

	if (!$keepalive) exit;
}

function db_columns($tablename, $omitSystemFields=false) {
	global $_josh;
	error_debug('<b>db_columns</b> running', __file__, __line__);
	if (!db_table_exists($tablename)) return false;
	
	$return = array();
	if ($_josh['db']['language'] == 'mysql') {
		$result = db_query('SHOW FULL COLUMNS FROM ' . $tablename);
		while ($r = db_fetch($result)) {
			if ($omitSystemFields && (in_array($r['Field'], $_josh['system_columns']))) continue;
			
			$name = $r['Field'];
			@list($type, $length) = explode('(', str_replace(')', '', $r['Type']));
			$required = ($r['Null'] == 'YES') ? false : true;
			$default = $r['Default'];
			$comments = $r['Comment'];
			$return[] = compact('name','type','required','default','comments','length');
		}
	} else {
		$result = db_table('SELECT 
				c.name, 
				t.name type, 
				CASE WHEN c.isnullable = 0 THEN 1 ELSE 0 END required, 
				m.text [default],
				s.value comments
			FROM syscolumns c
			JOIN sysobjects o ON o.id = c.id
			JOIN systypes t on c.xtype = t.xtype
			LEFT JOIN syscomments m on c.cdefault = m.id
			LEFT JOIN sysproperties s ON c.colid = s.smallid
			WHERE o.name = "' . $tablename . '"
			ORDER BY c.colorder');
		$count = count($result);
		for ($i = 0; $i < $count; $i++) {
			if ($omitSystemFields && (in_array($result[$i]['name'], $_josh['system_columns']))) continue;
			if ($result[$i]['default']) $result[$i]['default'] = substr($result[$i]['default'], 1, strlen($result[$i]['default']) - 2);
			array_push($return, $result[$i]);
		}
	}
	return $return;
}

function db_connected() {
	//return boolean if database is connected or not -- stop referring to pointer
	global $_josh;
	return (isset($_josh['db']['pointer']) && $_josh['db']['pointer']);
}

function db_date() {
	global $_josh;
	return ($_josh['db']['language'] == 'mssql') ? 'GETDATE()' : 'NOW()';
}

function db_datediff($date1=false, $date2=false) {
	global $_josh;
	if ($_josh['db']['language'] == 'mssql') {
		if (!$date1) $date1 = 'GETDATE()';
		if (!$date2) $date2 = 'GETDATE()';
		return 'DATEDIFF(dd, ' . $date1 . ', ' . $date2 . ')';
	} elseif ($_josh['db']['language'] == 'mysql') {
		if (!$date1) $date1 = 'NOW()';
		if (!$date2) $date2 = 'NOW()';
		return 'DATEDIFF(' . $date2 . ', ' . $date1 . ')';
	}
}

function db_delete($table, $id=false) {
	//deleting an object does not update it
	global $_SESSION, $_GET;
	if (!$id) {
		if (isset($_GET['id'])) {
			$id = $_GET['id'];
		} else {
			error_handle('expecting \$_GET[\'id\']', 'db_delete is expecting an id variable');
		}
	}
	$user = (isset($_SESSION['user_id']) && !empty($_SESSION['user_id'])) ? $_SESSION['user_id'] : 'NULL';
	db_query('UPDATE ' . $table . ' SET 
		deleted_date = ' . db_date() . ', 
		deleted_user = ' . $user . ', 
		is_active = 0 
		WHERE id = ' . $id);
}

function db_fetch($result) {
	global $_josh;
	if ($_josh['db']['language'] == 'mysql') {
		return mysql_fetch_assoc($result);
	} elseif ($_josh['db']['language'] == 'mssql') {
		return mssql_fetch_assoc($result);
	}
}

function db_fetch_field($result, $i) {
	global $_josh;
	if ($_josh['db']['language'] == 'mysql') {
		return mysql_fetch_field($result, $i);
	} elseif ($_josh['db']['language'] == 'mssql') {
		return mssql_fetch_field($result, $i);
	}
}

function db_field_type($result, $i) {
	global $_josh;
	if ($_josh['db']['language'] == 'mysql') {
		return mysql_field_type($result, $i);
	} elseif ($_josh['db']['language'] == 'mssql') {
		return mssql_field_type($result, $i);
	}
}

function db_found($result) {
	global $_josh;
	if ($_josh['db']['language'] == 'mysql') {
		return @mysql_num_rows($result);
	} elseif ($_josh['db']['language'] == 'mssql') {
		return @mssql_num_rows($result);
	}
}

function db_grab($query, $checking=false) {
	global $_josh;
	error_debug('<b>db_grab</b> running', __file__, __line__);
	$result = db_query($query, 1, $checking);
	if (!db_found($result)) {
		error_debug('grabbing value', __file__, __line__);
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
	if ($_josh['db']['language'] == 'mysql') {
		return mysql_insert_id();
	} elseif ($_josh['db']['language'] == 'mssql') {
		return db_grab('SELECT @@IDENTITY');
	}
}

function db_key() {
	global $_josh;
	if ($_josh['db']['language'] == 'mssql') {
		return ''; //todo: not yet implemented for mssql
	} elseif ($_josh['db']['language'] == 'mysql') {
		return 'REPLACE(REPLACE(ENCRYPT(UUID()), "/", "|"), ".", "!")';
	}
}

function db_keys_from($table, $exclude_table=false) {
	global $_josh;

	$exclude_table = ($exclude_table) ? 'AND referenced_table_name <> "' . $exclude_table . '"' : '';

	$result = db_table('SELECT 
		column_name name, 
		constraint_name label,
		referenced_table_name ref_table, 
		referenced_column_name ref_name
		FROM information_schema.key_column_usage 
		WHERE table_schema = "' . $_josh['db']['database'] . '"
			AND table_name = "' . $table . '" ' . $exclude_table . '
			AND referenced_table_name IS NOT NULL');
	
	//i really don't like how numbers are showing up in the sql statement
	foreach ($result as &$r) {
		if ($pos = strpos($r['label'], '_')) $r['label'] = substr($r['label'], 0, $pos);
	}
	return $result;
}

function db_keys_to($table) {
	global $_josh;
	$keys = db_table('SELECT
			constraint_name,
			table_name,
			column_name
		FROM information_schema.key_column_usage 
		WHERE table_schema = "' . $_josh['db']['database'] . '"
			AND constraint_name NOT LIKE "exclude%"
			AND referenced_table_name = "' . $table . '"');
	foreach ($keys as &$key) {
		if ($pos = strpos($key['constraint_name'], '_')) $key['constraint_name'] = substr($key['constraint_name'], 0, $pos);
		if ($result = current(db_keys_from($key['table_name'], $table))) $key = array_merge($key, $result);
	}
	return $keys;
}

function db_num_fields($result) {
	global $_josh;
	if ($_josh['db']['language'] == 'mysql') {
		return mysql_num_fields($result);
	} elseif ($_josh['db']['language'] == 'mssql') {
		return mssql_num_fields($result);
	}
}

function db_open($location=false, $username=false, $password=false, $database=false, $language=false) {
	global $_josh;
	
	//skip if already connected
	if (db_connected()) return;
	
	error_debug('<b>db_open</b> running', __file__, __line__);

	//reset variables if you're specifying which ones to use
	if ($location) $_josh['db']['location'] = $location;
	if ($username) $_josh['db']['username'] = $username;
	if ($password) $_josh['db']['password'] = $password;
	if ($database) $_josh['db']['database'] = $database;
	if ($language) $_josh['db']['language'] = $language;
		
	//connect to db
	error_debug('<b>db_open</b> trying to connect ' . $_josh['db']['language'] . ' on ' . $_josh['db']['location'], __file__, __line__);
	if ($_josh['db']['language'] == 'mysql') {
		$_josh['db']['pointer'] = @mysql_connect($_josh['db']['location'], $_josh['db']['username'], $_josh['db']['password']);
	} elseif ($_josh['db']['language'] == 'mssql') {
	die('hi');
		error_debug('<b>db_open</b> trying to connect mssql on ' . $_josh['db']['location'] . ' with username ' . $_josh['db']['username'], __file__, __line__);
		$_josh['db']['pointer'] = @mssql_connect($_josh['db']['location'], $_josh['db']['username'], $_josh['db']['password']);
		//msssql 2000 doesn't support utf8
	}

	//handle error
	if (!db_connected()) {
		error_handle('Database Connection', 'Most likely, you haven\'t yet configured the variables in ' . $_josh['config'] . '.  It\'s also possible that the database is suddenly down.');
		exit; //to prevent massive repetition
	}
	
	//set utf8 -- todo mssql 2005
	if ($_josh['db']['language'] == 'mysql') mysql_set_charset('utf8', $_josh['db']['pointer']);
	
	//select db
	db_switch();
}

function db_option($table, $value) {
	//enter $value into option $table.  $table schema must conform
	//used for living cities salesforce calendar api
	//don't know whether to escape quotes
	if ($id = db_grab('SELECT id FROM ' . $table . ' WHERE title = \'' . $value . '\'')) return $id;
	
	//does not exist, enter it
	if ($id = db_query('INSERT INTO ' . $table . ' ( title, created_date, is_active ) VALUES ( \'' . $value . '\', NOW(), 1 )')) return $id;
	
	//there was an error
	error_handle(__function__ . ' error', 'could not add db_option' . $value . ' to table ' . $table);
}

function db_pwdcompare($string, $field) {
	global $_josh;
	error_debug('<b>db_pwdcompare</b> running', __file__, __line__);
	if ($_josh['db']['language'] == 'mssql') {
		return 'PWDCOMPARE("' . $string . '", ' . $field . ')';
	} else {
		if (empty($string)) {
			return 'CASE WHEN ' . $field . ' IS NULL THEN 1 ELSE 0 END';
		} else {
			return 'IF (' . $field . ' = PASSWORD("' . $string . '"), 1, 0)';
		}
	}
}

function db_pwdencrypt($string) {
	global $_josh;
	error_debug('<b>db_pwdcompare</b> running', __file__, __line__);
	if ($_josh['db']['language'] == 'mssql') {
		return 'PWDENCRYPT("' . $string . '")';
	} else {
		return 'PASSWORD("' . $string . '")';
	}
}

function db_query($query, $limit=false, $suppress_error=false) {
	global $_josh;
	db_open();
	$query = trim($query);
	if (isset($_josh['basedblanguage']) && ($_josh['basedblanguage'] != $_josh['db']['language'])) $query = db_translate($query, $_josh['basedblanguage'], $_josh['db']['language']);
	$_josh['queries']++;
	if ($_josh['db']['language'] == 'mysql') {
		if ($limit) $query .= ' LIMIT ' . $limit;
		$result = @mysql_query($query, $_josh['db']['pointer']);
		$error = mysql_error();
		if (!$error) {
			if (strlen($query) > 2000) $query = substr($query, 0, 2000);
			error_debug('<b>db_query</b> <i>' . $query . '</i>, ' . db_found($result) . ' results returned', __file__, __line__);
			if (format_text_starts('insert', $query)) return db_id();
			return $result;
		} else {
			if (strlen($query) > 2000) $query = substr($query, 0, 2000);
			error_debug('<b>db_query</b> failed <i>' . $query . '</i>', __file__, __line__);
			if ($suppress_error) return false;
			error_handle('mysql error', format_code($query) . $error);
			//error_handle('mysql error', $error);
		}
	} elseif ($_josh['db']['language'] == 'mssql') {
		if ($limit) $query = 'SELECT TOP ' . $limit . substr($query, 6);

		if ($result = @mssql_query($query, $_josh['db']['pointer'])) {
			if (strlen($query) > 2000) $query = substr($query, 0, 2000);
			error_debug('<b>db_query</b> <i>' . $query . '</i>, ' . db_found($result) . ' results returned', __file__, __line__);
			if (format_text_starts('insert', $query)) return db_id();
			return $result;
		} else {
			if (strlen($query) > 2000) $query = substr($query, 0, 2000);
			if ($suppress_error) return false;
			error_handle('mssql error', format_code($query) . mssql_get_last_message());
		}
	}
}

function db_save($table, $id='get', $array=false) {
	global $_SESSION, $_POST, $_josh;
		
	//default behavior is to use $_GET['id'] as the id number to deal with
	if ($id == 'get') $id = url_id();
	if ($id == 'id') {
		//this happened once, and was problematic because it evaluates to all records
		error_handle('db_save can\'t process', 'a value of "id" was set for the ID');
		exit;
	}
	
	//if (!isset($_SESSION['user_id'])) error_handle('session not set', 'db_save needs a session user_id variable');
	$userID = (isset($_SESSION['user_id'])) ? $_SESSION['user_id'] : 'NULL';
	
	if (!$array) $array = $_POST;
	
	$columns	= db_columns($table, true);
	$query1		= array();
	$query2		= array();
	$required	= $_josh['system_columns'];
	//debug();
	
	foreach ($columns as $c) {
		error_debug('<b>db_save</b> looking at column ' . $c['name'] . ', of type ' . $c['type'], __file__, __line__);
		if (isset($array[$c['name']])) {
			//we have a value to save for this column
			if (($c['type'] == 'decimal') || ($c['type'] == 'float')) {
				$value = format_null(format_numeric($array[$c['name']], false));
			} elseif ($c['type'] == 'int') { //integer
				$value = format_null(format_numeric($array[$c['name']], true));
			} elseif (($c['type'] == 'mediumblob') && ($c['name'] == 'password')) {
				if ($id) {
					$query1[] = $c['name'] . ' = ' . db_pwdencrypt($value);
				} else {
					$query1[] = $c['name'];
					$query2[] = db_pwdencrypt($value);
				}
			} elseif (($c['type'] == 'mediumblob') || ($c['type'] == 'image')) { //document
				$value = format_binary($array[$c['name']]);
			} elseif ($c['type'] == 'varchar') { //text
				if ($_josh['db']['language'] == 'mssql') $array[$c['name']] = format_accents_encode($array[$c['name']]);
				$value = "'" . $array[$c['name']] . "'";
				//$value = ''' . format_html_entities($array[$c['name']]) . ''';
				if (($value == "''") && (!$c['required'])) $value = 'NULL'; //special null
			} elseif ($c['type'] == 'text') { //textarea
				if ($_josh['db']['language'] == 'mssql') $array[$c['name']] = format_accents_encode($array[$c['name']]);
				$value = "'" . format_html($array[$c['name']] . "'");
			} elseif (($c['type'] == 'tinyint') || ($c['type'] == 'bit')) { //bit
				$value = format_boolean($array[$c['name']], '1|0');
			} elseif ($c['type'] == 'datetime') {
				//this would never happen
				$value = '"' . format_date($array[$c['name']], '', 'sql') . '"';
			} elseif ($c['type'] == 'date') {
				//new date field
				if (empty($array[$c['name']])) {
					if (!$c['required']) {
						$value = 'NULL';
					} else {
						error_handle('required value', $c['name'] . ' is required');
					}
				} else {
					$value = '"' . format_date($array[$c['name']], '', 'sql') . '"';
				}
			} else {
				error_handle('unhandled data type', 'db_save hasn\'t been programmed yet to handle ' . $c['type']);
			}
			
			if ($id) {
				$query1[] = $c['name'] . ' = ' . $value;
			} else {
				$query1[] = $c['name'];
				$query2[] = $value;
			}
		} elseif (isset($array[$c['name'] . 'Month'])) {
			//this could be a date or datetime -- field names don't match column because there are three parts
			if ($c['type'] == 'datetime') {
				$value = format_post_date($c['name'], $array);
			} elseif ($c['type'] == 'date') {
				$value = format_post_date($c['name'], $array);
			}
			if ($id) {
				$query1[] = $c['name'] . ' = ' . $value;
			} else {
				$query1[] = $c['name'];
				$query2[] = $value;
			}
		} elseif (($c['type'] == 'mediumblob') && ($file = file_get_uploaded($c['name']))) {
			//file isn't getting passed in (after resizing eg), but was uploaded
			$value = format_binary($file);		
			if ($id) {
				$query1[] = $c['name'] . ' = ' . $value;
			} else {
				$query1[] = $c['name'];
				$query2[] = $value;
			}
		} elseif ($id) {
			//this is an update, so don't do anything
			//needs to go above the default one!  eg living cities web pages level field
		} elseif (!empty($c['default'])) {
			//we have a default value to set for this
			if ($id) {
				$query1[] = $c['name'] . ' = ' . $c['default'];
			} else {
				$query1[] = $c['name'];
				$query2[] = $c['default'];
			}
		} elseif ($c['name'] == 'precedence') {
			//setting the precedence for a new object -- insert into slot 1, increment everything else
			$query1[] = 'precedence';
			$query2[] = 1;
			db_query('UPDATE ' . $table . ' SET precedence = precedence + 1');
		} elseif ($c['required']) {
			//fill values with admin defaults
			if ($c['name'] == 'secret_key') {
				$query1[] = $c['name'];
				$query2[] = db_key();
			} elseif (($c['type'] == 'bit') || ($c['type'] == 'tinyint')) {
				$query1[] = $c['name'];
				$query2[] = 0;
			} else {
				error_handle('required value missing', 'db_save is expecting a value for ' . $c['name']);
			}
		}
	}

	if ($id) {
		$query1[] = 'updated_date = ' .  db_date();
		$query1[] = 'updated_user = ' . ((isset($array['updated_user'])) ? $array['updated_user'] : $userID);
		if (!db_query('UPDATE ' . $table . ' SET ' . implode(', ', $query1) . ' WHERE id = ' . $id)) return false;
	} else {
		$query1[] = 'created_date';
		$query2[] = db_date();
		$query1[] = 'created_user';
		$query2[] = ((isset($array['created_user'])) ? $array['created_user'] : $userID);
		$query1[] = 'is_active';
		$query2[] = 1;
		$query = 'INSERT INTO ' . $table . ' ( ' . implode(', ', $query1) . ' ) VALUES ( ' . implode(', ', $query2) . ' )';
		$id = db_query($query);
	}
	
	//handle checkboxes based on foreign key situation
	if ($keys = db_keys_to($table)) {
		foreach ($keys as $key) {
			db_checkboxes($key['ref_table'], $key['table_name'], $key['column_name'], $key['name'], $id);
		}
	}
	return $id;
}

function db_switch($target=false) {
	global $_josh;
	db_open();
	if (!$target) $target = $_josh['db']['database'];
	if ($_josh['db']['language'] == 'mssql') {
		mssql_select_db($target, $_josh['db']['pointer']);
	} elseif ($_josh['db']['language'] == 'mysql') {
		mysql_select_db($target, $_josh['db']['pointer']);
	}
	$_josh['db']['switched'] = ($target == $_josh['db']['database']) ? false : true;
}

function db_table($sql, $limit=false, $suppress_error=false) {
	$return = array();
	$result = db_query($sql, $limit, $suppress_error);
	while ($r = db_fetch($result)) $return[] = $r;
	return $return;
}

function db_table_exists($name) {
	global $_josh;
	if ($_josh['db']['language'] == 'mssql') {
		//not implemented yet
		error_handle('db_table_exists', 'this function is not yet implemented for mssql');
	} elseif ($_josh['db']['language'] == 'mysql') {
		return db_found(db_query('SHOW TABLES LIKE \'' . $name . '\''));
	}
	return false;
}

function db_translate($sql, $from, $to) {
	if (($from == 'mssql') && ($to == 'mysql')) {
		$sql = str_replace('PWDENCRYPT(', 'PASSWORD(', $sql);
		$sql = str_replace('GETDATE(', 'NOW(', $sql);
		$sql = str_replace('ISNULL(', 'IFNULL(', $sql);
		$sql = str_replace('NEWID(', 'RAND(', $sql);
	} elseif (($from == 'mysql') && ($to == 'mssql')) {
		$sql = str_replace('PASSWORD(', 'PWDENCRYPT(', $sql);
		$sql = str_replace('NOW(', 'GETDATE(', $sql);
		$sql = str_replace('IFNULL(', 'ISNULL(', $sql);
		$sql = str_replace('RAND(', 'NEWID(', $sql);
	}
	return $sql;
}

function db_undelete($table, $id=false) {
	//undeleting an object does not update it
	global $_SESSION;
	if (!$id) {
		if (isset($_GET['id'])) {
			$id = $_GET['id'];
		} else {
			error_handle('expecting \$_GET[\'id\']', 'db_delete is expecting an id variable');
		}
	}
	db_query('UPDATE ' . $table . ' SET 
		deleted_date = NULL, 
		deleted_user = NULL, 
		is_active = 1
		WHERE id = ' . $id);
}

function db_updated($table=false) {
	//$table generally means a disambuiguator, eg SELECT t.id FROM table t, although it could also be the table name
	$table = ($table) ? $table . '.' : '';
	return 'IFNULL(' . $table . 'updated_date, ' . $table . 'created_date) updated';
}

?>