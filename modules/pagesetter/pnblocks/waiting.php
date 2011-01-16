<?php
// $Id: waiting.php,v 1.7 2006/02/23 21:14:02 jornlind Exp $
// =======================================================================
// Pagesetter by Jorn Lind-Nielsen (C) 2004.
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
// but WIthOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// To read the license please visit http://www.gnu.org/copyleft/gpl.html
// =======================================================================

require_once("modules/pagesetter/common.php");


/**
 * initialise block
 */
function pagesetter_waitingblock_init()
{
    // Security
  pnSecAddSchema('pagesetter:Waitingblock:', 'Block title:Block Id:Type Id');
}


/**
 * get information on block
 */
function pagesetter_waitingblock_info()
{
    // Values
  return array('text_type'      => 'pagesetterWaiting',
               'module'         => 'pagesetter',
               'text_type_long' => 'Pagesetter waiting list',
               'allow_multiple' => true,
               'form_content'   => false,
               'form_refresh'   => false,
               'show_preview'   => true);
}


function pagesetter_waitingblock_display($blockinfo)
{
    // Get variables from content block
  $vars = pnBlockVarsFromContent($blockinfo['content']);

  if (!pnModAPILoad('pagesetter', 'admin'))
    return "Failed to load Pagesetter user API";

    // Security check
  if (!pnSecAuthAction(0, 'pagesetter:Waitingblock:', "$blockinfo[title]:$blockinfo[bid]", ACCESS_READ))
    return;
  
  list($dbconn) = pnDBGetConn();

  $pubTypes = pnModAPIFunc( 'pagesetter', 'admin', 'getPublicationTypes' );
  if ($pubTypes === false)
    return ""; // No error here, it would always get printed instead of the block for people with no access

  $waiting = array();

  foreach ($pubTypes as $pubType)
  {
    $tid = $pubType['id'];
  
    if (pnSecAuthAction(0, 'pagesetter::', "$tid::", pagesetterAccessEditor))
    {
      $sql = array_key_exists("sql:$tid",$vars) ? $vars["sql:$tid"] : null;

      if ($sql != null)
      {
        $result = $dbconn->execute($sql);

        if ($dbconn->errorNo() != 0)
          return pagesetterErrorApi(__FILE__, __LINE__, '"display waiting" failed: ' 
                                                        . $dbconn->errorMsg() . " while executing: <pre>$sql</pre>"); 

        $count = $result->fields[0];
        $url = pnModURL('pagesetter', 'user', 'publist', array('tid' => $tid));
        $url = htmlspecialchars($url);

        if ($count > 0)
        {
          $waiting[] = array('title' => $vars["title:$tid"],
                             'count' => $count,
                             'url'   => $url,
                             'tid'   => $tid);
        }

        $result->Close();
      }
    }
  }

  if (count($waiting) > 0)
  {
    $smarty = new pnRender('pagesetter');
    $smarty->caching = 0;
    $smarty->assign('waiting', $waiting);
    $html = $smarty->fetch('pagesetter_waitingblock_display.html');
  }
  else
    return;

  $blockinfo['content'] = $html;

  return themesideblock($blockinfo);
}


/**
 * modify block settings
 */
function pagesetter_waitingblock_modify($blockinfo)
{
  if (!pnModAPILoad('pagesetter', 'admin'))
    return "Failed to load Pagesetter user API";
  if (!pnModAPILoad('pagesetter', 'workflow'))
    return "Failed to load Pagesetter user API";

  $vars = pnBlockVarsFromContent($blockinfo['content']);

  $pubTypes = pnModAPIFunc( 'pagesetter', 'admin', 'getPublicationTypes' );
  if ($pubTypes === false)
    return pagesetterErrorAPIGet();

  $setupInfo = array();

  foreach ($pubTypes as $pubType)
  {
    $tid = $pubType['id'];

    $pubInfo = pnModAPIFunc( 'pagesetter', 'admin', 'getPubTypeInfo',
                             array('tid' => $tid) );
    if ($pubInfo === false)
      return pagesetterErrorAPIGet();

    $workflowName = $pubInfo['publication']['workflow'];
    $workflow = pnModAPIFunc('pagesetter', 'workflow', 'load', array('workflow' => $workflowName) );
    if ($workflow === false)
      return pagesetterErrorAPIGet();

    $states = array();

    foreach ($workflow->getStates() as $stateName => $state)
    {
      $varName = "$tid:$stateName";
      $checked = (array_key_exists($varName,$vars) && $vars[$varName] == 1 ? "checked=\"1\"" : "");

      $states[] = array( 'name'     => $stateName, 
                         'title'    => $state->getTitle(),
                         'varName'  => $varName,
                         'checked'  => $checked);
    }

    $info = array( 'title'    => $pubInfo['publication']['title'],
                   'workflow' => array('title' => $workflow->getTitle(), 'name' => $workflowName),
                   'states'   => $states );

    $setupInfo[] = $info;
  }

  $smarty = new pnRender('pagesetter');
  $smarty->caching = 0;
  $smarty->assign('types', $setupInfo);
  $html = $smarty->fetch('pagesetter_waitingblock_modify.html');

  return $html;
}


/**
 * update block settings
 */
function pagesetter_waitingblock_update($blockinfo)
{
  if (!pnModAPILoad('pagesetter', 'admin'))
    return "Failed to load Pagesetter user API";
  if (!pnModAPILoad('pagesetter', 'workflow'))
    return "Failed to load Pagesetter user API";

  $pubTypes = pnModAPIFunc( 'pagesetter', 'admin', 'getPublicationTypes' );
  if ($pubTypes === false)
    return pagesetterErrorAPIGet();

  $pntable = pnDBGetTables();
  $pubColumn = $pntable['pagesetter_pubdata_column'];

  $vars = array();

  foreach ($pubTypes as $pubType)
  {
    $tid = $pubType['id'];

    $pubTable = pagesetterGetPubTableName($tid);
    
    $pubInfo = pnModAPIFunc( 'pagesetter', 'admin', 'getPubTypeInfo',
                             array('tid' => $tid) );
    if ($pubInfo === false)
      return pagesetterErrorAPIGet();

    $workflowName = $pubInfo['publication']['workflow'];
    $workflow = pnModAPIFunc('pagesetter', 'workflow', 'load', array('workflow' => $workflowName) );
    if ($workflow === false)
      return pagesetterErrorAPIGet();

    $whereClause = '';

    foreach ($workflow->getStates() as $stateName => $state)
    {
      $varName = "$tid:$stateName";
      $vars[$varName] = (pnVarCleanFromInput($varName) == '1' ? 1 : 0);
      $checked = ($vars[$varName] == 1);

      if ($checked)
      {
        if ($whereClause != '')
          $whereClause .= ' OR ';

        $whereClause .= "$pubColumn[approvalState] = '" . $stateName . "'";
      }
    }

    if ($whereClause != '')
    {
      $sql = "SELECT COUNT(*)
              FROM   $pubTable
              WHERE  $whereClause";

      $vars["sql:$tid"] = $sql;
      $vars["title:$tid"] = $pubInfo['publication']['title'];
    }
    else
      $vars["sql:$tid"] = null;
  }
  
  $blockinfo['content'] = pnBlockVarsToContent($vars);

  return $blockinfo;
}


?>
