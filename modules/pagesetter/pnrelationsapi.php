<?php
// $Id: pnrelationsapi.php,v 1.19 2006/05/16 21:05:10 jornlind Exp $
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

/**
 * Generates a table array, creates a table
 * 
 * Just an internal function to the tablecolumns, but uses the 
 * fields (sourceTid,sourcePid,sourceField,targetTid,targetPid,targetField) 
 * instead of (tid1,pid1,field1,tid2,pid2,field2) and points them to the right columns.
 * This is because the entries in the table are sorted by (tid1 <= tid2) in order to maintain
 * uniqueness.
 * The generated array adresses the fields relative, so you don't have to worry which field is target 
 * and which on is source later on.  
 * @param int	sourceTid   TypeID of the source publication
 * @param int	targetTid 	TypeID of the target publication
 * @return array the table and its columns      
 **/
 
 
function pagesetterGetRelationsTable($sourceTid,$targetTid,$alias) {

	$pntable = &pnDBGetTables();
	$relationsColumn = &$pntable['pagesetter_relations_column']; 
	
	// which one is smaller?
	$sourceFirst = ($sourceTid < $targetTid); 
	
	$return['pagesetter_relations'] = $pntable['pagesetter_relations'];
	
	$prefix = isset($alias) ? $alias.'.' : $pntable['pagesetter_relations'].'.';
	// generate the columns array 
	$return['pagesetter_relations_column'] = $sourceFirst 
						?	array (
								'sourceTid' =>  $prefix . $relationsColumn['tid1'],
								'sourcePid' => $prefix . $relationsColumn['pid1'],
								'sourceField' => $prefix . $relationsColumn['fieldId1'], 
								'targetTid' => $prefix . $relationsColumn['tid2'],
								'targetPid' => $prefix . $relationsColumn['pid2'],
								'targetField' => $prefix . $relationsColumn['fieldId2']
							) 
						:	array ( 
								'sourceTid' => $prefix . $relationsColumn['tid2'],
								'sourcePid' => $prefix . $relationsColumn['pid2'],
								'sourceField' => $prefix . $relationsColumn['fieldId2'], 
								'targetTid' => $prefix . $relationsColumn['tid1'],
								'targetPid' => $prefix . $relationsColumn['pid1'],
								'targetField' => $prefix . $relationsColumn['fieldId1']
							);
	
	return $return;
}

function pagesetter_relationsapi_getRelationsTable($args)
{
	if (!isset($args['sourceTid'])  ||  $args['sourceTid']=='')
    	return pagesetterErrorApi(__FILE__, __LINE__, "Missing argument 'tid' in 'pagesetter_relationsapi_getRelationsTable'", false);
	if (!isset($args['targetTid'])  ||  $args['targetTid']=='')
    	return pagesetterErrorApi(__FILE__, __LINE__, "Missing argument 'targetTid' in 'pagesetter_relationsapi_getRelationsTable'", false);
	
	return pagesetterGetRelationsTable($args['sourceTid'], $args['targetTid'], $args['tableAlias']);
}

function pagesetter_relationsapi_getInputFieldHtml($args)
{
	if (!isset($args['tid'])  ||  $args['tid']=='')
    	return pagesetterErrorApi(__FILE__, __LINE__, "Missing argument 'tid' in 'pagesetter_relationsapi_getInputFieldHtml'", false);
	if (!isset($args['fieldId'])  ||  $args['fieldId']=='')
    	return pagesetterErrorApi(__FILE__, __LINE__, "Missing argument 'fieldId' in 'pagesetter_relationsapi_getInputFieldHtml'", false);
	if (!isset($args['targetTid'])  ||  $args['targetTid']=='')
    	return pagesetterErrorApi(__FILE__, __LINE__, "Missing argument 'targetTid' in 'pagesetter_relationsapi_getInputFieldHtml'", false);
	if (!isset($args['name'])  ||  $args['name']=='')
    	return pagesetterErrorApi(__FILE__, __LINE__, "Missing argument 'name' in 'pagesetter_relationsapi_getInputFieldHtml'", false);
	if (!isset($args['id'])  ||  $args['id']=='')
    	return pagesetterErrorApi(__FILE__, __LINE__, "Missing argument 'id' in 'pagesetter_relationsapi_getInputFieldHtml'", false);
    	
    	
    	$targetTid = $args['targetTid'];
    	$filter = $args['filter'];
    	$popup = (bool)$args['popup'];
    	$style = isset($args['style']) ? $args['style'] : 0; 
    	$name = $args['name'];
    	$id = $args['id'];
    	$selected = $args['selected'];
	
    if (!pnModAPILoad('pagesetter','user'))
      return pagesetterErrorApi(__FILE__,__LINE__, "Failed to load User API in 'pagesetter_relationsapi_getInputFieldHtml'", false);

    $relations = isset($selected) ? $selected : (($args['pid'] !== false && isset($args['pid'])) ? pnModAPIFunc('pagesetter','relations','getRelations', $args) : array());
    $pubList = pnModAPIFunc('pagesetter', 'user', 'getPubList', array ('tid' => $targetTid, 'noOfItems' => 1000, 'filterSet' => (!empty($args['filter']) ? explode('&',$filter) : NULL )));

    $css = '';

    if (isset($args['width']))
      $css .= "width: " . $args['width'] . "px;";
    if (isset($args['height']))
      $css .= "height: " . $args['height'] . "px;";
    if ($css != '')
      $css = " style=\"$css\"";

    if ($args['readonly'])
      $readonly = ' disabled="disabled"';
    else
      $readonly = '';

    if ($popup) {
    	  // Display as popup window
    	  
    	    if (!guppy_pageBlockExists('relations_js')) {
    	    	  $block = "<script>\n" .
    	    	  		   "  function openRelationWindow(url,id)\n" .
    	    	  		   "  {\n" .
    	    	  		   "    hidden = document.getElementById(id);\n" .
    	    	  		   "    window.open(url.replace('/xhiddenx/',hidden.value),'relations','location=no,menubar=no,left=100,top=50,dependent=yes,scrollbar=yes,resizable=yes,width=500,height=400,status=no');\n" .
    	    	  		   "  }\n" .
    	    	  		   "</script>";
    	    	  guppy_registerPageBlock('relations_js',$block);
    	    }
    	    
		$html = "<table$css><tr><td width=\"70%\">";
    	     
    	    $html .= "<span id=\"${id}_text\">";
		$first = true;    	
    		foreach ($pubList['publications'] as $pub) {
    			if (in_array ($pub['pid'],$relations)) {
    				if (!$first) 
    					$html .= ', ';
 				$first = false;
 				$html .= $pub['title'];
    			}
    		}   			
    		
    		$html .= "</span>";
    		
    		$args['selected'] = '/xhiddenx/';
    		
    		$url = pnModUrl ('pagesetter','relations','relationsSelect',$args);
    		
    		$html .= "<td><button type=\"button\" onClick=\"openRelationWindow('$url','". $id ."_hidden');\">...</button></td></tr></table>\n";
    		
		$html .= "<input type=\"hidden\" id=\"${id}_hidden\" name=\"${name}_hidden\" value=\"" . implode (':',$relations) ."\" />";    		
    } 
    else 
    {
		switch ($style) {
		  case 1:
    	    		// Display advanced Select fields
    	    	  		$block = "<script>\n" .
						 "function selectorMove(srcid, dstid)\n" .
						 "{\n" .
						 "  var src = document.getElementById (srcid);\n" .
						 "  var dst = document.getElementById (dstid);\n" .
						 "  var srcSize = src.length;\n" .
						 "  for (var i=0; i<srcSize; ++i)\n" .
						 "  {\n" .
						 "    if (src.options[i].selected)\n" .
						 "    {\n" .
						 "      var len = dst.length;\n" .
						 "      dst.options[len] = new Option(src.options[i].text);\n" .
						 "      dst.options[len].value = src.options[i].value;\n" .
						 "    }\n" .
						 "  }\n" .
						 "  for (var i=srcSize-1; i>=0; --i)\n" .
						 "  {\n" .
						 "    if (src.options[i].selected)\n" .
						 "      src.options[i] = null;\n" .
						 "  }\n" .
						 "}\n" .
						 "function selectorGetValue(srcid,hiddenid)\n" .
						 "{\n" .
						 "  var src = document.getElementById(srcid);\n" .
						 "  var srcSize = src.length;\n" .
						 "  var result = \"\";\n" .
						 "  for (var i=0; i<srcSize; ++i)\n" .
						 "  {\n" .
						 "    if (i == 0)\n" .
						 "      result += src.options[i].value;\n" .
						 "    else\n" .
						 "      result += \":\" + src.options[i].value;\n" .
						 "  }\n" .
						 "  hidden = document.getElementById(hiddenid);\n" .
						 "  hidden.value = result;\n" .
						 "}\n" .
						 "</script>";
			if (function_exists('guppy_registerPageBlock')) {
    	    			if (!guppy_pageBlockExists('relations_select')) {
    	    	  			guppy_registerPageBlock('relations_select',$block);
    	    			}
    	    			$html = '';
			} else {
    	    			$html = $block;
			}
    	    		$html .= "<table>\n<tr>\n";
    	    		$html .= "<th>"._PGREL_SELECTFROM."</th><td>&nbsp;</td><th>"._PGREL_SELECTED."</th>\n";
    	    		$html .= "</tr>\n<tr>\n";
    	    		
    	    		$html .= "<td>";
			$html .= "<select id=\"${id}_from\" name=\"" . $name . "_from\" multiple=\"1\" size=\"10\"$css$readonly/>";
			foreach ($pubList['publications'] as $pub)
			{
				$disabled = (pnSecAuthAction(0,'pagesetter::',"$targetTid:$pub[pid]:",ACCESS_COMMENT) ? '' : ' disabled="disabled"');
				if (!in_array($pub['pid'],$relations ))
				  $html .= "<option value=\"$pub[pid]\"$disabled>$pub[title]</option>\n";
			}
			$html .= "</select>\n";
			$html .= "</td>\n";
    	    		
			$html .= "<td>\n";
			$html .= "<button type=\"button\" onClick=\"selectorMove('${id}_from','${id}_to');selectorGetValue('${id}_to','${id}_hidden');\">--&gt;</button><br />\n";
			$html .= "<button type=\"button\" onClick=\"selectorMove('${id}_to','${id}_from');selectorGetValue('${id}_to','${id}_hidden');\">&lt;--</button><br />\n";
			$html .= "</td>\n";
			
    	    		$html .= "<td>";
			$html .= "<select id=\"${id}_to\" name=\"" . $name . "_to\" multiple=\"1\" size=\"10\"$css$readonly/>";
			foreach ($pubList['publications'] as $pub)
			{
				$disabled = (pnSecAuthAction(0,'pagesetter::',"$targetTid:$pub[pid]:",ACCESS_COMMENT) ? '' : ' disabled="disabled"');
				if (in_array($pub['pid'],$relations ))
				  $html .= "<option value=\"$pub[pid]\"$disabled>$pub[title]</option>\n";
			}
			$html .= "</select>\n";
			$html .= "</td>\n";
			
			$html .= "</tr>\n";
			$html .= "</table>\n";
			
			$html .= "<input type=\"hidden\" id=\"${id}_hidden\" name=\"${name}_hidden\" value=\"" . implode (':',$relations) ."\" />";    		
			break;

		  case 2:
    	    		// Display as checkboxes
    	    		
    	    		$html = '';
    	    		$i = 0;
    	    		foreach ($pubList['publications'] as $pub)
    	    		{   
						$disabled = (pnSecAuthAction(0,'pagesetter::',"$targetTid:$pub[pid]:",ACCESS_COMMENT) && !$args['readonly']
                                     ? '' : ' disabled="disabled"');    		
    	    			if (in_array($pub['pid'],$relations))
    	    			  $checked = ' checked="checked"';
    	    			else
    	    			  $checked = '';
                        $cbxid = "$id-$pub[pid]";
                        $textid = "${id}_text_-$pub[pid]";
    	    			$html .= "<input type=\"checkbox\" name=\"". $name ."[]\" id=\"$cbxid\" value=\"$pub[pid]\"$checked$disabled /><label for=\"$cbxid\" id=\"$textid\">$pub[title]</label>\n";
    	    			$html .= "<input type=\"hidden\" id=\"" . $id . "_text_" . $i++ . "\" name=\"" . $name . "_text_" . $i . "\" value=\"$pub[title]\" /><br />\n"; 
    	    		} 
			break;

		  case 3:
			// Display as Read Only
    	    
			$html .= "<span id=\"${id}_text\">";
			$first = true;    	
    			foreach ($pubList['publications'] as $pub) {
    				if (in_array ($pub['pid'],$relations)) {
    					if (!$first) 
    						$html .= ', ';
 						$first = false;
 						$html .= $pub['title'];
    				}
    			}   			
    		
    			$html .= "</span>";
			$html .= "<input type=\"hidden\" name=\"${name}_hidden\" value=\"" . implode (':',$relations) ."\" />";
			break;
		  default:
			// Display as simple multiple select box.
    	
			$html = "<select id=\"$id\" name=\"" . $name . "[]\" multiple=\"1\" size=\"10\"$css$readonly/>";

			foreach ($pubList['publications'] as $pub)
			{
				$disabled = (pnSecAuthAction(0,'pagesetter::',"$targetTid:$pub[pid]:",ACCESS_COMMENT) ? '' : ' disabled="disabled"');
				if (in_array($pub['pid'],$relations ))
				  $selected = ' selected="1"';
				else
				  $selected = '';

				$html .= "<option value=\"$pub[pid]\"$selected$disabled>$pub[title]</option>\n";
			}

			$html .= "</select>\n";
		}
    }
    
    return $html;
}

/**
 * gets the related publications
 * 
 * API-Function: Delivers an array of the pid's of the related publications.
 * @param int tid the type ID of the source publication
 * @param int pid the pid of the source publication (the one which initiated the relation)
 * @param int fieldId the source field ID
 * @param int targetTid the type ID the publication should point to
 * @param int targetPermission=pagesetterAccessAuthor the permission needed to show a publication
 */
function pagesetter_relationsapi_getRelations($args) {
	
	  // Check parameters
	if (!isset($args['tid'])  ||  $args['tid']=='')
    	return pagesetterErrorApi(__FILE__, __LINE__, "Missing argument 'tid' in 'pagesetter_relationsapi_getRelations'", false);
	if (!isset($args['pid'])  ||  $args['pid']=='')
    	return pagesetterErrorApi(__FILE__, __LINE__, "Missing argument 'pid' in 'pagesetter_relationsapi_getRelations'", false);
	if (!isset($args['fieldId'])  ||  $args['fieldId']=='')
    	return pagesetterErrorApi(__FILE__, __LINE__, "Missing argument 'fieldId' in 'pagesetter_relationsapi_getRelations'", false);
	if (!isset($args['targetTid'])  ||  $args['targetTid']=='')
    	return pagesetterErrorApi(__FILE__, __LINE__, "Missing argument 'targetTid' in 'pagesetter_relationsapi_getRelations'", false);
	
	  // Get parameters
	$sourceTid = $args['tid'];
	$sourcePid = $args['pid'];
	$sourceField = $args['fieldId'];
	$targetTid = $args['targetTid'];
	
	  //Check permissions on source publication
	if (!pnSecAuthAction(0, 'pagesetter::', "$targetTid:$targetPid:", ACCESS_READ))
		pagesetterErrorAPI(__FILE__,__LINE__,_PGNOAUTH);

	  // Get DB connection and table infos
	list($dbconn) = pnDBGetConn();
	$table = pagesetterGetRelationsTable($sourceTid,$targetTid); 
	
	$relTable = $table['pagesetter_relations'];
	$relColumn = &$table['pagesetter_relations_column'];
	
	  // build sql-statement...	
	$sql = "SELECT $relColumn[targetPid] " .
			"FROM $relTable " .
			"WHERE " .
			"$relColumn[sourceTid] = '" . pnVarPrepForStore($sourceTid) . "' " .
			"AND $relColumn[sourcePid] = '" . pnVarPrepForStore($sourcePid ) . "' " .
			"AND $relColumn[sourceField] = '" . pnVarPrepForStore($sourceField ) . "' " .
			"AND $relColumn[targetTid] = '" . pnVarPrepForStore($targetTid) . "'";
	
	  // ...and execute
	$result = $dbconn->Execute($sql); 

	if ($dbconn->errorNo() != 0)
   			return pagesetterErrorApi(__FILE__, __LINE__, '"getRelations" failed: ' 
                                                 . $dbconn->errorMsg() . " while executing: $sql");
	
	  // build array with the related pids and return it  
	$related = array();
	for (;!$result->EOF; $result->MoveNext()) {
		$targetPid = $result->fields[0];
		  //Check permissions on target publication
		if (!pnSecAuthAction(0, 'pagesetter::', "$targetTid:$targetPid:", ACCESS_READ))
			continue;
		$related[] = $targetPid;
	}
	
	$result->Close;
	
	return $related;
}

/**
 * Sets the relations
 * 
 * Updates the relations to the given publication
 * @param int tid
 * @param int pid The source publication: this is the publication from which the relation is initiated.
 * @param int fieldId The field from which the relation is initiated
 * @param int targetTid
 * @param int targetField
 * @param array targetPids The publications that get related. pids not in this array will be removed from table
 */
function pagesetter_relationsapi_setRelations($args) {

	  // Check parameters
	if (!isset($args['tid'])  ||  $args['tid']=='')
    	return pagesetterErrorApi(__FILE__, __LINE__, "Missing argument 'tid' in 'pagesetter_relationsapi_setRelations'", false);
	if (!isset($args['pid']))
    	return pagesetterErrorApi(__FILE__, __LINE__, "Missing argument 'pid' in 'pagesetter_relationsapi_setRelations'", false);
	if (!isset($args['targetTid'])  ||  $args['targetTid']=='')
    	return pagesetterErrorApi(__FILE__, __LINE__, "Missing argument 'targetTid' in 'pagesetter_relationsapi_setRelations'", false);
	if (!isset($args['targetPids'])  ||  !is_array($args['targetPids']))
    	return pagesetterErrorApi(__FILE__, __LINE__, "Missing or wrong argument 'targetPids' in 'pagesetter_relationsapi_setRelations'", false);
	
	  // Get parameters
	$sourceTid = $args['tid'];
	$sourcePid = $args['pid'];
	$sourceField = $args['fieldId'];
	$targetTid = $args['targetTid'];
	$targetPids = $args['targetPids'];
	$targetField = $args['targetField'];
	
	
	
	/* if (!pnModAPILoad('pagesetter', 'admin'))
		return pagesetterErrorApi(__FILE__, __LINE__, 'Failed to load Pagesetter admin API');
	if (($pubTypeInfo = pnModAPILoad ('pagesetter','admin','getPubTypeInfo',array('tid' => $sourceTid))) === false)
		return false;
		
	$typeData = explode(':',$pubTypeInfo['fieldIdIndex'][$sourceField]['typeData'],2);
	$targetField = !isset($typeData[1]) || $typeData[1] == '' ? NULL : $typeData[1]; */

	  // Access is not checked at this point, because the access to the 
	  // source publication should have been checked already.
	  // (for example in pagesetter_edit_updatePub)	

	  // Get DB connection and table infos
	list($dbconn) = pnDBGetConn();
	$table = pagesetterGetRelationsTable($sourceTid,$targetTid); 
	
	$relTable = $table['pagesetter_relations'];
	$relColumn = &$table['pagesetter_relations_column'];
	
	$sql = "SELECT $relColumn[sourceTid], $relColumn[targetPid] FROM $relTable " .
			"WHERE " .
			"$relColumn[sourceTid] = '". pnVarPrepForStore($sourceTid) . "' " .
			"AND $relColumn[sourcePid] = '". pnVarPrepForStore($sourcePid ) . "' " .
			"AND $relColumn[sourceField] = '". pnVarPrepForStore($sourceField ) . "' " .
			"AND $relColumn[targetTid] = '". pnVarPrepForStore($targetTid) . "'";
	$result = $dbconn->Execute($sql);
	
	if ($dbconn->errorNo() != 0) {
   		return pagesetterErrorApi(__FILE__, __LINE__, '"setRelations" failed: ' 
                                                 . $dbconn->errorMsg() . " while executing: $sql");
	}
	
	  // update the relations:	
	$dbconn->StartTrans();
	for (;!$result->EOF; $result->MoveNext()) {
		
		$targetPid = $result->fields[1];
		  // is the result-field in the new pids array?
		$pos = array_search ($targetPid,$targetPids);
		  // if not, delete it
		if ($pos === false) {
			  // permissions check: Do I have comment permission to target? Otherwise leave it as it is
			  //TODO: more sophisticated permissions check (topics and edit own)
			if (!pnSecAuthAction(0, 'pagesetter::', "$targetTid:$targetPid:", ACCESS_COMMENT))
				continue;

			$sql = "DELETE FROM $relTable " .
					"WHERE $relColumn[sourcePid] = '". pnVarPrepForStore($sourcePid) . "' " .
					"AND $relColumn[sourceTid] = '". pnVarPrepForStore($sourceTid) . "' " .
					"AND $relColumn[sourceField] = '". pnVarPrepForStore($sourceField) . "' " .
					"AND $relColumn[targetPid] = '$targetPid' " .
					"AND $relColumn[targetTid] = '". pnVarPrepForStore($targetTid) . "' " .
					"LIMIT 1";
			$dbconn->Execute($sql);
   		    if ($dbconn->errorNo() != 0) { 
   				return pagesetterErrorApi(__FILE__, __LINE__, '"setRelations" failed: ' 
                                                 . $dbconn->errorMsg() . " while executing: $sql");
   		    }
   		  // otherwise remove it from array (for it is processed)
		} else {
			array_splice ($targetPids, $pos, 1);
		}
	}
	
	  // the remaining pids in the array have to be written to db
	foreach ($targetPids as $newPid) {
		  // permissions check: Do I have comment permission to target? Otherwise leave it as it is
		  //TODO: more sophisticated permissions check (topics and edit own)
		if (!pnSecAuthAction(0, 'pagesetter::', "$targetTid:$newPid:", ACCESS_COMMENT))
			continue;

		$sql = "INSERT INTO $relTable (" .
				"$relColumn[sourceTid], " .
				"$relColumn[sourcePid], " .
				"$relColumn[sourceField], " .
				"$relColumn[targetTid], " .
				"$relColumn[targetPid], " .
				"$relColumn[targetField]) " .
				"VALUES (".
				"'". pnVarPrepForStore($sourceTid) . "', " .
				"'". pnVarPrepForStore($sourcePid) . "', " .
				"'". pnVarPrepForStore($sourceField) . "', " .
				"'". pnVarPrepForStore($targetTid) . "', " .
				"'". pnVarPrepForStore($newPid) . "', " .
				(isset ($targetField) ? ("'" . pnVarPrepForStore($targetField) . "'") : "NULL") . ")";
		$dbconn->Execute($sql);
		$mes .= "$sql\n\n";
		if ($dbconn->errorNo != 0) {
	   		return pagesetterErrorApi(__FILE__, __LINE__, '"setRelations" failed: ' 
                                                 . $dbconn->errorMsg() . " while executing: $sql");
		}
	}
	if (!$dbconn->CompleteTrans()) {
   		return pagesetterErrorApi(__FILE__, __LINE__, '"setRelations" failed: ' 
                                                 . $dbconn->errorMsg() . " while completing Trans ($mes)");	
	}

	  // everything worked!
	return true;
}
	
/**
 * tells the pid for a given id
 * 
 * this function is here for the reason that when I update the relations table
 * from creating a new publication or adressing publications by id, I have to get the pid
 * for the relations table
 */	
function pagesetter_relationsapi_id2Pid($args) {
	if (!isset($args['tid'])  ||  $args['tid']=='')
    	return pagesetterErrorApi(__FILE__, __LINE__, "Missing argument 'tid' in 'pagesetter_relationsapi_id2Pid'");
  	if (!isset($args['id']) || $args['id'==''])
    	return pagesetterErrorApi(__FILE__, __LINE__, "Missing argument 'id' in 'pagesetter_relationsapi_id2Pid'");
	
	if (!pnModAPILoad('pagesetter', 'user'))
    	return pagesetterErrorApi(__FILE__, __LINE__, 'Failed to load Pagesetter user API');
	
	$pub = pnModAPIFunc('pagesetter', 'user', 'getPub', $args);
	
	if (is_array($pub))
		return $pub['core_pid'];
	else return false;
}

/**
 * removes all relations to a publication type / field
 */	
function pagesetter_relationsapi_removeRelations($args) {

	  // Check parameters
	if (!isset($args['tid'])  ||  $args['tid']=='')
    	return pagesetterErrorApi(__FILE__, __LINE__, "Missing argument 'tid' in 'pagesetter_relationsapi_removeRelations'", false);
	if (!isset($args['fieldId']))
    	return pagesetterErrorApi(__FILE__, __LINE__, "Missing argument 'fieldId' in 'pagesetter_relationsapi_removeRelations'", false);
	if (!isset($args['targetTid'])  ||  $args['targetTid']=='')
    	return pagesetterErrorApi(__FILE__, __LINE__, "Missing argument 'targetTid' in 'pagesetter_relationsapi_removeRelations'", false);

	  // Get parameters
	$sourceTid = $args['tid'];
	$sourceField = $args['fieldId'];
	$targetTid = $args['targetTid'];
	
	// echo "removeRelations: $sourceTid, $sourceField, $targetTid"; die;
	
	  // FIXME: Check permissions
	
	  // Get DB connection and table infos
	list($dbconn) = pnDBGetConn();
	$table = pagesetterGetRelationsTable($sourceTid,$targetTid); 
	
	$relTable = $table['pagesetter_relations'];
	$relColumn = &$table['pagesetter_relations_column'];

	$sql = "DELETE FROM $relTable " .
			"WHERE " .
			"$relColumn[sourceTid] = '" . pnVarPrepForStore($sourceTid) . "' " .
			"AND $relColumn[sourceField] = '" . pnVarPrepForStore($sourceField) . "' " .
			"AND $relColumn[targetTid] = '" . pnVarPrepForStore($targetTid) . "'";
	$dbconn->Execute($sql);
    if ($dbconn->errorNo() != 0) { 
		return pagesetterErrorApi(__FILE__, __LINE__, '"removeRelations" failed: ' 
                                                 . $dbconn->errorMsg() . " while executing: $sql");
    }
    
    return true;
}

/**
 * converts relations between fields to independent relations
 */
function pagesetter_relationsapi_convertToIndependent($args) {
	  // Check parameters
	if (!isset($args['tid'])  ||  $args['tid']=='')
    	return pagesetterErrorApi(__FILE__, __LINE__, "Missing argument 'tid' in 'pagesetter_relationsapi_convertToIndependent'", false);
	if (!isset($args['fieldId'])  ||  $args['fieldId']=='')
    	return pagesetterErrorApi(__FILE__, __LINE__, "Missing argument 'fieldId' in 'pagesetter_relationsapi_convertToIndependent'", false);
	if (!isset($args['targetTid'])  ||  $args['targetTid']=='')
    	return pagesetterErrorApi(__FILE__, __LINE__, "Missing argument 'targetTid' in 'pagesetter_relationsapi_convertToIndependent'", false);
	if (!isset($args['oldTargetFieldId']))
    	return pagesetterErrorApi(__FILE__, __LINE__, "Missing argument 'oldTargetFieldId' in 'pagesetter_relationsapi_convertToIndependent'", false);
	
	  // Get parameters
	$sourceTid = $args['tid'];
	$sourceField = $args['fieldId'];
	$targetTid = $args['targetTid'];
	$oldTargetField = $args['oldTargetFieldId'];
	
	// echo "convertToIndependent: $sourceTid, $sourceField, $targetTid, $oldTargetField"; die;
	
	  // FIXME: Check permissions

	  // Get DB connection and table infos
	list($dbconn) = pnDBGetConn();
	$dbconn->StartTrans();
	$table = pagesetterGetRelationsTable($sourceTid,$targetTid); 
	
	$relTable = $table['pagesetter_relations'];
	$relColumn = &$table['pagesetter_relations_column'];
	
	  // Get a effected rows
	$sql = "SELECT $relColumn[sourceTid], " .
			      "$relColumn[sourcePid], " .
			      "$relColumn[sourceField], " .
			      "$relColumn[targetTid], " .
			      "$relColumn[targetPid], " .
			      "$relColumn[targetField] " .
			      "FROM $relTable " .
			      "WHERE " .
			      "$relColumn[sourceTid] = '" . pnVarPrepForStore($sourceTid) . "' " .
			      "AND $relColumn[sourceField] = '" . pnVarPrepForStore($sourceField) . "' " .
			      "AND $relColumn[targetTid] = '" . pnVarPrepForStore($targetTid) . "' " .
			      "AND $relColumn[targetField] = '" . pnVarPrepForStore($oldTargetField) . "'";
	
	$result = $dbconn->Execute($sql);
    if ($dbconn->errorNo() != 0) { 
		return pagesetterErrorApi(__FILE__, __LINE__, '"convertToIndependent" failed: ' 
                                                 . $dbconn->errorMsg() . " while executing: $sql");
    }
    
      // remove Target Field
    $sql = "UPDATE $relTable SET " .
                  "$relColumn[targetField] = NULL " .
                  "WHERE " .
			      "$relColumn[sourceTid] = '" . pnVarPrepForStore($sourceTid) . "' " .
			      "AND $relColumn[sourceField] = '" . pnVarPrepForStore($sourceField) . "' " .
			      "AND $relColumn[targetTid] = '" . pnVarPrepForStore($targetTid) . "' " .
			      "AND $relColumn[targetField] = '" . pnVarPrepForStore($oldTargetField) . "'";

	$dbconn->Execute($sql);
    if ($dbconn->errorNo() != 0) { 
		return pagesetterErrorApi(__FILE__, __LINE__, '"convertToIndependent" failed: ' 
                                                 . $dbconn->errorMsg() . " while executing: $sql");
    }
    
      // duplicate Entries
	for (;!$result->EOF; $result->MoveNext()) {
		$sql = "INSERT INTO $relTable (" .
				  "$relColumn[sourceTid], " .
			      "$relColumn[sourcePid], " .
			      "$relColumn[sourceField], " .
			      "$relColumn[targetTid], " .
			      "$relColumn[targetPid], " .
			      "$relColumn[targetField]) " .
			   "VALUES (" .
			      "'".$result->fields[0] . "', " .
			      "'".$result->fields[1] . "', " .
			      " NULL, " .
			      "'".$result->fields[3] . "', " .
			      "'".$result->fields[4] . "', " .
			      "'".$result->fields[5] . "')";
	
		$dbconn->Execute($sql);
    	if ($dbconn->errorNo() != 0) { 
			return pagesetterErrorApi(__FILE__, __LINE__, '"convertToIndependent" failed: ' 
            	                                     . $dbconn->errorMsg() . " while executing: $sql");
    	}
    		
	}
	
	  // change type extra data of target field
	  // TODO: doing this directly in database, maybe a API function where better?
	$pntable = &pnDBGetTables();
	$pubFieldsTable = $pntable['pagesetter_pubfields'];
	$pubFieldsColumn = &$pntable['pagesetter_pubfields_column'];
	
	$sql = "SELECT $pubFieldsColumn[typeData] FROM $pubFieldsTable
         WHERE 
           $pubFieldsColumn[id] = " . $oldTargetField;

	$result = $dbconn->Execute($sql);
   	if ($dbconn->errorNo() != 0) { 
		return pagesetterErrorApi(__FILE__, __LINE__, '"convertToIndependent" failed: ' 
           	                                     . $dbconn->errorMsg() . " while executing: $sql");
   	}
   	
   	$oldTypeData = explode (':',$result->fields[0],7);

	$sql = "UPDATE $pubFieldsTable SET
           $pubFieldsColumn[typeData] = '$targetTid:$oldTargetField:$sourceTid::-1:-1:$oldTypeData[6]'
         WHERE 
           $pubFieldsColumn[id] = " . $oldTargetField;

	$dbconn->Execute($sql);
   	if ($dbconn->errorNo() != 0) { 
		return pagesetterErrorApi(__FILE__, __LINE__, '"convertToIndependent" failed: ' 
           	                                     . $dbconn->errorMsg() . " while executing: $sql");
   	}
	     
	if (!$dbconn->CompleteTrans()) {
   		return pagesetterErrorApi(__FILE__, __LINE__, '"convertToIndependent" failed: ' 
                                                 . $dbconn->errorMsg() . " while completing Trans");	
	}

	  // everything worked!
	return true;
}	

/**
 * converts relations between fields to connected relations
 */
function pagesetter_relationsapi_convertToConnected($args) {
	  // Check parameters
	if (!isset($args['tid'])  ||  $args['tid']=='')
    	return pagesetterErrorApi(__FILE__, __LINE__, "Missing argument 'tid' in 'pagesetter_relationsapi_convertToConnected'", false);
	if (!isset($args['fieldId'])  ||  $args['fieldId']=='')
    	return pagesetterErrorApi(__FILE__, __LINE__, "Missing argument 'fieldId' in 'pagesetter_relationsapi_convertToConnected'", false);
	if (!isset($args['targetTid'])  ||  $args['targetTid']=='')
    	return pagesetterErrorApi(__FILE__, __LINE__, "Missing argument 'targetTid' in 'pagesetter_relationsapi_convertToConnected'", false);
	if (!isset($args['targetFieldId'])  ||  $args['targetFieldId']=='')
    	return pagesetterErrorApi(__FILE__, __LINE__, "Missing argument 'targetFieldId' in 'pagesetter_relationsapi_convertToConnected'", false);
	
	  // Get parameters
	$sourceTid = $args['tid'];
	$sourceField = $args['fieldId'];
	$targetTid = $args['targetTid'];
	$targetField = $args['targetFieldId'];
	
	// echo "convertToConnected: $sourceTid, $sourceField, $targetTid, $targetField"; die;
	
	  // FIXME: Check permissions

	  // Get DB connection and table infos
	list($dbconn) = pnDBGetConn();
	$dbconn->StartTrans();
	$table = pagesetterGetRelationsTable($sourceTid,$targetTid); 
	
	$relTable = $table['pagesetter_relations'];
	$relColumn = &$table['pagesetter_relations_column'];
	
	  // Get a effected rows
	$sql = "SELECT $relColumn[sourceTid], " .
			      "$relColumn[sourcePid], " .
			      "$relColumn[sourceField], " .
			      "$relColumn[targetTid], " .
			      "$relColumn[targetPid], " .
			      "$relColumn[targetField] " .
			      "FROM $relTable " .
			      "WHERE " .
			      "$relColumn[sourceTid] = '" . pnVarPrepForStore($sourceTid) . "' " .
			      "AND $relColumn[targetTid] = '" . pnVarPrepForStore($targetTid) . "' " .
			      "AND ($relColumn[targetField] = '" . pnVarPrepForStore($targetField) . "'OR $relColumn[sourceField] = '" . pnVarPrepForStore($sourceField) . "')";
	
	$result = $dbconn->Execute($sql);
    if ($dbconn->errorNo() != 0) { 
		return pagesetterErrorApi(__FILE__, __LINE__, '"convertToConnected" failed: ' 
                                                 . $dbconn->errorMsg() . " while executing: $sql");
    }
    
      // merge Entries
	for (;!$result->EOF; $result->MoveNext()) {
		  // first delete matching entries to avoid dupes
		$sql = "DELETE FROM $relTable " .
				"WHERE " .
				"$relColumn[sourceTid] = '" . $result->fields[0] . "' " .
				"AND $relColumn[sourcePid] = '" . $result->fields[1] . "' " .
				"AND $relColumn[targetTid] = '" . $result->fields[3] . "' " .
				"AND $relColumn[targetPid] = '" . $result->fields[4] . "' " .
			    "AND ($relColumn[targetField] = '" . pnVarPrepForStore($targetField) . "' OR $relColumn[sourceField] = '" . pnVarPrepForStore($sourceField) . "' )";
			    
		$dbconn->Execute($sql);
    	if ($dbconn->errorNo() != 0) { 
			return pagesetterErrorApi(__FILE__, __LINE__, '"convertToConnected" failed: ' 
            	                                     . $dbconn->errorMsg() . " while executing: $sql");
    	}
    	  // then add a new entry
		$sql = "INSERT INTO $relTable (" .
				  "$relColumn[sourceTid], " .
			      "$relColumn[sourcePid], " .
			      "$relColumn[sourceField], " .
			      "$relColumn[targetTid], " .
			      "$relColumn[targetPid], " .
			      "$relColumn[targetField]) " .
			   "VALUES (" .
			      "'".$result->fields[0] . "', " .
			      "'".$result->fields[1] . "', " .
			      "'$sourceField', " .
			      "'".$result->fields[3] . "', " .
			      "'".$result->fields[4] . "', " .
			      "'$targetField')";
	
		$dbconn->Execute($sql);
    	if ($dbconn->errorNo() != 0) { 
			return pagesetterErrorApi(__FILE__, __LINE__, '"convertToConnected" failed: ' 
            	                                     . $dbconn->errorMsg() . " while executing: $sql");
    	}
    		
	}
	
	  // change type extra data of target field
	  // TODO: doing this directly in database, maybe a API function where better?
	$pntable = &pnDBGetTables();
	$pubFieldsTable = $pntable['pagesetter_pubfields'];
	$pubFieldsColumn = &$pntable['pagesetter_pubfields_column'];
	
	$sql = "SELECT $pubFieldsColumn[typeData] FROM $pubFieldsTable
         WHERE 
           $pubFieldsColumn[id] = " . $targetField;

	$result = $dbconn->Execute($sql);
   	if ($dbconn->errorNo() != 0) { 
		return pagesetterErrorApi(__FILE__, __LINE__, '"convertToIndependent" failed: ' 
           	                                     . $dbconn->errorMsg() . " while executing: $sql");
   	}
   	
   	$oldTypeData = explode (':',$result->fields[0],7);

	$sql = "UPDATE $pubFieldsTable SET
           $pubFieldsColumn[typeData] = '$targetTid:$targetField:$sourceTid:$sourceField:-1:-1:$oldTypeData[6]'
         WHERE 
           $pubFieldsColumn[id] = " . (int)$targetField;

	$dbconn->Execute($sql);
   	if ($dbconn->errorNo() != 0) { 
		return pagesetterErrorApi(__FILE__, __LINE__, '"convertToConnected" failed: ' 
           	                                     . $dbconn->errorMsg() . " while executing: $sql");
   	}
	     
	if (!$dbconn->CompleteTrans()) {
   		return pagesetterErrorApi(__FILE__, __LINE__, '"convertToIndependent" failed: ' 
                                                 . $dbconn->errorMsg() . " while completing Trans");	
	}

	  // everything worked!
	return true;
}	

function pagesetter_relationsapi_updateRelations($args)
{
	  // Check parameters
	if (!isset($args['tid'])  ||  $args['tid']=='')
    	return pagesetterErrorApi(__FILE__, __LINE__, "Missing argument 'tid' in 'pagesetter_relationsapi_updateRelations'", false);
	if (!isset($args['fieldId'])  ||  $args['fieldId']=='')
    	return pagesetterErrorApi(__FILE__, __LINE__, "Missing argument 'fieldId' in 'pagesetter_relationsapi_updateRelations'", false);
	if (!isset($args['targetTid']))
    	return pagesetterErrorApi(__FILE__, __LINE__, "Missing argument 'targetTid' in 'pagesetter_relationsapi_updateRelations'", false);
	if (!isset($args['targetFieldId']))
    	return pagesetterErrorApi(__FILE__, __LINE__, "Missing argument 'targetFieldId' in 'pagesetter_relationsapi_updateRelations'", false);
	if (!isset($args['oldTargetTid']))
    	return pagesetterErrorApi(__FILE__, __LINE__, "Missing argument 'targetTid' in 'pagesetter_relationsapi_updateRelations'", false);
	if (!isset($args['oldTargetFieldId']))
    	return pagesetterErrorApi(__FILE__, __LINE__, "Missing argument 'targetFieldId' in 'pagesetter_relationsapi_updateRelations'", false);
	
	  // Get parameters
	$sourceTid = $args['tid'];
	$sourceField = $args['fieldId'];
	$targetTid = $args['targetTid'];
	$targetField = $args['targetFieldId'];
	$oldTargetTid = $args['oldTargetTid'];
	$oldTargetField = $args['oldTargetFieldId'];
	
	  // Check which actions to perform based on what has changed
	if (($targetTid == $oldTargetTid || (int)$oldTargetTid == -1) && ($targetField == $oldTargetField || (int)$oldTargetField == -1)) {
		return true;
	} elseif ($targetTid != $oldTargetTid && (int)$oldTargetTid != -1) {
		if (!pnModAPILoad('pagesetter','relations'))
			return false;
		if ($oldTargetField != '') {
			if (!pnModAPIFunc('pagesetter','relations','convertToIndependent',$args)) {
				return false;
			}
		}
		if (!pnModAPIFunc('pagesetter','relations','removeRelations', array ('tid' => $sourceTid, 'fieldId' => $sourceField, 'targetTid' => $oldTargetTid))) {
			return false;
		}
		if ($targetField != '') {
			if (!pnModAPIFunc('pagesetter','relations','convertToConnected',$args)) {
				return false;
			}
		}
	} elseif ($targetField != $oldTargetField && (int)$oldTargetField != -1) {
		if (!pnModAPILoad('pagesetter','relations'))
			return false;
		if (!pnModAPIFunc('pagesetter','relations','convertToIndependent',$args)) {
			return false;
		}
		if ($targetField != '') {
			if (!pnModAPIFunc('pagesetter','relations','convertToConnected',$args)) {
				return false;
			}
		}	
	}
	return true;
}

?>