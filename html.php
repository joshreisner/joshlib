<?php
/**
 * 
 * HTML
 * 
 * Methods for generating HTML.
 *
 * One of the key params you'll see over and over in this class is the $arguments class.  This can be either a string 
 * or an associative array.  If an array, keys are arguments such as class=>foo, id=>bar, width=>12.  If a string, it's
 * assumed to be a class unless prepended with a #, in which case it's an id.  
 * html::div('#foo') becomes <div id="foo"></div>
 * html::div('bar') becomes <div class="bar"></div>
 * html::div(array('id'=>'foo', 'class'=>'bar)) becomes <div id="foo" class="bar"></div>
 *
 * Order of arguments is a tricky thing, and consistency is balanced against utility.  "Generic containers" are available for
 * all the tags in the $generic_containers array, and are in the format html::h1($content, $arguments) 
 * A notable exception are <div>s, which are reversed so they can be used in constructions where it's helpful to have the arguments
 * near the tag
 * html::div('classname',
 * 		html::h1('Here is a content title') . 
 * 		html::p('Here is an intro paragraph', 'intro') . 
 *		html::p('Here is another paragraph')
 * );
 *
 * Todo: research whether there's a way to define the generic container functions from within a repeat loop, either that, or
 * do a catch-all function?  These are: 
 * 
 * @package Joshlib
 */

class html {

	private static $generic_containers = array('article', 'aside', 'code', 'em', 'fieldset', 'footer', 'h1', 'h2', 'h3', 'h4', 'h5', 
		'header', 'li', 'p', 'pre', 'section', 'small', 'span', 'strong');
	
	/**
	  * Generic container catch-all function
	  *
	  * @param	string	$tag		Not called directly, this is the span in html::span()
	  * @param	mixed	$arguments	Any arguments supplied to the container, usually html::p($content, $arguments)
	  * @return	string				The formed container, eg <footer>Some content</footer>
	  */
	static function __callStatic($tag, $arguments) {
		if (!in_array($tag, self::$generic_containers)) trigger_error('html tag ' . $tag . ' was called but not found.');
		if (count($arguments) == 2) {
			$content = $arguments[0];
			$arguments = $arguments[1];
		} else {
			$content = (is_array($arguments)) ? $arguments[0] : $arguments;
			$arguments = false;
		}
		return self::tag($tag, $arguments, $content);
	}

	/**
	  * Special <a> container
	  * Todo: add auto-email-obscuring
	  *
	  * @param	string	$href		The URL link target
	  * @param	mixed	$arguments	String for class, string prepended with # for id, or array for multiple arguments
	  * @param	string	$content	The content contained inside the link
	  * @return	string				The HTML anchor link
	  */
	static function a($href=false, $content='', $arguments=false) {
		$arguments = self::arguments($arguments);
		if (empty($href)) {
			$arguments['class'] = (empty($arguments['class'])) ? 'empty' : $arguments['class'] . ' empty';
		} else {
			$arguments['href'] = $href;
		}
		if ($email = str::starts($href, 'mailto:')) {
			$encoded = str::encode($email);
			$href = 'mailto:' . $encoded;
			$content = str_replace($email, $encoded, $content);
		}
		return self::tag('a', $arguments, $content);
	}

	/**
	  * Helper function to parse a mixed $arguments variable into an array of tag arguments
	  *
	  * @param	mixed	$arguments	String for class, string prepended with # for id, or array for multiple arguments
	  * @return	array	The array of tag arguments	
	  */
	private static function arguments($arguments=false) {
		//convert arguments to array
		if (empty($arguments)) return array();

		//arguments can be string for shorthand, class or prepend with # for id
		if (is_string($arguments)) {
			if ($id = str::starts($arguments, '#')) {
				$arguments = array('id'=>$id);
			} else {
				$arguments = array('class'=>$arguments);
			}
		}
		
		//clean up classes
		if (!empty($arguments['class']) && stristr($arguments['class'], ' ')) {
			$arguments['class'] = implode(' ', array_values(array_filter(array_unique(explode(' ', $arguments['class'])))));
		}
		
		return $arguments;
	}
	
	/**
	  * Make an open <body> tag and add the requested folders, so /example/folder will yield <body class="example folder">
	  *
	  * @param	mixed	$arguments	String for class, string prepended with # for id, or array for multiple arguments
	  * @return	string	The open <body> tag
	  */
	static function body_open($arguments=false) {
	
		//add folders to body class
		$arguments = self::arguments($arguments);
		if (!isset($arguments['class'])) $arguments['class'] = '';
		
		if ($folders = http::request('folders')) {
			foreach ($folders as $folder) $arguments['class'] .= ' ' . $folder;
		} else {
			$arguments['class']	.= ' home';
		}
		
		return self::tag('body', $arguments, false, true);
	}
	
	/**
	  * Link to a CSS file
	  * Todo: add filemtime to help browser caching
	  *
	  * @param	string	$href		the URL of the CSS file
	  * @return	string	<link rel="stylesheet" href="/example/stylesheet.css">
	  */
	static function css($href) {
		return self::tag('link', array('rel'=>'stylesheet', 'href'=>$href));
	}
	
	/**
	  * Special <dl> constructor
	  *
	  * @param	array	$elements	Associative array of Key=>Content elements, will be wrapped in <dd> and <dt> tags
	  * @param	mixed	$arguments	String for class, string prepended with # for id, or array for multiple arguments
	  * @return	string	The contructed <dl>
	  */
	static function dl($elements=false, $arguments=false) {
		$content = '';
		foreach ($elements as $key=>$value) $content .= self::tag('dt', false, $key) . self::tag('dd', false, $value);
		return self::tag('dl', $arguments, $content);
	}
	
	/**
	  * Special <div> container.  Arguments are reversed, see description at top for why.  The intent is not to confuse.
	  *
	  * @param	mixed	$arguments	String for class, string prepended with # for id, or array for multiple arguments
	  * @param	string	$content	The content contained inside the div
	  * @return	string	<div class="example">Some content</div>
	  */
	static function div($arguments=false, $content='') {
		return self::tag('div', $arguments, $content);
	}
	
	/**
	  * Special <div> container, open version.  Useful in includes.  As with DIVs above, arguments are reversed.
	  *
	  * @param	mixed	$arguments	String for class, string prepended with # for id, or array for multiple arguments
	  * @param	string	$content	The content contained inside the div
	  * @return	string	<div class="example">Some content
	  */
	static function div_open($arguments=false, $content='') {
		return self::tag('div', $arguments, $content, true);
	}

	/**
	  * Special function for debugging.  It's like die() but with formatting
	  *
	  * @param	mixed	$variable	Any kind of variable
	  */
	static function dump($variable) {
		if (is_string($variable)) {
			echo html::pre($variable);
		}
		exit;
	}
	
	/**
	  * Twitter Bootstrap helper function.  Creates a set of div.row and div.spanX for fluid layouts.
	  *
	  * @param	array	$array		One-dimensional array of contents for the grid
	  * @param	int		$columns	The number of columns for each row, must be 1, 2, 3, 4, 6 or 12
	  * @param string	$class		A class to append to each div.row
	  * @return	string				Bootstrap grid HTML
	  */
	static function grid($array, $columns=3, $class='') {
		if (!in_array($columns, array(1, 2, 3, 4, 6, 12))) return false;
		if (!empty($class)) $class = ' ' . $class;
		$span = round(12 / $columns);
		$rows = array_sets($array, $columns);
		foreach ($rows as &$row) {
			foreach ($row as &$column) $column = self::div('span' . $span, $column);
			$row = self::div('row' . $class, implode($row));
		}
		return implode($rows);
	}
	
	/**
	  * Special <head> container tag.  Prepends meta charset because I cannot 
	  * imagine a scenario where that would not be needed
	  *
	  * @param	string	$content	The content contained inside the h1
	  * @return	string				The HEAD tag
	  */
	static function head($content='') {
		$content = self::meta('charset') . $content; //auto-prepend charset because it's always needed
		return self::tag('head', false, $content);
	}
	
	/**
	  * Special <img> function.  Tries to get height & width if not specified
	  *
	  * @param	string	$filename	The filename of the image
	  * @return	string				The IMG tag
	  */
	static function img($filename, $arguments=false) {
		$arguments = self::arguments($arguments);
		
	}
	
	/**
	  * Special <input> function.  
	  *
	  * @param	string	$type		Eg text, date, time, color, etc.
	  * @return	string				The IMG tag
	  */
	static function input($type, $name, $value='', $arguments=false) {
		$arguments = self::arguments($arguments);
		$arguments = array_merge($arguments, compact('type', 'name', 'value'));
		return self::tag('input', $arguments);
	}
	
	/**
	  * Include a JavaScript file.
	  * Todo: add filemtime to help with browser caching
	  *
	  * @param	string	$src	The URL of the JS file
	  * @return	string	<script src="/example/javascript.js"></script>
	  */
	static function js($src=false) {
		if (!$src) $src = config::get('js');
		return self::tag('script', array('src'=>$src));
	}
	
	/**
	  * Special <meta> tag.
	  *
	  * @param	string	$key		accepts charset, description, keywords, viewport
	  * @param	string	$value		The value for the meta tag.  Does not apply to charset, to change the charset, do it with config::set()
	  * @return	string	<li>Some content</li>
	  */
	static function meta($key, $value=false) {
		if ($value !== false) $value = strip_tags($value); //can't have tags inside a meta tag
		switch ($key) {
			case 'charset';
			return self::tag('meta', array('charset'=>config::get('charset')));

			case 'description';
			if ($value === false) return '';
			return self::tag('meta', array('name'=>'description', 'content'=>$value));

			case 'keywords';
			if ($value === false) return '';
			return self::tag('meta', array('name'=>'keywords', 'content'=>$value));

			case 'viewport':
			if ($value !== false) config::set('viewport', $value); //save new viewport
			return self::tag('meta', array('name'=>'viewport', 'content'=>config::get('viewport')));
		}
		return false; //$key was not supported
	}
	
	/**
	  * Special <nav> container.  
	  *
	  * @param	array	$elements	Associative array of URL=>Content nav elements, will be wrapped in <a> tags
	  * @param	mixed	$arguments	String for class, string prepended with # for id, or array for multiple arguments
	  * @param	string	$separator	Optional string to sandwich between the <a>s
	  * @param	array	$classes	One-dimensional array of class names for each <a>
	  * @return	string				HTML <nav> with <a> child elements
	  */
	static function nav($elements=false, $arguments=false, $separator='', $classes=false) {
		$anchors = array();
		if ($elements) {
			foreach ($elements as $key=>$value) {
				$class = ($key == http::request('path_query')) ? 'active' : '';
				$anchors[] = self::a($key, $value, @array_shift($classes) . $class);
			}
		}
		return self::tag('nav', $arguments, implode($separator, $anchors));
	}
	
	/**
	  * Special navigation HTML.  Creates a set of nested ULs with links.  Adds an active class on links to the current page.
	  *
	  * @param	$array	$pages		Associative array, $pages should have keys for title and URL, optionally children with another array
	  * @param	int		$depth		Don't use this.  It's a recursive function and needs to know when it gets back to the top.
	  * @return	string				Nested ULs, LIs and As
	  */
	static function navigation($pages, $depth=1) {
		$active = false;
		$elements = $classes = array();
		foreach ($pages as $page) {
			$class = '';
			
			//get selected
			if (http::request('path_query') == $page['url']) {
				$class = 'active';
				$active = true;
			}
			
			$return = self::a($page['url'], $page['title']);
			
			if (isset($page['children']) && count($page['children'])) {
				list($content, $descendant_active) = self::navigation($page['children'], $depth + 1);
				$return .= $content;
				if ($descendant_active) {
					$class = 'descendant-active';
					$active = true;
				}
			}
			
			$classes[] = $class;
			$elements[] = $return;
		}
			
		$return = self::ul($elements, false, $classes);
		if ($depth == 1) return $return;
		return array($return, $active); //have to pass the fact that there was a selected item up the chain
	}
	
	/**
	  * Special <ol> container.  
	  *
	  * @param	array	$elements	Each of these will be wrapped in a LI
	  * @param	mixed	$arguments	String for class, string prepended with # for id, or array for multiple arguments
	  * @param	array	$classes	One dimensional array of class names for each LI
	  * @return	string				HTML ol with li child elements
	  */
	static function ol($elements=false, $arguments=false, $classes=false) {
		if ($elements) {
			foreach ($elements as &$element) {
				$element = self::li($element, @array_shift($classes));
			}
		}
		return self::tag('ol', $arguments, implode($elements));
	}
	
	/**
	  *	Special HTML block to take care of charset headers, doctype and opening HTML tag
	  *
	  * @param	bool	$modernizr	Whether to use the modernizr construction
	  * @param	string	$manifest	The URL for an offline manifest
	  * @return	string				Block of HTML
	  */
	static function start($modernizr=false, $manifest=false) {
	
		//send headers
		if (!headers_sent()) header('Content-Type: text/html; charset=' . config::get('charset'));

		$lang = config::get('lang');
		
		$return = '<!DOCTYPE html>';
		
		if ($modernizr) {
			$return .= '
			<!--[if IEMobile 7 ]>' . self::tag('html', array('class'=>'no-js ie iem7', 'lang'=>$lang, 'manifest'=>$manifest), false, true) . '<![endif]-->
			<!--[if lt IE 7 ]>' . self::tag('html', array('class'=>'no-js ie ie6', 'lang'=>$lang, 'manifest'=>$manifest), false, true) . '<![endif]-->
			<!--[if IE 7 ]>' . self::tag('html', array('class'=>'no-js ie ie7', 'lang'=>$lang, 'manifest'=>$manifest), false, true) . '<![endif]-->
			<!--[if IE 8 ]>' . self::tag('html', array('class'=>'no-js ie ie8', 'lang'=>$lang, 'manifest'=>$manifest), false, true) . '<![endif]-->
			<!--[if IE 9 ]>' . self::tag('html', array('class'=>'no-js ie iem9', 'lang'=>$lang, 'manifest'=>$manifest), false, true) . '<![endif]-->
			<!--[if (gt IE 9)|(gt IEMobile 7)|!(IEMobile)|!(IE)]><!-->' . self::tag('html', array('class'=>'no-js', 'lang'=>$lang, 'manifest'=>$manifest), false, true) . '<!--<![endif]-->
			';
		} else {
			$return .= self::tag('html', array('lang'=>$lang, 'manifest'=>$manifest), false, true);
		}
		
		return $return;
	}
	
	/**
	  * Helper function to draw tags.
	  *
	  * @param	string	$tag		The name of the tag, eg p, ul, etc.
	  * @param	mixed	$arguments	String for class, string prepended with # for id, or array for multiple arguments
	  * @param	string	$content	The content contained inside the tag
	  * @param	boolean	$open		Whether the tag should be left open or not
	  * @return	string				The HTML tag
	  */
	private static function tag($tag, $arguments=false, $content='', $open=false) {
		$tag = strtolower(trim($tag));
		
		//start tag
		$return = '<' . $tag;

		//format arguments
		$arguments = self::arguments($arguments);
		foreach ($arguments as $key=>$value) {
			if ($value !== false) $return .= ' ' . strtolower(trim($key)) . '="' . htmlentities(trim($value)) . '"';
		}
		
		//close tag
		$return .= '>';
		if (!in_array($tag, array('br', 'hr', 'img', 'meta'))) {
			//is a container tag
			$return .= $content;
			if ($open === false) $return .= '</' . $tag . '>';
		}
		
		return $return;
	}

	/**
	  * Special <title> container.  Accepts no $arguments, and strips any tags out of the $content
	  *
	  * @param	string	$content	The content contained inside the title
	  * @return	string	<title>Example Page</title>
	  */
	static function title($content='') {
		return self::tag('title', false, strip_tags($content));
	}
	
	/**
	  * Special <ul> container.  
	  *
	  * @param	array	$elements	Each of these will be wrapped in a LI
	  * @param	mixed	$arguments	String for class, string prepended with # for id, or array for multiple arguments
	  * @param	array	$classes	One dimensional array of class names for each LI
	  * @return	string				HTML ul with li child elements
	  */
	static function ul($elements=false, $arguments=false, $classes=false) {
		if ($elements) {
			foreach ($elements as &$element) {
				$element = self::li($element, @array_shift($classes));
			}
		}
		return self::tag('ul', $arguments, implode($elements));
	}
}