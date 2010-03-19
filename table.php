<?php
error_debug('including table.php', __file__, __line__);

class table {
	var $columns	= array();
	var $draggable	= false;
	var $dragcolumn	= false;
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
		$bodies			= array();
		$bodycounter	= -1;
		
		if (!$count_columns) {
			//there were no columns defined.  no columns, no table
			$return .= $this->draw_header(false) . $this->draw_empty('Sorry, no columns defined!');
		} elseif (!$count_rows) {
			//no rows, return errmsg
			$return .= $this->draw_header(false) . $this->draw_empty($errmsg);
		} else {
			$return .= $this->draw_header();
			$row	= 'odd';
			$group	= '';

			foreach ($values as $v) {
				if (isset($v['group']) && ($group != $v['group'])) {
					$bodycounter++;
					$bodies[$bodycounter]['rows'] = array();
					$bodies[$bodycounter]['title'] = $v['group'];
					$row = 'odd'; //reset even/odd at the beginning of groups
					$group = $v['group'];
				} elseif ($bodycounter == -1) {
					$bodycounter++;
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
				$id = (isset($v['id'])) ? 'item_' . $v['id'] : false;
				$class = $row;
				if (isset($v['class']) && !empty($v['class'])) $class .= ' ' . $v['class'] . ' ' . $row . '_' . $v['class'];
				if ($counter == 1) $class .= ' first_row';
				if ($counter == $count_rows) $class .= ' last_row';
				$onclick = (isset($v['link'])) ? 'location.href=\'' . $v['link'] . '\';' : false;
				$inner = '';
				foreach ($this->columns as $c) $inner .= draw_tag('td', array('class'=>$c['name'] . ' ' . $c['class'], 'style'=>(($c['width']) ? 'width:' . $c['width'] . 'px;': false)), $v[$c['name']]);
				
				$bodies[$bodycounter]['rows'][] = draw_tag('tr', array('id'=>$id, 'class'=>$class, 'onclick'=>$onclick), $inner);
				
				$row = ($row == 'even') ? 'odd' : 'even';
				$counter++;
			}
			
			//assemble rows into tbody tags
			for ($i = 0; $i <= $bodycounter; $i++) {
				if (isset($bodies[$i]['title'])) $return .= draw_tag('tr', false, draw_tag('td', array('colspan'=>$count_columns, 'class'=>'group'), $bodies[$i]['title']));
				$return .= draw_tag('tbody', array('id'=>$this->name . $i), implode(NEWLINE, $bodies[$i]['rows']));
			}

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
				$return .= '</td>';
			}
			$return .= '</tr></tfoot>';
		}
		
		$class = $this->name . ' table'; //temp for intranet
		$return = draw_container('table', $return, array('cellspacing'=>0, 'class'=>$class));
		
		//drag and drop table
		if ($this->draggable && $count_rows) {
			for ($i = 0; $i <= $bodycounter; $i++) {
				$return .= draw_javascript('
					function reorder() {
						var ampcharcode= "%26";
						var serializeOpts = Sortable.serialize("' . $this->name . $i . '") + unescape(ampcharcode) + "table=' . $this->name . '" + unescape(ampcharcode) + "column=' . $this->dragcolumn . '";
						var options = { method:"post", parameters:serializeOpts, onSuccess:function(transport) {
							//alert(transport.responseText);
						} };
						new Ajax.Request("' . url_action_add('ajax_reorder') . '", options);
					}
					Sortable.create("' . $this->name . $i . '", { tag:"tr", ' . (($this->draghandle) ? 'handle:"' . $this->draghandle . '", ' : '') . 'ghosting:true, constraint:"vertical", onUpdate:reorder, tree:true });
					');
			}
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
		if ($name == 'draggy') $this->set_draggable('draggy');
	}
	
	function set_draggable($draghandle=false, $dragcolumn='precedence') {
		$this->draggable = true;
		$this->draghandle = $draghandle;
		$this->dragcolumn = $dragcolumn;
	}
	
	function set_title($html) {
		$this->title = $html;
	}
}
?>