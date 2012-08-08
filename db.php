<?php
//a collection of functions that facilitate interactions with the database
//code needs review 

error_debug('including db.php', __file__, __line__);

function db_array($sql, $array=false, $prepend_id=false, $prepend_value=false, $limit=false) {
	//returns a single one-dimensional array from the database, or an associative array like array_key_promote(db_table())

	//db_table	returns multiple associative results like array(0=>array('name'=>'josh', 'role'=>'coder', 'gender'=>'m'), ...);
	//db_grab	returns a single associative result, like array('name'=>'josh', 'role'=>'coder', 'gender'=>'m')
	//db_array	returns a single one-dimensional array('josh', 'coder', 'm') or array('josh', 'jane', 'jessica')
	
	global $_josh;
	$result = db_query($sql, $limit);
	if (!$array) $array = array();
	$key = false;
	
	if (db_num_fields($result) == 1) {
		//multiple rows, single column.  one-dimensional array.
		while ($r = db_fetch($result)) {
			if (!$key) $key = array_keys($r);
			if ($prepend_id) $r[$key[0]] = $prepend_id . $r[$key[0]];
			$array[] = $r[$key[0]];
		}		
	} elseif (db_found($result) == 1) {
		//multiple columns, single result. one-dimensional array.
		return db_fetch($result);
	} else {
		//multidimensional array.  works like array_key_promote
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
	if (db_language() != 'mysql') error_handle('Language Not Supported', __function__ . ' is only currently implemented in MySQL.', __file__, __line__);

	//check up on the folder
	file_dir_writable('backups');
	$folder = DIRECTORY_WRITE . '/backups/';
	if ($limit) {
		//limit the number of backup files that can live in this folder
		if ($files = file_folder($folder, '.gz')) {
			$delete = count($files) - $limit + 1;
			for ($i = 0; $i < $delete; $i++) file_delete($files[$i]['path_name']);
		}
	}
	
	//filename is /_write_folder/backups/YYYY-MM-DD.sql.gz -- delete any existing file of that name
	$target = $folder . date('Y-m-d') . '.sql.gz';
	file_delete($target);
	
	//debug(false);
	
	//build command, socket hack, execute
	//todo add command for DROP TABLE IF EXISTS
	$command = 'mysqldump --opt --host="' . $_josh['db']['location'] . '" --user="' . $_josh['db']['username'] . '" --password="' . $_josh['db']['password'] . '" "' . $_josh['db']['database'] . '" | gzip > ' . DIRECTORY_ROOT . $target;
	$command = str_replace(':', '" --socket="', $command);
	
	//sometimes a path to mysqldump is required.  eg on macs, it's /usr/local/mysql/bin/.  specify it in the config file, if necessary
	if (isset($_josh['mysql_path'])) $command = $_josh['mysql_path'] . $command;

	//execute	
	system($command);
	
	if (file_check($target) > 200) { //sometimes when it fails, mysqldump creates a file that's 20B
		//export was a success.  return the path for linking.
		error_debug('<b>' . __function__ . '</b> successfully ran ' . $command . ', creating file: ' . $target, __file__, __line__);	
		return $target;
	} else {
		//export failed
		error_handle('export failed', '<b>' . __function__ . '</b> was not successful executing ' . $command, __file__, __line__);
		file_delete($target);
		return false;
	}
}

function db_checkboxes($name, $linking_table, $object_col, $option_col, $object_id) {
	//for example db_checkboxes('doc', 'documents_to_categories', 'document_id', 'category_id', $id);
	db_query('DELETE FROM ' . $linking_table . ' WHERE ' . $object_col . ' = ' . $object_id);
	$categories = array_checkboxes($name);
	error_debug('<b>' . __function__ . '</b> for ' . $name . ' is ' . draw_array($categories), __file__, __line__);
	foreach ($categories as $category_id) db_query('INSERT INTO ' . $linking_table . ' ( ' . $object_col . ', ' . $option_col . ' ) VALUES ( ' . $object_id . ', ' . $category_id . ' )');
}

function db_close($keepalive=false) { //close connection and quit
	global $_josh;
	error_debug('<b>' . __function__ . '</b>', __file__, __line__);
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

function db_column($table, $column) {
	//alias for db_column_exists, deprecate?
	return db_column_exists($table, $column);
}

function db_column_add($table, $column, $type=false, $datatype=false) {
	//add a column to a table
	//$type is a josh type as found in system fields
	//$datatype is an actual SQL datatype
	
	if (db_column_exists($table, $column)) return false;

	$length = $decimals = false;
	$default = 'DEFAULT NULL';
		
	//handle single-field translations.  multi-field and linked tables are todo
	if (!$datatype && $type) {
		switch ($type) {
			case 'char': //single character field
			$datatype = 'char';
			$length = 1;
			break;
		
			case 'checkbox': 
			$datatype = 'tinyint';
			$default = 'NOT NULL';
			break;
		
			case 'color': 
			$datatype = 'varchar';
			$length = 7;
			break;
		
			case 'date': 
			$datatype = 'date';
			break;

			case 'datetime': 
			$datatype = 'datetime';
			break;
		
			case 'file': 
			case 'image': 
			case 'image-alt': 
			$datatype = 'mediumblob';
			break;
		
			case 'file-type': 
			$datatype = 'varchar';
			$length = 5;
			break;
		
			case 'file-size': 
			$datatype = 'int';
			break;
		
			case 'int': 
			case 'select': 
			$datatype = 'int';
			break;
		
			case 'money':
			$datatype = 'decimal';
			$decimals = 2;
			break;
	
			case 'textarea':
			case 'textarea-plain': 
			$datatype = 'text';
			break;
			
			case 'email': 
			case 'latlon': 
			case 'text': 
			case 'url': 
			case 'url-local': 
			$datatype = 'varchar';
			break;

			error_handle('unknown data type', __function__ . ' received a request for ' . $type . ' which is not handled.', __file__, __line__);
		}
	}
	
	if ($datatype && db_query('ALTER TABLE ' . $table . ' ADD ' . $column . ' ' . db_column_type($datatype, $length, $decimals) . ' ' . $default)) return $column;
	return false;
}

function db_column_drop($table, $column) {
	if (db_language() == 'mssql') error_handle('MSSQL Not Yet Supported', __function__ . ' is only currently implemented in MySQL.', __file__, __line__);
	$result = db_found(db_query('ALTER TABLE ' . $table . ' DROP COLUMN ' . $column));
}

function db_column_exists($table, $column) {
	//return column array info if exists
	$columns = db_columns($table);
	foreach ($columns as $c) if ($c['name'] == $column) return $c;
	return false;
}

function db_column_key($table, $column) {
	if (db_language() == 'mssql') error_handle('MSSQL Not Yet Supported', __function__ . ' is only currently implemented in MySQL.', __file__, __line__);
	return db_query('ALTER TABLE `' . $table . '` MODIFY `' . $column . '` INT(11) NOT NULL AUTO_INCREMENT, ADD PRIMARY KEY (' . $column . ');');
}

function db_column_rename($table, $before, $after) {
	if (db_language() == 'mssql') error_handle('MSSQL Not Yet Supported', __function__ . ' is only currently implemented in MySQL.', __file__, __line__);
	$col = db_column_exists($table, $before);
	if (($col['type'] == 'varchar') && $col['length']) $col['type'] = $col['type'] . '(' . $col['length'] . ')';
	$result = db_found(db_query('ALTER TABLE `' . $table . '` CHANGE `' . $before . '` `' . $after . '` ' . strToUpper($col['type'])));
}

function db_column_type($datatype, $length=false, $decimals=false) {
	//edit $datatype declaration in sql statement
	//$datatype in this case is a mysql/mssql datatype
	$datatype = strToUpper($datatype);
	
	//these have no explicit length
	if (in_array($datatype, array('DATE', 'DATETIME', 'MEDIUMBLOB', 'LONGBLOB', 'TEXT'))) return $datatype;
	
	//mssql INT can have no length
	if ((db_language() == 'mssql') && ($datatype == 'INT')) return $datatype;

	//these do
	if (!$length) {
		$maxes = array('INT'=>11, 'DECIMAL'=>11, 'TINYINT'=>4, 'VARCHAR'=>255);
		$length = $maxes[$datatype];
	}
	
	//this also has precision
	if ($datatype == 'DECIMAL') {
		if (!$decimals) $decimals = 2;
		$length .= ',' . $decimals;
	}
	
	return $datatype . '(' . $length . ')';
}

function db_column_type_set($table, $column, $newtype) {
	if (db_language() == 'mssql') error_handle('MSSQL Not Yet Supported', __function__ . ' is only currently implemented in MySQL.', __file__, __line__);
	return db_query('ALTER TABLE `' . $table . '` MODIFY `' . $column . '` ' . db_column_type($newtype));
}

function db_columns($tablename, $omitSystemFields=false, $includeMetaData=true) {
	global $_josh;
	error_debug('<b>db_columns</b> running', __file__, __line__);

	//check for cached columns
	if (isset($_josh['columns'][$tablename])) return $_josh['columns'][$tablename];

	if (!db_table_exists($tablename)) return false;
	
	$return = array();
	if ($_josh['db']['language'] == 'mysql') {
		$result = db_query('SHOW FULL COLUMNS FROM ' . $tablename);
		while ($r = db_fetch($result)) {
			if ($omitSystemFields && (in_array($r['Field'], $_josh['system_columns']))) continue;
			
			$name = $r['Field'];
			@list($type, $length) = explode('(', str_replace(')', '', $r['Type']));
			$decimals = false;
			@list($length, $decimals) = explode(',', $length);
			$required = ($r['Null'] == 'YES') ? false : true;
			$auto = ($r['Extra'] == 'auto_increment') ? true : false;
			$default = $r['Default'];
			$comments = $r['Comment'];
			$return[] = ($includeMetaData) ? compact('name','type','required','auto','default','comments','length','decimals') : $name;
		}
	} else {
		if (!$columns = db_table('SELECT 
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
				ORDER BY c.colorder')) return false;
		foreach ($columns as &$c) {
			if ($omitSystemFields && (in_array($c['name'], $_josh['system_columns']))) continue;
			if ($c['default']) $c['default'] = substr($c['default'], 1, strlen($c['default']) - 2); //stripping off first and last?
			$return[] = ($includeMetaData) ? $c : $c['name'];
		}
	}

	$_josh['columns'][$tablename] = $return;
	return $return;
}

function db_connected() {
	//return boolean if database is connected or not -- stop referring to pointer
	global $_josh;
	return (isset($_josh['db']['pointer']) && (is_resource($_josh['db']['pointer']) || is_object($_josh['db']['pointer'])));
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
			error_handle('expecting \$_GET[\'id\']', 'db_delete is expecting an id variable', __file__, __line__);
		}
	}
	db_query('UPDATE ' . $table . ' SET deleted_date = ' . db_date() . ', deleted_user = ' . user('NULL') . ', updated_user = ' . user('NULL') . ', updated_date = NOW(), is_active = 0 WHERE id = ' . $id);
}

function db_fetch($result) {
	global $_josh;
	if ($_josh['db']['pdo']) {
		return $result->fetch(PDO::FETCH_ASSOC);
	} elseif ($_josh['db']['language'] == 'mysql') {
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
	if ($_josh['db']['pdo']) {
		if (!is_object($result)) return 0;
		return $result->rowCount();
	} elseif ($_josh['db']['language'] == 'mysql') {
		return @mysql_num_rows($result);
	} elseif ($_josh['db']['language'] == 'mssql') {
		return @mssql_num_rows($result);
	}
}

function db_grab($query, $checking=false) {
	global $_josh;
	error_debug('<b>' . __function__ . '</b> running', __file__, __line__);
	$result = db_query($query, 1, $checking);

	if (!db_found($result)) return false;
	
	$r = db_fetch($result);
	if (count($r) == 1) {
		$key = array_keys($r);
		$r = $r[$key[0]]; //if returning just one value, make it scalar
	}
	return $r;
}

function db_id() {
	global $_josh;
	if ($_josh['db']['pdo']) {
		return $_josh['db']['pointer']->lastInsertId();
	} elseif ($_josh['db']['language'] == 'mysql') {
		return mysql_insert_id();
	} elseif ($_josh['db']['language'] == 'mssql') {
		return db_grab('SELECT @@IDENTITY');
	}
}

function db_import($file) {
	//todo add gunzip and error handling
	///usr/local/mysql/bin/mysql -h "localhost" -u "root" "earnbenefits" < /Users/joshreisner/Desktop/2012-01-31.sql

	global $_josh;
	$command = 'mysql -h "' . $_josh['db']['location'] . '" -u "' . $_josh['db']['username'] . '"';

	//password could be empty
	if (!empty($_josh['db']['password'])) $command .= ' -p' . $_josh['db']['password'];
	
	$command .= ' "' . $_josh['db']['database'] . '" < ' . DIRECTORY_ROOT . $file;
	
	//prepend path to mysql if necessary
	if (isset($_josh['mysql_path'])) $command = $_josh['mysql_path'] . $command;

	system($command);
}

function db_key() {
	//return a random alphanumberic 'key' -- often used in cookies to remember users
	//tables with secret_key fields will autopopulate this if db_save() is called
	global $_josh;
	if ($_josh['db']['language'] == 'mssql') {
		return ''; //todo: not yet implemented for mssql
	} elseif ($_josh['db']['language'] == 'mysql') {
		return 'REPLACE(REPLACE(ENCRYPT(UUID()), "/", "|"), ".", "!")';
	}
}

function db_keys_from($table, $exclude_table=false) {
	//detects foreign keys
	//need to check usage, might be able to deprecate
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
	//inverse of db_keys_from, detects foreign keys
	//todo check usage, might be able to deprecate
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

function db_language($set_language=false) {
	//return or set the db_language, eg mysql, mssql
	global $_josh;
	if ($set_language) $_josh['db']['language'] = $set_language;
	return $_josh['db']['language'];
}

function db_num_fields($result) {
	//returns the number of fields in a resultset
	global $_josh;
	if ($_josh['db']['pdo']) {
		return $result->columnCount();
	} elseif (db_language() == 'mysql') {
		return mysql_num_fields($result);
	} else {
		return mssql_num_fields($result);
	}
}

function db_open($location=false, $username=false, $password=false, $database=false, $language=false) {
	//creates connection to database.  will be invoked automatically by db_query, not necessary to put this in your code
	global $_josh;

	//skip if already connected
	if (db_connected()) return true;

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
		if (extension_loaded('pdo_mysql')) {
			$_josh['db']['pdo'] = true;
			try {
				$_josh['db']['pointer'] = new PDO('mysql:host=' . $_josh['db']['location'] . ';dbname=' . $_josh['db']['database'] . ';', $_josh['db']['username'], $_josh['db']['password'], array(PDO::MYSQL_ATTR_INIT_COMMAND=>'SET NAMES utf8'));
				$_josh['db']['pointer']->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION );  
			} catch (PDOException $e) {
				print 'Error!:' . $e->getMessage() . '<br>';
				die();
				//todo error handle
			}
		} else {
			$_josh['db']['pdo'] = false;
			$_josh['db']['pointer'] = @mysql_connect($_josh['db']['location'], $_josh['db']['username'], $_josh['db']['password']);
			if (function_exists('mysql_set_charset')) {
				mysql_set_charset('utf8', $_josh['db']['pointer']);
			} else {
				mysql_query('SET CHARACTER SET utf8');
			    mysql_query('SET NAMES utf8');
			}
		}
	} elseif ($_josh['db']['language'] == 'mssql') {
		if (extension_loaded('pdo_sqlsrv')) {
			error_debug('<b>db_open</b> attempting to use pdo', __file__, __line__);
			$_josh['db']['pdo'] = true;
			try {
				$_josh['db']['pointer'] = new PDO('sqlsrv:Server=' . $_josh['db']['location'] . ';Database=' . $_josh['db']['database'] . ';', $_josh['db']['username'], $_josh['db']['password']);
			} catch (PDOException $e) {
				print 'Error!:' . $e->getMessage() . '<br>';
				die();
				//todo error handle
			}
		} else {
			error_debug('<b>db_open</b> attempting to use mssql_connect', __file__, __line__);
			$_josh['db']['pdo'] = false;
			$_josh['db']['pointer'] = @mssql_connect($_josh['db']['location'], $_josh['db']['username'], $_josh['db']['password']);
			//mssql 2000 doesn't support utf8
		}
	}

	//handle error
	if (!db_connected()) {
		error_handle('Database Connection', 'Most likely, you have not yet configured the variables in ' . $_josh['config'] . '.  Or the database could be down.', __file__, __line__);
		exit; //to prevent massive repetition
	}
	
	//select db
	if (!$_josh['db']['pdo']) db_switch();
	
	return db_connected();
}

function db_option($table, $value) {
	//enter $value into option $table.  $table schema must conform
	//used for living cities salesforce calendar api
	//don't know whether to escape quotes
	if ($id = db_grab('SELECT id FROM ' . $table . ' WHERE title = \'' . $value . '\'')) return $id;
	
	//does not exist, enter it
	if ($id = db_query('INSERT INTO ' . $table . ' ( title, created_date, is_active ) VALUES ( \'' . $value . '\', NOW(), 1 )')) return $id;
	
	//there was an error
	error_handle(__function__ . ' error', 'could not add db_option' . $value . ' to table ' . $table, __file__, __line__);
}

function db_pwdcompare($string, $field='password') {
	//check encrypted password against a string
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
	//encrypt a password for insertion into the database
	global $_josh;
	error_debug('<b>db_pwdcompare</b> running', __file__, __line__);
	if ($_josh['db']['language'] == 'mssql') {
		return 'PWDENCRYPT("' . $string . '")';
	} else {
		return 'PASSWORD("' . $string . '")';
	}
}

function db_query($sql, $limit=false, $suppress_error=false) {
	global $_josh;
	db_open();

	$query = trim($sql);
	if (isset($_josh['basedblanguage']) && ($_josh['basedblanguage'] != $_josh['db']['language'])) $query = db_translate($query, $_josh['basedblanguage'], $_josh['db']['language']);
	
	if ($limit) {
		if ($_josh['db']['language'] == 'mysql') {
			if (strstr($limit, ',')) {
				list($limit, $offset) = explode(',', $limit);
				$query .= ' LIMIT ' . $limit . ' OFFSET ' . $offset;
			} else {
				$query .= ' LIMIT ' . $limit;
			}
		} elseif ($_josh['db']['language'] == 'mssql') {
			$query = 'SELECT TOP ' . $limit . substr($query, 6);
		}
	}

	if ($_josh['db']['pdo']) {
		try {
			$result = $_josh['db']['pointer']->query($query);
			$error = false;
		} catch (PDOException $e) {
			$error = $e->getMessage();
		}
	} elseif ($_josh['db']['language'] == 'mysql') {
		$result = @mysql_query($query, $_josh['db']['pointer']);
		$error = mysql_error();
	} elseif ($_josh['db']['language'] == 'mssql') {
		$error = ($result = @mssql_query($query, $_josh['db']['pointer'])) ? false : mssql_get_last_message();
	}

	if ($error) {
		//check for dbCheck--this is a local function you can define to check the db schema to see if it needs an update
		if (!isset($_josh['db']['checked']) || !$_josh['db']['checked']) { //prevent recursion
			if (function_exists('dbCheck')) dbCheck();
			$_josh['db']['checked'] = true;
			return db_query($sql, $limit, $suppress_error);
		}
		
		//report error
		if (strlen($query) > 2000) $query = substr($query, 0, 2000);
		error_debug('<b>db_query</b> failed <i>' . $query . '</i>', __file__, __line__, '#ffdddd');
		if ($suppress_error) return false;
		error_handle('Database Error', format_code($query) . $error, __file__, __line__);
	} else {
		//handle success
		if (strlen($query) > 2000) $query = substr($query, 0, 2000);
		error_debug('<b>db_query</b> <i>' . $query . '</i>, ' . db_found($result) . ' results returned', __file__, __line__, '#ffe');
		if (format_text_starts('insert', $query)) return db_id();
		return $result;
	}

}

function db_save($table, $id='get', $array='post', $create_index=true) {
	global $_josh;
		
	//default behavior is to use $_GET['id'] as the id number to deal with
	if ($id == 'get') $id = url_id();
	if ($id == 'id') {
		//this happened once, and was problematic because it evaluates to all records
		error_handle('db_save can\'t process', 'a value of "id" was set for the ID', __file__, __line__);
		exit;
	}
	
	$userID = user('NULL');
	
	if ($array == 'post') $array = $_POST;
	if ($id) $values = db_grab('SELECT * FROM ' . $table . ' WHERE id = ' . $id);
	
	$query1		= array();
	$query2		= array();
	$required	= $_josh['system_columns'];
	$full_text	= false;
	$col_names	= array();
	//debug();
	
	
	if ($columns = db_columns($table)) {
		foreach ($columns as $c) {
			
			$col_names[] = $c['name'];
			if (in_array($c['name'], $_josh['system_columns'])) continue;
			
			error_debug('<b>db_save</b> looking at column ' . $c['name'] . ', of type ' . $c['type'], __file__, __line__);
			
			//making bits always required, never null
			if (($c['type'] == 'tinyint') || ($c['type'] == 'bit') || ($c['type'] == 'smallint')) { //bit
				$value = format_boolean(empty($array[$c['name']]), '0|1');
				if ($id) {
					$query1[] = $c['name'] . ' = ' . $value;
				} else {
					$query1[] = $c['name'];
					$query2[] = $value;
				}
			} elseif (isset($array[$c['name']])) {
				//we have a value to save for this column
				if (($c['type'] == 'decimal') || ($c['type'] == 'float')) {
					$value = format_null(format_numeric($array[$c['name']], false));
				} elseif (($c['type'] == 'int') || ($c['type'] == 'bigint')) { //integer
					$value = format_null(format_numeric($array[$c['name']], true));
					error_debug('<b>' . __function__ . '</b> checked int, value is ' . $value, __file__, __line__);
				} elseif (($c['type'] == 'mediumblob') && ($c['name'] == 'password')) {
					if ($id) {
						$query1[] = $c['name'] . ' = ' . db_pwdencrypt($value);
					} else {
						$query1[] = $c['name'];
						$query2[] = db_pwdencrypt($value);
					}
				} elseif (($c['type'] == 'mediumblob') || ($c['type'] == 'longblob') || ($c['type'] == 'image')) { //document
					$value = format_binary($array[$c['name']]);
					//die('length is ' . strlen($array[$c['name']]));
				} elseif ($c['type'] == 'varchar') { //text
					if ($_josh['db']['language'] == 'mssql') $array[$c['name']] = format_accents_encode($array[$c['name']]);
					$value = "'" . $array[$c['name']] . "'";
					if (($c['name'] != 'url') && ($c['name'] != 'password')) $full_text .= $value;
					if (($value == "''") && (!$c['required'])) $value = 'NULL'; //special null
					if (($value == "'http://'")) $value = 'NULL'; //url special null
				} elseif (($c['type'] == 'text') || ($c['type'] == 'longtext')) { //textarea
					if ($_josh['db']['language'] == 'mssql') $array[$c['name']] = format_accents_encode($array[$c['name']]);
					$value = "'" . format_html($array[$c['name']] . "'");
					$full_text .= $value;
				} elseif ($c['type'] == 'datetime') {
					//this happens in bb task mgmt
					if (($array[$c['name']] == db_date()) || ($array[$c['name']] == 'NULL')) {
						$value = $array[$c['name']];
					} else {
						$value = '"' . format_date($array[$c['name']], '', 'sql') . '"';
					}
				} elseif ($c['type'] == 'date') {
					//new date field
					if (empty($array[$c['name']])) {
						if (!$c['required']) {
							$value = 'NULL';
						} else {
							error_handle('required value', $c['name'] . ' is required', __file__, __line__);
						}
					} else {
						$value = '"' . format_date($array[$c['name']], '', 'sql') . '"';
					}
				} else {
					error_handle('unhandled data type', 'db_save hasn\'t been programmed yet to handle ' . $c['type'], __file__, __line__);
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
			} elseif (in_array($c['type'], array('mediumblob', 'longblob')) && ($file = file_get_uploaded($c['name']))) {
				//file isn't getting passed in (after resizing eg), but was uploaded
				$value = format_binary($file);		
				if ($id) {
					$query1[] = $c['name'] . ' = ' . $value;
				} else {
					$query1[] = $c['name'];
					$query2[] = $value;
				}
			} elseif (($c['type'] == 'varchar') && ($c['name'] == 'secret_key')) {
				if ($id && empty($values['secret_key'])) {
					$query1[] = 'secret_key = ' . db_key();
				} elseif (!$id) {
					$query1[] = $c['name'];
					$query2[] = db_key();
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
			} elseif ($c['required']) {
				//fill values with admin defaults
				if (($c['type'] == 'bit') || ($c['type'] == 'tinyint')) {
					$query1[] = $c['name'];
					$query2[] = 0;
				} else {
					error_handle('required value missing', 'db_save is expecting a value for ' . $table . '.' . $c['name'], __file__, __line__);
				}
			}
		}
	}

	//if table has is_published stuff, add it
	if (in_array('is_published', $col_names)) {
		if (empty($array['is_published'])) {
			if ($id) {
				$query1[] = 'is_published = 0';
				$query1[] = 'publish_date = NULL';
				$query1[] = 'publish_user = NULL';
			} else {
				$query1[] = 'is_published';
				$query2[] = 0;
			}
		} else {
			if ($id) {
				$query1[] = 'is_published = 1';
				$query1[] = 'publish_date = ' .  db_date();
				$query1[] = 'publish_user = ' . ((isset($array['publish_user'])) ? $array['publish_user'] : $userID);
			} else {
				$query1[] = 'publish_date';
				$query2[] = db_date();
				$query1[] = 'publish_user';
				$query2[] = ((isset($array['publish_user'])) ? $array['publish_user'] : $userID);
				$query1[] = 'is_published';
				$query2[] = 1;
			}
		}
	}
	
	if ($id) {
		//updating existing record
		if (isset($array['created_user'])) $query1[] = 'created_user = ' .  $array['created_user']; //could be changing created_user (eg intranet)
		if (db_column_exists($table, 'updated_date')) $query1[] = 'updated_date = ' . ((isset($array['updated_date'])) ? '"' . $array['updated_date'] . '"' : db_date());
		if (db_column_exists($table, 'updated_user')) $query1[] = 'updated_user = ' . ((isset($array['updated_user'])) ? $array['updated_user'] : $userID);
		if (!db_query('UPDATE ' . $table . ' SET ' . implode(', ', $query1) . ' WHERE id = ' . $id)) return false;
	} else {
		//adding new record
		if (db_column_exists($table, 'created_date')) {
			$query1[] = 'created_date';
			$query2[] = ((isset($array['created_date'])) ? '"' . $array['created_date'] . '"' : db_date());
		}
		if (db_column_exists($table, 'created_user')) {
			$query1[] = 'created_user';
			$query2[] = ((isset($array['created_user'])) ? $array['created_user'] : $userID);
		}
		if (db_column_exists($table, 'is_active')) {
			$query1[] = 'is_active';
			$query2[] = 1;
		}

		//precedence
		if (db_column_exists($table, 'precedence')) {
			$query1[] = 'precedence';
			if (isset($array['precedence'])) {
				$query2[] = $array['precedence'];
				db_query('UPDATE ' . $table . ' SET precedence = precedence + 1 WHERE precedence >= ' . $array['precedence']);
			} else {
				//otherwise append to list
				$query2[] = db_grab('SELECT MAX(precedence) FROM ' . $table) + 1;
			}
		}

		$query = 'INSERT INTO ' . $table . ' ( ' . implode(', ', $query1) . ' ) VALUES ( ' . implode(', ', $query2) . ' )';
		$id = db_query($query);
	}
	
	//if possible, populate search indexes
	if ($create_index && $full_text) db_words($full_text, $id, $table . '_to_words');
	
	return $id;
}

function db_schema_check($schema) {
	//check to verify that db structure is current
	//returns true if passed check, false if changes were made
	//used by bureaublank cms

	$passed = true;
	foreach ($schema as $table=>$fields) {
		if (!db_table_exists($table)) {
			//create any missing tables
			$passed = false;
			db_table_create($table, $fields, true);
		} else {
			//check columns are accurate
			foreach ($fields as $column=>$datatype) {
				if (!db_column_exists($table, $column)) {
					$passed = false;
					db_column_add($table, $column, false, $datatype);
				}
			}
		}
	}
	
	return $passed;
}

function db_search_words($words=false) {
	//pass this an array or string of words
	if (is_string($words)) $words = array_separated($words, ' ');
	foreach ($words as &$w) $w = 'w.word = "' . format_accents_encode($w) . '"';
	if (empty($words)) return false;
	return 'AND (' . implode(' OR ', $words) . ')';
}

function db_switch($target=false) {
	global $_josh;
	db_open();
	if (!$target) $target = $_josh['db']['database'];
	if ($_josh['db']['language'] == 'mssql') {
		mssql_select_db($target, $_josh['db']['pointer']);
	} elseif ($_josh['db']['language'] == 'mysql') {
		if (!mysql_select_db($target, $_josh['db']['pointer'])) $_josh['db']['pointer'] = false;
	}
	$_josh['db']['switched'] = ($target == $_josh['db']['database']) ? false : true;
}

function db_table($sql, $limit=false, $suppress_error=false) {
	$return = array();
	if (!$result = db_query($sql, $limit, $suppress_error)) return false;
	while ($r = db_fetch($result)) $return[] = $r;
	return $return;
}

function db_table_create($tablename, $fields=false) {
	//create table based on array schema
	
	//exists
	if (db_table_exists($tablename)) return false;
	
	//config columns
	$columns = '';
	if ($fields) foreach ($fields as $field=>$type) {
		if (is_array($type)) extract($type); //type could be array('type'=>'int', 'length'=>12, 'decimals'=>4) per db_table_from_array
		$columns .= '`' . strToLower($field) . '` ' . db_column_type($type, @$length, @$decimals) . ' DEFAULT NULL, ';
	}
	
	//run sql
	//todo make system fields optional
	if (db_query('CREATE TABLE `' . $tablename . '` (
		  `id` int(11) NOT NULL AUTO_INCREMENT,
		  ' . $columns . '
		  `created_date` datetime NOT NULL,
		  `created_user` int(11) DEFAULT NULL,
		  `publish_date` datetime DEFAULT NULL,
		  `publish_user` int(11) DEFAULT NULL,
		  `is_published` tinyint(4) NOT NULL,
		  `updated_date` datetime DEFAULT NULL,
		  `updated_user` int(11) DEFAULT NULL,
		  `deleted_date` datetime DEFAULT NULL,
		  `deleted_user` int(11) DEFAULT NULL,
		  `is_active` tinyint(4) NOT NULL,
		  `precedence` int(11) DEFAULT NULL,
		  PRIMARY KEY (`id`)) ENGINE=InnoDB DEFAULT CHARSET=utf8;')) return $tablename;
	
	//or not
	error_handle('could not create table' . $tablename, __file__, __line__);
}

function db_table_drop($tables) {
	if (db_language() == 'mssql') error_handle('MSSQL Not Yet Supported', __function__ . ' is only currently implemented in MySQL.', __file__, __line__);
	$tables = array_separated($tables);
	$counter = 0;
	foreach ($tables as $t) if (db_table_exists($t) && db_query('DROP TABLE ' . $t)) $counter++;
	return $counter;
}

function db_table_duplicate($old, $new) {
	if (!db_table_exists($old)) error_handle(__function__ . ' trying to copy from ' . $old . ' which does not exist.', __file__, __line__);
	if (db_table_exists($new)) error_handle(__function__ . ' trying to overwrite ' . $new . ' which already exists.', __file__, __line__);
	
	if (db_language() == 'mssql') error_handle('MSSQL Not Yet Supported', __function__ . ' is only currently implemented in MySQL.', __file__, __line__);

	db_query('CREATE TABLE ' . $new . ' LIKE ' . $old);
	db_query('INSERT ' . $new . ' SELECT * FROM ' . $old);
	db_query('UPDATE ' . $new . ' SET created_date = ' . db_date() . ', created_user = ' . user('NULL') . ', updated_date = NULL, updated_user = NULL');
}

function db_table_exists($name) {
	return in_array($name, db_tables());
	//if (db_language() == 'mssql') error_handle('MSSQL Not Yet Supported', __function__ . ' is only currently implemented in MySQL.', __file__, __line__);
	//return db_found(db_query('SHOW TABLES LIKE \'' . $name . '\''));
}

function db_table_from_array($array, $table_name=false) {
	//creates a database table to house the array you pass it
	//$array should be multi-dimensional
		
	$columns = array();
	$sql = array();
	
	foreach ($array as $a) {
		$row = array();
		foreach ($a as $column=>$value) {
			$column = str_replace('#', '', $column); //illegal character
			if ($value == '""') $value = '';
			$value = trim($value);
			//if (substr($value, 0, 1) == '"') $value = substr($value, 1);
			//if (substr($value, -1) == '"') $value = substr($value, 0, -1);
			if (substr($value, 0, 1) == '$') {
				$value = substr(str_replace(',', '', $value), 1);
			}
			$row[] = format_null(format_quotes($value));
			if (!array_key_exists($column, $columns)) $columns[$column] = array('type'=>'int', 'length'=>false, 'decimals'=>false);
			$type	= format_get($value);
			$length	= strlen($value);
			if ($length > $columns[$column]['length']) $columns[$column]['length'] = $length;
			if ($type == 'decimal') {
				$decimals = $length - strpos($value, '.') - 1;
				if ($decimals > $columns[$column]['decimals']) $columns[$column]['decimals'] = $decimals;
				if ($columns[$column]['type'] == 'int') $columns[$column]['type'] = 'decimal';
			} elseif ($type == 'varchar') {
				if ($columns[$column]['type'] != 'text') $columns[$column]['type'] = 'varchar';
			} elseif ($type == 'text') {
				$columns[$column]['type'] = 'text';
			}
		}
		if ($table_name) $sql[] = 'INSERT INTO ' . $table_name . ' ( `' . implode('`, `', array_keys($columns)) . '`, created_date, created_user, is_active ) VALUES ( ' . implode(', ', $row) . ', ' . db_date() . ', ' . user('NULL') . ', 1 )';
	}
	
	if ($table_name) {
		db_table_drop($table_name);
		db_table_create($table_name, $columns);
		foreach ($sql as $s) db_query($s);
	} else {
		//echo draw_array($columns, '', true);
		return $columns;
	}
}

function db_table_rename($before, $after) {
	if (db_language() == 'mssql') error_handle('MSSQL Not Yet Supported', __function__ . ' is only currently implemented in MySQL.', __file__, __line__);
	return db_query('RENAME TABLE ' . $before . ' TO ' . $after);
}

function db_tables() {
	global $_josh;
	if (db_language() == 'mssql') {
		$tables = db_table('sp_tables @table_type = "\'TABLE\'"');
		foreach ($tables as &$t) $t = $t['TABLE_NAME'];
		sort($tables);
		return $tables;
	} elseif (db_language() == 'mysql') {
		$tables = db_table('SHOW TABLES FROM ' . $_josh['db']['database']);
		foreach ($tables as &$t) $t = strToLower($t['Tables_in_' . $_josh['db']['database']]);
		sort($tables);
		return $tables;
	}
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
	if (!$id) $id = url_id();
	if (!$id) error_handle('expecting \$_GET[\'id\']', __function__ . ' is expecting an id variable', __file__, __line__);
	db_query('UPDATE ' . $table . ' SET deleted_date = NULL, deleted_user = NULL, updated_user = ' . user('NULL') . ', updated_date = NOW(), is_active = 1 WHERE id = ' . $id);
}

function db_updated($table='') {
	//$table generally means a disambuiguator, eg SELECT t.id or SELECT table_name.id
	if (!empty($table)) $table .= '.';
	return 'IFNULL(' . $table . 'updated_date, ' . $table . 'created_date) updated';
}

function db_words($text, $object_id, $join_table='objects_to_words', $words_table='words') {
	//maintain an index of words for searching.  requires a words table and a linking table
	global $_josh;

	//todo make part of db_table_create
	if (!db_table_exists($words_table)) db_query('CREATE TABLE `' . $words_table . '` ( `id` int(11) NOT NULL AUTO_INCREMENT, `word` varchar(255) NOT NULL, PRIMARY KEY (`id`) ) ENGINE=InnoDB DEFAULT CHARSET=utf8;');
	if (!db_table_exists($join_table)) db_query('CREATE TABLE `' . $join_table . '` ( `object_id` int(11) NOT NULL, `word_id` int(11) NOT NULL, `count` int(11) NOT NULL ) ENGINE=InnoDB DEFAULT CHARSET=utf8;');
	
	//todo need to fix usage of split here
	$words = array_diff(@split('[^[:alpha:]]+', strtolower(format_accents_remove(strip_tags($text)))), $_josh['ignored_words']);
	
	$words_unique = array_unique($words);
	//die(draw_array($words) . '<hr><pre>' . $text . '</pre>');

	db_query('DELETE FROM ' . $join_table . ' WHERE object_id = ' . $object_id);
	foreach ($words_unique as $word) {
		if (!$word_id = db_grab('SELECT id FROM ' . $words_table . ' WHERE word = "' . $word . '"')) $word_id = db_query('INSERT INTO ' . $words_table . ' ( word ) VALUES ( "' . $word . '" )');
		db_query('INSERT INTO ' . $join_table . ' ( word_id, object_id, count ) VALUES ( ' . $word_id . ', ' . $object_id . ', ' . array_instances($words, $word) . ' )');
	}
}

function db_words_refresh($specific_tables=false, $words_table='words') {
	
	if ($specific_tables) $specific_tables = array_separated($specific_tables);

	//refresh indexes for whole database
	$tables = db_tables();
	foreach ($tables as $t) {
		if ($t == $words_table) continue;
		if ($specific_tables && !in_array($t, $specific_tables)) continue;
		$columns = db_columns($t);
		$text_cols = array();
		$id_present = false;
		foreach ($columns as $c) {
			if (($c['name'] == 'id') && ($c['type'] == 'int')) $id_present = true;
			if ((($c['type'] == 'varchar') || ($c['type'] == 'text')) && ($c['name'] != 'url') && ($c['name'] != 'password')) $text_cols[] = $t . '.' . $c['name'];
		}
		if ($id_present && count($text_cols)) {
			echo implode(', ', $text_cols) . BR;
			if (count($text_cols) > 1) {
				$values = db_table('SELECT id, CONCAT_WS(" ", ' . implode(', ', $text_cols) . ') text FROM ' . $t);
			} else {
				$values = db_table('SELECT id, ' . $text_cols[0] . ' text FROM ' . $t);
			}
			foreach ($values as $v) db_words($v['text'], $v['id'], $t . '_to_words', $words_table);
		}
	}
	exit;
}