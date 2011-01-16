<?php

require_once 'modules/pagesetter/guppy/plugins/input.string.php';


class GuppyInput_mediasharealbum extends GuppyInput_string {
	function render($guppy) {
		$htmlClass = 'mshtml';
		
		if ($this->mandatory) {
			if ($this->data == '') {
		    	$htmlClass .= " mde";
			} else {
		    	$htmlClass .= " mdt";
			}
		}
		
		$style = $this->getHtmlStyle();
		if ($style != '') {
			$style = " style=\"$style\"";
		}
		
		if ($this->readonly) {
			$readonly = " readonly=\"1\"";
		} else { 
			$readonly = '';
		}
		
		$albums = pnModAPIFunc('mediashare', 'user', 'getAllAlbums', array('albumId' => 1));

    	$html .= "<select name=\"" . $this->name . "\" id=\"" . $this->ID . "\" class=\"$htmlClass\" $style value=\"$imgHtml\"$readonly>";
    	if (!$this->mandatory) {
	    	$selectedHtml = ($this->value == -1 ? ' selected="1"' : '');
    		$html .= "<option value=\"-1\"$selectedHtml> - </option>\n";
    	}

		// code borrowed from function.mediashare_albumSelector.php
		foreach ($albums as $album) {
	    	$title = pnVarPrepForDisplay($album['title']);
	    	$id    = (int)$album['id'];
	    	$level = $album['nestedSetLevel'] - 1;
	
	    	$indent = '';
	    	for ($i=0; $i<$level; ++$i)
	      		$indent .= '+ ';
	
	    	$selectedHtml = ($id == $this->value ? ' selected="1"' : '');
	    	$html .= "<option value=\"$id\"$selectedHtml>$indent$title</option>\n";
	  	}
	
	    return $html;
	}


	function validate() {
	    if (!parent::validate()) {
      		return false;
	    }

		if (!is_numeric($this->value)) {
			return false;
		}
		
    	if (($this->mandatory) && ($this->value < 0)) {
      		return false;
    	}

    	return true;
	}

	// ===[ Pagesetter interface ]==============================================
		
	function active() {
		return true;
	}
		
	function getTitle() {
		return _GUPPYMEDIASHAREALBUM;
	}
		
	function getSqlType() {
		return 'VARCHAR(255)';
	}
}

?>