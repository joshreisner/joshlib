/*	joshlib js functions

	this file is generated automatically -- only edit through joshlib SVN
	
	contains the following sections
	
	css
	form
	format
	img
	map
	scroll
	url
	window
*/

/* css */
function css_add(object, what) {
	if ((object == null) || (typeof(object) != "object")) return false;
	if (!css_check(object, what)) object.className += (object.className) ? " " + what : what;
}

function css_check(object, what) {
	return new RegExp('\\b' + what + '\\b').test(object.className)
}

function css_objects(searchClass, node, tag) {
	//adapted from http://www.dustindiaz.com/getelementsbyclass/
	var classElements = new Array();
	if (node == null) node = document;
	if (tag  == null) tag = '*';
	var els = node.getElementsByTagName(tag);
	var elsLen = els.length;
	var pattern = new RegExp("(^|\\s)"+searchClass+"(\\s|$)");
	for (i = 0, j = 0; i < elsLen; i++) {
		if ( pattern.test(els[i].className) ) {
			classElements[j] = els[i];
			j++;
		}
	}
	return classElements;
}

function css_remove(object, what) {
	var str = object.className.match(" " + what) ? " " + what : what;
	object.className = object.className.replace(str, "");
}

function css_set(object, what) {
	//overwrite existing values, set as what
	alert('hi');
	if (typeof(object) != "object") object = getElementById(object);
	object.className = what;
}


/* form */
function form_checkbox_toggle(which) {
	document.getElementById(which).checked = !document.getElementById(which).checked;
}

function form_errors(errors) {
	var error;
	if (errors.length == 0) return true;
	if (errors.length == 1) {
		error = "This form could not go through because " + errors[0] + ".  Please fix this before continuing.";
	} else {
		var numbers = new Array('two','three','four','five','six','seven','eight','nine');
		var errornumber = (errors.length < 10) ? numbers[errors.length-2] : errors.length;
		error = "This form could not go through because of the following " + errornumber + " errors:\n\n";
		for (var i = 0; i < errors.length; i++) {
			error += " - " + errors[i] + "\n";
		}
		error += "\nPlease fix before continuing.";
	}
	
	alert(error);
	return false;
}

function form_tinymce_init(cssLocation, imageManager) {
	if (imageManager) {
		tinyMCE.init({
			mode : "textareas",
			theme : "advanced",
			skin : "default",
			theme_advanced_buttons1 : "bold,italic,underline,strikethrough,separator,justifyleft,justifycenter,justifyright,justifyfull,bullist,numlist,separator,undo,redo,separator,link,unlink,image,|,code",
			theme_advanced_buttons2 : "",
			theme_advanced_buttons3 : "",
			theme_advanced_resizing : true,		
			theme_advanced_statusbar_location : "bottom",
			theme_advanced_toolbar_location : "top",
			theme_advanced_toolbar_align : "center",
			extended_valid_elements : "a[href|target|rel],img[class|src|border=0|alt|title|hspace|vspace|width|height|align|onmouseover|onmouseout|name],hr[class|width|size|noshade],font[face|size|color|style],span[align|style],p[align]",
			content_css : cssLocation + "?" + new Date().getTime(),
			plugins : "imagemanager",
			editor_selector : "mceEditor",
			editor_deselector : "mceNoEditor"
		});
	} else {
		tinyMCE.init({
			mode : "textareas",
			theme : "advanced",
			skin : "default",
			theme_advanced_buttons1 : "bold,italic,underline,strikethrough,separator,justifyleft,justifycenter,justifyright,justifyfull,bullist,numlist,separator,undo,redo,separator,link,unlink,image,|,code",
			theme_advanced_buttons2 : "",
			theme_advanced_buttons3 : "",
			theme_advanced_resizing : true,		
			theme_advanced_statusbar_location : "bottom",
			theme_advanced_toolbar_location : "top",
			theme_advanced_toolbar_align : "center",
			extended_valid_elements : "a[href|target|rel],img[class|src|border=0|alt|title|hspace|vspace|width|height|align|onmouseover|onmouseout|name],hr[class|width|size|noshade],font[face|size|color|style],span[align|style],p[align]",
			content_css : cssLocation + "?" + new Date().getTime(),
			editor_selector : "mceEditor",
			editor_deselector : "mceNoEditor"
		});
	}
}

function form_checkboxes_empty(form, name) {
	oneFound = false;
	for (var i = 0; i < form.elements.length; i++) {
		var checkParts = form.elements[i].name.split("_");
		if ((checkParts[0] == "chk") && (checkParts[1] == name) && (form.elements[i].checked)) oneFound = true;
	}
	return !oneFound;
}

function form_field_default(which, clear, str) {
	if (clear && (which.value == str)) {
		which.value = "";
	} else if (!clear && (which.value == "")) {
		which.value = str;
	}
}

function form_file_suggest(str, target) {
	//var types = new Array('xls','gif','jpg','nrl','pdf','png','ppt','pub','doc');
	var fileParts   = str.split('.');
	var extension	= fileParts.pop();
	var filename	= fileParts.join(' ');

	if (form_text_empty(target)) target.value = format_title(filename);
}

function form_radio_empty(radio) {
	var oneFound = false;
	for (var i = 0; i < radio.length; i++) {
		if (radio[i].checked) oneFound=true;
	}
	return !oneFound;
}

function form_text_empty(text) {
	return !text.value.length;
}

function form_text_complex(text) {
	var complex = true;
	if (text == text.toLowerCase()) complex = false;	//can't be all lowercase
	if (text == text.toUpperCase()) complex = false;	//can't be all uppercase either
	if (text.length < 8) complex = false;				//must be 8 chars
	var specialchars = Array("!", "@", "#", "$", "%", "^", "&", "*", "(", ")", "_", "-", "+", "=");
	var onefound = false;
	for (var i = 0; i < specialchars.length; i++) if (text.indexOf(specialchars[i]) != -1) onefound = true;
	if (!onefound) complex = false;
	return complex;
}

/* format */
function format_title(string) {
	string = string.replace(/_/g, " ");
	string = string.replace(/-/g, " ");
	
	var words = string.split(" ");
	var lower = new Array('a', 'an', 'and', 'at', 'by', 'for', 'from', 'if', 'in', 'is', 'of', 'the', 'to');
	for (i = 0; i < words.length; i++) {
		if (i == 0) {
			//always capitalize the first word
			words[i] = (words[i].substring(0,1)).toUpperCase() + words[i].substring(1);
		} else if (lower.indexOf(words[i]) < 0) { 
		  	words[i] = (words[i].substring(0,1)).toUpperCase() + words[i].substring(1);
		}
	}
	return words.join(" ");
}


/*img*/
function img_roll(what, how) { 
	eval("document." + what + ".src = " + what + "_" + how + ".src;"); 
}


/* map */
function map_marker(latitude, longitude, html, icon) {
	var point	= new GLatLng(latitude, longitude);
	var marker	= new GMarker(point, icon);
	GEvent.addListener(marker, 'click', function() {
		marker.openInfoWindowHtml(html);
	});
	return marker;
}


/* scroll*/
function scroll_init(which, count, width) {
	scroll_element		= document.getElementById(which);
	scroll_current		= 1;
	scroll_count		= count;
	scroll_width		= width;
	scroll_initialized	= true;
	scrolling			= false;
	if (window.location.hash) {
		if (parseInt(window.location.hash.substr(1)) <= scroll_count) {
			scroll_to(window.location.hash.substr(1));
		} else {
			window.location.hash = "";
		}
	}
}

function scroll_direction(direction) {
	if (!scroll_initialized || scrolling) return false;
	if (direction == "left") {
		newPallet		= (scroll_current == 1) ? scroll_count : scroll_current - 1;
	} else {
		newPallet		= (scroll_current == scroll_count) ? 1 : scroll_current + 1;
	}
	scroll_to(newPallet);
}

function scroll_horizontally() {
	if (scrollvars.time > scrollvars.duration) {
		clearInterval(scrollvars.timer);
		scrollvars.timer = null;
		window.location.hash = scroll_current;
		if (typeof(scrollFinished) == "function") scrollFinished(scroll_current);
		scrolling = false;
	} else {
		scrollvars.element.scrollLeft = -scrollvars.change / 2 * (Math.cos(Math.PI * scrollvars.time / scrollvars.duration) - 1) + scrollvars.begin;
		scrollvars.time++;
	}
}

function scroll_to(newPallet) {
	if (!scroll_initialized || scrolling) return false;
	scrolling = true;
	if (typeof(scrollStarted) == "function") scrollStarted(newPallet);
	currentLocation		= ((scroll_current - 1) * scroll_width);
	scroll_current		= newPallet;
	newLocation			= ((newPallet - 1) * scroll_width);
	scrollvars			= new Object();
	scrollvars.time		= 0;
	scrollvars.begin	= currentLocation;
	scrollvars.change	= newLocation - currentLocation;
	scrollvars.duration	= 25;
	scrollvars.element	= scroll_element;
	scrollvars.timer	= setInterval("scroll_horizontally();", 15);
}


/* url */
function url_id() {
	return url_query("id");
}

function url_prompt(url, question) {
	if (question) {
		if (confirm(question)) location.href = url;
	} else {
		return false;
	}
}

function url_query(key) {
	pairs = window.location.search.substring(1).split('&');
	for (var i = 0; i < pairs.length; i++) {
		pair = pairs[i].split("=");
		if (pair[0] == key) return pair[1];
	}
	return false;
}

function url_query_set(key, value) {
	var query = window.location.search.substring(1);
	var pairs = query.split("&");
	var found = false;
	for (var i = 0; i < pairs.length; i++) {
		var pair = pairs[i].split("=");
		if (pair[0] == key) {
			pairs[i] = pair[0] + "=" + encodeURIComponent(value);
			found = true;
		}
    } 
    if (!found) pairs[i] = key + "=" + encodeURIComponent(value);
    if (query) {
	    location.href = location.href.replace(query, pairs.join("&"));
    } else {
	    location.href = location.href + '?' + pairs.join("&");
    }
}
	

/* window */
function window_scroll_top() {
	if (navigator.platform != "Win32") {
		scrollTo(0,0);
	} else {
		var ts = 0;
		
		if (document.layers) {
			ts = window.pageYOffset;
		} else if (document.body.scrollTop) {
			ts = document.body.scrollTop;
		} else {
			ts = window.pageYOffset;
		}
		
		if (ts > 0) {
			var nv = (ts -5) * 0.2;
			if (nv < 1) nv = 1;
			scrollBy(0, -nv);
			setTimeout("window_scroll_top()", 20);
		}
	}
}