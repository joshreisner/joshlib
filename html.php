<?php
error_debug('including html.php', __file__, __line__);

class HTML {
	private static $instance;
	
	private function __construct() {

	}
	
	public static function getInstance() {
		if (is_null(self::$instance)) self::$instance = new self();
		return self::$instance;
	}
	
	public function div($content=false, $class_args=false) {
		return tag('div', $content, $class_args);
	}
	
	private function tag($tag, $content=false, $class=false) {
	
	}
}

/*
$html->p('title',
	'Some text' . $html->strong(false, 'Michael')
);
*/