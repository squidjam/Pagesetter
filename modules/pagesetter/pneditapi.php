<?php
// $Id: pneditapi.php,v 1.49 2007/02/08 21:30:41 jornlind Exp $
// =======================================================================
// Pagesetter by Jorn Lind-Nielsen (C) 2003.
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

// This file holds the API functions which are specific to editing of publications
// in the hope of reducing the code load time needed to display them only.

require_once("modules/pagesetter/common.php");
require_once("modules/pagesetter/common-edit.php");



function pagesetter_editapi_startWorkflow($args)
{
  global $uploadFilesForDelete;
  $uploadFilesForDelete = array();
}


function pagesetter_editapi_endWorkflow($args)
{
  global $uploadFilesForDelete;

  foreach ($uploadFilesForDelete as $filename => $dummy)
  {
    unlink($filename);
    unlink( str_replace('.dat', '-tmb.dat', $filename) );
  }
}


function pagesetterSmartyRES_GetTemplate($tpl_name, &$tpl_source, &$smarty_obj)
{
  $tid = (int)$tpl_name;
  $pubInfo = pnModAPIFunc( 'pagesetter', 'admin', 'getPubTypeInfo',
                               array('tid' => $tid) );
  if ($pubInfo === false)
    return false;

  $template = $pubInfo['publication']['defaultSubFolder'];

  $tpl_source = $template;
  return true;
}


function pagesetterSmartyRES_GetTimestamp($tpl_name, &$tpl_timestamp, &$smarty_obj)
{
  $tpl_timestamp = time();
  return true;
}


function pagesetterSmartyRES_GetSecure($tpl_name, &$smarty_obj)
{
  // assume all templates are secure
  return true;
}


function pagesetterSmartyRES_GetTrusted($tpl_name, &$smarty_obj)
{
  // not used for templates
}


function pagesetterExpandSubFolder($subFolder, $pubData, $tid)
{
  $smarty = new pnRender('pagesetter');

  $smarty->register_resource("pgfolder", array("pagesetterSmartyRES_GetTemplate",
                                               "pagesetterSmartyRES_GetTimestamp",
                                               "pagesetterSmartyRES_GetSecure",
                                               "pagesetterSmartyRES_GetTrusted"));
  //print_r($pubData); exit(0);
  $smarty->right_delimiter = '}';
  $smarty->left_delimiter = '{';
  $smarty->assign($pubData);
  $smarty->caching = 0;
  $folderName = $smarty->fetch("pgfolder:$tid");

  return $folderName;
}


function pagesetter_editapi_getPubGuppySpec($args)
{
  if (!isset($args['tid']))
    return pagesetterErrorApi(__FILE__, __LINE__, "Missing argument 'tid' in 'pagesetter_editapi_getPubGuppySpec'");
  if (!isset($args['isUserLayout']))
    return pagesetterErrorApi(__FILE__, __LINE__, "Missing argument 'isUserLayout' in 'pagesetter_editapi_getPubGuppySpec'");

  $tid = (int)$args['tid'];
  $isUserLayout = (bool)$args['isUserLayout'];

    // Check access
  if (!pnSecAuthAction(0, 'pagesetter::', "$tid::", ACCESS_READ))
    return pagesetterErrorApi(__FILE__, __LINE__, _PGNOAUTH);

  if (!pnModAPILoad('pagesetter', 'admin'))
    return pagesetterErrorApi(__FILE__, __LINE__, 'Failed to load Pagesetter admin API');

  $pubTypeInfo =  pnModAPIFunc( 'pagesetter', 'admin', 'getPubTypeInfo',
                                array('tid' => $tid) );

    // Build spec and layout
  $fieldSpec = array();
  $fieldLayout = array();
  $first = true;

  foreach ($pubTypeInfo['fields'] as $field)
  {
    $fieldInfo = pagesetterFieldTypesGet($field['type']);

    $fieldName = $field['name'];
    $spec = array('kind'      => $fieldInfo['fieldKind'],
                  'type'      => $fieldInfo['fieldType'],
                  'typeData'  => $field['typeData'],
                  'name'      => $fieldName,
                  'title'     => $field['title'],
                  'mandatory' => $field['isMandatory'],
                  'inUse'     => !$isUserLayout); // Not in use until seen in layout - when using user specific layout

    if (isset($fieldInfo['options']))
      $spec['options'] = $fieldInfo['options'];

    $layout = array( array('kind'   => 'title',
                           'title'  => $field['title'],
                           'name'   => $fieldName),
                     array('kind'   => $fieldInfo['layoutKind'],
                           'width'  => $fieldInfo['width'],
                           'name'   => $fieldName) );

      // Add field description as a popup hint on the field
    if (isset($field['description']))
      $spec['hint'] = $layout[1]['hint'] = $field['description'];

    if (isset($fieldInfo['view']))
      $layout[1]['view'] = $fieldInfo['view'];

    if (isset($fieldInfo['height']))
      $layout[1]['height'] = $fieldInfo['height'];

    if ($first)
      $layout[1]['initialFocus'] = true;
    $first = false;

    $fieldSpec[$fieldName] = $spec;
    $fieldLayout[] = $layout;
  }

  //echo "<pre>"; var_dump(array('fieldSpec' => $fieldSpec, 'fieldLayout'  => $fieldLayout)); echo "</pre>\n"; exit(0);

  return array('fieldSpec'    => $fieldSpec,
               'fieldLayout'  => $fieldLayout);
}


function pagesetter_editapi_createPub($args)
{
  if (!isset($args['tid']))
    return pagesetterErrorApi(__FILE__, __LINE__, "Missing argument 'tid' in 'pagesetter_editapi_createPub'");
  if (!isset($args['pubData']))
    return pagesetterErrorApi(__FILE__, __LINE__, "Missing argument 'pubData' in 'pagesetter_editapi_createPub'");

  $tid             = $args['tid'];
  $id              = (isset($args['id']) ? $args['id'] : null);
  $copyCreatedDate = (isset($args['copyCreatedDate']) ? $args['copyCreatedDate'] : false);
  $pubData         = $args['pubData'];

    // Check access
  if (!pnSecAuthAction(0, 'pagesetter::', "$tid::", pagesetterAccessAuthor))
    return pagesetterErrorApi(__FILE__, __LINE__, _PGNOAUTH);

  if (!pnModAPILoad('pagesetter', 'admin'))
    return pagesetterErrorApi(__FILE__, __LINE__, 'Failed to load Pagesetter admin API');

  $isNewPublication = ($id === null);

    // Get publication type info so we know which fields to update
  
  $pubInfo = pnModAPIFunc( 'pagesetter', 'admin', 'getPubTypeInfo',
                           array('tid' => $tid) );
  if ($pubInfo === false)
    return false;

  if (!pagesetterHasTopicAccess($pubInfo, $pubData['core_topic'], 'write'))
    return pagesetterErrorApi(__FILE__, __LINE__, 'You do not have write access to the selected topic');

  $pubFields = $pubInfo['fields'];

  list($dbconn) = pnDBGetConn();
  $pntable = pnDBGetTables();

    // Fetch or create pid
  if ($id == null)
  {
    $pid = pagesetterGetNextCount($dbconn, $pntable, "tid$tid");
    if ($pid === false)
      return false;
  }
  else
    $pid = $pubData['core_pid'];

    // Store information about newly created publication in a place where the
    // environment/calling functions can access it for further processing.
    // The values here are overwritten by the last executed workflow operation.
  global $pagesetterWorkflowResult;
  $pagesetterWorkflowResult['pid'] = $pid;

    // Create insert statement based on dynamic and static field data

  $pubTable = pagesetterGetPubTableName($tid);
  $pubColumn = &$pntable['pagesetter_pubdata_column'];
  $pubHeaderTable = $pntable['pagesetter_pubheader'];
  $pubHeaderColumn = $pntable['pagesetter_pubheader_column'];
  $revisionsTable = $pntable['pagesetter_revisions'];
	$modulevarsTable = $pntable['module_vars'];

    // Lock pub table due to the non-atomar fetching of revision count in one place and
    // updating of it in another
  if (!pagesetterLockTables($dbconn, array($pubTable,$pubHeaderTable,$revisionsTable,$modulevarsTable)))
    return false;

  $revision = pagesetterGetNextRevision($dbconn, $pubTable, $pubColumn, $pid);
  if ($revision === false)
    return false;

  if ($pid === false)
  {
    pagesetterUnlockTables($dbconn);
    return false;
  }

  if ($copyCreatedDate)
    $createdSQL = pagesetterFormatSQLDateTime($pubData['core_created']);
  else
    $createdSQL = "NOW()";

  $sql = "INSERT INTO $pubTable (
            $pubColumn[pid],
            $pubColumn[approvalState],
            $pubColumn[online],
            $pubColumn[revision],
            $pubColumn[topic],
            $pubColumn[showInMenu],
            $pubColumn[showInList],
            $pubColumn[author],
            $pubColumn[creatorID],
            $pubColumn[created],
            $pubColumn[lastUpdated],
            $pubColumn[publishDate],
            $pubColumn[expireDate],
            $pubColumn[language]";

  $dataSql = "";
  foreach ($pubFields as $fieldName => $fieldSpec)
  {
    $fieldName  = $fieldSpec['name'];
    $columnName = pagesetterGetPubColumnName($fieldSpec['id']);

    $sql .= ",\n  $columnName";

    if ($fieldSpec['type'] == pagesetterFieldTypeImageUpload || $fieldSpec['type'] == pagesetterFieldTypeUpload)
    {
      if ($pubData[$fieldName] != null)
      {
        $fieldData = pagesetterHandleFileUpload($tid, $pid, $revision, $fieldName, $pubData[$fieldName], $fieldSpec['type']);
        if ($fieldData === false)
        {
          pagesetterUnlockTables($dbconn);
          return false;
        }
      }
      else
        $fieldData = null;

      $dataSql .= ",\n  '" . pnVarPrepForStore($fieldData) . "'";
    }
    else if ($pubData[$fieldName] == null)
      $dataSql .= ",\n  NULL";
    else
      $dataSql .= ",\n  '" . pnVarPrepForStore($pubData[$fieldName]) . "'";
  }

  $sql .= ")
           VALUES (
            $pid,
            '" . pnVarPrepForStore($pubData['core_approvalState']) . "',
            " . (int)$pubData['core_online'] . ",
            $revision,
            " . (int)$pubData['core_topic'] . ",
            " . (int)$pubData['core_showInMenu'] . ",
            " . (int)$pubData['core_showInList'] . ",
            '" . pnVarPrepForStore($pubData['core_author']) . "',
            " . (int)$pubData['core_creatorID'] . ",
            $createdSQL,
            NOW(),
            " . pagesetterSqlNullCheck($pubData['core_publishDate']) . ",
            " . pagesetterSqlNullCheck($pubData['core_expireDate']) . ",
            '" . pnVarPrepForStore($pubData['core_language']) . "'";

  $sql .= "$dataSql)";

    // Execute the SQL
  //echo "copyCreatedDate = $copyCreatedDate<br/>\n";
  //echo "<pre>"; print_r($pubData); print_r($pubInfo); echo "\n</pre>";
  //echo "<pre>$sql</pre>"; exit(0);
  $dbconn->execute($sql);

  if ($dbconn->errorNo() != 0)
  {
      $msg = $dbconn->errorMsg();
      pagesetterUnlockTables($dbconn);
      return pagesetterErrorApi(__FILE__, __LINE__, "\"createPub\" failed: $msg while executing: $sql");
  }

  $id = $dbconn->Insert_ID();


    // Add pub. header
  if ($isNewPublication)
  {
    $sql = "INSERT INTO $pubHeaderTable (
              $pubHeaderColumn[tid],
              $pubHeaderColumn[pid],
              $pubHeaderColumn[hitCount],
              $pubHeaderColumn[onlineID],
              $pubHeaderColumn[deleted])
            VALUES (
              " . (int)$tid . ",
              " . (int)$pid . ",
              0,
              " . (int)$pid . ",
              0)";

    $dbconn->execute($sql);

    if ($dbconn->errorNo() != 0)
    {
      $msg = $dbconn->errorMsg();
      pagesetterUnlockTables($dbconn);
      return pagesetterErrorApi(__FILE__, __LINE__, "\"createPub\" failed: $msg while executing: $sql");
    }
  }

    // Add revision log
  $ok = pnModAPIFunc( 'pagesetter', 'edit', 'createRevisionLogEntry',
                      array('tid'         => $tid,
                            'pid'         => $pid,
                            'id'          => $id,
                            'prevVersion' => pnVarPrepForStore($pubData['core_revision']),
                            'user'        => pnUserGetVar('uid')) );

  if (!$ok)
  {
    pagesetterUnlockTables($dbconn);
    return false;
  }

  pagesetterUnlockTables($dbconn);

    // Inform hooks
  if ($isNewPublication)
  {
    if ($pubInfo['publication']['enableHooks'])
      pnModCallHooks('item', 'create', pagesetterGetPublicationUniqueID($tid,$pid), 'tpid');

      // Inform Guppy plugins
    pnModAPIFunc( 'pagesetter', 'edit', 'invokePluginEvents',
                  array('eventName' => 'OnPublicationCreated',
                        'tid'       => $tid,
                        'pid'       => $pid,
                        'id'        => $id,
                        'pubInfo'   => $pubInfo) );
  } else {
      // Inform Guppy plugins
    pnModAPIFunc( 'pagesetter', 'edit', 'invokePluginEvents',
                  array('eventName' => 'OnPublicationUpdated',
                        'tid'       => $tid,
                        'pid'       => $pid,
                        'id'        => $id,
                        'pubInfo'   => $pubInfo) );
  }
  return array('tid' => $tid, 'pid' => $pid, 'id' => $id);
}


function pagesetterHandleFileUpload($tid, $pid, $revision, $fieldName, &$uploadInfo, $uploadType)
{
  // It is assumed that Guppy has already moved the uploaded file to a safe place.
  // So we just have to move that (*not* using "move_uploaded_file") to a Pagesetter location

  if ($uploadInfo == 'delete')
  {
    return NULL;
  }

  $uploadDir = pnModGetVar('pagesetter','uploadDirDocs');

  $uploadFilename = pagesetterGetUploadFilename($pid, $tid, $revision, $fieldName);
  $uploadFilePath = $uploadDir . '/' . $uploadFilename;

  if (isset($uploadInfo['guppy_path']))
  {
    // User has uploaded something - move guppy file to Pagesetter location

    // Old file may be pending a delete, so just remove it now
    unlink($uploadFilePath);
    unlink( str_replace('.dat', '-tmb.dat', $uploadFilePath) );
    global $uploadFilesForDelete;
    unset($uploadFilesForDelete[$uploadFilePath]);

    if (!rename($uploadInfo['guppy_path'], $uploadFilePath))
      return pagesetterErrorApi(__FILE__, __LINE__, "Could not move uploaded file '$uploadInfo[path]' to '$uploadFilePath'");

    // Store the upload information in *one* database field
    // - make sure ['name'] is at the end. If cut-off hapens then only the original name will be cut off.
    $fieldData = $uploadInfo['type']  . '|' . $uploadInfo['size'] . '|' . $uploadFilename  . '|' . $uploadInfo['name'];
  }
  else
  if (!isset($uploadInfo['tmpname'])  &&  !isset($uploadInfo['guppy_path']))
  {
    // No previous upload existed, and user has *not* uploaded anything

    $fieldData = null;
  }
  else
  if (isset($uploadInfo['tmpname'])  &&  !isset($uploadInfo['guppy_path']))
  {
    // Previous upload existed, and user has *not* uploaded anything
    // Create duplicate for new revision (future version should use reference counting).

    $oldFilePath = $uploadDir . '/' . $uploadInfo['tmpname'];

    // Do not copy if identical files (happens when revision control is off)
    if ($oldFilePath != $uploadFilePath)
    {
      if (!copy($oldFilePath, $uploadFilePath)) // FIXME: language
        return pagesetterErrorApi(__FILE__, __LINE__, "Could not copy uploaded file '$oldFilePath' to '$uploadFilePath'");
    }
    else
    {
      // Identical files - make sure the uploaded file is not deleted by the "moveToDepot" operation
      global $uploadFilesForDelete;
      unset($uploadFilesForDelete[$oldFilePath]);
    }

    // Store the upload information in *one* database field
    // - make sure ['name'] is at the end. If cut-off hapens then only the original name will be cut off.
    $fieldData = $uploadInfo['type']  . '|' . $uploadInfo['size'] . '|' . $uploadFilename  . '|' . $uploadInfo['name'];
  }

  if ($uploadType == pagesetterFieldTypeImageUpload)
  {
    $thumbnailFilename = pagesetterGetThumbnailFilename($pid, $tid, $revision, $fieldName);
    $thumbnailFilePath = $uploadDir . '/' . $thumbnailFilename;

    $ok = pagesetterCreateThumbnail($uploadFilePath, $thumbnailFilePath, $uploadInfo['type']);
    if ($ok === false)
      return false;
  }

  return $fieldData;
}


function pagesetterCreateThumbnail($uploadFilePath, $thumbnailFilePath, $mimeType)
{
  if (!pnModAPILoad('pagesetter', 'image'))
    return pagesetterErrorApi(__FILE__, __LINE__, 'Failed to load Pagesetter image API');

  $ok = pnModAPIFunc( 'pagesetter', 'image', 'createThumbnailFromFile',
                      array('imageFilePath'     => $uploadFilePath, 
                            'thumbnailFilePath' => $thumbnailFilePath,
                            'mimeType'          => $mimeType) );

  return $ok;
}


function pagesetter_editapi_createNewRevision($args)
{
  if (!isset($args['id']))
    return pagesetterErrorApi(__FILE__, __LINE__, "Missing argument 'id' in 'pagesetter_editapi_createNewRevision'");

  return pagesetter_editapi_createPub($args);
}


function pagesetter_editapi_updatePub($args)
{
  if (!isset($args['tid']))
    return pagesetterErrorApi(__FILE__, __LINE__, "Missing argument 'tid' in 'pagesetter_editapi_updatePub'");
  if (!isset($args['id']))
    return pagesetterErrorApi(__FILE__, __LINE__, "Missing argument 'id' in 'pagesetter_editapi_updatePub'");
  if (!isset($args['pubData']))
    return pagesetterErrorApi(__FILE__, __LINE__, "Missing argument 'pubData' in 'pagesetter_editapi_updatePub'");

  $pubData  = $args['pubData'];
  $tid      = $args['tid'];
  $id       = $args['id'];
  $pid      = $pubData['core_pid'];

    // Get publication type info so we know which fields to update
  $pubInfo = pnModAPIFunc( 'pagesetter', 'admin', 'getPubTypeInfo',
                           array('tid' => $tid) );
  if ($pubInfo === false)
    return false;

    // Normal access check
  if (!pnSecAuthAction(0, 'pagesetter::', "$tid:$pid:", pagesetterAccessAuthor))
    return pagesetterErrorPage(__FILE__, __LINE__, _PGNOAUTH);

    // Now check for "edit access to own" being enabled, otherwise "editor" access is required
  if (!($pubInfo['publication']['enableEditOwn']  ||  pnSecAuthAction(0, 'pagesetter::', "$tid:$pid:", pagesetterAccessEditor)))
    return pagesetterErrorPage(__FILE__, __LINE__, _PGNOAUTH);

  if (!pnModAPILoad('pagesetter', 'admin'))
    return pagesetterErrorApi(__FILE__, __LINE__, 'Failed to load Pagesetter admin API');

  $pubFields = $pubInfo['fields'];

  if (!pagesetterHasTopicAccess($pubInfo, $pubData['core_topic'], 'write'))
    return pagesetterErrorApi(__FILE__, __LINE__, 'You do not have write access to the selected topic');

  list($dbconn) = pnDBGetConn();
  $pntable = pnDBGetTables();

    // Create update statement based on dynamic and static field data
    // Iterates through the passed fields (in $args), not the actual pub. type def.

  $pubTable = pagesetterGetPubTableName($tid);
  $pubColumn = $pntable['pagesetter_pubdata_column'];

  $sql = "UPDATE $pubTable SET
            $pubColumn[approvalState] = '" . pnVarPrepForStore($pubData[core_approvalState]) . "',
            $pubColumn[topic] = " . (int)$pubData[core_topic] . ",
            $pubColumn[showInMenu] = " . (int)$pubData[core_showInMenu] . ",
            $pubColumn[showInList] = " . (int)$pubData[core_showInList] . ",
            $pubColumn[author] = '" . pnVarPrepForStore($pubData[core_author]) . "',
            $pubColumn[lastUpdated] = NOW(),
            $pubColumn[publishDate] = " . pagesetterSqlNullCheck($pubData[core_publishDate]) . ",
            $pubColumn[expireDate] = " . pagesetterSqlNullCheck($pubData[core_expireDate]) . ",
            $pubColumn[language] = '" . pnVarPrepForStore($pubData[core_language]) . "'";

  foreach ($pubFields as $fieldName => $fieldSpec)
  {
    $fieldName  = $fieldSpec['name'];
    $columnName = pagesetterGetPubColumnName($fieldSpec['id']);

    if (gettype($pubData[$fieldName]) == 'boolean')
    {
      $sql .= ",\n  $columnName = " . ($pubData[$fieldName] ? '1' : '0');
    }
    else if ($fieldSpec['type'] == pagesetterFieldTypeImageUpload  ||  $fieldSpec['type'] == pagesetterFieldTypeUpload)
    {
      if ($pubData[$fieldName] != null)
      {
        $revision = $pubData['core_revision'];
        $fieldData = pagesetterHandleFileUpload($tid, $pid, $revision, $fieldName, $pubData[$fieldName], $fieldSpec['type']);

        //$uploadInfo = &$pubData[$fieldName];
        //$fieldData = $uploadInfo['type']  . '|' . $uploadInfo['size'] . '|' . $uploadInfo['tmpname']  . '|' . $uploadInfo['name'];
        $sql .= ",\n  $columnName = '" . pnVarPrepForStore($fieldData) . "'";
      }
    }
    else if ($pubData[$fieldName] === null)
      $sql .= ",\n  $columnName = NULL";
    else
      $sql .= ",\n  $columnName = '" . pnVarPrepForStore($pubData[$fieldName]) . "'";
  }

  $sql .= "\nWHERE $pubColumn[id] = " . (int)$id;

    // Execute the SQL
  // echo "<pre>$sql</pre>"; exit(0);
  $result = $dbconn->execute($sql);

  if ($dbconn->errorNo() != 0)
    return pagesetterErrorApi(__FILE__, __LINE__, '"updatePage" failed: ' 
                                                  . $dbconn->errorMsg() . " while executing: $sql");   

  pagesetterSmartyClearCache($tid, $pid);

    // Inform Guppy plugins
  pnModAPIFunc( 'pagesetter', 'edit', 'invokePluginEvents',
                array('eventName' => 'OnPublicationUpdated',
                      'tid'       => $tid,
                      'pid'       => $pid,
                      'id'        => $id,
                      'pubInfo'   => $pubInfo) );

  return true;
}


function pagesetter_editapi_updateOnlineStatus($args)
{
  if (!isset($args['tid']))
    return pagesetterErrorApi(__FILE__, __LINE__, "Missing argument 'tid' in 'pagesetter_editapi_updateOnlineStatus'");
  if (!isset($args['pid']))
    return pagesetterErrorApi(__FILE__, __LINE__, "Missing argument 'pid' in 'pagesetter_editapi_updateOnlineStatus'");
  if (!isset($args['id']))
    return pagesetterErrorApi(__FILE__, __LINE__, "Missing argument 'id' in 'pagesetter_editapi_updateOnlineStatus'");
  if (!isset($args['online']))
    return pagesetterErrorApi(__FILE__, __LINE__, "Missing argument 'online' in 'pagesetter_editapi_updateOnlineStatus'");

  $tid    = $args['tid'];
  $pid    = $args['pid'];
  $id     = $args['id'];
  $online = $args['online'];

  $pubInfo = pnModAPIFunc( 'pagesetter', 'admin', 'getPubTypeInfo',
                           array('tid' => $tid) );
  // Check access
  if (!$pubInfo['publication']['enableEditOwn']  && !pnSecAuthAction(0, 'pagesetter::', "$tid:$pid:", pagesetterAccessModerator))    return pagesetterErrorApi(__file__, __line__, _PGNOAUTH);
  
  list($dbconn) = pnDBGetConn();
  $pntable = pnDBGetTables();

  $pubTable = pagesetterGetPubTableName($tid);
  $pubColumn = $pntable['pagesetter_pubdata_column'];

  if ($online)
  {
      // If setting this version online then remove the other online version
    $sql =   "UPDATE $pubTable SET $pubColumn[online] = 0 WHERE $pubColumn[online] = 1 "
           . "AND $pubColumn[pid] = " . (int)$pid;

  }
  else
  {
      // If setting this version offline then do so (but don't touch others)
    $sql = "UPDATE $pubTable SET $pubColumn[online] = 0 WHERE $pubColumn[id] = " . (int)$id;

  }

    // Execute the SQL
  //echo "<pre>$sql</pre>"; //exit(0);
  $result = $dbconn->execute($sql);

  if ($dbconn->errorNo() != 0)
    return pagesetterErrorApi(__FILE__, __LINE__, '"updateOnlineStatus" failed: ' 
                                                    . $dbconn->errorMsg() . " while executing: $sql");

    // If setting online then set it so
  if ($online)
  {
    $sql = "UPDATE $pubTable SET $pubColumn[online] = 1 WHERE $pubColumn[id] = " . (int)$id;

      // Execute the SQL
    //echo "<pre>$sql</pre>"; //exit(0);
    $result = $dbconn->execute($sql);

    if ($dbconn->errorNo() != 0)
      return pagesetterErrorApi(__FILE__, __LINE__, '"updateOnlineStatus" failed: ' 
                                                    . $dbconn->errorMsg() . " while executing: $sql");   
  }

  return true;
}


function pagesetter_editapi_moveToDepot($args)
{
  return pagesetterDepotTransport($args, 'toDepot');
}


function pagesetter_editapi_moveFromDepot($args)
{
  return pagesetterDepotTransport($args, 'fromDepot');
}


function pagesetterDepotTransport($args, $direction)
{
  if (!isset($args['tid']))
    return pagesetterErrorApi(__FILE__, __LINE__, "Missing argument 'tid' in 'pagesetterDepotTransport'");

  $tid = $args['tid'];
  $id  = $args['id'];

  if (!pnModAPILoad('pagesetter', 'admin'))
    return pagesetterErrorApi(__FILE__, __LINE__, 'Failed to load Pagesetter admin API');

  $pubInfo = pnModAPIFunc( 'pagesetter', 'admin', 'getPubTypeInfo',
                           array('tid' => $tid) );

  if ($pubInfo === false)
    return false;

  if ($pubInfo['publication']['enableTopicAccess'])
  {
    if (!pnModAPILoad('pagesetter', 'user'))
      return pagesetterErrorApi(__FILE__, __LINE__, 'Failed to load Pagesetter user API');

    $pub = pnModAPIFunc( 'pagesetter',
                         'user',
                         'getPub',
                         array('tid'    => $tid,
                               'id'     => $id,
                               'format' => 'database') );
    if ($pub === false)
      return false;

      // Don't check if pub. doesn't exist
    if ($pub !== true)
    {
      if (!pagesetterHasTopicAccess($pubInfo, $pub['core_topic'], 'write'))
        return pagesetterErrorApi(__FILE__, __LINE__, 'You do not have write access to the selected topic');
    }
  }

  if ($pubInfo['publication']['enableRevisions']  ||  $direction != 'toDepot')
    return pagesetterDepotTransportReal($args, $direction);
  else
    return pagesetterDepotTransportDelete($args);
}


function pagesetterDepotTransportReal($args, $direction)
{
  if (!isset($args['tid']))
    return pagesetterErrorApi(__FILE__, __LINE__, "Missing argument 'tid' in 'pagesetterDepotTransportReal'");
  if (!isset($args['pid']))
    return pagesetterErrorApi(__FILE__, __LINE__, "Missing argument 'pid' in 'pagesetterDepotTransportReal'");
  if (!isset($args['id']))
    return pagesetterErrorApi(__FILE__, __LINE__, "Missing argument 'id' in 'pagesetterDepotTransportReal'");

  $tid        = $args['tid'];
  $pid        = $args['pid'];
  $id         = $args['id'];
  $state      = $args['state'];
  $moveOthers = (isset($args['moveOthers']) ? $args['moveOthers'] : false);

    // Check access
  if (!pnSecAuthAction(0, 'pagesetter::', "$tid:$pid:", pagesetterAccessAuthor))
    return pagesetterErrorApi(__FILE__, __LINE__, _PGNOAUTH);

  list($dbconn) = pnDBGetConn();
  $pntable = pnDBGetTables();

  $pubTable = pagesetterGetPubTableName($tid);
  $pubColumn = $pntable['pagesetter_pubdata_column'];

  if ($direction == 'toDepot')
    $inDepot = '1';
  else
    $inDepot = '0';

  if (isset($state)  &&  $state != null)
    $stateSQL = ", $pubColumn[approvalState] = '" . pnVarPrepForStore($state) . "'";

  $idOperator = ($moveOthers ? '!=' : '=');
  $sql = "UPDATE $pubTable SET $pubColumn[inDepot] = $inDepot $stateSQL WHERE $pubColumn[id] $idOperator " 
         . (int)$id . " AND $pubColumn[pid] = " . (int)$pid;

    // Execute the SQL
  //echo "<pre>$sql</pre>"; exit(0);
  $result = $dbconn->execute($sql);

  if ($dbconn->errorNo() != 0)
    return pagesetterErrorApi(__FILE__, __LINE__, '"pagesetterDepotTransportReal" failed: ' 
                                                    . $dbconn->errorMsg() . " while executing: $sql");
  return true;
}

  // This one is used to delete old revisions instead of moving them to the depot
  // - which should be done when revision history is disabled
function pagesetterDepotTransportDelete($args)
{
  if (!isset($args['tid']))
    return pagesetterErrorApi(__FILE__, __LINE__, "Missing argument 'tid' in 'pagesetterDepotTransportDelete'");
  if (!isset($args['pid']))
    return pagesetterErrorApi(__FILE__, __LINE__, "Missing argument 'pid' in 'pagesetterDepotTransportDelete'");
  if (!isset($args['id']))
    return pagesetterErrorApi(__FILE__, __LINE__, "Missing argument 'id' in 'pagesetterDepotTransportDelete'");

  $tid        = (int)$args['tid'];
  $pid        = (int)$args['pid'];
  $id         = (int)$args['id'];
  $moveOthers = (isset($args['moveOthers']) ? $args['moveOthers'] : false);

  if ($moveOthers)
  {
      // Delegate to another function (with "exceptId" set to $id)
    return pagesetterDeleteAllRevisions($tid, $pid, $id);
  }

    // Check access
  if (!pnSecAuthAction(0, 'pagesetter::', "$tid:$pid:", pagesetterAccessAuthor))
    return pagesetterErrorApi(__FILE__, __LINE__, _PGNOAUTH);

  list($dbconn) = pnDBGetConn();
  $pntable = pnDBGetTables();

  $pubTable = pagesetterGetPubTableName($tid);
  $pubColumn = $pntable['pagesetter_pubdata_column'];


    // Make sure optional uploads are also removed 
    // Must be done before the delete below, since it accesses the DB
  if (pagesetterEraseUpload($pid, $tid, $id, null) === false)
    return false;

    // Delete from database

  $sql = "DELETE FROM $pubTable
          WHERE $pubColumn[id] = $id";

  //echo "<pre>$sql</pre>"; exit(0);
  $result = $dbconn->execute($sql);

  if ($dbconn->errorNo() != 0)
    return pagesetterErrorApi(__FILE__, __LINE__, '"pagesetterDepotTransportDelete" failed: ' 
                                                    . $dbconn->errorMsg() . " while executing: $sql");

  return true;
}


function pagesetter_editapi_getRevisions($args)
{
  if (!isset($args['tid'])  ||  $args['tid']=='')
    return pagesetterErrorApi(__FILE__, __LINE__, "Missing argument 'tid' in 'pagesetter_editapi_getRevisions'", false);
  if (!isset($args['pid'])  ||  $args['pid']=='')
    return pagesetterErrorApi(__FILE__, __LINE__, "Missing argument 'pid' in 'pagesetter_editapi_getRevisions'", false);

  $tid = $args['tid'];
  $pid = $args['pid'];

    // Check access
  if (!pnSecAuthAction(0, 'pagesetter::', "$tid::", pagesetterAccessEditor))
    return pagesetterErrorApi(__FILE__, __LINE__, _PGNOAUTH);

  if (!pnModAPILoad('pagesetter', 'admin'))
    return pagesetterErrorApi(__FILE__, __LINE__, 'Failed to load Pagesetter admin API');

  if (!pnModAPILoad('pagesetter', 'workflow'))
    return pagesetterErrorApi(__FILE__, __LINE__, 'Failed to load Pagesetter workflow API');

    // Get publication info for title field name

  $pubInfo =  pnModAPIFunc( 'pagesetter', 'admin', 'getPubTypeInfo',
                            array('tid' => $tid) );

  if ($pubInfo === false)
    return false;

  $titleColumn = pagesetterGetPubColumnName($pubInfo['publication']['titleFieldID']);


  $workflowName = $pubInfo['publication']['workflow'];
  $workflow = pnModAPIFunc('pagesetter', 'workflow', 'load', array('workflow' => $workflowName) );
  if ($workflow === false)
    return false;


    // Make database query

  list($dbconn) = pnDBGetConn();
  $pntable = pnDBGetTables();

  $pubTable  = pagesetterGetPubTableName($tid);
  $pubColumn = $pntable['pagesetter_pubdata_column'];
  $revisionsTable = $pntable['pagesetter_revisions'];
  $revisionsColumn = $pntable['pagesetter_revisions_column'];

  $sql = "SELECT   $pubTable.$pubColumn[id],
                   $titleColumn,
                   $pubColumn[revision],
                   $pubColumn[approvalState],
                   $pubColumn[online],
                   $pubColumn[inDepot],
                   $revisionsColumn[previousVersion],
                   $revisionsColumn[user],
                   $revisionsColumn[timestamp]
          FROM     $pubTable
                   LEFT JOIN $revisionsTable ON $revisionsColumn[tid] = " . (int)$tid . "
                                            AND $revisionsColumn[id] = $pubTable.$pubColumn[id]
          WHERE    $pubTable.$pubColumn[pid] = " . (int)$pid . "
          ORDER BY $pubColumn[revision] DESC";

  //echo "<pre>$sql</pre>";// exit(0);

  $result = $dbconn->execute($sql);

  if ($dbconn->errorNo() != 0)
    return pagesetterErrorApi(__FILE__, __LINE__, '"getRevisions" failed: ' 
                                                  . $dbconn->errorMsg() . " while executing: $sql");   

  for ($revisions = array(); !$result->EOF; $result->MoveNext())
  {
    $approvalTitle = pagesetterApprovalState2String($result->fields[3], $workflow);

    $revisions[] = array('id'            => $result->fields[0],
                         'title'         => $result->fields[1],
                         'revision'      => $result->fields[2],
                         'approvalTitle' => $approvalTitle,
                         'online'        => $result->fields[4],
                         'inDepot'       => $result->fields[5],
                         'prevRevision'  => $result->fields[6],
                         'user'          => pnUserGetVar('uname',$result->fields[7]),
                         'timestamp'     => $result->fields[8]);
  }

  return $revisions;
}


function pagesetter_editapi_getEmptyPub($args)
{
  if (!isset($args['tid']))
    return pagesetterErrorApi(__FILE__, __LINE__, "Missing argument 'tid' in 'pagesetter_editapi_getEmptyPub'");

  $tid = $args['tid'];

    // Check access
  if (!pnSecAuthAction(0, 'pagesetter::', "$tid::", pagesetterAccessAuthor))
    return pagesetterErrorApi(__FILE__, __LINE__, _PGNOAUTH);

  $uname = pnUserGetVar('uname');
  $name = pnUserGetVar('name');
  if (empty($name))
    $name = $uname;

    // Extract set_xxx values from URL into defaults

  if (!pnModAPILoad('pagesetter', 'admin'))
    return pagesetterErrorApi(__FILE__, __LINE__, 'Failed to load Pagesetter admin API');

  $pubInfo = pnModAPIFunc( 'pagesetter', 'admin', 'getPubTypeInfo',
                           array('tid' => $tid) );

  if ($pubInfo === false)
    return false;

  $defaults = array();

  foreach ($pubInfo['fields'] as $field)
  {
    $fieldName = "set_$field[name]";
    $columnName = pagesetterGetPubColumnName($field['id']);

    $val = pnVarCleanFromInput($fieldName);

    if (isset($val))
      $defaults[$field['name']] = pagesetterCoerceFieldValue($field['type'], $val);
  }

    // Take special care of topic
  $topic = pnVarCleanFromInput('topicid');
  if ($topic != '')
    $topic = (int)$topic;
  else
    $topic = -1;

    // Combine standard defaults and URL values

  return array( 'core_author'        => $name,
                'core_creatorID'     => pnUserGetVar('uid'),
                'core_creator'       => $uname,
                'core_approvalState' => null,
                'core_approvalTitle' => '',
                'core_topic'         => $topic,
                'core_showInList'    => true,
                'core_showInMenu'    => true,
                'core_online'        => false,
                'core_revision'      => 0,
                'core_language'      => 'x_all')
         + $defaults;
}


function pagesetter_editapi_cacheClearAll($args)
{
    // Check access
  if (!pnSecAuthAction(0, 'pagesetter::', '::', pagesetterAccessEditor))
    return pagesetterErrorApi(__FILE__, __LINE__, _PGNOAUTH);

  $smarty = new pnRender('pagesetter');

  $smarty->clear_all_cache();

  return true;
}


function pagesetter_editapi_eraseRevision($args)
{
  if (!isset($args['tid'])  ||  $args['tid']=='')
    return pagesetterErrorApi(__FILE__, __LINE__, "Missing argument 'tid' in 'pagesetter_editapi_eraseRevision'");
  if (!isset($args['id'])  ||  $args['id']=='')
    return pagesetterErrorApi(__FILE__, __LINE__, "Missing argument 'id' in 'pagesetter_editapi_eraseRevision'");
  if (!isset($args['pid'])  ||  $args['pid']=='')
    return pagesetterErrorApi(__FILE__, __LINE__, "Missing argument 'pid' in 'pagesetter_editapi_eraseRevision'");

  $tid = $args['tid'];
  $id  = $args['id'];
  $pid = $args['pid'];

    // Check access
  if (!pnSecAuthAction(0, 'pagesetter::', "$tid::", pagesetterAccessEditor))
    return pagesetterErrorApi(__FILE__, __LINE__, _PGNOAUTH);

    // Make sure optional uploads are also removed 
    // This must be done first, since it makes a DB lookup on the pub.
  if (pagesetterEraseUpload($pid, $tid, $id, null) == false)
    return false;

  list($dbconn) = pnDBGetConn();
  $pntable = pnDBGetTables();

  $pubTable = pagesetterGetPubTableName($tid);
  $pubColumn = $pntable['pagesetter_pubdata_column'];

  $sql = "DELETE FROM $pubTable 
          WHERE  $pubColumn[id] = " . (int)$id;
  
  //echo "<pre>$sql</pre>"; exit(0);
  $result = $dbconn->execute($sql);

  if ($dbconn->errorNo() != 0)
    return pagesetterErrorApi(__FILE__, __LINE__, '"eraseRevision" failed: ' 
                                                  . $dbconn->errorMsg() . " while executing: $sql"); 
  
  pagesetterSmartyClearCache($tid, $pid);

    // Now that one revision has been erased we need to see if there are more revisions. If not then
    // we must also removed the pub. header associated with the pub.

  $sql = "SELECT COUNT(*)
          FROM   $pubTable 
          WHERE  $pubColumn[pid] = " . (int)$pid;
  
  //echo "<pre>$sql</pre>"; exit(0);
  $result = $dbconn->execute($sql);

  if ($dbconn->errorNo() != 0)
    return pagesetterErrorApi(__FILE__, __LINE__, '"eraseRevision" failed: ' 
                                                  . $dbconn->errorMsg() . " while executing: $sql"); 
  $count = $result->fields[0];
  $result->Close();

  if ($count == 0)
  {
    $pubHeaderTable = $pntable['pagesetter_pubheader'];
    $pubHeaderColumn = $pntable['pagesetter_pubheader_column'];

    $sql = "DELETE FROM $pubHeaderTable 
            WHERE  $pubHeaderColumn[pid] = " . (int)$pid . " AND
                   $pubHeaderColumn[tid] = " . (int)$tid;
    
    //echo "<pre>$sql</pre>"; exit(0);
    $result = $dbconn->execute($sql);

    if ($dbconn->errorNo() != 0)
      return pagesetterErrorApi(__FILE__, __LINE__, '"eraseRevision" failed: ' 
                                                    . $dbconn->errorMsg() . " while executing: $sql"); 
  }

  return true;
}


function pagesetterEraseUpload($pid, $tid, $id, $revision)
{
  if (!pnModAPILoad('pagesetter', 'admin'))
    return pagesetterErrorApi(__FILE__, __LINE__, 'Failed to load Pagesetter admin API');

  if ($revision == null)
  {
      // No revision available - fetch from database via ID

    list($dbconn) = pnDBGetConn();
    $pntable = pnDBGetTables();

    $pubTable = pagesetterGetPubTableName($tid);
    $pubColumn = $pntable['pagesetter_pubdata_column'];

    $sql = "SELECT $pubColumn[revision] FROM $pubTable 
            WHERE  $pubColumn[id] = " . (int)$id;
    
    //echo "<pre>$sql</pre>"; exit(0);
    $result = $dbconn->execute($sql);

    if ($dbconn->errorNo() != 0)
      return pagesetterErrorApi(__FILE__, __LINE__, '"EraseUpload" failed: ' 
                                                    . $dbconn->errorMsg() . " while executing: $sql"); 
    if ($result->EOF)
      return pagesetterErrorApi(__FILE__, __LINE__, '"EraseUpload" failed: no revisions matching id ' . $id); 

    $revision = $result->fields[0];
    $result->Close();
  }

    // Find upload fields and remove files

  $pubInfo = pnModAPIFunc( 'pagesetter', 'admin', 'getPubTypeInfo',
                           array('tid' => $tid) );
  if ($pubInfo === false)
    return false;

  $pubFields = $pubInfo['fields'];
  $uploadDir = pnModGetVar('pagesetter','uploadDirDocs');

  foreach ($pubFields as $fieldName => $fieldSpec)
  {
    if ($fieldSpec['type'] == pagesetterFieldTypeImageUpload || $fieldSpec['type'] == pagesetterFieldTypeUpload)
    {
      $fieldName  = $fieldSpec['name'];

      $uploadFilename = pagesetterGetUploadFilename($pid, $tid, $revision, $fieldName);
      $uploadFilePath = $uploadDir . '/' . $uploadFilename;

      // Enqueue file for deletion (later workflow operation may want to undo this deletion)
      global $uploadFilesForDelete;
      $uploadFilesForDelete[$uploadFilePath] = 1;
    }
  }

  return true;
}


function pagesetter_editapi_deletePub($args)
{
  if (!isset($args['tid']))
    return pagesetterErrorApi(__FILE__, __LINE__, "Missing argument 'tid' in 'pagesetter_editapi_deletePub'");
  if (!isset($args['pid']))
    return pagesetterErrorApi(__FILE__, __LINE__, "Missing argument 'pid' in 'pagesetter_editapi_deletePub'");

  $tid = $args['tid'];
  $pid = $args['pid'];
  $id  = $args['id'];

  if (!pnModAPILoad('pagesetter', 'admin'))
    return pagesetterErrorApi(__FILE__, __LINE__, 'Failed to load Pagesetter admin API');

  $pubInfo = pnModAPIFunc( 'pagesetter', 'admin', 'getPubTypeInfo',
                           array('tid' => $tid) );

  if ($pubInfo === false)
    return false;

  if ($pubInfo['publication']['enableTopicAccess'])
  {
    if (!pnModAPILoad('pagesetter', 'user'))
      return pagesetterErrorApi(__FILE__, __LINE__, 'Failed to load Pagesetter user API');

    $pub = pnModAPIFunc( 'pagesetter',
                         'user',
                         'getPub',
                         array('tid'    => $tid,
                               'id'     => $id,
                               'format' => 'database') );
    if ($pub === false)
      return false;

      // Don't check if pub. doesn't exist
    if ($pub !== true)
    {
      if (!pagesetterHasTopicAccess($pubInfo, $pub['core_topic'], 'write'))
        return pagesetterErrorApi(__FILE__, __LINE__, 'You do not have write access to the selected topic');
    }
  }

  if ($pubInfo['publication']['enableRevisions'])
    $ok = pagesetterDeletePub($args, true);
  else
    $ok = pagesetterDeleteAllRevisions($tid, $pid, null);

    // Inform Guppy plugins
  if ($ok)
  {
    pnModAPIFunc( 'pagesetter', 'edit', 'invokePluginEvents',
                  array('eventName' => 'OnPublicationDeleted',
                        'tid'       => $tid,
                        'pid'       => $pid,
                        'id'        => $id,
                        'pubInfo'   => $pubInfo) );
  }

  return $ok;
}


function pagesetter_editapi_unDeletePub($args)
{
  return pagesetterDeletePub($args, false);
}


  // Delete or undelete publication (using depot)
function pagesetterDeletePub($args, $doDelete)
{
  if (!isset($args['tid'])  ||  $args['tid']=='')
    return pagesetterErrorApi(__FILE__, __LINE__, "Missing argument 'tid' in 'pagesetter_editapi_deletePub'");
  if (!isset($args['pid'])  ||  $args['pid']=='')
    return pagesetterErrorApi(__FILE__, __LINE__, "Missing argument 'pid' in 'pagesetter_editapi_deletePub'");

  $tid = $args['tid'];
  $pid = $args['pid'];

    // Get type info
  $pubInfo = pnModAPIFunc( 'pagesetter', 'admin', 'getPubTypeInfo',
                           array('tid' => $tid) );

  if ($pubInfo === false)
    return false;

    // Normal access check
  if (!pnSecAuthAction(0, 'pagesetter::', "$tid::", pagesetterAccessAuthor))
    return pagesetterErrorPage(__FILE__, __LINE__, _PGNOAUTH);

    // Now check for "edit access to own" being enabled, otherwise "editor" access is required
  if (!($pubInfo['publication']['enableEditOwn']  ||  pnSecAuthAction(0, 'pagesetter::', "$tid::", pagesetterAccessEditor)))
    return pagesetterErrorPage(__FILE__, __LINE__, _PGNOAUTH);

  list($dbconn) = pnDBGetConn();
  $pntable = pnDBGetTables();

  $pubHeaderTable = $pntable['pagesetter_pubheader'];
  $pubHeaderColumn = $pntable['pagesetter_pubheader_column'];

  $deleteValue = ($doDelete ? 1 : 0);

  $sql = "UPDATE $pubHeaderTable SET
            $pubHeaderColumn[deleted] = $deleteValue
          WHERE $pubHeaderColumn[pid] = " . (int)$pid . " AND
                $pubHeaderColumn[tid] = " . (int)$tid;
  
  //echo "<pre>$sql</pre>"; exit(0);
  $result = $dbconn->execute($sql);

  if ($dbconn->errorNo() != 0)
    return pagesetterErrorApi(__FILE__, __LINE__, '"deletePub" failed: ' 
                                                  . $dbconn->errorMsg() . " while executing: $sql"); 
  
  pagesetterSmartyClearCache($tid, $pid);

  return true;
}


  // Delete publication for real (when depot is disabled)
function pagesetterDeleteAllRevisions($tid, $pid, $exceptId)
{
    // Check access
  if (!pnSecAuthAction(0, 'pagesetter::', "$tid:$pid:", pagesetterAccessModerator))
    return pagesetterErrorApi(__FILE__, __LINE__, _PGNOAUTH);

  list($dbconn) = pnDBGetConn();
  $pntable = pnDBGetTables();

  $pubTable = pagesetterGetPubTableName($tid);
  $pubColumn = $pntable['pagesetter_pubdata_column'];

    // Delete all uploads (optionally except one)
  if ($exceptId != null)
    $exceptSql = "AND $pubColumn[id] != $exceptId";
  else
    $exceptSql = '';

  $sql = "SELECT $pubColumn[revision] FROM $pubTable
          WHERE  $pubColumn[pid] = " . (int)$pid . "
                 $exceptSql";

  $result = $dbconn->execute($sql);

  if ($dbconn->errorNo() != 0)
    return pagesetterErrorApi(__FILE__, __LINE__, '"deleteAllRevisions" failed: ' 
                                                  . $dbconn->errorMsg() . " while executing: $sql"); 

  for ( ; !$result->EOF; $result->MoveNext())
  {
    $revision = $result->fields[0];

    if (pagesetterEraseUpload($pid, $tid, null, $revision) == false)
      return false;
  }

  $result->close();

    // Delete all database data (optionally except one)

  $sql = "DELETE FROM $pubTable 
          WHERE  $pubColumn[pid] = " . (int)$pid . "
                 $exceptSql";
  
  //echo "<pre>$sql</pre>"; exit(0);
  $result = $dbconn->execute($sql);

  if ($dbconn->errorNo() != 0)
    return pagesetterErrorApi(__FILE__, __LINE__, '"deleteAllRevisions" failed: ' 
                                                  . $dbconn->errorMsg() . " while executing: $sql"); 
  
  pagesetterSmartyClearCache($tid, $pid);

  return true;
}


function pagesetter_editapi_createRevisionLogEntry($args)
{
  if (!isset($args['tid']))
    return pagesetterErrorApi(__FILE__, __LINE__, "Missing argument 'tid' in 'pagesetter_editapi_createRevisionLogEntry'");
  if (!isset($args['pid']))
    return pagesetterErrorApi(__FILE__, __LINE__, "Missing argument 'pid' in 'pagesetter_editapi_createRevisionLogEntry'");
  if (!isset($args['id']))
    return pagesetterErrorApi(__FILE__, __LINE__, "Missing argument 'id' in 'pagesetter_editapi_createRevisionLogEntry'");
  if (!isset($args['prevVersion']))
    return pagesetterErrorApi(__FILE__, __LINE__, "Missing argument 'prevVersion' in 'pagesetter_editapi_createRevisionLogEntry'");
  if (!isset($args['user']))
    return pagesetterErrorApi(__FILE__, __LINE__, "Missing argument 'user' in 'pagesetter_editapi_createRevisionLogEntry'");

  $tid         = $args['tid'];
  $pid         = $args['pid'];
  $id          = $args['id'];
  $prevVersion = $args['prevVersion'];
  $user        = $args['user'];

    // Check access
  if (!pnSecAuthAction(0, 'pagesetter::', "$tid:$pid:", pagesetterAccessAuthor))
    return pagesetterErrorApi(__FILE__, __LINE__, _PGNOAUTH);

  list($dbconn) = pnDBGetConn();
  $pntable = pnDBGetTables();

  $revisionsTable = $pntable['pagesetter_revisions'];
  $revisionsColumn = $pntable['pagesetter_revisions_column'];

  $sql = "REPLACE INTO $revisionsTable
          (
            $revisionsColumn[tid],
            $revisionsColumn[pid],
            $revisionsColumn[id],
            $revisionsColumn[previousVersion],
            $revisionsColumn[user],
            $revisionsColumn[timestamp]
          )
          VALUES
          (
            '" .  pnVarPrepForStore($tid) . "',
            '" .  pnVarPrepForStore($pid) . "',
            '" .  pnVarPrepForStore($id) . "',
            '" .  pnVarPrepForStore($prevVersion) . "',
            '" .  pnVarPrepForStore($user) . "',
            NOW()
          )";

  //echo "<pre>$sql</pre>"; exit(0);
  $result = $dbconn->execute($sql);

  if ($dbconn->errorNo() != 0)
    return pagesetterErrorApi(__FILE__, __LINE__, '"createRevisionLogEntry" failed: ' 
                                                  . $dbconn->errorMsg() . " while executing: $sql"); 

  return true;
}


// =======================================================================
// Plugin events
// =======================================================================

function pagesetter_editapi_invokePluginEvents(&$args)
{
  //echo "EVENT: $args[eventName]. ";
  guppy_invokeEvent($args['eventName'], $args);
  //exit(0);
}


// =======================================================================
// Preview handling
// =======================================================================

function pagesetter_editapi_savePreviewData($args)
{
  if (!isset($args['tid']))
    return pagesetterErrorApi(__FILE__, __LINE__, "Missing argument 'tid' in 'pagesetter_editapi_savePreviewData'");
  if (!isset($args['pubData']))
    return pagesetterErrorApi(__FILE__, __LINE__, "Missing argument 'pubData' in 'pagesetter_editapi_savePreviewData'");

  if (!pnSessionSetVar("pagesetterPreview", $args))
    return pagesetterErrorApi(__FILE__, __LINE__, 'Failed to write session data for preview');
  //print_r($args); exit(0);
  return true;
}


function pagesetter_editapi_getFormattedPreview($args)
{
    // Get preview data from session
  $previewData = pnSessionGetVar("pagesetterPreview");

  if (!isset($previewData))
    return _PGUNKNOWNPUB;

    // Beware that pub. data in editor is in core_xxx form, not core.xxx
  $pubData = &$previewData['pubData'];
  pagesetterUnflattenPubData($pubData);

    // Convert data to format useable by the templates (names need to be converted from column names to field names)

  if (!pnModAPILoad('pagesetter', 'admin'))
    return pagesetterErrorApi(__FILE__, __LINE__, 'Failed to load Pagesetter admin API');

  $tid = $previewData['tid'];
  $pubInfo = pnModAPIFunc('pagesetter', 'admin', 'getPubTypeInfo',
                           array('tid' => $tid) );
  if ($pubInfo === false)
    return false;

  $url = pnModURL('pagesetter', 'user', 'preview', array());
  $url = htmlspecialchars($url);

  $pubData['core']['pageCount'] = 0;
  $pubData['core']['baseURL'] = $url;

  $fieldData = array();
  foreach ($pubInfo['fields'] as $fieldInfo)
  {
    $fieldID    = $fieldInfo['id'];
    $fieldName  = $fieldInfo['name'];
    $fieldType  = $fieldInfo['type'];

      // Split pageable fields
    if ($fieldInfo['isPageable'])
    {
      $fieldData[$fieldName] = pagesetterSplitPages( pnVarPrepHTMLDisplay($pubData[$fieldName]) );
      $pubData['core']['pageCount'] = count($fieldData[$fieldName]);
    }
    else
    {
      if ($fieldInfo['isTitle'])
        $pubData['core']['title'] = pnVarPrepHTMLDisplay($pubData[$fieldName]);

      if ($fieldType == pagesetterFieldTypeImageUpload  ||  $fieldType == pagesetterFieldTypeUpload)
      {
        $fieldData[$fieldName] = $pubData[$fieldName];
        if (empty($fieldData[$fieldName]['url']))
        {
          $path = $fieldData[$fieldName]['guppy_path'];
          if (preg_match('/tmp([0-9]+)\.image/', $path, $matches))
            $id = $matches[1];
          else
            $id = 'unknown';
          $url = pnModURL('pagesetter', 'file', 'preview',
                          array('id'    => $id,
                                'field' => $fieldName));
          $fieldData[$fieldName]['url'] = $url;
        }
      }
      else
        $fieldData[$fieldName] = pnVarPrepHTMLDisplay($pubData[$fieldName]);
    }

    unset($pubData[$fieldName]);
  }

  $page = pnVarCleanFromInput('page');
  if (isset($page))
    --$page;  // Offset is from 1 in URL, but zero based in core
  else
    $page = 0;
  $pubData['core']['page'] = $page;

    // Assign various dummy values to other core fields
  $pubData['core']['printThis'] = '<i>preview</i>';
  $pubData['core']['printThisURL'] = 'preview';
  $pubData['core']['sendThis'] = '<i>preview</i>';
  $pubData['core']['sendThisURL'] = 'preview';
  $pubData['core']['editThis'] = '<i>preview</i>';
  $pubData['core']['editInfo'] = '<i>preview</i>';
  $pubData['core']['hitCount'] = '<i>preview</i>';

  $pubData = $pubData + $fieldData;

    // Now output data through pnRender

  $smarty = new pnRender('pagesetter');

  $templateFile = pagesetterSmartyGetTemplateFilename($smarty, $tid, 'full', $expectedName);

  //echo "<pre>"; print_r($pubData); echo "</pre>"; exit(0);
  $smarty->assign($pubData);
  $smarty->caching = 0;

  if (!$smarty->template_exists($templateFile))
    return _PGSORRYNOTEMPLATE . ": $expectedName<p>";

  $pubFormatted = $smarty->fetch($templateFile);

  return $pubFormatted;
}


function pagesetter_editapi_clearPreviewData($args)
{
  pnSessionDelVar("pagesetterPreview");

  return true;
}


?>
