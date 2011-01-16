<?php
// $Id: pnrelations.php,v 1.6 2006/05/16 21:05:10 jornlind Exp $
// =======================================================================
// Pagesetter by Jorn Lind-Nielsen (C) 2003. -- Relations API added by Carsten Kollmeier
// ----------------------------------------------------------------------
// For POST-NUKE Content Management System
// Copyright (C) 2002 by the PostNuke Development Team.
// http://www.postnuke.com/
// ----------------------------------------------------------------------
// LICENSE
//
// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License (GPL)
// as published by the Free Software Foundation; either version 2
// of the License, or (at your option) any later version.
//
// This program is distributed in the hope that it will be useful,
// but WithOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// To read the license please visit http://www.gnu.org/copyleft/gpl.html
// =======================================================================

// Experimental!

require_once("modules/pagesetter/common.php");

function pagesetter_relations_relationsSelect()
{
	$tid            = pnVarCleanFromInput('tid');
	$fieldId        = pnVarCleanFromInput('fieldId');
	$targetTid      = pnVarCleanFromInput('targetTid');
	$filter         = pnVarCleanFromInput('filter');
	$style          = (int)pnVarCleanFromInput('style');
	$name           = pnVarCleanFromInput('name');
	$id             = pnVarCleanFromInput('id');
	$title          = pnVarCleanFromInput('title');
	$pid            = pnVarCleanFromInput('pid');
	$okButton       = pnVarCleanFromInput('okbutton');
	$cancelButton   = pnVarCleanFromInput('cancelbutton');
	$selected       = pnVarCleanFromInput('selected');
	$width          = pnVarCleanFromInput('width');
	$height         = pnVarCleanFromInput('height');
	$readonly       = pnVarCleanFromInput('readonly');
	$result         = pnVarCleanFromInput($name);
	$result_hidden  = pnVarCleanFromInput($name.'_hidden');
	$result_text    = pnVarCleanFromInput($name.'_text');
	
	$selected = explode(':',$selected);
	
	$args = compact ('tid','fieldId','targetTid','filter','style','name','id','title','pid','selected','width','height','readonly');
		
	if ($okButton != '')
	{
		echo "<html>\n";
		echo "<head>\n";
		echo "<script>\n";

        if (is_array($result)) 
			$result_hidden = implode (':',$result);

		echo "var hiddenInput = window.opener.document.getElementById('${id}_hidden');\n";
		echo "var inputText = window.opener.document.getElementById('${id}_text');\n";
		echo "hiddenInput.value = '$result_hidden';\n";
		echo "if (inputText.firstChild) { inputText.firstChild.data = '$result_text'; }\n";
		echo "   else { inputText.appendChild(window.opener.document.createTextNode('$result_text')); }\n";
		echo "window.close();\n";
		echo "</script>\n";
		
	 	echo "</head>\n";
	 	echo "<body></body>\n";
		echo "</html>";
		return true;
	} else if ($cancelButton != '') {
		echo "<html><script>window.close();</script></html>";
		return true;
	}
	
	$output = pnModAPIFunc ('pagesetter','relations','getInputFieldHtml', $args);

	$render = new pnRender('pagesetter');
	$render->caching = 0;
	$render->assign($args);
	
	$render->assign('output',$output);
	
	echo $render->fetch('pagesetter_relations_relationsSelect.html');
	
	return true;
}