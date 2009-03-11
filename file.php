<?php
error_debug("~ including file.php");

function file_array($content, $filename=false) {
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

function file_folder($folder, $types=false) {
	global $_josh;
	error_debug("<b>file folder</b> running with $folder");
	if (!is_dir($folder)) {
		error_debug("<b>file folder</b> $folder is not a directory");
		$folder = $_josh["root"] . $folder;
		if (!is_dir(!$folder)) {
			error_debug("<b>file folder</b> $folder is not a directory either, exiting");
			return false;
		}
	}
	error_debug("<b>file folder</b> $folder is a directory!");
	if ($types) $types = explode(",", $types);
	if ($handle = opendir($folder)) {
		error_debug("<b>file folder</b> $folder opened");
		$return = array();
		while (($name = readdir($handle)) !== false) {
			if (($name == ".") || ($name == "..") || ($name == ".DS_Store")) continue;
			$nameparts = explode(".", $name);
			$thisfile = array(
				"name"=>$name,
				"ext"=>$nameparts[count($nameparts)-1],
				"path_name"=>$folder . $name,
				"type"=>filetype($folder . $name),
				"fmod"=>filemtime($folder . $name),
				"size"=>filesize($folder . $name)
			);
			if ($thisfile["type"] == "dir") $thisfile["path_name"] .= "/";
			error_debug("<b>file folder</b> found " . $thisfile["name"] . " of type " . $thisfile["type"]);
			if ($types) {
				$oneFound = false;
				foreach ($types as $type) if (($thisfile["ext"] == trim($type)) || (($type == "dir") && ($thisfile["type"] == "dir"))) $oneFound = true;
				if ($oneFound) $return[] = $thisfile;
			} else {
				$return[] = $thisfile;
			}
		}
		error_debug("<b>file folder</b> closing handle");
		closedir($handle);
		if (count($return)) return arraySort($return);
		error_debug("<b>file folder</b> no return count");
	}
	return false;
}

function file_get($filename) {
	global $_josh;
	if (!$file = @fopen($filename, "r")) {
		$filename = $_josh["root"] . $filename;
		if (!$file = @fopen($filename, "r")) return false;
	}
	error_debug("<b>file_get</b> filename is " . $filename);
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

function file_image_resize($source_name, $target_name, $new_width) {
	//src is not root because it's probably uploaded
	global $_josh;
	if (!function_exists("imagecreatefromjpeg")) error_handle("library missing", "the GD library needs to be installed to run file_image_resize");
	if (!file_exists($source_name)) return false; //return if no file source
	list($width, $height) = getimagesize($source_name);
	if ($width == $new_width) {
		copy($source_name, $target_name);
	} else {
		if (!$image = @imagecreatefromjpeg($source_name)) error_handle("couldn't create image", "the system could not create an image from " . $src);
		$new_height = ($height / $width) * $new_width;
		$tmp = imagecreatetruecolor($new_width, $new_height);
		imagecopyresampled($tmp, $image, 0, 0, 0, 0, $new_width, $new_height, $width, $height);
		imagejpeg($tmp, $_josh["root"] . $target_name, 100);
		imagedestroy($image);
		imagedestroy($tmp);
	}
	return true;
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

function file_name($filepath) {
	global $_josh;
	//error_debug("file_name receiving filepath = $filepath");
	$pathparts	= explode("/", $filepath);
	$file		= array_pop($pathparts);
	$path		= implode($_josh["folder"], $pathparts);
	$fileparts	= explode(".", $file);
	$extension	= array_pop($fileparts);
	$filename	= implode(".", $fileparts);
	error_debug("file_name returning file = $file, ext = $extension, path = $path");
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
	
	//$items should be an array with a bunch of stuff in it
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