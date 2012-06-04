## Welcome to Joshlib
https://github.com/joshreisner/joshlib

## License
all files other than lib.zip are available to the public under LGPL
	
## 3rd Party Software
	included in lib.zip.  thank you so much to each of the contributors for these excellent packages
	
	~~TITLE~~~~~~~~~~~~~~~~~LANG~~~~LICENSE~~~~~VERSION~DEVELOPER~URL~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
	> bootstrap				js/css				2.0.3	http://twitter.github.com/bootstrap
	> codepress				js		LGPL				http://sourceforge.net/projects/codepress/
	> dbug					php		
	> file_icons			png		-			-		-
	> fpdf					php							http://fpdf.org/
	> google-code-prettify	js/css	Apache 2			http://code.google.com/p/google-code-prettify/
	> innershiv				js		
	> jquery				js		MIT			1.7.1	http://jquery.com/
	> lorem_ipsum			js							http://develobert.blogspot.com/2007/11/automated-lorem-ipsum-generator.html
	> salesforce			php							http://developer.force.com/							
	> simple_html_dom		php		MIT					http://sourceforge.net/projects/simplehtmldom/
	> swfobject				js		MIT			2.2		http://code.google.com/p/swfobject/
	> swiftmailer			php		LGPL		4.0.6	http://swiftmailer.org/
	> tinymce				js		LGPL		3.3.8	http://tinymce.moxiecode.com/
	> uploadify				js		MIT			3.0.0	http://uploadify.com/

	JQUERY EXTENSIONS (in the jquery folder)
	> fancybox					CC ANC 3.0	2.0.4	http://fancyapps.com/fancybox/
	> jscrollpane				MIT/GPL				http://jscrollpane.kelvinluck.com/
	> validate					MIT/GPL				http://bassistance.de/jquery-plugins/jquery-plugin-validation/
	> table drag and drop		LGPL		0.5		http://www.isocra.com/2008/02/table-drag-and-drop-jquery-plugin/		

## Using the Debugger
	you can run the debug() function after joshlib has been included to see output of various processes
	to debug the loading of the joshlib itself, set $_josh['mode'] = 'debug' before you include it

## Running on the Command Line
	joshlib depends on certain $_SERVER variables being present.  add these lines before including joshlib:
	$_SERVER['HTTP_HOST']		= 'backend.livingcities.org';
	$_SERVER['SCRIPT_NAME']		= '/salesforce/index.php';
	$_SERVER['DOCUMENT_ROOT']	= '/home/livingcities/www/backend';
	
## Getting Started
	note that error messages will not be thrown unless you specify this as a dev server.
	use a .dev or .site TLD when developing, like www.yoursite.dev
	
