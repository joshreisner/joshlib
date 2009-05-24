<?php
error_debug("including file.php", __file__, __line__);

function file_array($content, $filename=false) {
	//output function -- creates a html table file which, if file_downloaded as a .xls, will spoof an excel spreadsheet
	//all you need to do is pass it a two-dimensional array (i think)
	$header = false;
	$rows = "";
	foreach ($content as $line) {
		$rows .= "<tr>";
		if (!$header) $hreturn = array();
		foreach($line as $key=>$value) {
			if (!$header) $hreturn[] = "<td>" . format_text_human($key) . "</td>";
			$rows .= "<td>" . $value . "</td>";
		}
		
		if (!$header) $header = '<tr style="background-color:#fafade; font-weight:bold;">' . implode("", $hreturn) . '</tr>';
		$rows .= "</tr>";
	}
	$content = '<table border="1" style="font-family:Verdana; font-size:9px;">' . $header . $rows . '</table>';
	if ($filename) return file_download($content, $filename, "xls");
	return $content;
}

function file_delete($filename) {
	global $_josh;
	if (file_exists($_josh["root"] . $filename)) unlink($_josh["root"] . $filename);
	return true;
}

function file_download($content, $filename, $extension) {
	//header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
	//header("Cache-Control: public");

	//for IE over SSL
	header("Cache-Control: maxage=1"); //In seconds
	header("Pragma: public");
	
	header("Content-Description: File Transfer");
	header("Content-Type: application/octet-stream");
	header("Content-Length: " . strlen($content));
	header("Content-Disposition: attachment; filename=" . format_file_name($filename, $extension));
	echo $content;
	db_close();
}

function file_dynamic($filename, $lastmod, $query) {
	global $_josh; // mtime = 1242850776, lastmod = 1242682931
	if (!file_exists($_josh["root"] . $filename) || (strToTime($lastmod) > filemtime($_josh["root"] . $filename))) file_put($filename, db_grab($query));
	return true;
}

function file_folder($folder, $endfilter=false) {
	global $_josh;
	error_debug("<b>file folder</b> running with $folder", __file__, __line__);
	
	//check to make sure folder exists
	if (!is_dir($folder)) {
		error_debug("<b>file folder</b> $folder is not a directory", __file__, __line__);
		$folder = $_josh["root"] . $folder;
		if (!is_dir($folder)) {
			error_debug("<b>file folder</b> $folder is not a directory either, exiting", __file__, __line__);
			return false;
		}
	}
	error_debug("<b>file folder</b> $folder is a directory!", __file__, __line__);

	//set up filter
	if ($endfilter) $endfilter = explode(",", $endfilter);

	//open it
	if ($handle = opendir($folder)) {
		error_debug("<b>file folder</b> $folder opened", __file__, __line__);
		$return = array();
		while (($name = readdir($handle)) !== false) {
			if (($name == ".") || ($name == "..") || ($name == ".DS_Store")) continue;
			$nameparts = explode(".", $name);
			$thisfile = array(
				"name"=>$name,
				"ext"=>array_pop($nameparts),
				"human"=>format_text_human(implode(" ", $nameparts)), 
				"path_name"=>$folder . $name,
				"type"=>filetype($folder . $name),
				"fmod"=>filemtime($folder . $name),
				"size"=>filesize($folder . $name)
			);
			if ($thisfile["type"] == "dir") $thisfile["path_name"] .= "/";
			error_debug("<b>file folder</b> found " . $thisfile["name"] . " of type " . $thisfile["type"], __file__, __line__);
			if ($endfilter) {
				$oneFound = false;
				foreach ($endfilter as $e) if (format_text_ends(trim($e), $thisfile["path_name"]) || (($e == "dir") && ($thisfile["type"] == "dir"))) $oneFound = true;
				if ($oneFound) $return[] = $thisfile;
			} else {
				$return[] = $thisfile;
			}
		}
		error_debug("<b>file folder</b> closing handle", __file__, __line__);
		closedir($handle);
		if (count($return)) return array_sort($return);
		error_debug("<b>file folder</b> no return count", __file__, __line__);
	}
	return false;
}

function file_get($filename) {
	global $_josh;
	if (!$file = @fopen($filename, "r")) {
		$filename = $_josh["root"] . $filename;
		if (!$file = @fopen($filename, "r")) return false;
	}
	error_debug("<b>file_get</b> filename is " . $filename, __file__, __line__);
	if (!$size = @filesize($filename)) return false;
	$data = fread($file, $size);
	fclose($file);
	return ($data);
}

function file_get_max($pretty=true) {
	$filesize = format_size_bytes(ini_get("upload_max_filesize"));
	$postsize = format_size_bytes(ini_get("post_max_size"));
	$max_size = ($filesize > $postsize) ? $postsize : $filesize;
	if ($pretty) return format_size($max_size);
	return $max_size;
}

function file_get_type_id($filename, $table="documents_types") {
	list($filename, $extension) = file_name($_FILES["content"]["name"]);
	if (!$type_id = db_grab("SELECT id FROM " . $table . " WHERE extension = '" . $extension . "'")) {
		return db_query("INSERT INTO " . $table . " ( extension ) VALUES ( '" . $extension . "' )");
	}
	return $type_id;
}

function file_import_fixedlength($content, $definitions) {
	$return = array();
	$lines = explode("\n", $content);
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
	global $_josh;
	return file_exists($_josh["root"] . $filename);
}

function file_name($filepath) {
	global $_josh;
	error_debug("file_name receiving filepath = $filepath", __file__, __line__);
	$pathparts	= explode("/", $filepath);
	$file		= array_pop($pathparts);
	$path		= implode($_josh["folder"], $pathparts);
	$fileparts	= explode(".", $file);
	$extension	= array_pop($fileparts);
	$filename	= implode(".", $fileparts);
	error_debug("file_name returning file = $file, ext = $extension, path = $path", __file__, __line__);
	return array($filename, $extension, $path);
}

function file_pass($filename) {
	//is this strictly necessary?  what's this for?
	global $_josh;
	$content		= file_get($filename);
	//die($filename);
	$nameparts		= explode($_josh["folder"], $filename);
	$filenameparts	= explode(".", $nameparts[count($nameparts) - 1]);
	$extension		= array_pop($filenameparts);
	$filename		= implode(".", $filenameparts);
	return file_download($content, $filename, $extension);
}

function file_put($filename, $content) {
	global $_josh;
	file_delete($filename);
	//arguments should be reversed?
	$file = @fopen($_josh["root"] . $filename, "w");
	if ($file === false) {
		error_handle("could not open file", "the file " . $_josh["root"] . $filename . " could not be opened for writing.  perhaps it is a permissions problem.");
	} else {
		if (is_array($content)) $content = implode($content);
		$bytes = fwrite($file, $content);
		fclose($file);
		return $bytes;
	}
}

function file_rss($title, $link, $items, $filename=false) {
	global $_josh;
	//$items should be an array with title, description, link and date
	//dtfmt Wed, 12 Nov 2008 09:13:11 -0500
	$return = "";
	$lastBuildDate = false;
	
	foreach ($items as $i) {
		if (!$lastBuildDate) $lastBuildDate = format_date_rss($i["date"]);
		$return .= '
		<item>
			<title>' . htmlspecialchars($i["title"]) . '</title>
			<description><![CDATA[' . $i["description"] . ']]></description>
			<link>' . $i["link"] . '</link>
			<guid isPermaLink="true">' . $i["link"] . '</guid>
			<pubDate>' . format_date_rss($i["date"]) . '</pubDate>
			<author>' . $i["author"] . '</author>
		</item>
		';
	}
	
	$description = "";
	
	$return = '<?xml version="1.0" encoding="utf-8"?>
		<rss version="2.0">
		<channel>
		<title>' . $title . '</title>
		<link>' . $link . '</link>
		<description>' . $description . '</description>
		<language>en-us</language>
		<managingEditor>' . $_josh["email_admin"] . '</managingEditor>
		<copyright>' . '</copyright>
		<lastBuildDate>' . $lastBuildDate . '</lastBuildDate>
		<generator>http://joshreisner.com/joshlib/</generator>
		<webMaster>' . $_josh["email_admin"] . '</webMaster>
		<ttl>15</ttl>
		' . $return . '		
		</channel>
		</rss>';
	if (!$filename) return $return;
	return file_put($filename, utf8_encode($return));
}

function file_sister($filename, $ext) {
	global $_josh;
	//this will tell you if there's a 'sister file' in the same directory, eg picture.jpg && picture.html
	if (file_exists($filename)) {
		list ($file, $extension, $path) = file_name($filename);
		$sister = $path . $_josh["folder"] . $file . "." . $ext;
		if (file_exists($sister)) {
			error_debug("file sister file exists");
			return $sister;
		} else {
			error_debug("file sister $sister does not exist");
		}
	}
	return false;
}
?>