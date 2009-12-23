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
	global $_josh;
	return @filesize($_josh['root'] . $filename);
}

function file_delete($filename) {
	global $_josh;
	if (file_exists($_josh['root'] . $filename)) unlink($_josh['root'] . $filename);
	return true;
}

function file_download($content, $filename, $extension) {
	//header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
	//header('Cache-Control: public');

	//for IE over SSL
	header('Cache-Control: maxage=1'); //In seconds
	header('Pragma: public');
	
	header('Content-Description: File Transfer');
	header('Content-Type: application/octet-stream');
	header('Content-Length: ' . strlen($content));
	header('Content-Disposition: attachment; filename=' . format_file_name($filename, $extension));
	echo $content;
	db_close();
}

function file_dynamic($table, $column, $id, $extension, $lastmod=false) {
	//function file_dynamic($filename, $lastmod, $query);
	global $_josh; // mtime = 1242850776, lastmod = 1242682931
	$filename = $_josh['write_folder'] . '/dynamic/' . $table . '-' . $column . '-' . $id . '.' . $extension;
	error_debug('<b>' . __function__ . '</b> running with filename = ' . $filename, __file__, __line__);
	if (!$lastmod || !file_exists($_josh['root'] . $filename) || (strToTime($lastmod) > filemtime($_josh['root'] . $filename))) {
		if ($content = db_grab('SELECT ' . $column . ' FROM ' . $table . ' WHERE id = ' . $id)) {
			file_put($filename, $content);
		} else {
			error_debug('<b>' . __function__ . '</b> returning false because no select', __file__, __line__);
			return false;
		}
	} else {
		error_debug('<b>' . __function__ . '</b> if statement did not qualify', __file__, __line__);	
	}
	return $filename;
}

function file_folder($folder, $endfilter=false) {
	global $_josh;
	error_debug('<b>file folder</b> running with ' . $folder, __file__, __line__);
	
	//check to make sure folder exists
	if (!is_dir($_josh['root'] . $folder)) {
		error_debug('<b>file folder</b> ' . $folder . ' is not a directory, exiting', __file__, __line__);
		return false;
	}
	error_debug('<b>file folder</b> $folder is a directory!', __file__, __line__);

	//set up filter
	if ($endfilter) $endfilter = explode(',', $endfilter);

	//open it
	if ($handle = opendir($_josh['root'] . $folder)) {
		$return = array();
		while (($name = readdir($handle)) !== false) {
			if (($name == '.') || ($name == '..') || ($name == '.DS_Store')) continue;
			$nameparts = explode('.', $name);
			$thisfile = array(
				'name'=>$name,
				'ext'=>array_pop($nameparts),
				'human'=>format_text_human(implode(' ', $nameparts)), 
				'path_name'=>$folder . $name,
				'type'=>@filetype($_josh['root'] . $folder . $name),
				'fmod'=>@filemtime($_josh['root'] . $folder . $name),
				'size'=>@filesize($_josh['root'] . $folder . $name)
			);
			if ($thisfile['type'] == 'dir') $thisfile['path_name'] .= '/';
			error_debug('<b>file folder</b> found ' . $thisfile['name'] . ' of type ' . $thisfile['type'], __file__, __line__);
			if ($endfilter) {
				$oneFound = false;
				foreach ($endfilter as $e) if (format_text_ends(trim($e), $thisfile['path_name']) || (($e == 'dir') && ($thisfile['type'] == 'dir'))) $oneFound = true;
				if ($oneFound) $return[] = $thisfile;
			} else {
				$return[] = $thisfile;
			}
		}
		error_debug('<b>file folder</b> closing handle', __file__, __line__);
		closedir($handle);
		if (count($return)) return array_sort($return);
	}
	error_debug('<b>file folder</b> no return count', __file__, __line__);
	return false;
}

function file_get($filename) {
	global $_josh;
	if (!$file = @fopen($filename, 'r')) {
		$filename = $_josh['root'] . $filename;
		if (!$file = @fopen($filename, 'r')) return false;
	}
	error_debug('<b>file_get</b> filename is ' . $filename, __file__, __line__);
	if (!$size = @filesize($filename)) return false;
	$data = fread($file, $size);
	fclose($file);
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
	list($filename, $extension) = file_name($filename);
	if (!$type_id = db_grab('SELECT id FROM ' . $table . ' WHERE extension = "' . $extension . '"')) {
		return db_query('INSERT INTO ' . $table . ' ( extension ) VALUES ( "' . $extension . '" )');
	}
	return $type_id;
}

function file_get_uploaded($fieldname, $types_table=false) {
	global $_FILES;
	error_debug('<b>file_get_uploaded</b> running ~ user is uploading a file', __file__, __line__);
	$content = file_get($_FILES[$fieldname]['tmp_name']);
	@unlink($_FILES[$fieldname]['tmp_name']);
	if ($types_table) return array($content, file_get_type_id($_FILES[$fieldname]['name'], $types_table));
	return $content;
}

function file_uploaded_image_orientation($fieldname) {
	//for smarter toddler, resize one way if oriented landscape, resize another if portrait
	global $_FILES;
	error_debug('<b>file_uploaded_image_orientation</b>', __file__, __line__);
	list($width, $height) = getimagesize($_FILES[$fieldname]['tmp_name']);
	if ($width > $height) return "landscape";
	return "portrait";
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

function file_is($filename) {
	//deprecated, use file_check
	global $_josh;
	return file_exists($_josh['root'] . $filename);
}

function file_name($filepath) {
	global $_josh;
	error_debug('file_name receiving filepath = $filepath', __file__, __line__);
	$pathparts	= explode('/', $filepath);
	$file		= array_pop($pathparts);
	$path		= implode($_josh['folder'], $pathparts);
	$fileparts	= explode('.', $file);
	$extension	= array_pop($fileparts);
	$filename	= implode('.', $fileparts);
	error_debug('file_name returning file = ' . $file . ', ext = ' . $extension . ', path = ' . $path, __file__, __line__);
	return array($filename, $extension, $path);
}

function file_pass($filename) {
	//is this strictly necessary?  what's this for?
	global $_josh;
	$content		= file_get($filename);
	//die($filename);
	$nameparts		= explode($_josh['folder'], $filename);
	$filenameparts	= explode('.', $nameparts[count($nameparts) - 1]);
	$extension		= array_pop($filenameparts);
	$filename		= implode('.', $filenameparts);
	return file_download($content, $filename, $extension);
}

function file_put($filename, $content) {
	global $_josh;
	file_delete($filename);
	//arguments should be reversed?
	$file = @fopen($_josh['root'] . $filename, 'w');
	if ($file === false) {
		error_handle('could not open file', 'the file ' . $_josh['root'] . $filename . ' could not be opened for writing.  perhaps it is a permissions problem.');
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
	
	$return	 = '<?php' . $_josh['newline'];
	
	//db variables
	$return .= '$_josh[\'db\'][\'location\']	= \'' . $_josh['db']['location'] . '\'; //server' . $_josh['newline'];
	$return .= '$_josh[\'db\'][\'language\']	= \'' . $_josh['db']['language'] . '\'; //mysql or mssql' . $_josh['newline'];
	$return .= '$_josh[\'db\'][\'database\']	= \'' . $_josh['db']['database'] . '\';' . $_josh['newline'];
	$return .= '$_josh[\'db\'][\'username\']	= \'' . $_josh['db']['username'] . '\';' . $_josh['newline'];
	$return .= '$_josh[\'db\'][\'password\']	= \'' . $_josh['db']['password'] . '\';' . $_josh['newline'];
	$return .= '$_josh[\'basedblanguage\']	= \'' . $_josh['basedblanguage'] . '\'; //mysql or mssql (not necessary unless you want it translated)' . $_josh['newline'];
	$return .= $_josh['newline'];
	
	//error variables
	$return .= '$_josh[\'error_log_api\']		= ' . (($_josh['error_log_api']) ? $_josh['error_log_api'] : 'false') . '; //error logging url, eg http://tasks.joshreisner.com/errorapi.php' . $_josh['newline'];
	$return .= '$_josh[\'email_default\']		= \'' . $_josh['email_default'] . '\'; //regular site emails come from this address' . $_josh['newline'];
	$return .= '$_josh[\'email_admin\']		= \'' . $_josh['email_admin'] . '\'; //error emails go to this address' . $_josh['newline'];
	$return .= $_josh['newline'];
	
	//url variables
	$return .= '$_josh[\'is_secure\']			= ' . (($_josh['is_secure']) ? $_josh['is_secure'] : 'false') . '; //indicates whether it should use https (true) or not (false)' . $_josh['newline'];
	$return .= $_josh['newline'];

	$return .= '?>';
	
	return file_put($_josh['config'], $return);
}

function file_rss($title, $link, $items, $filename=false) {
	global $_josh;
	//$items should be an array with title, description, link, author and date
	//dtfmt Wed, 12 Nov 2008 09:13:11 -0500
	
	//w3c feed validator wants this, if possible
	$return = ($filename) ? '<atom:link href="' . url_base() . $filename . '" rel="self" type="application/rss+xml" />' : '';
	
	$lastBuildDate = false;

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
	return file_put($filename, utf8_encode($return));
}

function file_sister($filename, $ext) {
	//this will tell you if there's a 'sister file' in the same directory, eg picture.jpg && picture.html
	//todo - rename to file_sibling
	//developed for jeffrey monteiro
	global $_josh;
	if (file_is($filename)) {
		list ($file, $extension, $path) = file_name($filename);
		$sister = $path . $_josh['folder'] . $file . '.' . $ext;
		if (file_is($sister)) {
			error_debug('file sister file exists', __file__, __line__);
			return $sister;
		} else {
			error_debug('file sister $sister does not exist', __file__, __line__);
		}
	}
	return false;
}

function file_unzip($source, $target) {
	global $_josh;
	
	//check to see if the ZIP library is installed
	if (!function_exists('zip_open')) {
		return error_handle('ZIP library missing', 'trying to unzip a file but the library is not installed.  if you don\'t want to install it, you must manually unzip lib.zip and put the resulting folder inside ' . $_josh['write_folder'] . ' for joshlib to run.');
	}
	
    $zip = zip_open($source);

    if (!is_resource($zip)) {
		$errors = array(
		'Multi-disk zip archives not supported.', 'Renaming temporary file failed.',
		'Closing zip archive failed',  'Seek error', 'Read error', 'Write error', 'CRC error', 'Containing zip archive was closed', 'No such file.', 'File already exists',
		'Can\'t open file', 'Failure to create temporary file.', 'Zlib error', 'Memory allocation failure', 'Entry has been changed', 'Compression method not supported.', 
		'Premature EOF', 'Invalid argument', 'Not a zip archive', 'Internal error', 'Zip archive inconsistent', 'Can\'t remove file', 'Entry has been deleted'
		);
		error_handle('ZIP won\'t open', 'zip_open failed with ' . $errors[$zip] . ' for ' . $source);
    }

	while ($zip_entry = zip_read($zip)) {
		$folder = dirname(zip_entry_name($zip_entry));
		if (format_text_starts('.', $folder)) continue;
		if (format_text_starts('__MACOSX', $folder)) continue;

        $completePath = $_josh['root'] . $target . $_josh['folder'] . $folder;
        $completeName = $_josh['root'] . $target . $_josh['folder'] . zip_entry_name($zip_entry);
        if (!file_exists($completeName)) {
            $tmp = '';
            foreach(explode($_josh['folder'], $folder) AS $k) {
                $tmp .= $k . $_josh['folder'];
                if(!is_dir($_josh['root'] . $target . $_josh['folder'] . $tmp)) mkdir($_josh['root'] . $target . $_josh['folder'] . $tmp, 0777);
            }
        }
        
        if (zip_entry_open($zip, $zip_entry, 'r')) {
            if ($fd = @fopen($completeName, 'w+')) {
                fwrite($fd, zip_entry_read($zip_entry, zip_entry_filesize($zip_entry)));
                fclose($fd);
            } else {
                // We think this was an empty directory
                if(!is_dir($tmp)) mkdir($completeName, 0777);
            }
            zip_entry_close($zip_entry);
        }
    }
    zip_close($zip);

	return true;
}

function file_write_folder($folder=false) {
	//make sure there's a writable folder where you said.  defaults to write_folder
	global $_josh;
	
	if (!$folder) $folder = $_josh['write_folder'];
	$folder = $_josh['root'] . $folder;
	
	//make folder
	if (!is_dir($folder) && !@mkdir($folder)) error_handle('couldn\'t create folder', 'file_write_folder tried to create a folder at ' . $folder . ' but could not.  please create a folder there and make it writable.');

	//set permissions
	if (!is_writable($folder)) {
		@chmod($folder, 0777); //might only need 755?
		return is_writable($folder);
	} else {
		return true;
	}
}

?>