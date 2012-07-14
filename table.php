<?php
error_debug('including table.php', __file__, __line__);

class table {
	var $columns	= array();
	var $draggable	= false;
	var $dragcolumn	= false;
	var $name		= false;
	var $nested		= false;
	var $title		= false;

	public function __construct($name='table', $title=false) {
		$this->name = format_text_code($name);
		$this->title = $title;
	}
	
	function draw($values, $errmsg=false, $total=false) {
		global $_josh;
		$class			= $this->name;
		$count_columns	= count($this->columns);
		$count_rows		= count($values);
		$counter		= 1; //to determine first and last
		$totals			= array(); //var to hold totals, if applicable
		$return			= ''; //hold the output
		$bodies			= array();
		$bodycounter	= -1;
		
		if (!$count_columns) {
			//there were no columns defined.  no columns, no table
			$return .= $this->draw_header(false) . $this->draw_empty('Sorry, no columns defined!');
		} elseif (!$count_rows) {
			//no rows, return errmsg
			if (!$errmsg) return false; //this should be the default behavior
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
				$id = (isset($v['id'])) ? $this->name . '-row-' . $v['id'] : false;
				$class = $row;
				if (!empty($v['class'])) $class .= ' ' . $v['class']; // . ' ' . $row . '_' . $v['class'];
				if ($counter == 1) $class .= ' first';
				if ($counter == $count_rows) $class .= ' last';

				$inner = '';
				foreach ($this->columns as $c) $inner .= draw_tag('td', array('class'=>$c['name'] . ' ' . $c['class'], 'style'=>(($c['width']) ? 'width:' . $c['width'] . 'px;': false)), $v[$c['name']]);
				
				$bodies[$bodycounter]['rows'][] = draw_tag('tr', array('id'=>$id, 'class'=>$class), $inner);
				
				$row = ($row == 'even') ? 'odd' : 'even';
				$counter++;
			}
			
			//assemble rows into tbody tags
			for ($i = 0; $i <= $bodycounter; $i++) {
				if (isset($bodies[$i]['title'])) $return .= draw_tag('tr', array('class'=>'group'), draw_tag('td', array('colspan'=>$count_columns), '<a name="' . format_text_code($bodies[$i]['title']) . '"></a>' . $bodies[$i]['title']));
				$return .= draw_tag('tbody', array('id'=>$this->name . $i), implode(NEWLINE, $bodies[$i]['rows']));
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
		}
		
		$class = $this->name . ' table'; //temp for intranet
		$return = draw_container('table', $return, array('cellspacing'=>0, 'class'=>$class, 'id'=>$this->name));
		
		//drag and drop table
		if ($this->draggable && $count_rows) {
			$return .= lib_get('tablednd') . draw_javascript('$(document).ready(function() { 
				table_dnd("' . $this->name . '", "' . $this->dragcolumn . '", "' . $this->draghandle . '");
			});');
		}
		return $return;
	}

	function draw_column($c) {
		$class = $c['name'];
		if (!empty($c['class'])) $class .= ' ' . $c['class'];
		$style = ($c['width']) ? 'width:' . $c['width'] . 'px;' : false;
		$content = ($c['title']) ? $c['title'] : format_text_human($c['name']);
		return draw_tag('th', array('style'=>$style, 'class'=>$class), $content);
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
		return ($this->title) ? draw_container('tr', draw_container('th', $this->title, array('class'=>'table_title', 'colspan'=>count($this->columns)))) : '';
	}
	
	function col($name, $class=false, $title=false, $width=false) {
		error_deprecated(__function__ . ' is deprecated as of 4/7/2010, use set_column instead');
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
	
	function unset_draggable() {
		$this->draggable = $this->draghandle = $this->dragcolumn = false;
	}
	
	function set_nested($nested=true) {
		$this->nested = $nested;
	}
	
	function set_title($html) {
		$this->title = $html;
	}
}