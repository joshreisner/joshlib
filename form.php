<?php
//one big class for making forms
error_debug('including form.php', __file__, __line__);

class form { 
	var $name			= false;
	var $fields			= array();
	var $legend			= false;
	var $legend_prefix	= false; //for intranet
	var $id				= false;
	var $table			= false;
	var $values			= array();
	var $submit			= false;
	var $readonly		= false;
	var $focus			= false;
	var $counter		= 1;
	var $validate		= true; //validator turned on by default
	
	function __construct($name=false, $id=false, $submit=true, $cancel=false, $readonly=false) {
		global $_josh;
		
		//$name is the db table you're referencing.  good for putting up a quick form scaffolding
		//$id is the id column of the table -- you can add values to the form (say if you are editing)
		//$submit is a boolean, and indicates whether you should auto-add a submit button at the bottom
		//if you pass $submit as a string, it will legend use the text you passed, and legend the form that
		
		if (!$name) error_handle('form error', 'your form must have a $name.  eg ' . format_code('$f = new form(\'foo\');'), __file__, __line__);
		
		$this->name		= $name;
		$this->cancel	= $cancel;
		$this->id		= $id;
		$this->readonly	= $readonly;
		$this->action	= $_josh['request']['path_query'];
		
		if ($name) $this->set_table($name);
		if ($submit === true) {
			$this->legend = $this->submit = (($id) ? 'Edit ' : 'Add New ') . format_singular(format_text_human($name));
		} else {
			$this->legend = $this->submit = $submit;
		}
		if ($this->table && $id) $this->set_values(db_grab('SELECT * FROM ' . $this->table . ' WHERE id = ' . $id));
	}

	function draw($values=false, $focus=true) {
		global $_josh;
		
		if ($values) $this->set_values($values);
		
		//add submit?
		if ($this->submit) {
			$additional = false;
			
			//add cancel?
			if (isset($_GET['return_to'])) {
				if ($this->cancel) $additional = ($this->cancel === true) ? 'or ' . draw_link($_GET['return_to'], 'cancel') : $this->cancel;
				$this->set_field(array('type'=>'hidden', 'name'=>'return_to', 'value'=>$_GET['return_to']));
			} elseif (isset($_josh['referrer']['url'])) {
				if ($this->cancel) $additional = ($this->cancel === true) ? 'or ' . draw_link($_josh['referrer']['url'], 'cancel') : $this->cancel;
				$this->set_field(array('type'=>'hidden', 'name'=>'return_to', 'value'=>$_josh['referrer']['url']));
			}
			
			//add submit
			$this->set_field(array('type'=>'submit', 'value'=>$this->submit, 'additional'=>$additional));
		}

		//start output
		if (!$this->legend) $this->legend = ''; //legend is showing up <legend/>
		$return = draw_container('legend', draw_container('span', $this->legend_prefix . $this->legend));
		
		//send form name
		$this->set_hidden('form_id', $this->name);

		//add fields
		foreach ($this->fields as $field) $return .= $this->draw_row($field);

		//wrap in unnecessary fieldset
		$return = draw_div('fieldset', draw_tag('fieldset', false, $return));
		
		//wrap in form
		$return = draw_tag('form', array('method'=>'post', 'enctype'=>'multipart/form-data', 'accept-charset'=>'UTF-8', 'action'=>$this->action, 'id'=>$this->name), $return);
		
		//focus on first element
		if ($focus && !empty($this->focus)) $return .= draw_form_focus($this->focus);
		
		//run validator
		if ($this->validate) {
			$return .= lib_get('validate') . 
				draw_javascript_ready('
				$("form#' . $this->name . '").validate({
					highlight: function(element, errorClass, validClass) {
						$(element).addClass(errorClass).removeClass(validClass);
						$(element).closest("div.field").addClass(errorClass);
						//$(element.form).find("label[for=" + element.id + "]").addClass(errorClass);
					},
					unhighlight: function(element, errorClass, validClass) {
						$(element).removeClass(errorClass).addClass(validClass);
						$(element).closest("div.field").removeClass(errorClass);
						//$(element.form).find("label[for=" + element.id + "]").removeClass(errorClass);
					},
					submitHandler: function(form){ 
						if (typeof submit_' . $this->name . ' == "function") {
							if (submit_' . $this->name . '($("form#' . $this->name . '"))) form.submit();
						} else {
							form.submit();
						}
					}
				})');
		}

		return $return;
	}
	
	function draw_row($field) {
		extract($field);
		$return = '';

		if (($name == 'secret_key') || ($name == 'last_login') || ($type == 'image-alt') || ($type == 'file-type')) return false; //not fields you show in a form	

		//value is being set manually		
		if (!$value && isset($this->values[$name])) $value = $this->values[$name];
		
		//values has default (doesn't work for checkbox?)
		if (!$value && $default && ($type != 'checkbox')) $value = $default;
				
		//won't always need this
		if (!$options_table) $options_table = 'options_' . str_replace('_id', '', $name);
		if (!$option_title) $option_title = 'title';
		
		//wrap additional
		if ($additional && ($type != 'checkboxes')) $additional = draw_tag('span', array('class'=>'additional help-inline'), $additional);
		
		error_debug('<b>' . __function__ . '</b> drawing field ' . $name . ' of type ' . $type, __file__, __line__);
		
		if ($required) $class .= ' required';
		$class .= ' ' . $type;
		$class = trim($class);

		//draw the field
		if ($type == 'hidden') {
			$return .= draw_form_hidden($name, $value);
		} else {
			$args = array();
			if ($label && ($type != 'checkbox')) { //checkbox labels should go on right side
				if ($additional && (($type == 'checkboxes') || ($type == 'textarea'))) $label .= $additional;
				$return .= draw_tag('label', array('for'=>$name), $label);
			}
			switch ($type) {
				case 'checkbox':
					$return .= ($allow_changes) ? draw_form_checkbox($name, $value) . draw_tag('label', array('for'=>$name), $label) : format_boolean($value);
					break;
				case 'checkboxes':
					if (!$option_title) {
						if (empty($options_table)) error_handle('options table not set', 'please specify your options table', __file__, __line__);
						if ($options_columns = db_columns($options_table)) {
							$option_title = $options_columns[1]['name'];
						} else {
							error_handle('options_table does not exist', 'form checkboxes ' . $name . ' is looking for ' . $options_table . ' which does not exist', __file__, __line__);
						}
					}
					if (!$options) {
						if (!$sql) {
							if ($this->id) {
								if (empty($linking_table)) error_handle('linking_table not set', 'please specify your linking table for a form checkboxes field', __file__, __line__);
								$sql = 'SELECT o.id, o.' . $option_title . ', (SELECT COUNT(*) FROM ' . $linking_table . ' l WHERE l.' . $option_id . ' = o.id AND l.' . $object_id . ' = ' . $this->id . ') checked FROM ' . $options_table . ' o WHERE o.is_active = 1 ORDER BY o.' . $option_title;
							} else {
								$value = (strToLower($value) == 'all') ? 1 : 0; //questionable
								if ($default) $value = 1;
								$sql = 'SELECT id, ' . $option_title . ', ' . $value . ' checked FROM ' . $options_table . ' WHERE is_active = 1 ORDER BY ' . $option_title;
							}
						}
						$options = db_table($sql);					
					}
					foreach ($options as &$o) {
						if ($maxlength) $o[$option_title] = format_string($o[$option_title], $maxlength);
						$chkname = 'chk-' . $name . '-' . $o['id'];
						if (!isset($o['checked'])) $o['checked'] = false;
						$o = draw_form_checkbox($chkname, $o['checked']) . draw_form_label($chkname, $o[$option_title]);
					}
					$return .= draw_list($options, array('id'=>$options_table));
					break;
				case 'date':
					if ($allow_changes) {
						$return .= draw_form_date($name, $value, false, false, $required) . $additional;
					} else {
						$return .= format_date($value);
					}
					//return .= draw_form_date_cal($name, $value) . $additional;
					break;
				case 'datetime':
					if ($allow_changes) {
						$return .= draw_form_date($name, $value, true, false, $required) . $additional;
					} else {
						$return .= format_date_time($value);
					}
					break;
				case 'email':
					if ($allow_changes) {
						$return .= draw_form_text($name, $value, $class, $maxlength, false, false) . $additional;
					} else {
						$return .= draw_link('mailto:' . $value);
					}
					break;
				case 'file':
					$return .= draw_form_file($name, $class, $onchange) . $additional;
					//todo -- this is wonky -- presupposes it's a jpg
					if ($value && $preview) $return .= draw_img(file_dynamic($this->table, $name, $this->id, 'jpg'), false, array('maxwidth'=>545, 'class'=>'preview'));
					break;
				case 'html':
				case 'group':
					$return .= $value;
					break;
				case 'latlon':
				  $coors = explode(',', $value);
				  if(count($coors) == 3) list($lat, $lon, $zoom) = $coors;
				  if(empty($lat)) $lat = 0;
				  if(empty($lon)) $lon = 0;
          if(empty($zoom)) $zoom = 0;       
				  
				  $return .= lib_get('latlon-picker') . '<div class="gllpLatlonPicker" style="margin-left:110px;">
        		  <div class="input-prepend">
                <span class="add-on">Latitude</span><input class="span1 gllpLatitude" name="'.$name.'_lat" type="text" value="'.$lat.'">
                <span class="add-on">Longitude</span><input class="span1 gllpLongitude" name="'.$name.'_lon" type="text" value="'.$lon.'">
                <span class="add-on">Zoom</span><input class="span1 gllpZoom" name="'.$name.'_zoom" type="text" value="'.$zoom.'">
                <input type="button" class="gllpUpdateButton btn" value="update map">
              </div>
              
              <div class="gllpMap">Google Maps</div>
              
              <div class="input-append">
                <input class="span3 gllpSearchField" type="text"><button class="btn gllpSearchButton" type="button">Search Map</button>
              </div>         	
        	</div>';
				  break;
				case 'note':
					//todo deprecate
					$return .= '<div class="note">' . $additional . '</div>';
					break;
				case 'password':
					if (!$this->focus) $this->set_focus($name); //can accept insertion point
					$return .= draw_form_password($name, $value, $class, 255, false) . $additional;
					break;
				case 'radio':
					if (!$options) {
						if (!$sql) {
							if (!db_table_exists($options_table)) error_handle('Form Error', 'No options provided for radio field ' . $name . '.  Either pass $options, $sql or have content in ' . $options_table . '.' . $option_title, __file__, __line__);
							$sql = 'SELECT id, ' . $option_title . ' FROM ' . $options_table . ' WHERE is_active = 1 ORDER BY ' . $option_title;
						}
						$options = db_array($sql);
					}
					$return .= '<ul class="radio">';
					
					//todo what's append?
					if ($append) while (list($addkey, $addval) = each($append)) $options[$addkey] = $addval;
					
					foreach ($options as $id=>$description) {
						$return .= draw_li(draw_form_radio($name, $id, ($value == $id), $class, $description));
					}
					
					$return .= '</ul>';
					break;
				case 'select':
					if (!$options) {
						if (!$sql) $sql = 'SELECT id, ' . $option_title . ' FROM ' . $options_table . ' WHERE is_active = 1 ORDER BY ' . $option_title;
						$options = db_table($sql);
					}
					if ($append) while (list($addkey, $addval) = each($append)) $options[$addkey] = $addval;
					if ($null_value) $required = false;
					$return .= draw_form_select($name, $options, $value, $required, $class, $action, $null_value, 60, !$allow_changes) . $additional;
					if (!$allow_changes) $return .= draw_form_hidden($name, $value);
					break;
				case 'submit':
					if (substr($value, 0, 1) == '/') {
						//if the button text starts with a / then the implication is it's an image
						$return .= draw_form_img($value, $class) . $additional;						
					} else {
						//bootstrap adding btn class to all input type="submit" fields
						$return .= draw_form_submit($value, ((empty($class)) ? 'btn' : $class . ' btn')) . $additional;
					}
					break;
				case 'color':
				case 'int':
				case 'text':
				case 'url':
				case 'url-local':
					if (!$maxlength) $maxlength = 255;
					if ($allow_changes) {
						if (!$this->focus) $this->set_focus($name); //accepts insertion point
						$args = array('class'=>$type);
						if ($type == 'color') {
							//js color picker
							$return .= lib_get('jscolor'); 
							$args['class'] .= ' {hash:true}';
						}
						if ($required) $args['class'] .= ' required';
						if (!empty($default)) $args['placeholder'] = $default;
						$return .= draw_form_text($name, $value, $args, $maxlength, false, false) . $additional;
					} else {
						$return .= (($type == 'url') || ($type == 'url-local')) ? draw_link($value, $value) : $value;
					}
					break;
				case 'textarea':
				case 'textarea-plain':
					if ($allow_changes) {
						//accepts insertion point
						if (!$this->focus) $this->set_focus($name);
						$args['class'] = $class;
						if (stristr($class, 'tinymce')) $return .= lib_get('tinymce');
						if (!empty($default)) $args['placeholder'] = $default;
						$return .= draw_form_textarea($name, $value, $args);
					} else {
						$return .= $value;
					}
					break;
			}
						
			//wrap it up
			$div_class = implode(' ', array('field', 'control-group', 'field_' . $this->counter, $name, $class));
			$return = draw_div($div_class, $return) . NEWLINE;
			$this->counter++;
		}
		return $return;
	}
	
	function set_action($target) {
		$this->action = $target;
	}
	
	function set_defaults() {
		foreach ($this->fields as &$f) if (empty($f['default'])) $f['default'] = $f['label'];
	}
	
	function set_field($array) {
		//defaults
		$type = $value = $class = $default = $name = $label = $required = $append = $position = false;
		$sql = $action = $onchange = $additional = $maxlength = $options_table = $option_id = false;
		$option_title = $object_id = $options = $linking_table = $null_value = $preview = false;
		$allow_changes = true;
		
		//load inputs
		if (!is_array($array)) return error_handle('Array Error', 'Array not set', __file__, __line__);
		extract($array);

		//type is required
		if (!$type) return error_handle('Type Error', 'type not set', __file__, __line__);
		if ((($type == 'text') || ($type == 'password')) && !isset($array['additional']) && $required) $additional = '(required)';

		if (!$name)	$name	= format_text_code($label);
		if (($label === false) && ($type != 'submit')) {
			if ($label = format_text_ends('_id', $name)) {
			} else {
				$label = $name;
			}
			$label = format_text_human($label);
		}
		if (!$value) $value	= (isset($this->values[$name])) ? $this->values[$name] : false;
		if (!$class) $class	= '';
		if (!$option_id) $option_id	= 'option_id';
		if (!$object_id) $object_id	= 'object_id';
		
		//form is read-only
		if ($this->readonly) $allow_changes = false;
		
		error_debug('<b>' . __function__ . '</b> adding ' . $name . ' of type ' . $type, __file__, __line__);

		//package and save
		if ($position === false) {
			$this->fields[$name] = compact('name', 'type', 'label', 'value', 'default', 'append', 'required', 'allow_changes', 'sql', 'class', 'action', 'onchange', 'additional', 'options_table', 'option_id', 'option_title', 'object_id', 'options', 'linking_table', 'maxlength', 'null_value', 'preview');
		} else {
			if (isset($this->fields[$name])) unset($this->fields[$name]);
			$this->fields = array_insert_assoc($this->fields, $position, $name, compact('name', 'type', 'label', 'value', 'default', 'append', 'required', 'allow_changes', 'sql', 'class', 'action', 'onchange', 'additional', 'options_table', 'option_id', 'option_title', 'object_id', 'options', 'linking_table', 'maxlength', 'null_value', 'preview'));
		}
	}
	
	function set_field_class($name, $value='') {
		$this->set_field_property($name, 'class', $value);
	}
	
	function set_field_default($name, $value='') {
		$this->set_field_property($name, 'default', $value);
	}
	
	function set_field_label($name, $value='') {
		$this->set_field_property($name, 'label', $value);
	}
	
	function set_field_type($names, $value='') {
		$names = array_separated($names, ',');
		foreach ($names as $name) $this->set_field_property($name, 'type', $value);
	}
	
	function set_field_value($name, $value='') {
		$this->set_field_property($name, 'value', $value);
	}
	
	function set_field_labels($pairs) {
		foreach ($pairs as $name=>$value) $this->set_field_label($name, $value);
	}

	function set_field_property($name, $property, $value=false) {
		//generic field property setter
		if (isset($this->fields[$name])) $this->fields[$name][$property] = $value;
	}
	
	function set_focus($field) {
		$this->focus = $field;
	}
	
	function set_group($string='', $position=false) {
		$this->set_field(array('name'=>'group' . (($position) ? $position : count($this->fields)), 'type'=>'group', 'value'=>$string, 'position'=>$position, 'label'=>''));
	}
	
	function set_hidden($name, $value=false) {
		$this->set_field(array('name'=>$name, 'value'=>$value, 'type'=>'hidden'));
	}
	
	function set_legend($legend=false) {
		if ($legend) $this->legend = $legend;
	}
	
	function set_legend_prefix($prefix=false) {
		$this->legend_prefix = $prefix;
	}
	
	function set_order($strorder='') {
		$fields = array_separated($strorder);
		$return = array();
		foreach ($fields as $f) {
			if (isset($this->fields[$f])) {
				$return[$f] = $this->fields[$f];
				unset($this->fields[$f]);
			}
		}
		$this->fields = array_merge($return, $this->fields);
	}
	
	function set_submit($value) {
		$this->submit = $value;
	}
	
	function set_table($table) {
		$this->table = false;
		if ($cols = db_columns($table, true)) {		
			$this->table = $table;
			foreach ($cols as $c) {
				if ($c['type'] == 'varchar') {
					if ($c['name'] == 'password') {
						$this->set_field(array('type'=>'password', 'name'=>$c['name'], 'additional'=>$c['comments'], 'required'=>$c['required'], 'maxlength'=>$c['length']));
					} elseif ($c['name'] == 'secret_key') {
						//hide this field
					} else {
						$this->set_field(array('type'=>'text', 'name'=>$c['name'], 'additional'=>$c['comments'], 'required'=>$c['required'], 'maxlength'=>$c['length']));
					}
				} elseif ($c['type'] == 'text') {
					$this->set_field(array('type'=>'textarea', 'name'=>$c['name'], 'class'=>'tinymce'));
				} elseif (($c['type'] == 'bit') || ($c['type'] == 'tinyint')) {
					$this->set_field(array('type'=>'checkbox', 'name'=>$c['name']));
				} elseif ($c['type'] == 'date') {
					$this->set_field(array('type'=>'date', 'name'=>$c['name'], 'additional'=>$c['comments'], 'required'=>$c['required']));
				} elseif ($c['type'] == 'datetime') {
					$this->set_field(array('type'=>'datetime', 'name'=>$c['name'], 'additional'=>$c['comments'], 'required'=>$c['required']));
				} elseif (($c['type'] == 'image') || ($c['type'] == 'mediumblob') || ($c['type'] == 'longblob')) {
					$this->set_field(array('type'=>'file', 'name'=>$c['name'], 'additional'=>$c['comments']));
				} elseif ($c['type'] == 'int') {
					if ($c['name'] == 'precedence') {
						$this->set_field(array('type'=>'hidden', 'name'=>$c['name']));
					} else {
						$this->set_field(array('type'=>'text', 'name'=>$c['name'], 'class'=>'int', 'required'=>$c['required'], 'additional'=>$c['comments']));
					}
				}
			}
		}
	}
	
	function set_title($legend=false) {
		/*backwards compatibility*/
		if ($legend) $this->legend = $legend;
	}
	
	function set_title_prefix($prefix=false) {
		$this->legend_prefix = $prefix;
	}
	
	function set_validate($validate=true) {
		$this->validate = $validate;
	}
	
	function set_values($values=false) {
		//if you want to do a custom select and pass in the associative array
		if (!is_array($values)) return false;
		foreach ($values as $key=>$value) {
			$this->values[$key] =  $value;
		}
	}
	
	function unset_fields($fields) {
		if (!is_array($fields)) $fields = array_separated($fields);
		foreach ($fields as $f) unset($this->fields[$f]);
	}
}