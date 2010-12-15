//joshlib javascript functions
//this file is generated automatically, only edit through joshlib SVN

function ajax_publish(which) {
	//requires jquery
	//which.name eg chk_news-items_12
	var action = which.name.split("_");
	$.ajax({
		url : url_action_add('ajax_publish', true),
		type : "POST",
		data : "table=" + action[1].replace(/-/g, "_") + "&id=" + action[2] + "&checked=" + which.checked,
		success : function(data) {
			//alert('hi' + data);
		}
	});
	return false;
}

function ajax_set(table, column, id, value, update) {
	//requires jquery
	if (!value) value = '';
	$.ajax({
		url : url_action_add('ajax_set', true),
		type : "POST",
		data : "table=" + table + "&column=" + column + "&id=" + id + "&value=" + value,
		success : function(data) { 
			//alert(data);
			if (update) update.html(data); 
		}
	});
}

function cookie_get(name) {
	var nameEQ = name + "=";
	var ca = document.cookie.split(';');
	for(var i=0;i < ca.length;i++) {
		var c = ca[i];
		while (c.charAt(0)==' ') c = c.substring(1,c.length);
		if (c.indexOf(nameEQ) == 0) return c.substring(nameEQ.length,c.length);
	}
	return null;
}

function cookie_set(name, value, days) {
	if (days) {
		var date = new Date();
		date.setTime(date.getTime() + (days * 24 * 60 * 60 * 1000));
		var expires = "; expires=" + date.toGMTString();
	} else {
		var expires = "";
	}
	document.cookie = name + "=" + value + expires + "; path=/";
}

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

function form_checkbox_toggle(which) {
	document.getElementById(which).checked = !document.getElementById(which).checked;
}

function form_checkbox_add(options_table, target) {
	//var which is the options_table
	var new_value = prompt("Please enter a name for the new checkbox", "");
	if (new_value) {
		//get checkbox states
		var checkboxes = document.getElementsByTagName("input");
		var needle = "chk_" + options_table;
		var checked = new Array();
		for (var i = 0; i < checkboxes.length; i++) {
			if ((checkboxes[i].name.substr(0, needle.length) == needle) && checkboxes[i].checked) checked.push(checkboxes[i].name);
		}
		
		//send request		
		new Ajax.Request(target, {
			method: 'post',
			parameters: { 'new_value':new_value, 'options_table':options_table, 'checked':checked.join(",") },
			onSuccess: function(transport) {
				document.getElementById(options_table).innerHTML = transport.responseText;
			}
		});
	}
}

function form_checkboxes_toggle(form, checked) {
	if (!object_exists(form)) {
		alert('form dne');
		return false;
	}
	for (var i = 0; i < form.elements.length; i++) {
		var chkParts = form.elements[i].name.split('_');
		if ((chkParts.length == 3) && (chkParts[0] == 'chk')) form.elements[i].checked = checked;
	}
}

function form_checkboxes_checked(form, filter) {
	//returns all the checkboxes checked in the form.  the name must be in the format chk_type_id, eg chk_topics_12
	returnArray = new Array();
	if (!object_exists(form)) {
		alert('form dne');
		return false;
	}
	for (var i = 0; i < form.elements.length; i++) {
		var chkParts = form.elements[i].name.split('_');
		if ((chkParts.length == 3) && (chkParts[0] == 'chk') && form.elements[i].checked) {
			if (!filter || (filter && (chkParts[1] == filter))) {
				returnArray[returnArray.length] = chkParts[2];
			}
		}
	}
	return returnArray;
}

function form_checkboxes_empty(form, name) {
	oneFound = false;
	for (var i = 0; i < form.elements.length; i++) {
		var checkParts = form.elements[i].name.split("-");
		if ((checkParts[0] == "chk") && (checkParts[1] == name) && (form.elements[i].checked)) oneFound = true;
	}
	return !oneFound;
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

function form_tinymce_clear(field_id) {
	tinyMCE.getInstanceById(field_id).getBody().innerHTML='';
}

function form_tinymce_init(cssLocation, showplugins) {
	var buttons = (showplugins) ? 
		"styleselect,|,bold,italic,underline,strikethrough,|,justifyleft,justifycenter,|,bullist,numlist,outdent,indent,|,link,unlink,insertimage,image,|,code" :
		"bold,italic,underline,strikethrough,|,justifyleft,justifycenter,blockquote,|,bullist,numlist,|,link,unlink,|,code";		

	tinyMCE.init({
		mode : "specific_textareas",
		editor_selector : "tinymce",
		theme : "advanced",
		theme_advanced_buttons1 : buttons,
		theme_advanced_buttons2 : "",
		theme_advanced_resizing : true,		
		theme_advanced_toolbar_location : "top",
		extended_valid_elements : "a[href|target|rel],img[class|src|border=0|alt|title|hspace|vspace|width|height|align|style],dir,hr[class|width|size|noshade],font[face|size|color|style],span[align|class],p[align|class],table[cellspacing,align,border,cellpadding,class],tr[class],td[width,align,class]",
		content_css : cssLocation + "?" + new Date().getTime(),
		plugins : "imagemanager,filemanager,paste",
		relative_urls : false,
		remove_script_host : false /*we need this again for lc backend */
	});
}

function form_field_email(obj) {
   var regExp = /^([A-Za-z0-9_\-\.])+\@([A-Za-z0-9_\-\.])+\.([A-Za-z]{2,4})$/;
   return regExp.test(obj.value);
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
	var pathParts   = str.split('\\');
	var fileParts   = pathParts[pathParts.length-1].split('.');
	var extension	= fileParts.pop();
	var filename	= fileParts.join(' ');
	
	if (form_text_empty(target)) target.value = format_title(filename);
}

function form_radio_empty(radio) {
	var oneFound = false;
	for (var i = 0; i < radio.length; i++) if (radio[i].checked) oneFound = true;
	return !oneFound;
}

function form_text_empty(obj) {
	return !obj.value.length;
}

function form_tinymce_empty(obj) {
	tinyMCE.triggerSave();
	return !obj.value.length;
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

function form_text_empty(obj) {
	var str = trim(obj.value);
	return !str.length;
}

function form_url_empty(obj) {
	var str = trim(obj.value);
	str = str.replace('http://', '').replace('https://', '');
	return !str.length;
}

function form_validate(form) {
	if (eval("typeof validate_" + form.name + " == 'function'")) {
		return eval("validate_" + form.name + "(form);");
	}
	return true;
}

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

function format_zeropad(num, len) {
	var str = num + '';
	while (str.length < len) str = "0" + str;
	return str;
}

function function_attach(fn, object, event) { 
	//eg function_attach(sideBarInit);
	if (!object) object = window;
	if (!event) event = 'load';
	if (object.addEventListener) { 
		object.addEventListener(event, fn, false); 
		return true; 
	} else if (object.attachEvent) { 
		var r = object.attachEvent("on" + event, fn); 
		return r; 
	} else { 
		return false; 
	} 
}

function function_run(name, argument) {
	if (typeof(argument) == 'string') argument = "'" + argument + "'";
	if (eval("typeof " + name + " == 'function'")) {
		eval(name + "(" + argument + ")");
	}
} 

function google_search(form) {
	//for psa
	location.href = 'http://www.google.com/search?q=' + url_encode('site:' + window.location.hostname + ' ' + form.q.value);
	return false;
}

function img_roll(what, how) { 
	eval("document." + what + ".src = " + what + "_" + how + ".src;"); 
}

function map_marker(latitude, longitude, html, icon, autoclick) {
	var point	= new GLatLng(latitude, longitude);
	var marker	= new GMarker(point, icon);
	GEvent.addListener(marker, 'click', function() {
		marker.openInfoWindowHtml(html);
	});
	if (autoclick) marker.openInfoWindowHtml(html);
	return marker;
}

function nl2br(str, reverse) {
	if (reverse) {
		return str.replace(/<br[\s\/]?>/gi, '\n');
	} else {
		return str.replace('\n', '<br/>');
	}
}

function object_exists(obj) {
	return (typeof(obj) == 'object');
}

function scroll_auto() {
	scroll_direction('right', true);
}

function scroll_init(which, count, width, hashes, auto_interval) {
	scroll_element		= document.getElementById(which);
	scroll_current		= 1;
	scroll_count		= count;
	scroll_width		= width;
	scroll_initialized	= true;
	scrolling			= false;
	leave_hashes		= (typeof(hashes) == 'undefined');
	interval = false;
	
	//set inner element width, if it's a UL
	var uls = scroll_element.getElementsByTagName('ul');
	if (uls.length) uls[0].style.width = scroll_width * scroll_count + 'px';
	
	if (leave_hashes && window.location.hash) {
		if (parseInt(window.location.hash.substr(1)) <= scroll_count) {
			scroll_to(window.location.hash.substr(1));
		} else {
			window.location.hash = "";
		}
	}
	
	if (auto_interval) {
		interval = setInterval("scroll_auto()", auto_interval);
	}
}

function scroll_direction(direction, dont_clear_interval) {
	if (!scroll_initialized || scrolling) return false;
	if (direction == "left") {
		newPallet		= (scroll_current == 1) ? scroll_count : scroll_current - 1;
	} else {
		newPallet		= (scroll_current == scroll_count) ? 1 : (scroll_current - 0) + 1;
	}
	scroll_to(newPallet, dont_clear_interval);
}

function scroll_horizontally() {
	if (scrollvars.time > scrollvars.duration) {
		clearInterval(scrollvars.timer);
		scrollvars.timer = null;
		if (leave_hashes) window.location.hash = scroll_current;
		if (typeof(scrollFinished) == "function") scrollFinished(scroll_current);
		scrolling = false;
	} else {
		scrollvars.element.scrollLeft = -scrollvars.change / 2 * (Math.cos(Math.PI * scrollvars.time / scrollvars.duration) - 1) + scrollvars.begin;
		scrollvars.time++;
	}
}

function scroll_to(newPallet, dont_clear_interval) {
	if (!scroll_initialized || scrolling) return false;
	if (interval && !dont_clear_interval) clearInterval(interval);
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

function table_dnd(name, column, handle) {
	//jquery and tablednd are required
	$("#" + name).tableDnD({
        onDrop: function(table, row) {
	        $.ajax({
				type: "POST",
				data: "table=" + name + "&column=" + column + "&" + $("#" + name).tableDnDSerialize(),
				url: url_action_add('ajax_reorder', true)
			});
			var thisclass = "odd";
			$("#" + name + " tr").each(function(){
				if ($(this).hasClass("group")) {
					thisclass = "odd";
				} else {
					$(this).removeClass("odd even").addClass(thisclass);
					thisclass = (thisclass == "odd") ? "even" : "odd";
				}
			});
        },
		onDragClass: "dragclass",
        dragHandle: handle
    });
}

function trim(str) {
	var l = 0;
	var r = str.length -1;
	while (l < str.length && str[l] == ' ') l++;
	while (r > l && str[r] == ' ') r -= 1;
	return str.substring(l, r+1);
}

function url_action_add(value, returnval) {
	return url_query_set('action', value, returnval);
}

function url_encode(str) {
	return escape(str).replace(/\+/g,'%2B').replace(/%20/g, '+').replace(/\*/g, '%2A').replace(/\//g, '%2F').replace(/@/g, '%40');
}

function url_id() {
	var id = url_query("id");
	if (id) return id;
	var urlparts = window.location.toString().split("/");
	return urlparts[urlparts.length-1];
	//return url_query("id");
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

function url_query_set(key, value, returnval) {
	//sets a query string value.  leaves other query elements alone
	var query	= window.location.search.substring(1);

	var pairs	= query.split("&");
	var found	= false;
	var ret		= Array();
	for (var i = 0; i < pairs.length; i++) {
		var pair = pairs[i].split("=");
		if (pair[0] == key) {
			found = true;
			if (value) ret.push(pair[0] + "=" + encodeURIComponent(value));
		} else if (pair[0]) {
			ret.push(pair[0] + "=" + encodeURIComponent(pair[1]));
		}
    }
    if (!found) ret.push(key + "=" + encodeURIComponent(value));
    ret.sort();
    
    var target = location.href;
   	if (location.hash) {
   		target = target.substring(0, target.length - location.hash.length);
   	} else if (target.substring(target.length-1) == "#") {
   		target = target.substring(0, target.length-1);
   	}
   	if (ret.length) {
	    target = (query) ? target.replace(query, ret.join("&")) : target + '?' + ret.join("&");
   	} else {
   		//clear query
   		target = target.replace("?" + query, "");
   	}
    if (location.hash) target += location.hash;
    
    //alert(target);
	//return;
	
    if (returnval) return target;
	location.href = target;
}
	
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