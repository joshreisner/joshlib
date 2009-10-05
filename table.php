<?php
/*
table class
*/
error_debug('including table.php', __file__, __line__);

class table {
	var $columns	= array();
	var $draggable	= false;
	var $name		= false;
	var $title		= false;

	public function __construct($name='table', $title=false) {
		$this->name = format_text_code($name);
		$this->title = $title;
	}
	
	function draw($values, $errmsg='Sorry, no results!', $total=false) {
		global $_josh;
		$class			= $this->name;
		$count_columns	= count($this->columns);
		$count_rows		= count($values);
		$counter		= 1; //to determine first_row and last_row
		$totals			= array(); //var to hold totals, if applicable
		$return			= ''; //hold the output
		if (!$count_columns) {
			//there were no columns defined.  no columns, no table
			$return .= $this->draw_header(false) . $this->draw_empty('Sorry, no columns defined!');
		} elseif (!$count_rows) {
			//no rows, return errmsg
			$return .= $this->draw_header(false) . $this->draw_empty($errmsg);
		} else {
			$row	= 'odd';
			$group	= '';
			$return .= $this->draw_header() . '<tbody id="' . $this->name . '">';
			
			foreach ($values as $v) {
				if (isset($v['group']) && ($group != $v['group'])) {
					$return .= draw_tag('tr', false, draw_tag('td', array('colspan'=>$count_columns, 'class'=>'group'), $v['group']));
					$row = 'odd'; //reset even/odd at the beginning of groups
					$group = $v['group'];
				}
				if ($total) {
					//must be array
					foreach ($total as $t) {
						if (isset($v[$t])) {
							if (!isset($totals[$t])) $totals[$t] = 0;
							$totals[$t] += format_numeric($v[$t]);
						}
					}
				}
				
				//row and arguments
				$return .= '<tr';
				if (isset($v['id'])) $return .= ' id="item_' . $v['id'] . '"';				
				$return .= ' class="' . $row;
				if (isset($v['class']) && !empty($v['class'])) $return .= ' ' . $v['class'] . ' ' . $row . '_' . $v['class'];
				if ($counter == 1) $return .= ' first_row';
				if ($counter == $count_rows) $return .= ' last_row';
				$return .= '"';
				if (isset($v['link'])) $return .= ' onclick="location.href=\'' . $v['link'] . '\';"';
				$return .= '>' . $_josh['newline'];
				
				foreach ($this->columns as $c) $return .= draw_tag('td', array('class'=>$c['name'] . ' ' . $c['class']), $v[$c['name']]);
				
				$return .= '</tr>' . $_josh['newline'];
				
				$row = ($row == 'even') ? 'odd' : 'even';
			}
			$return .= '</tbody>';
			$counter++;
		}
		if ($total) {
			$return .= '<tfoot><tr class="total">';
			foreach ($this->columns as $c) {
				$return .= '<td class="' . $c['name'];
				if ($c['class']) $return .= ' ' . $c['class'];
				$return .= '">';
				if (isset($totals[$c['name']])) {
					$return .= $totals[$c['name']];
				} else {
					$return .= '&nbsp;';
				}
				$return .= '</td>' . $_josh['newline'];
			}
			$return .= '</tr></tfoot>' . $_josh['newline'];
		}
		
		$class .= ' table'; //temp for intranet
		$return = draw_container('table', $return, array('cellspacing'=>0, 'class'=>$class));
		
		//drag and drop table
		if ($this->draggable && $count_rows) {
			$return .= draw_javascript('
				function reorder() {
					var ampcharcode= "%26";
					var serializeOpts = Sortable.serialize("' . $this->name . '") + unescape(ampcharcode) + "key=' . $this->name . '" + unescape(ampcharcode) + "update=' . $this->name . '";
					var options = { method:"post", parameters:serializeOpts, onSuccess:function(transport) {
						//alert(transport.responseText);
					} };
					new Ajax.Request("' . url_action_add('ajax_reorder') . '", options);
				}
				Sortable.create("' . $this->name . '", { tag:"tr", ' . (($this->draghandle) ? 'handle:"' . $this->draghandle . '", ' : '') . 'ghosting:true, constraint:"vertical", onUpdate:reorder, tree:true });
				');
		}
		return $return;
	}

	function draw_column($c) {
		$class = $c['name'];
		if ($c['class']) $class .= ' ' . $c['class'];
		$style = ($c['width']) ? 'width:' . $c['width'] . 'px;': false;
		$content = ($c['title']) ? $c['title'] : format_text_human($c['name']);
		return draw_container('th', $content, array('style'=>$style, 'class'=>$c['class']));
	}

	function draw_columns() {
		$return = '';
		foreach ($this->columns as $c) $return .= $this->draw_column($c);
		return draw_container('tr', $return);
	}
	
	function draw_empty($string) {
		return draw_container('tr', draw_container('td', $string, array('class'=>'empty')));
	}
	
	
	function draw_header($show_columns=true) {
		return draw_container('thead', $this->draw_title() . (($show_columns) ? $this->draw_columns() : ''));
	}
	
	function draw_title() {
		return ($this->title) ? draw_container('tr', draw_container('th', $this->title, array('class'=>'title', 'colspan'=>count($this->columns)))) : '';
	}
	
	function col($name, $class=false, $title=false, $width=false) {
		//legacy alias, todo ~ deprecate
		$this->set_column($name, $class, $title, $width);
	}
	
	function set_column($name, $class=false, $title=false, $width=false) {
		$this->columns[] = compact('name', 'class', 'title', 'width');
	}
	
	function set_draggable($draghandle=false) {
		$this->draggable = true;
		$this->draghandle = $draghandle;
	}
	
	function set_title($html) {
		$this->title = $html;
	}
}
?>