<?php
/*
this whole file is just the form class
*/
error_debug('including form.php', __file__, __line__);

class form { 
	var $name			= false;
	var $fields			= array();
	var $title			= false;
	var $title_prefix	= false; //for intranet
	var $id				= false;
	var $table			= false;
	var $values			= array();
	var $submit			= false;
	
	function __construct($name, $id=false, $submit=true, $cancel=false) {
		//$table is the db table you're referencing.  good for putting up a quick form scaffolding
		//$id is the id column of the table -- you can add values to the form (say if you are editing)
		//$submit is a boolean, and indicates whether you should auto-add a submit button at the bottom
		//if you pass $submit as a string, it will title use the text you passed, and title the form that
		
		$this->name		= $name;
		$this->submit	= $submit;
		$this->cancel	= $cancel;
		$this->id		= $id;
		if ($name) $this->set_table($name);
		if ($submit === true) {
			$this->title = (($id) ? 'Edit ' : 'Add New ') . format_singular(format_text_human($name));
		} else {
			$this->title = $submit;
		}
		if ($this->table && $id) $this->set_values(db_grab('SELECT * FROM ' . $this->table . ' WHERE id = ' . $id));
	}

	function draw($values=false, $focus=true) {
		global $_josh, $_GET;
		
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
			$this->set_field(array('type'=>'submit', 'value'=>strip_tags($this->title), 'additional'=>$additional));
		}
				
		//start output
		if (!$this->title) $this->title = ''; //legend is showing up <legend/>
		$return = draw_container('legend', draw_container('span', $this->title_prefix . $this->title));

		//add fields
		foreach ($this->fields as $field) $return .= $this->draw_row($field);

		//wrap in unnecessary fieldset
		$return = draw_div_class('fieldset', draw_tag('fieldset', false, $return));
		
		//wrap in form
		$return = draw_tag('form', array('method'=>'post', 'enctype'=>'multipart/form-data', 'accept-charset'=>'UTF-8', 'action'=>$_josh['request']['path_query'], 'name'=>$this->name, 'class'=>$this->name, 'onsubmit'=>'javascript:return form_validate(this);'), $return);
		
		//focus on first element
		reset($this->fields);
		if ($focus && $this->fields[key($this->fields)]['name']) $return .= draw_form_focus($this->fields[key($this->fields)]['name']);

		return $return;
	}
	
	function draw_row($field) {
		global $_josh;
		extract($field);
		$return = '';

		//value is being set manually		
		if (!$value && isset($this->values[$name])) $value = $this->values[$name];
		
		//values has default
		if (!$value && $default) $value = $default;
		
		//this is a bad idea
		//if (!$value && !$required && !$additional) $additional = 'optional';
		
		//wrap additional
		if ($additional) $additional = draw_tag('span', array('class'=>'additional'), $additional);
		
		//draw the field
		if ($type == 'hidden') {
			$return .= draw_form_hidden($name, $value);
		} else {
			if ($label) {
				if ($additional && (($type == 'checkboxes') || ($type == 'textarea'))) $label .= $additional;
				$return .= draw_tag('label', array('for'=>$name), $label);
			}
			switch ($type) {
				case 'checkbox':	
					$return .= draw_div_class('checkbox_option', draw_form_checkbox($name, $value) . '<span class="option_name" onclick="javascript:form_checkbox_toggle(\'' . $name . '\');">' . $additional . '</span>');
					break;
				case 'checkboxes':
					if (!$option_title) {
						//$option_title = 'title';
						if ($options_columns = db_columns($options_table)) {
							$option_title = $options_columns[1]['name'];
						} else {
							error_handle('options_table does not exist', 'form checkboxes ' . $name . ' is looking for ' . $options_table . ' which does not exist');
						}
					}
					if ($this->id) {
						$options = db_table('SELECT o.id, o.' . $option_title . ', (SELECT COUNT(*) FROM ' . $linking_table . ' l WHERE l.' . $option_id . ' = o.id AND l.' . $object_id . ' = ' . $this->id . ') checked FROM ' . $options_table . ' o WHERE o.is_active = 1 ORDER BY o.' . $option_title);
					} else {
						$value = (strToLower($value) == 'all') ? 1 : 0;
						$options = db_table('SELECT id, ' . $option_title . ', ' . $value . ' checked FROM ' . $options_table . ' WHERE is_active = 1 ORDER BY ' . $option_title);
					}
					foreach ($options as &$o) {
						$chkname = 'chk-' . $name . '-' . $o['id'];
						$o = draw_form_checkbox($chkname, $o['checked']) . '<span class="option_name" onclick="javascript:form_checkbox_toggle(\'' . $chkname . '\');">' . $o[$option_title] . '</span>';
					}
					if ($allow_changes) $options[] = '<a class="option_add" href="javascript:form_checkbox_add(\'' . $options_table . '\', \'' . $allow_changes . '\');">add new</a>';
					$return .= draw_list($options, array('id'=>$options_table));
					break;
				case 'date':
					//$return .= draw_form_date($name, $value, false, false, $required) . $additional;
					$return .= draw_form_date_cal($name, $value) . $additional;
					break;
				case 'datetime':
					$return .= draw_form_date($name, $value, true, false, $required) . $additional;
					break;
				case 'file':
					$return .= draw_form_file($name, $class, $onchange) . $additional;
					//todo -- this is wonky -- presupposes it's a jpg
					if ($value) $return .= draw_img(file_dynamic($this->table, $name, $this->id, 'jpg'));
					break;
				case 'group':
					$return .= $value;
					break;
				case 'note':
					$return .= '<div class="note">' . $additional . '</div>';
					break;
				case 'password':
					$return .= draw_form_password($name, $value, $class, 255, false) . $additional;
					break;
				case 'radio':
					if (!$options) {
						if (!$sql) $sql = 'SELECT id, name FROM options_' . str_replace('_id', '', $name);
						$options = db_array($sql);
					}
					$return .= '<div class="radio">';
					if ($append) while (list($addkey, $addval) = each($append)) $options[$addkey] = $addval;
					foreach ($options as $id=>$description) {
						$return .= '<div class="radio_option">' . draw_form_radio($name, $id, ($value == $id), $class) . $description . '</div>';
					}
					$return .= '</div>';
					break;
				case 'readonly':
					$return .= $value . ' ' . $additional;
					break;
				case 'select':
					if (!$options) {
						if (!$sql) $sql = 'SELECT id, name FROM options_' . str_replace('_id', '', $name);
						$options = (stristr($sql, 'optgroup')) ? db_table($sql) : db_array($sql);
					}
					if ($append) while (list($addkey, $addval) = each($append)) $options[$addkey] = $addval;
					$return .= draw_form_select($name, $options, $value, $required, $class, $action) . $additional;
					break;
				case 'submit':
					$return .= draw_form_submit($value, $class) . $additional;
					break;
				case 'text':
					$return .= draw_form_text($name, $value, $class, $maxlength, false, false) . $additional;
					break;
				case 'textarea':
					$return .= draw_form_textarea($name, $value, $class) . $additional;
					break;
			}
						
			//wrap it up
			$return = draw_div_class('field ' . $type . ' ' . $name . (($class) ? ' ' . $class : ''), $return);
		}
		return $return;
	}
	
	function set_field($array) {
		//defaults
		$type = $value = $class = $default = $name = $label = $required = $append = $position = $allow_changes = $sql = $action = $onchange = $additional = $maxlength = $options_table = $option_id = $option_title = $object_id = $options = $linking_table = false;
		
		//load inputs
		if (!is_array($array)) return error_handle('array not set');
		extract($array);

		//type is required
		if (!$type) return error_handle('type not set');

		if ((($type == 'text') || ($type == 'password')) && !isset($array['additional']) && $required) $additional = '(required)';

		error_debug('adding field ' . $label, __file__, __line__);

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
		
		if ($type == 'checkbox') {
			$additional = $label;
			$label = '&nbsp;';
		}

		//package and save
		if ($position === false) {
			$this->fields[$name] = compact('name', 'type', 'label', 'value', 'default', 'append', 'required', 'allow_changes', 'sql', 'class', 'action', 'onchange', 'additional', 'options_table', 'option_id', 'option_title', 'object_id', 'options', 'linking_table', 'maxlength');
		} else {
			$this->fields = array_insert_assoc($this->fields, $position, $name, compact('name', 'type', 'label', 'value', 'default', 'append', 'required', 'allow_changes', 'sql', 'class', 'action', 'onchange', 'additional', 'options_table', 'option_id', 'option_title', 'object_id', 'options', 'linking_table', 'maxlength'));
		}
	}
	
	function set_field_label($name, $label='') {
		if (isset($this->fields[$name])) $this->fields[$name]['label'] = $label;
	}
	
	function set_field_labels($pairs) {
		foreach ($pairs as $name=>$label) $this->set_field_label($name, $label);
	}
	
	function set_group($string='', $position=false) {
		$this->set_field(array('name'=>'group' . (($position) ? $position : count($this->fields)), 'type'=>'group', 'value'=>$string, 'position'=>$position, 'label'=>''));
	}
	
	function set_order($strorder='') {
		$fields = array_post_fields($strorder);
		$return = array();
		foreach ($fields as $f) {
			if (isset($this->fields[$f])) {
				$return[$f] = $this->fields[$f];
				unset($this->fields[$f]);
			}
		}
		$this->fields = array_merge($return, $this->fields);
	}
	
	function set_table($table) {
		$this->table = false;
		if ($cols = db_columns($table, true)) {
		
			//preload foreign keys
			$foreign_keys = array();
			if ($keys = db_keys_from($table)) foreach ($keys as $key) $foreign_keys[$key['name']] = $key;

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
					$this->set_field(array('type'=>'textarea', 'name'=>$c['name'], 'class'=>'mceEditor'));
				} elseif (($c['type'] == 'bit') || ($c['type'] == 'tinyint')) {
					$this->set_field(array('type'=>'checkbox', 'name'=>$c['name']));
				} elseif ($c['type'] == 'date') {
					$this->set_field(array('type'=>'date', 'name'=>$c['name'], 'additional'=>$c['comments'], 'required'=>$c['required']));
				} elseif ($c['type'] == 'datetime') {
					$this->set_field(array('type'=>'datetime', 'name'=>$c['name'], 'additional'=>$c['comments'], 'required'=>$c['required']));
				} elseif (($c['type'] == 'image') || ($c['type'] == 'mediumblob')) {
					$this->set_field(array('type'=>'file', 'name'=>$c['name'], 'additional'=>$c['comments']));
				} elseif ($c['type'] == 'int') {
					if (isset($foreign_keys[$c['name']])) {
						$this->set_field(array('type'=>'select', 'name'=>$key['name'], 'label'=>$key['label'], 'sql'=>'SELECT * FROM ' . $key['ref_table'], 'additional'=>$c['comments'], 'required'=>$c['required']));
					} elseif ($c['name'] != 'precedence') {
						$this->set_field(array('type'=>'hidden', 'name'=>$c['name']));
					}
				}
			}
			
			//load checkboxes tables
			if ($keys = db_keys_to($table)) {
				foreach ($keys as $key) {
					if (isset($key['ref_table'])) {
						$this->set_field(array(
							'type'=>'checkboxes', 
							'value'=>$this->id, //this doesn't feel like the right place to set this
							'name'=>$key['ref_table'], //todo make this unnecessary: make it the linking table instead of the options table
							'label'=>$key['constraint_name'],
							//'option_title'=>'name',
							//"additional"=>$additional, 
							//"allow_changes"=>$allow_changes, 
							'options_table'=>$key['ref_table'], 
							'linking_table'=>$key['table_name'], 
							'option_id'=>$key['name'], 
							'object_id'=>$key['column_name']
							)
						);
					}
				}
			}
		}
		
	}
	
	function set_title($title=false) {
		//title should be a string, and indicates you want a title dd at the top of your form
		//if you don't pass a $title, there had better be a title already set via the constructor
		if ($title) $this->title = $title;
		array_unshift($this->fields, array('type'=>'title', 'name'=>'','class'=>'title', 'value'=>$this->title, 'label'=>false));
	}
	
	function set_title_prefix($prefix=false) {
		$this->title_prefix = $prefix;
	}
	
	function set_values($values=false) {
		//if you want to do a custom select and pass in the associative array
		if (!is_array($values)) return false;
		foreach ($values as $key=>$value) {
			$this->values[$key] =  $value;
		}
	}
	
	function unset_fields($fields) {
		if (!is_array($fields)) $fields = array_post_fields($fields);
		foreach ($fields as $f) unset($this->fields[$f]);
	}
}
?>