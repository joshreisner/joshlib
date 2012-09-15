<?php
error_debug('including file.php', __file__, __line__);

function file_array($content, $filename=false) {
	//output function -- creates a html table file which, if file_downloaded as a .xls, will spoof an excel spreadsheet
	//all you need to do is pass it a two-dimensional array (i think)
	$header = false;
	$rows = '';
	foreach ($content as $line) {
		$rows .= '<tr>';
		if (!$header) $hreturn = array();
		foreach($line as $key=>$value) {
			if (!$header) $hreturn[] = '<td>' . format_text_human($key) . '</td>';
			$rows .= '<td>' . $value . '</td>';
		}
		
		if (!$header) $header = '<tr style="background-color:#fafade; font-weight:bold;">' . implode('', $hreturn) . '</tr>';
		$rows .= '</tr>';
	}
	$content = '<table border="1" style="font-family:Verdana; font-size:9px;">' . $header . $rows . '</table>';
	if ($filename) file_download($content, $filename, 'xls');
	return $content;
}

function file_check($filename) {
	return @filesize(DIRECTORY_ROOT . $filename);
}

function file_csv($content, $filename=false) {
	//output function -- creates a tab-delimited csv from an associative array
	$header = false;
	$rows = '';
	foreach ($content as $line) {
		if (!$header) $header = implode(TAB, array_keys($line)) . NEWLINE;
		foreach($line as $key=>$value) $line[$key] = str_replace(TAB, '     ', $value); //remove tabs
		$rows .= implode(TAB, $line) . NEWLINE;
	}
	$content = $header . $rows;
	if ($filename) file_download($content, $filename, 'csv');
	return $content;
}

/*obsoleting 2012-07-05
function file_csv($filename) {
	//should be named array_csv, think this is obsolete
	//potential replacement to array_csv.  running into problems with delimiters being inside quotes, want to use fgetcsv() for this purpose
	$count = false;
	$return = $cols = array();
	if (($handle = fopen(DIRECTORY_ROOT . $filename, 'r'))) {
	    while (($data = fgetcsv($handle, 1000, ',')) !== FALSE) {
	    	if (!$count) {
	    		//set header cols
	    		$cols = $data;
	    		$count = count($cols);
	    		echo 'count was ' . $count . ' and headers were ' . draw_array($data);
	    	} else {
	    		//add row
	    		$row = array();
				for ($i = 0; $i < $count; $i++) $row[$cols[$i]] = $data[$i];
				$return[] = $row;
	    	}
	    }
	    fclose($handle);
	}
	return $return;
}*/

function file_delete($filename) {
	if (!file_exists(DIRECTORY_ROOT . $filename)) return false;
	unlink(DIRECTORY_ROOT . $filename);
	return true;
}

/* recursive delete, not ready yet
function file_delete($str) {
	if (!stristr($str, DIRECTORY_ROOT)) $str = DIRECTORY_ROOT . $str;
	echo 'attempting to delete' . str_replace(DIRECTORY_ROOT, '', $str) . '<br/>';
    if (is_dir($str)) { 
	    foreach (glob(rtrim($str, '/') . '/*') as $file) file_delete($file);
		rmdir($str . '/');
    } else {
		unlink($str);
    }
}*/

function file_dir_writable($subdirectory=false) {
	//make sure there's a writable folder where you said.  defaults to write_folder
	$directory = DIRECTORY_ROOT . DIRECTORY_WRITE;
	if ($subdirectory) $directory .= DIRECTORY_SEPARATOR . $subdirectory;
	
	//make folder
	if (!is_dir($directory) && !@mkdir($directory)) error_handle('couldn\'t create folder', 'file_dir_writable tried to create a folder at ' . format_ascii($directory) . ' but could not.  please create a folder there and make it writable.', __file__, __line__);

	//set permissions
	if (!is_writable($directory) && !@chmod($directory, 0777)) error_handle('couldn\'t set permissions', 'file_dir_writable needs the ' . $directory . ' to be writable by the webserver.', __file__, __line__);
	
	return $directory;
}

function file_download($content, $filename, $extension) {
	//header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
	//header('Cache-Control: public');
	//todo add more types from http://www.iana.org/assignments/media-types/

	$filename = format_file_name($filename, $extension);
	
	//for IE over SSL
	header('Cache-Control: maxage=1'); //In seconds
	header('Pragma: public');
	header('Content-Description: File Transfer');
	header('Content-Type: ' . file_mime($extension));
	header('Content-Length: ' . strlen($content));
	header('Content-Disposition: attachment; filename="' . $filename . '"');
	echo $content;
	db_close();
}

function file_dynamic($table, $column, $id, $extension, $lastmod=true, $filename=false) {
	//get an image from the database, return a filename
	//lastmod is to decide whether the image needs to be updated.  you can pass false to skip updating.
	file_dir_writable('dynamic');
	//debug();
	if (!$filename) $filename = $table . '-' . $column . '-' . $id . '.' . $extension;
	$filename = DIRECTORY_WRITE . '/dynamic/' . $filename;
	error_debug(draw_strong(__function__) . ' running for filename = ' . $filename . ' with lastmod ' . $lastmod, __file__, __line__);
	if (($lastmod !== false) && (($lastmod === true) || !file_exists(DIRECTORY_ROOT . $filename) || (strToTime($lastmod) > filemtime(DIRECTORY_ROOT . $filename)))) {
		if (!file_exists(DIRECTORY_ROOT . $filename)) error_debug(draw_strong(__function__) . ' ' . DIRECTORY_ROOT . $filename . ' does not exist ', __file__, __line__);		
		if ($content = db_grab('SELECT ' . $column . ' FROM ' . $table . ' WHERE id = ' . $id)) {
			file_put($filename, $content);
			chmod(DIRECTORY_ROOT . $filename, 0755); //set file permissions per AHIC
		} else {
			error_debug('<b>' . __function__ . '</b> returning false because ' . $table . '.' . $column . ' was empty for id ' . $id, __file__, __line__);
			return false;
		}
	} else {
		error_debug('<b>' . __function__ . '</b> if statement did not qualify', __file__, __line__);	
	}
	return $filename;
}

function file_ext($filename) {
	$info = pathinfo(strToLower($filename));
	
	switch ($info['extension']) {
		//correct horrible filenames
		case 'htm' :
		return 'html';

		case 'jpeg' :
		return 'jpg';
	}
		
	return $info['extension'];
}

function file_folder($folder=false, $endfilter=false, $simple=false) {
	error_debug('<b>file folder</b> running with ' . $folder, __file__, __line__);
	
	//default to current folder
	if (!$folder) $folder = DIRECTORY;

	//check to make sure folder exists
	if (is_dir($folder)) {
		//could be relative
		$folder = realpath($folder);
	} else {
		if (is_dir(DIRECTORY_ROOT . $folder)) {
			$folder = realpath(DIRECTORY_ROOT . $folder);
		} else {
			error_debug('<b>file folder</b> ' . htmlspecialchars($folder) . ' is not a directory, exiting', __file__, __line__);
			return false;
		}
	}

	//set up filter
	if ($endfilter && is_string($endfilter)) $endfilter = array_separated($endfilter);

	//open it
	if ($handle = opendir($folder)) {
		$return = array();
		while (($name = readdir($handle)) !== false) {
			if (substr($name, 0, 1) == '.') continue;
			$file = pathinfo($folder . $name);
			list($filename, $extension, $path) = file_name($name);
			$path = str_replace(DIRECTORY_ROOT, '', $folder);
			$thisfile = array(
				'name'=>$name,
				'ext'=>$extension,
				'path_name'=>$path . '/' . $name,
				'type'=>@filetype($folder . '/' . $name),
				'fmod'=>@filemtime($folder . '/' . $name),
				'size'=>@filesize($folder . '/' . $name),
				'base'=>$filename,
				'path'=>$path,
				'realpath'=>$folder
			);
			ksort($thisfile);
			error_debug('<b>file folder</b> found ' . $thisfile['name'] . ' of type ' . $thisfile['type'], __file__, __line__);
			if ($thisfile['type'] == 'dir') $thisfile['path_name'] .= '/';
			if ($endfilter) {
				$oneFound = false;
				foreach ($endfilter as $e) if (format_text_ends($e, $thisfile['path_name']) || (($e == 'dir') && ($thisfile['type'] == 'dir'))) $oneFound = true;
				if ($oneFound) $return[] = ($simple) ? $thisfile['path_name'] : $thisfile;
			} else {
				$return[] = ($simple) ? $thisfile['path_name'] : $thisfile;
			}
		}
		error_debug('<b>' . __function__ . '</b> grabbed ' . count($return) . ' files from ' . $folder, __file__, __line__);
		closedir($handle);
	}
	if (!count($return)) return false;
	if ($simple) {
		sort($return);
		return $return;
	} else {
		return array_sort($return);
	}
	
	error_debug('<b>file folder</b> no return count', __file__, __line__);
	return false;
}

function file_get($filename, $eval=false) {
	$filename = urldecode($filename);
	if (!$file = @fopen($filename, 'r')) {
		$filename = DIRECTORY_ROOT . $filename;
		if (!$file = @fopen($filename, 'r')) return false;
	}
	error_debug('<b>file_get</b> filename is ' . $filename, __file__, __line__);
	if (!$size = @filesize($filename)) return false;
	$data = fread($file, $size);
	fclose($file);
	if ($eval) $data = eval($data);
	return ($data);
}

function file_get_max($pretty=true) {
	$filesize = format_size_bytes(ini_get('upload_max_filesize'));
	$postsize = format_size_bytes(ini_get('post_max_size'));
	$max_size = ($filesize > $postsize) ? $postsize : $filesize;
	if ($pretty) return format_size($max_size);
	return $max_size;
}

function file_get_type_id($filename, $table='documents_types') {
	//error_deprecated(__FUNCTION__ . ' is deprecated now in favor of extension columns like pdf, html, doc, xlsx etc');
	list($filename, $extension) = file_name($filename);
	if (!$type_id = db_grab('SELECT id FROM ' . $table . ' WHERE extension = "' . $extension . '"')) return db_query('INSERT INTO ' . $table . ' ( extension ) VALUES ( "' . $extension . '" )');
	return $type_id;
}

function file_get_uploaded($fieldname, $types_table=false) {
	if (!isset($_FILES[$fieldname])) return false;
	if ($_FILES[$fieldname]['error'] && ($_FILES[$fieldname]['error'] == 4)) return false;
	if ($_FILES[$fieldname]['error']) error_handle('file_get_uploaded upload error', 'file max is ' . file_get_max() . draw_array($_FILES), __file__, __line__);
	
	$content = file_get($_FILES[$fieldname]['tmp_name']);
	error_debug(__function__ . ' running ~ user is uploading a file of ' . $_FILES[$fieldname]['size'], __file__, __line__);
	@unlink($_FILES[$fieldname]['tmp_name']);

	//deprecated
	if ($types_table) return array($content, file_get_type_id($_FILES[$fieldname]['name'], $types_table));

	return $content;
}

function file_ical($event) {
	//check event array has necessary infoz
	//eg $event = array('title'=>'Diego\'s Birthday', 'start'=>'2011-08-04 00:00:00', 'end'=>'2011-08-04 23:59:59');
	extract($event);
	if (!isset($title) || !isset($start) || !isset($end)) error_handle(__function__ . ' input missing', 'title, start and end are required', __file__, __line__);
	
	//todo standardize dates into function -- what format is this?
	if (!is_int($start))	$start	= strtotime($start);
	if (!is_int($end))		$end	= strtotime($end);
	
	//todo, implement better (negative numbers, or time zone code)
	if (isset($event['gmt_offset'])) {
		$start	+= $event['gmt_offset'] * 3600;
		$end	+= $event['gmt_offset'] * 3600;
	}
	
	//other optional variables
	if (!isset($location))		$location		= '';
	if (!isset($description))	$description	= '';
	if (!isset($uid))			$uid			= uniqid();
	
	//build file content
	$content = 'BEGIN:VCALENDAR
PRODID:-//Microsoft Corporation//Outlook 11.0 MIMEDIR//EN
VERSION:1.0
BEGIN:VEVENT
DTSTART:' . date('Ymd', $start) . 'T' . date('His', $start) . 'Z
DTEND:' . date('Ymd', $end) . 'T' . date('His', $end) . 'Z
LOCATION;ENCODING=QUOTED-PRINTABLE:' . quoted_printable_encode($location) . '
UID:' . $uid . '
DESCRIPTION;ENCODING=QUOTED-PRINTABLE:' . quoted_printable_encode($description) . '
SUMMARY;ENCODING=QUOTED-PRINTABLE:' . quoted_printable_encode($title) . '
PRIORITY:3
END:VEVENT
END:VCALENDAR
';
	
	file_download($content, $title, 'vcs');
}

function file_icon($filename_or_ext, $link=false, $type='16x16') {
	//show the icon for a given filename
	if (empty($filename_or_ext)) return false;
	$filename_or_ext = strtolower($filename_or_ext);
	if ((stristr($filename_or_ext, '.')) && (!$filename_or_ext = file_ext($filename_or_ext))) return false;
	if ($return = draw_img(DIRECTORY_WRITE . '/lib/file_icons/' . $type . '/' . $filename_or_ext . '.png', $link)) return $return;
	error_handle('file type not added yet', 'the file type "' . $filename_or_ext . '" was not found in the file_icons library.  this has been noted.', __file__, __line__);
	return draw_img(DIRECTORY_WRITE . '/lib/file_icons/' . $type . '/unknown.png', $link);
}

function file_import_fixedlength($content, $definitions) {
	$return = array();
	$lines = explode('\n', $content);
	foreach ($lines as $line) {
		$thisreturn = array();
		$counter = 0;
		foreach ($definitions as $name=>$length) {
			$thisreturn[$name] = trim(substr($line, $counter, $length));
			$counter += $length;
		}
		$return[] = $thisreturn;
	}
	return $return;
}

function file_include($filename) {
	global $return; //this is to allow the included file to return something
	if (file_exists(DIRECTORY_ROOT . $filename)) {
		include(DIRECTORY_ROOT . $filename);
		return true;
	}
	return false;
}

function file_mime($ext) {
	$types = array(
		'pdf'=>'application/pdf',
		'xls'=>'application/vnd.ms-excel',
		'doc'=>'application/msword',
		'zip'=>'application/zip',
		'gif'=>'image/gif',
		'png'=>'image/png',
		'jpg'=>'image/jpeg',
		'txt'=>'text/plain',
		'pub'=>'application/x-mspublisher',
		'eps'=>'application/postscript'
	);
	if (array_key_exists($ext, $types)) return $types[$ext];
	return 'application/octet-stream';
}

function file_name($filepath) {
	error_debug('file_name receiving filepath = ' . $filepath, __file__, __line__);
	$pathparts	= explode(DIRECTORY_SEPARATOR, $filepath);
	$file		= array_pop($pathparts);
	$path		= implode(DIRECTORY_SEPARATOR, $pathparts);
	$fileparts	= explode('.', $file);
	$extension	= array_pop($fileparts);
	$filename	= urldecode(implode('.', $fileparts));
	error_debug('file_name returning file = ' . $file . ', ext = ' . $extension . ', path = ' . $path, __file__, __line__);
	return array($filename, $extension, $path);
}

function file_pass($filename) {
	//open a file and tell the browser to download it
	//is this function necessary?  is it being used?
	$content		= file_get($filename);
	$nameparts		= explode(DIRECTORY_SEPARATOR, $filename);
	$filenameparts	= explode('.', $nameparts[count($nameparts) - 1]);
	$extension		= array_pop($filenameparts);
	$filename		= implode('.', $filenameparts);
	return file_download($content, $filename, $extension);
}

function file_put($filename, $content) {
	//arguments should be reversed?
	file_delete($filename);
	if (!$file = @fopen(DIRECTORY_ROOT . $filename, 'w')) $file = @fopen($filename, 'w');
	if ($file === false) {
		error_handle('could not write file', '<b>' . __function__ . '</b> could not open ' . DIRECTORY_ROOT . $filename . ' for writing.  perhaps it is a permissions problem.', __file__, __line__);
	} else {
		if (is_array($content)) $content = implode($content);
		$bytes = fwrite($file, $content);
		error_debug('<b>file_put</b> writing $bytes bytes to $filename', __file__, __line__);	
		fclose($file);
		return $bytes;
	}
}

function file_put_config() {
	global $_josh;
	
	$return	 = '<?php' . NEWLINE;
	
	//app name [new]
	$return .= '$_josh[\'app_name\']			= \'' . $_josh['app_name'] . '\'; //the name of your site' . NEWLINE;
	
	//db variables
	$return .= '$_josh[\'db\'][\'location\']	= \'' . $_josh['db']['location'] . '\'; //server' . NEWLINE;
	$return .= '$_josh[\'db\'][\'language\']	= \'' . $_josh['db']['language'] . '\'; //mysql or mssql' . NEWLINE;
	$return .= '$_josh[\'db\'][\'database\']	= \'' . $_josh['db']['database'] . '\';' . NEWLINE;
	$return .= '$_josh[\'db\'][\'username\']	= \'' . $_josh['db']['username'] . '\';' . NEWLINE;
	$return .= '$_josh[\'db\'][\'password\']	= \'' . $_josh['db']['password'] . '\';' . NEWLINE;
	$return .= '$_josh[\'basedblanguage\']	= \'' . $_josh['basedblanguage'] . '\'; //mysql or mssql (not necessary unless you want it translated)' . NEWLINE;
	$return .= NEWLINE;
	
	//error variables
	$return .= '//$_josh[\'error_log_api\']		= \'http://errors.joshreisner.com/log.php\'; //error logging url' . NEWLINE;
	$return .= '$_josh[\'email_default\']		= \'' . $_josh['email_default'] . '\'; //regular site emails come from this address' . NEWLINE;
	$return .= '$_josh[\'email_admin\']		= \'' . $_josh['email_admin'] . '\'; //error emails go to this address' . NEWLINE;
	$return .= NEWLINE;
	
	//url variables
	$return .= '$_josh[\'host\']				= \'' . $_josh['request']['host'] . '\'; //preferred domain for site' . NEWLINE;
	$return .= '$_josh[\'is_secure\']			= ' . (($_josh['is_secure']) ? $_josh['is_secure'] : 'false') . '; //indicates whether it should use https (true) or not (false)' . NEWLINE;
	$return .= NEWLINE;

	//smtp variables
	$return .= '//$_josh[\'smtp\'][\'location\']		= \'mail.' . $_josh['request']['domain'] . '\';' . NEWLINE;
	$return .= '//$_josh[\'smtp\'][\'username\']		= \'\';' . NEWLINE;
	$return .= '//$_josh[\'smtp\'][\'password\']		= \'\';' . NEWLINE;
	$return .= NEWLINE;
	
	//google api variables
	$return .= '//$_josh[\'google\'][\'mapkey\']		= \'\';' . NEWLINE;
	$return .= '//$_josh[\'google_search_api_key\']		= \'\';' . NEWLINE;
	$return .= '//$_josh[\'google_translate_api_key\']		= \'\';' . NEWLINE;
	
	return file_put($_josh['config'], $return);
}

function file_rss($title, $link, $items, $filename=false, $limit=false) {
	global $_josh;
	//$items should be an array with title, description, link, author and date
	//date should be a udate

	//w3c feed validator wants this, if possible
	$return = ($filename) ? '<atom:link href="' . url_base() . $filename . '" rel="self" type="application/rss+xml" />' : '';
	
	$lastBuildDate = false;
	
	$items = array_sort($items, 'desc', 'date');

	if ($limit && (count($items) > $limit)) $items = array_slice($items, 0, $limit);

	foreach ($items as $i) {
		if (!$lastBuildDate) $lastBuildDate = format_date_rss($i['date']);
		$i['link'] = str_replace('&', '&amp;', $i['link']);
		$return .= '
		<item>
			<title>' . htmlspecialchars($i['title']) . '</title>
			<description><![CDATA[' . $i['description'] . ']]></description>
			<link>' . $i['link'] . '</link>
			<guid isPermaLink="true">' . $i['link'] . '</guid>
			<pubDate>' . format_date_rss($i['date']) . '</pubDate>
			<author>' . $i['author'] . '</author>
		</item>
		';
	}
	
	$description = '';
		
	$return = '<?xml version="1.0" encoding="utf-8"?>
		<rss version="2.0" xmlns:atom="http://www.w3.org/2005/Atom">
		<channel>
		<title>' . $title . '</title>
		<link>' . $link . '</link>
		<description>' . $description . '</description>
		<language>en-us</language>
		<managingEditor>' . $_josh['email_admin'] . '</managingEditor>
		<copyright>' . '</copyright>
		<lastBuildDate>' . $lastBuildDate . '</lastBuildDate>
		<generator>http://joshlib.joshreisner.com/</generator>
		<webMaster>' . $_josh['email_admin'] . '</webMaster>
		<ttl>15</ttl>
		' . $return . '
		</channel>
		</rss>';
	
	if (!$filename) return $return;

	//we're going to put it in the special write_folder rss folder
	file_dir_writable('rss');
	return file_put(DIRECTORY_WRITE . DIRECTORY_SEPARATOR . 'rss' . DIRECTORY_SEPARATOR . $filename, utf8_encode($return));
}

function file_sister($filename, $ext) {
	//this will tell you if there's a 'sister file' in the same directory, eg picture.jpg && picture.html
	//todo - rename to file_sibling
	//developed for jeffrey monteiro, modified for brad ascalon 2010-08-03
	//debug();
	if (!$realpath = realpath($filename)) $realpath = realpath(DIRECTORY_ROOT . $filename);
	error_debug('given ' . $realpath, __file__, __line__);
	if ($realpath) {
		list($file, $extension, $path) = file_name($realpath);
		$sister = $path . DIRECTORY_SEPARATOR . $file . '.' . $ext;
		if (file_exists($sister)) {
			error_debug('file sister file exists', __file__, __line__);
			return str_replace(DIRECTORY_ROOT, '', $sister);
		} else {
			error_debug('file sister ' . $sister . ' does not exist', __file__, __line__);
		}
	}
	return false;
}

function file_type($filename) {
	$parts = explode('.', strToLower($filename));
	$count = count($parts);
	if ($count) {
		return $parts[$count-1];
	} else {
		return false;
	}
}

function file_unzip($source, $target, $chmod=false) {
	//unzip a file with the UNIX command, which is a bit opaque.  we can't be sure what it was we just unzipped, if the file has been renamed
	//php ZIP functions have been removed
	system('unzip -q ' . $source . ' -d ' . DIRECTORY_ROOT . $target);
	
	//this is causing problems on icdsoft (500 server errors on tinymce imagemanager and filemanager
	//if ($chmod) system('chmod -R 777 ' . DIRECTORY_ROOT . $target);

	list($filename, $extension, $path) = file_name($source);
	$parentDirectory = DIRECTORY_ROOT . $target . DIRECTORY_SEPARATOR . $filename; //guess what the file was
	
	if (is_dir(DIRECTORY_ROOT . $target . DIRECTORY_SEPARATOR . '__MACOSX')) {
		//special annoying folder created by MAC zipping
		system('rm -rf ' . DIRECTORY_ROOT . $target . DIRECTORY_SEPARATOR . '__MACOSX');
	}
	
	//try to clean up
	if (!is_dir($parentDirectory)) {
		error_debug('<b>' . __function__ . '</b> failed to unzip ' . $source . ' to ' . $parentDirectory . ' using UNIX unzip.', __file__, __line__);
		return false;
	} else {
		touch($parentDirectory); //set updated time
		error_debug('<b>' . __function__ . '</b> unzipped ' . $source . ' to ' . $parentDirectory . ' using UNIX unzip.  The last modified time is now ' . format_date_time(filemtime(DIRECTORY_ROOT . $target)), __file__, __line__);
		return true;
	}
}

function file_uploaded_image_orientation($fieldname) {
	//for smarter toddler, resize one way if oriented landscape, resize another if portrait
	global $_FILES;
	error_debug('<b>file_uploaded_image_orientation</b>', __file__, __line__);
	list($width, $height) = getimagesize($_FILES[$fieldname]['tmp_name']);
	if ($width > $height) return 'landscape';
	return 'portrait';
}