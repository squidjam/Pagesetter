<?php
// $Id: pninit.php,v 1.57 2006/04/20 16:44:31 jornlind Exp $
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
// but WIthOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// To read the license please visit http://www.gnu.org/copyleft/gpl.html
// =======================================================================


// -----------------------------------------------------------------------
// Module initialization
// -----------------------------------------------------------------------
function pagesetter_init()
{
  list($dbconn) = pnDBGetConn();
  $pntable = pnDBGetTables();
  
  $pubTypesTable = $pntable['pagesetter_pubtypes'];
  $pubTypesColumn = &$pntable['pagesetter_pubtypes_column'];

    // Create the table
  $sql = "CREATE TABLE $pubTypesTable (
          $pubTypesColumn[id] INT NOT NULL AUTO_INCREMENT,
          $pubTypesColumn[authorID] INT NOT NULL,
          $pubTypesColumn[created] DATE NOT NULL,
          $pubTypesColumn[title] VARCHAR(255),
          $pubTypesColumn[filename] VARCHAR(100),
          $pubTypesColumn[formname] VARCHAR(100),
          $pubTypesColumn[description] VARCHAR(255),
          $pubTypesColumn[listCount] INT,
          $pubTypesColumn[sortField1] VARCHAR(255),
          $pubTypesColumn[sortDesc1] TINYINT,
          $pubTypesColumn[sortField2] VARCHAR(255),
          $pubTypesColumn[sortDesc2] TINYINT,
          $pubTypesColumn[sortField3] VARCHAR(255),
          $pubTypesColumn[sortDesc3] TINYINT,
          $pubTypesColumn[defaultFilter] VARCHAR(255),
          $pubTypesColumn[enableHooks] TINYINT DEFAULT 1,
          $pubTypesColumn[workflow] VARCHAR(255) DEFAULT 'standard',
          $pubTypesColumn[enableRevisions] TINYINT DEFAULT 1,
          $pubTypesColumn[enableEditOwn] TINYINT default 0,
          $pubTypesColumn[enableTopicAccess] TINYINT default 0,
          $pubTypesColumn[defaultFolder] INT,
          $pubTypesColumn[defaultSubFolder] VARCHAR(255),
          $pubTypesColumn[defaultFolderTopic] INT,
          PRIMARY KEY(pg_id))";
  $dbconn->Execute($sql);

    // Check for an error with the database code, and if so set an
    // appropriate error message and return
  if ($dbconn->ErrorNo() != 0)
  {
    pnSessionSetVar('errormsg', 'Table creation failed: ' . 
                    $dbconn->ErrorMsg() . ". While executing $sql");
    return false;
  }
  
  $pubFieldsTable = $pntable['pagesetter_pubfields'];
  $pubFieldsColumn = &$pntable['pagesetter_pubfields_column'];

    // Create the table
  $sql = "CREATE TABLE $pubFieldsTable (
          $pubFieldsColumn[id] INT NOT NULL AUTO_INCREMENT,
          $pubFieldsColumn[tid] INT NOT NULL,
          $pubFieldsColumn[name] VARCHAR(255),
          $pubFieldsColumn[title] VARCHAR(255),
          $pubFieldsColumn[description] VARCHAR(255),
          $pubFieldsColumn[type] VARCHAR(50),
          $pubFieldsColumn[typeData] VARCHAR(255),
          $pubFieldsColumn[isTitle] TINYINT,
          $pubFieldsColumn[isPageable] TINYINT,
          $pubFieldsColumn[isSearchable] TINYINT DEFAULT 1,
          $pubFieldsColumn[isMandatory] TINYINT DEFAULT 0,
          $pubFieldsColumn[lineno] INT DEFAULT 0 NOT NULL,
          PRIMARY KEY(pg_id))";
  $dbconn->Execute($sql);

    // Check for an error with the database code, and if so set an
    // appropriate error message and return
  if ($dbconn->ErrorNo() != 0)
  {
    pnSessionSetVar('errormsg', 'Table creation failed: ' . 
                    $dbconn->ErrorMsg() . ". While executing $sql");
    return false;
  }

  if (!pagesetterInitLists($dbconn, $pntable))
    return false;

  if (!pagesetterInitPublicationHeader($dbconn, $pntable))
    return false;

  if (!pagesetterInitRevisions($dbconn, $pntable))
    return false;

  if (!pagesetterInitWFCFG($dbconn, $pntable))
    return false;

  if (!pagesetterInitCounters($dbconn, $pntable))
    return false;

  if (!pagesetterInitSession($dbconn, $pntable))
    return false;
    
  if (!pagesetterInitRelations($dbconn, $pntable))
  	return false;

  if (pnModAPILoad('pagesetter', 'integ'))
  {
    // No error checking - just try
    pnModAPIFunc('pagesetter', 'integ', 'importNews',
                 array('addImage' => true));
    pnModAPIFunc('pagesetter', 'integ', 'createArticle');
  }

    // Initialisation successful
  return true;
}


function pagesetterInitLists(&$dbconn, &$pntable)
{
  $listsTable = $pntable['pagesetter_lists'];
  $listsColumn = &$pntable['pagesetter_lists_column'];

    // Create the lists table
  $sql = "CREATE TABLE $listsTable (
          $listsColumn[id] INT NOT NULL AUTO_INCREMENT,
          $listsColumn[authorID] INT NOT NULL,
          $listsColumn[created] DATE NOT NULL,
          $listsColumn[title] VARCHAR(255),
          $listsColumn[description] VARCHAR(255),
          PRIMARY KEY(pg_id))";
  $dbconn->Execute($sql);

    // Check for an error with the database code, and if so set an
    // appropriate error message and return
  if ($dbconn->ErrorNo() != 0)
  {
    pnSessionSetVar('errormsg', 'Table creation failed: ' . 
                    $dbconn->ErrorMsg() . ". While executing $sql");
    return false;
  }

  $listItemsTable = $pntable['pagesetter_listitems'];
  $listItemsColumn = &$pntable['pagesetter_listitems_column'];

    // Create the list items table
  $sql = "CREATE TABLE $listItemsTable (
          $listItemsColumn[id] INT NOT NULL AUTO_INCREMENT,
          $listItemsColumn[lid] INT NOT NULL,
          $listItemsColumn[parentID] INT NOT NULL,
          $listItemsColumn[title] VARCHAR(255),
          $listItemsColumn[fullTitle] VARCHAR(255),
          $listItemsColumn[value] VARCHAR(255),
          $listItemsColumn[description] VARCHAR(255),
          $listItemsColumn[lineno] INT NOT NULL,
          $listItemsColumn[indent] INT NOT NULL,
          $listItemsColumn[lval] INT NOT NULL,
          $listItemsColumn[rval] INT NOT NULL,
          PRIMARY KEY(pg_id))";
  $dbconn->Execute($sql);

    // Check for an error with the database code, and if so set an
    // appropriate error message and return
  if ($dbconn->ErrorNo() != 0)
  {
    pnSessionSetVar('errormsg', 'Table creation failed: ' . 
                    $dbconn->ErrorMsg() . ". While executing $sql");
    return false;
  }

  return true;
}


function pagesetterInitPublicationHeader(&$dbconn, &$pntable)
{
  $pubheaderTable = &$pntable['pagesetter_pubheader'];
  $pubheaderColumn = &$pntable['pagesetter_pubheader_column'];

    // Create the publication header table
  $sql = "CREATE TABLE $pubheaderTable (
          $pubheaderColumn[tid] INT NOT NULL,
          $pubheaderColumn[pid] INT NOT NULL,
          $pubheaderColumn[hitCount] INT DEFAULT 0 NOT NULL,
          $pubheaderColumn[onlineID] INT,
          $pubheaderColumn[deleted] TINYINT DEFAULT 0 NOT NULL,
          PRIMARY KEY(pg_tid,pg_pid))";
  $dbconn->Execute($sql);

    // Check for an error with the database code, and if so set an
    // appropriate error message and return
  if ($dbconn->ErrorNo() != 0)
    return pagesetterInitError(__FILE__, __LINE__, "Failed creating publication header: " . $dbconn->errorMsg() 
                                                     . " while executing: $sql");

  return true;
}


function pagesetterInitRevisions(&$dbconn, &$pntable)
{
  $revisionsTable = &$pntable['pagesetter_revisions'];
  $revisionsColumn = &$pntable['pagesetter_revisions_column'];

    // Create the publication header table
  $sql = "CREATE TABLE $revisionsTable (
          $revisionsColumn[tid] INT NOT NULL,
          $revisionsColumn[id] INT NOT NULL,
          $revisionsColumn[pid] INT NOT NULL,
          $revisionsColumn[previousVersion] INT NOT NULL,
          $revisionsColumn[user] INT NOT NULL,
          $revisionsColumn[timestamp] DATETIME,
          PRIMARY KEY(pg_tid,pg_id))";
  $dbconn->Execute($sql);

    // Check for an error with the database code, and if so set an
    // appropriate error message and return
  if ($dbconn->ErrorNo() != 0)
    return pagesetterInitError(__FILE__, __LINE__, "Failed creating revision table: " . $dbconn->errorMsg() 
                                                     . " while executing: $sql");

  return true;
}


function pagesetterInitWFCFG(&$dbconn, &$pntable)
{
  $wfcfgTable = &$pntable['pagesetter_wfcfg'];
  $wfcfgColumn = &$pntable['pagesetter_wfcfg_column'];

    // Create the workflow configuration table
  $sql = "CREATE TABLE $wfcfgTable (
          $wfcfgColumn[workflow] VARCHAR(100) NOT NULL,
          $wfcfgColumn[tid] INT NOT NULL,
          $wfcfgColumn[setting] VARCHAR(100) NOT NULL,
          $wfcfgColumn[value] TEXT,
          PRIMARY KEY(pg_workflow,pg_tid,pg_setting))";
  $dbconn->Execute($sql);

    // Check for an error with the database code, and if so set an
    // appropriate error message and return
  if ($dbconn->ErrorNo() != 0)
    return pagesetterInitError(__FILE__, __LINE__, "Failed creating workflow configuration table: " . $dbconn->errorMsg() 
                                                     . " while executing: $sql");

  return true;
}


function pagesetterInitCounters(&$dbconn, &$pntable)
{
  $countersTable = &$pntable['pagesetter_counters'];
  $countersColumn = &$pntable['pagesetter_counters_column'];

    // Create the counters table
  $sql = "CREATE TABLE $countersTable (
          $countersColumn[name] VARCHAR(100) NOT NULL,
          $countersColumn[count] INT NOT NULL,
          PRIMARY KEY(pg_name))";
  $dbconn->Execute($sql);

    // Check for an error with the database code, and if so set an
    // appropriate error message and return
  if ($dbconn->ErrorNo() != 0)
    return pagesetterInitError(__FILE__, __LINE__, "Failed creating counters table: " . $dbconn->errorMsg() 
                                                     . " while executing: $sql");

  return true;
}


function pagesetterInitSession(&$dbconn, &$pntable)
{
  $sessionTable = &$pntable['pagesetter_session'];
  $sessionColumn = &$pntable['pagesetter_session_column'];

    // Create the session table
  $sql = "CREATE TABLE $sessionTable (
          $sessionColumn[sessionId] VARCHAR(50) NOT NULL,
          $sessionColumn[cache] MEDIUMBLOB NOT NULL,
          $sessionColumn[lastUsed] DATE NOT NULL,
          PRIMARY KEY(pg_sessionid))";
  $dbconn->Execute($sql);

    // Check for an error with the database code, and if so set an
    // appropriate error message and return
  if ($dbconn->ErrorNo() != 0)
    return pagesetterInitError(__FILE__, __LINE__, "Failed creating session table: " . $dbconn->errorMsg() 
                                                     . " while executing: $sql");

  return true;
}


function pagesetterInitRelations(&$dbconn, &$pntable)
{
	$relationsTable = &$pntable['pagesetter_relations'];
	$relationsColumn = &$pntable['pagesetter_relations_column'];
	
	  // Create the relations table
	$sql = "CREATE TABLE $relationsTable (" .
		   "$relationsColumn[tid1] INT NOT NULL, " .
		   "$relationsColumn[pid1] INT NOT NULL, " .
		   "$relationsColumn[fieldId1] INT, " .
		   "$relationsColumn[tid2] INT NOT NULL, " .
		   "$relationsColumn[pid2] INT NOT NULL, " .
		   "$relationsColumn[fieldId2] INT" .
		   ")";
	$dbconn->Execute($sql);

      // Check for an error with the database code, and if so set an
      // appropriate error message and return
  	if ($dbconn->ErrorNo() != 0)
    	return pagesetterInitError(__FILE__, __LINE__, "Failed creating relations table: " . $dbconn->errorMsg() 
                                                     . " while executing: $sql");
                                                     
    return true;
}

// -----------------------------------------------------------------------
// Module upgrade
// -----------------------------------------------------------------------
function pagesetter_upgrade($oldVersion)
{
  $ok = true;

    // Upgrade dependent on old version number
  switch($oldVersion)
  {
    case '0.9.0.0':
      // ignore
    case '1.0.0.0':
      $ok = $ok && pagesetter_upgrade_to_1_1_0_0($oldVersion);
    case '1.1.0.0':
      $ok = $ok && pagesetter_upgrade_to_1_1_0_1($oldVersion);
    case '1.2.0.0':
    case '2.0.0.0':
    case '2.0.1.0':
    case '3.0.0.0':
    case '3.0.1.0':
      // ignore
    case '3.1.0.0':
      $ok = $ok && pagesetter_upgrade_to_3_2_0_0($oldVersion);
    case '3.2.0.0':
    case '4.0.0.0':
      // ignore
    case '4.1.0.0':
      $ok = $ok && pagesetter_upgrade_to_4_2_0_0($oldVersion);
    case '4.2.0.0':
      $ok = $ok && pagesetter_upgrade_to_4_2_1_0($oldVersion);
    case '4.2.1.0':
      $ok = $ok && pagesetter_upgrade_to_4_2_2_0($oldVersion);
    case '4.2.2.0':
    case '4.2.3.0':
      $ok = $ok && pagesetter_upgrade_to_4_2_4_0($oldVersion);
    case '4.2.4.0':
      $ok = $ok && pagesetter_upgrade_to_4_2_5_0($oldVersion);
    case '4.2.5.0':
      $ok = $ok && pagesetter_upgrade_to_4_2_6_0($oldVersion);
    case '4.2.6.0':
      $ok = $ok && pagesetter_upgrade_to_4_2_7_0($oldVersion);
    case '4.2.7.0':
      $ok = $ok && pagesetter_upgrade_to_4_2_8_0($oldVersion);
    case '4.2.8.0':
      $ok = $ok && pagesetter_upgrade_to_4_2_9_0($oldVersion);
    case '4.2.9.0':
      $ok = $ok && pagesetter_upgrade_to_4_3_0_0($oldVersion);
    case '4.3.0.0':
    case '5.0.0.0':
      $ok = $ok && pagesetter_upgrade_to_5_0_2_0($oldVersion);
    case '5.0.2.0':
      $ok = $ok && pagesetter_upgrade_to_5_0_2_1($oldVersion);
    case '5.0.2.1':
      $ok = $ok && pagesetter_upgrade_to_5_0_2_2($oldVersion);
    case '5.0.2.2':
    case '5.1.0.0':
      $ok = $ok && pagesetter_upgrade_to_5_1_0_1($oldVersion);
    case '5.1.0.1':
    case '5.2.0.0':
      $ok = $ok && pagesetter_upgrade_to_6_0_0_0($oldVersion);
    case '6.0.0.0':
      $ok = $ok && pagesetter_upgrade_to_6_0_0_1($oldVersion);
    case '6.0.0.1':
    case '6.0.1.0':
      $ok = $ok && pagesetter_upgrade_to_6_1_0_0($oldVersion);
    case '6.1.0.0':
      $ok = $ok && pagesetter_upgrade_to_6_2_0_0($oldVersion);
    case '6.2.0.0':
      $ok = $ok && pagesetter_upgrade_to_6_2_0_1($oldVersion);
    case '6.2.0.1':
      $ok = $ok && pagesetter_upgrade_to_6_2_0_2($oldVersion);
    case '6.2.0.2':
      $ok = $ok && pagesetter_upgrade_to_6_2_0_3($oldVersion);
    case '6.2.0.3':
      $ok = $ok && pagesetter_upgrade_to_6_2_0_4($oldVersion);
    case '6.2.0.4':
      $ok = $ok && pagesetter_upgrade_to_6_2_0_5($oldVersion);
    case '6.2.0.5':  
    case '6.3.0.0':  
      // future ...
  }

    // Always delete smarty cache
  $smarty = new pnRender('pagesetter');
  $smarty->clear_all_cache();

    // Update successful
  return $ok;
}


function pagesetter_upgrade_to_1_1_0_0($oldVersion)
{
  if (!pnModAPILoad('pagesetter', 'admin', true))
    return pagesetterInitError(__FILE__, __LINE__, 'Failed to load Pagesetter admin API');

  $pubTypes = pnModAPIFunc('pagesetter',
                           'admin',
                           'getPublicationTypes');

  if ($pubTypes === false)
    return false;

  list($dbconn) = pnDBGetConn();
  $pntable = pnDBGetTables();

  $pubTypesTable  = $pntable['pagesetter_pubtypes'];
  $pubTypesColumn = $pntable['pagesetter_pubtypes_column'];

  if (!pagesetterAddDatabaseField($dbconn, $oldVersion, "1.1.0.0", $pubTypesTable, 
                                  "pg_enablehooks TINYINT default 1"))
    return false;


  $fieldsTable  = $pntable['pagesetter_pubfields'];
  $fieldsColumn = $pntable['pagesetter_pubfields_column'];

  if (!pagesetterAddDatabaseField($dbconn, $oldVersion, "1.1.0.0", $fieldsTable, 
                                  "pg_issearchable TINYINT default 1"))
    return false;

  return true;
}


function pagesetter_upgrade_to_1_1_0_1($oldVersion)
{
  if (!pnModAPILoad('pagesetter', 'admin', true))
    return pagesetterInitError(__FILE__, __LINE__, 'Failed to load Pagesetter admin API');

  list($dbconn) = pnDBGetConn();
  $pntable = pnDBGetTables();

  if (!pagesetterInitLists($dbconn, $pntable))
    return false;

  return true;
}


function pagesetter_upgrade_to_3_2_0_0($oldVersion)
{
  require_once "modules/pagesetter/guppy/guppy.php";
  guppy_setSetting('htmlAreaStyled', true);
  guppy_setSetting('htmlAreaEnabled', true);
  return true;
}


function pagesetter_upgrade_to_4_2_0_0($oldVersion)
{
  $newVersion = '4.2.0.0';

  if (!pnModAPILoad('pagesetter', 'admin', true))
    return pagesetterInitError(__FILE__, __LINE__, 'Failed to load Pagesetter admin API');

  $pubTypes = pnModAPIFunc( 'pagesetter',
                            'admin',
                            'getPublicationTypes' );

  list($dbconn) = pnDBGetConn();
  $pntable = pnDBGetTables();

    // Step 1 - add "indepot" and "revision" fields

  pagesetterAddDatabaseTypeField($dbconn, $oldVersion, $newVersion, $pubTypes, 
                                 "pg_indepot TINYINT default 0");

  pagesetterAddDatabaseTypeField($dbconn, $oldVersion, $newVersion, $pubTypes, 
                                 "pg_revision INT default 1");

    // Step 2 - convert approval state to generic string value

  foreach ($pubTypes as $pubType)
  {
    $tid = $pubType['id'];

    $tableName = pagesetterGetPubTableName($tid);
    $pubColumn = $pntable['pagesetter_pubdata_column'];

      // Change state type from integer to string

    $sql = "ALTER TABLE $tableName CHANGE COLUMN $pubColumn[approvalState] $pubColumn[approvalState] VARCHAR(255)";

    $dbconn->Execute($sql);
    if ($dbconn->errorNo() != 0)
      return pagesetterInitError(__FILE__, __LINE__, "Upgrade from $oldVersion to $newVersion failed: " . $dbconn->errorMsg() 
                                                     . " while executing: $sql");

      // Convert preview integer value to 'waiting'

    $sql = "UPDATE $tableName SET $pubColumn[approvalState] = 'waiting'
            WHERE $pubColumn[approvalState] = " . pagesetterApprovalPreview;

    $dbconn->Execute($sql);

    if ($dbconn->errorNo() != 0)
      return pagesetterInitError(__FILE__, __LINE__, "Upgrade from $oldVersion to $newVersion failed: " . $dbconn->errorMsg() 
                                                     . " while executing: $sql");

      // Convert approved integer value to 'approved'

    $sql = "UPDATE $tableName SET $pubColumn[approvalState] = 'approved'
            WHERE $pubColumn[approvalState] = " . pagesetterApprovalApproved;

    $dbconn->Execute($sql);

    if ($dbconn->errorNo() != 0)
      return pagesetterInitError(__FILE__, __LINE__, "Upgrade from $oldVersion to $newVersion failed: " . $dbconn->errorMsg() 
                                                     . " while executing: $sql");

      // Convert rejected integer value to 'rejected'

    $sql = "UPDATE $tableName SET $pubColumn[approvalState] = 'rejected'
            WHERE $pubColumn[approvalState] = " . pagesetterApprovalRejected;

    $dbconn->Execute($sql);

    if ($dbconn->errorNo() != 0)
      return pagesetterInitError(__FILE__, __LINE__, "Upgrade from $oldVersion to $newVersion failed: " . $dbconn->errorMsg() 
                                                     . " while executing: $sql");
  }

  return true;
}


function pagesetter_upgrade_to_4_2_1_0($oldVersion)
{
  $newVersion = '4.2.1.0';

  if (!pnModAPILoad('pagesetter', 'admin', true))
    return pagesetterInitError(__FILE__, __LINE__, 'Failed to load Pagesetter admin API');

  list($dbconn) = pnDBGetConn();
  $pntable = pnDBGetTables();
  $ok = true;

  $pubTypesTable  = $pntable['pagesetter_pubtypes'];
  $pubTypesColumn = $pntable['pagesetter_pubtypes_column'];

  $ok = $ok && pagesetterAddDatabaseField($dbconn, $oldVersion, $newVersion, $pubTypesTable, 
                                          "pg_workflow VARCHAR(255) DEFAULT 'standard'");

  return $ok;
}


function pagesetter_upgrade_to_4_2_2_0($oldVersion)
{
  $newVersion = '4.2.2.0';

  if (!pnModAPILoad('pagesetter', 'admin', true))
    return pagesetterInitError(__FILE__, __LINE__, 'Failed to load Pagesetter admin API');

  $pubTypes = pnModAPIFunc( 'pagesetter',
                            'admin',
                            'getPublicationTypes' );

  list($dbconn) = pnDBGetConn();
  $pntable = pnDBGetTables();

  $ok = true;

  $ok = $ok && pagesetterAddDatabaseTypeField($dbconn, $oldVersion, $newVersion, $pubTypes, 
                                              "pg_pid INT");

  foreach ($pubTypes as $pubType)
  {
    $tid = $pubType['id'];

    $tableName = pagesetterGetPubTableName($tid);
    $pubColumn = $pntable['pagesetter_pubdata_column'];

    $sql = "UPDATE $tableName SET $pubColumn[pid] = $pubColumn[id]";

    $dbconn->Execute($sql);

    if ($dbconn->errorNo() != 0)
      $ok = pagesetterInitError(__FILE__, __LINE__, "Upgrade from $oldVersion to $newVersion failed: " . $dbconn->errorMsg() 
                                                    . " while executing: $sql");

    $sql = "ALTER TABLE $tableName ADD INDEX ($pubColumn[pid], $pubColumn[online])";

    $dbconn->Execute($sql);

    if ($dbconn->errorNo() != 0)
      $ok = pagesetterInitError(__FILE__, __LINE__, "Upgrade from $oldVersion to $newVersion failed: " . $dbconn->errorMsg() 
                                                    . " while executing: $sql");
  }

  return $ok;
}


function pagesetter_upgrade_to_4_2_4_0($oldVersion)
{
  $newVersion = '4.2.4.0';

  if (!pnModAPILoad('pagesetter', 'admin', true))
    return pagesetterInitError(__FILE__, __LINE__, 'Failed to load Pagesetter admin API');

  $pubTypes = pnModAPIFunc( 'pagesetter',
                            'admin',
                            'getPublicationTypes' );

  list($dbconn) = pnDBGetConn();
  $pntable = pnDBGetTables();

  $ok = true;

  $ok = $ok && pagesetterInitPublicationHeader($dbconn, $pntable);

  $ok = $ok && pagesetterInitRevisions($dbconn, $pntable);

  foreach ($pubTypes as $pubType)
  {
    $tid = $pubType['id'];

    $tableName = pagesetterGetPubTableName($tid);
    $pubColumn = $pntable['pagesetter_pubdata_column'];

    $pubHeaderTable = &$pntable['pagesetter_pubheader'];
    $pubHeaderColumn = &$pntable['pagesetter_pubheader_column'];

    $sql = "REPLACE INTO $pubHeaderTable
            (
              $pubHeaderColumn[tid],
              $pubHeaderColumn[pid],
              $pubHeaderColumn[hitCount],
              $pubHeaderColumn[onlineID],
              $pubHeaderColumn[deleted]
            )
            SELECT $tid,
                   $pubColumn[pid],
                   $pubColumn[hitCount],
                   IF ($pubColumn[online], $pubColumn[pid], NULL),
                   0
            FROM $tableName
            ORDER BY $pubColumn[pid], $pubColumn[online]";  
            
            // (the online ordering ensures the onlineID is set if at least one revision is online - the last overwrites previous)

    $dbconn->execute($sql);
  
    if ($dbconn->errorNo() != 0)
      $ok = pagesetterInitError(__FILE__, __LINE__, "Upgrade from $oldVersion to $newVersion failed: " . $dbconn->errorMsg() 
                                                    . " while executing: $sql");
  }

    // Now that hit counts have been transfered - deleted the originals
  if ($ok)
  {
    $sql = "ALTER TABLE $tableName DROP COLUMN $pubColumn[hitCount]";

    $dbconn->execute($sql);

    if ($dbconn->errorNo() != 0)
      $ok = pagesetterInitError(__FILE__, __LINE__, "Upgrade from $oldVersion to $newVersion failed: " . $dbconn->errorMsg() 
                                                    . " while executing: $sql");
  }

  return $ok;
}


function pagesetter_upgrade_to_4_2_5_0($oldVersion)
{
  list($dbconn) = pnDBGetConn();
  $pntable = pnDBGetTables();

  return pagesetterInitWFCFG($dbconn, $pntable);
}


function pagesetter_upgrade_to_4_2_6_0($oldVersion)
{
  $newVersion = '4.2.6.0';

  if (!pnModAPILoad('pagesetter', 'admin', true))
    return pagesetterInitError(__FILE__, __LINE__, 'Failed to load Pagesetter admin API');

  $pubTypes = pnModAPIFunc( 'pagesetter',
                            'admin',
                            'getPublicationTypes' );

  list($dbconn) = pnDBGetConn();
  $pntable = pnDBGetTables();

  $ok = true;
  $uid = pnUserGetVar('uid');

  foreach ($pubTypes as $pubType)
  {
    $tid = $pubType['id'];

    $tableName = pagesetterGetPubTableName($tid);
    $pubColumn = $pntable['pagesetter_pubdata_column'];

    $revisionsTable = &$pntable['pagesetter_revisions'];
    $revisionsColumn = &$pntable['pagesetter_revisions_column'];

    $sql = "REPLACE INTO $revisionsTable
            (
              $revisionsColumn[tid],
              $revisionsColumn[pid],
              $revisionsColumn[id],
              $revisionsColumn[previousVersion],
              $revisionsColumn[user],
              $revisionsColumn[timestamp]
            )
            SELECT $tid,
                   $pubColumn[pid],
                   $pubColumn[id],
                   $pubColumn[revision]-1,
                   $uid,
                   NOW()
            FROM $tableName";  
            
    $dbconn->execute($sql);
  
    if ($dbconn->errorNo() != 0)
      $ok = pagesetterInitError(__FILE__, __LINE__, "Upgrade from $oldVersion to $newVersion failed: " . $dbconn->errorMsg() 
                                                    . " while executing: $sql");
  }

  return $ok;
}


function pagesetter_upgrade_to_4_2_7_0($oldVersion)
{
  $newVersion = '4.2.7.0';

  list($dbconn) = pnDBGetConn();
  $pntable = pnDBGetTables();

  $ok = true;

  $pubTypesTable = $pntable['pagesetter_pubtypes'];
  $pubTypesColumn = &$pntable['pagesetter_pubtypes_column'];

  $sql = "UPDATE $pubTypesTable SET $pubTypesColumn[workflow] = 'standard'
          WHERE $pubTypesColumn[workflow] IS NULL 
             OR $pubTypesColumn[workflow] = ''";
          
  $dbconn->execute($sql);

  if ($dbconn->errorNo() != 0)
    $ok = pagesetterInitError(__FILE__, __LINE__, "Upgrade from $oldVersion to $newVersion failed: " . $dbconn->errorMsg() 
                                                  . " while executing: $sql");

  return $ok;
}


function pagesetter_upgrade_to_4_2_8_0($oldVersion)
{
  $newVersion = '4.2.8.0';

  list($dbconn) = pnDBGetConn();
  $pntable = pnDBGetTables();

  $pubTypesTable  = $pntable['pagesetter_pubtypes'];
  $pubTypesColumn = $pntable['pagesetter_pubtypes_column'];

  if (!pagesetterAddDatabaseField($dbconn, $oldVersion, $newVersion, $pubTypesTable, 
                                  "pg_enablerevisions TINYINT default 1"))
    return false;

  return true;
}


function pagesetter_upgrade_to_4_2_9_0($oldVersion)
{
  $newVersion = '4.2.9.0';

  if (!pnModAPILoad('pagesetter', 'admin', true))
    return pagesetterInitError(__FILE__, __LINE__, 'Failed to load Pagesetter admin API');

  $pubTypes = pnModAPIFunc( 'pagesetter',
                            'admin',
                            'getPublicationTypes' );

  list($dbconn) = pnDBGetConn();
  $pntable = pnDBGetTables();

    // Code copied from 4.2.4.0 update code. Due to a bug the header tables was not update when creating new publications.

  $ok = true;

  $countersTable = $pntable['pagesetter_counters'];
  $countersColumn = $pntable['pagesetter_counters_column'];
  $pubHeaderTable = &$pntable['pagesetter_pubheader'];
  $pubHeaderColumn = &$pntable['pagesetter_pubheader_column'];

  $ok = $ok && pagesetterInitCounters($dbconn, $pntable);

  foreach ($pubTypes as $pubType)
  {
    $tid = $pubType['id'];

    $tableName = pagesetterGetPubTableName($tid);
    $pubColumn = $pntable['pagesetter_pubdata_column'];

    $sql = "INSERT IGNORE INTO $pubHeaderTable
            (
              $pubHeaderColumn[tid],
              $pubHeaderColumn[pid],
              $pubHeaderColumn[onlineID]
            )
            SELECT $tid,
                   $pubColumn[pid],
                   IF ($pubColumn[online], $pubColumn[pid], NULL)
            FROM $tableName
            ORDER BY $pubColumn[pid], $pubColumn[online]";  
            
            // The join/where clause ensures we only insert the missing pub. header entries.
            // The online ordering ensures the onlineID is set if at least one revision is online - the last overwrites previous.

    $dbconn->execute($sql);
  
    if ($dbconn->errorNo() != 0)
      $ok = pagesetterInitError(__FILE__, __LINE__, "Upgrade from $oldVersion to $newVersion failed: " . $dbconn->errorMsg() 
                                                    . " while executing: $sql");

      // Ensure counter for this tid is set correctly

    $sql = "SELECT MAX($pubColumn[pid]) FROM $tableName";
    $result = $dbconn->execute($sql);
  
    if ($dbconn->errorNo() != 0)
      $ok = pagesetterInitError(__FILE__, __LINE__, "Upgrade from $oldVersion to $newVersion failed: " . $dbconn->errorMsg() 
                                                    . " while executing: $sql");

    $maxPid = intval($result->fields[0]);
    $result->Close();

    $sql = "INSERT INTO $countersTable ($countersColumn[name], $countersColumn[count]) VALUES ('tid$tid', $maxPid)";
    $dbconn->execute($sql);
  
    if ($dbconn->errorNo() != 0)
      $ok = pagesetterInitError(__FILE__, __LINE__, "Upgrade from $oldVersion to $newVersion failed: " . $dbconn->errorMsg() 
                                                    . " while executing: $sql");
  }

  return $ok;
}


function pagesetter_upgrade_to_4_3_0_0($oldVersion)
{
  $newVersion = '4.3.0.0';

  list($dbconn) = pnDBGetConn();
  $pntable = pnDBGetTables();

  $pubTypesTable  = $pntable['pagesetter_pubtypes'];
  $pubTypesColumn = $pntable['pagesetter_pubtypes_column'];

  if (!pagesetterAddDatabaseField($dbconn, $oldVersion, $newVersion, $pubTypesTable, 
                                  "pg_enableeditown TINYINT default 0"))
    return false;

  return true;
}


function pagesetter_upgrade_to_5_0_2_0($oldVersion)
{
  $newVersion = '5.0.2.0';

  list($dbconn) = pnDBGetConn();
  $pntable = pnDBGetTables();

  if (!pagesetterInitSession($dbconn, $pntable))
    return false;

  return true;
}


function pagesetter_upgrade_to_5_0_2_1($oldVersion)
{
  $newVersion = '5.0.2.1';

  list($dbconn) = pnDBGetConn();
  $pntable = pnDBGetTables();

  $pubTypesTable  = $pntable['pagesetter_pubtypes'];
  $pubTypesColumn = $pntable['pagesetter_pubtypes_column'];

  if (!pagesetterAddDatabaseField($dbconn, $oldVersion, $newVersion, $pubTypesTable, 
                                  "pg_filename VARCHAR(100)"))
    return false;

  $sql = "UPDATE $pubTypesTable SET $pubTypesColumn[filename] = $pubTypesColumn[title]";

  $dbconn->execute($sql);

  if ($dbconn->errorNo() != 0)
    $ok = pagesetterInitError(__FILE__, __LINE__, "Upgrade from $oldVersion to $newVersion failed: " . $dbconn->errorMsg() 
                                                  . " while executing: $sql");

  return true;
}


function pagesetter_upgrade_to_5_0_2_2($oldVersion)
{
  $newVersion = '5.0.2.2';

  list($dbconn) = pnDBGetConn();
  $pntable = pnDBGetTables();

  $sessionTable = &$pntable['pagesetter_session'];
  $sessionColumn = &$pntable['pagesetter_session_column'];

  $sql = "ALTER TABLE $sessionTable CHANGE $sessionColumn[cache] $sessionColumn[cache] MEDIUMBLOB NOT NULL";

  $dbconn->execute($sql);

  if ($dbconn->errorNo() != 0)
    return pagesetterInitError(__FILE__, __LINE__, "Upgrade from $oldVersion to $newVersion failed: " . $dbconn->errorMsg() 
                                                   . " while executing: $sql");

  return true;
}


function pagesetter_upgrade_to_5_1_0_1($oldVersion)
{
  $newVersion = '5.0.2.2';

  list($dbconn) = pnDBGetConn();
  $pntable = pnDBGetTables();

  $pubTypesTable  = $pntable['pagesetter_pubtypes'];
  $pubTypesColumn = $pntable['pagesetter_pubtypes_column'];

  if (!pagesetterAddDatabaseField($dbconn, $oldVersion, $newVersion, $pubTypesTable, 
                                  "pg_defaultfilter VARCHAR(255)"))
    return false;

  return true;
}


function pagesetter_upgrade_to_6_0_0_0($oldVersion)
{
  $newVersion = '6.0.0.0';

  list($dbconn) = pnDBGetConn();
  $pntable = pnDBGetTables();

  $pubFieldsTable  = $pntable['pagesetter_pubfields'];
  $pubFieldsColumn = $pntable['pagesetter_pubfields_column'];

  $sql = "ALTER TABLE $pubFieldsTable CHANGE $pubFieldsColumn[type] $pubFieldsColumn[type] VARCHAR(50) NOT NULL";

  $dbconn->Execute($sql);

  if ($dbconn->errorNo() != 0)
    return pagesetterInitError(__FILE__, __LINE__, "Upgrade from $oldVersion to $newVersion failed: " . $dbconn->errorMsg() . " while executing: $sql");

  return true;
}


function pagesetter_upgrade_to_6_0_0_1($oldVersion)
{
  $newVersion = '6.0.0.1';

  if (!pnModAPILoad('pagesetter', 'admin', true))
    return pagesetterInitError(__FILE__, __LINE__, 'Failed to load Pagesetter admin API');

  $pubTypes = pnModAPIFunc( 'pagesetter',
                            'admin',
                            'getPublicationTypes' );

  list($dbconn) = pnDBGetConn();
  $pntable = pnDBGetTables();

  $ok = true;

    // Convert publish and expire date to datetime inputs
  foreach ($pubTypes as $pubType)
  {
    $tid = $pubType['id'];

    $tableName = pagesetterGetPubTableName($tid);
    $pubColumn = $pntable['pagesetter_pubdata_column'];

    $sql = "ALTER TABLE $tableName CHANGE $pubColumn[publishDate] $pubColumn[publishDate] DATETIME";

    $dbconn->execute($sql);
  
    if ($dbconn->errorNo() != 0)
      $ok = pagesetterInitError(__FILE__, __LINE__, "Upgrade from $oldVersion to $newVersion failed: " . $dbconn->errorMsg() 
                                                    . " while executing: $sql");

    $sql = "ALTER TABLE $tableName CHANGE $pubColumn[expireDate] $pubColumn[expireDate] DATETIME;";

    $dbconn->execute($sql);
  
    if ($dbconn->errorNo() != 0)
      $ok = pagesetterInitError(__FILE__, __LINE__, "Upgrade from $oldVersion to $newVersion failed: " . $dbconn->errorMsg() 
                                                    . " while executing: $sql");
  }

  return $ok;
}


function pagesetter_upgrade_to_6_1_0_0($oldVersion)
{
  $newVersion = '6.1.0.0';

  list($dbconn) = pnDBGetConn();
  $pntable = pnDBGetTables();

  $pubTypesTable  = $pntable['pagesetter_pubtypes'];
  $pubTypesColumn = $pntable['pagesetter_pubtypes_column'];

  if (!pagesetterAddDatabaseField($dbconn, $oldVersion, $newVersion, $pubTypesTable, 
                                  "pg_formname VARCHAR(100)"))
    return false;

  $sql = "UPDATE $pubTypesTable SET $pubTypesColumn[formname] = $pubTypesColumn[filename]";

  $dbconn->execute($sql);

  if ($dbconn->errorNo() != 0)
    $ok = pagesetterInitError(__FILE__, __LINE__, "Upgrade from $oldVersion to $newVersion failed: " . $dbconn->errorMsg() 
                                                  . " while executing: $sql");

  return true;
}


function pagesetter_upgrade_to_6_2_0_0($oldVersion)
{
  $newVersion = '6.2.0.0';

  list($dbconn) = pnDBGetConn();
  $pntable = pnDBGetTables();

  $pubTypesTable  = $pntable['pagesetter_pubtypes'];
  $pubTypesColumn = $pntable['pagesetter_pubtypes_column'];

  if (!pagesetterAddDatabaseField($dbconn, $oldVersion, $newVersion, $pubTypesTable, 
                                  "pg_enabletopicaccess TINYINT default 0"))
    return false;

  return true;
}


function pagesetter_upgrade_to_6_2_0_1($oldVersion)
{
  $newVersion = '6.2.0.1';

  list($dbconn) = pnDBGetConn();
  $pntable = pnDBGetTables();

  $pubTypesTable  = $pntable['pagesetter_pubtypes'];
  $pubTypesColumn = $pntable['pagesetter_pubtypes_column'];

  if (!pagesetterAddDatabaseField($dbconn, $oldVersion, $newVersion, $pubTypesTable, 
                                  "pg_defaultFolder INT"))
    return false;

  if (!pagesetterAddDatabaseField($dbconn, $oldVersion, $newVersion, $pubTypesTable, 
                                  "pg_defaultSubFolder VARCHAR(255)"))
    return false;

  return true;
}


function pagesetter_upgrade_to_6_2_0_2($oldVersion)
{
  $newVersion = '6.2.0.2';

  if (!pnModAPILoad('pagesetter', 'admin', true))
    return pagesetterInitError(__FILE__, __LINE__, 'Failed to load Pagesetter admin API');

  $pubTypes = pnModAPIFunc( 'pagesetter',
                            'admin',
                            'getPublicationTypes' );

  list($dbconn) = pnDBGetConn();
  $pntable = pnDBGetTables();

  $ok = true;

    // Remove 'hitcount' from pubdata table (should have been done a long time ago)
  foreach ($pubTypes as $pubType)
  {
    $tid = $pubType['id'];

    $tableName = pagesetterGetPubTableName($tid);
    $pubColumn = $pntable['pagesetter_pubdata_column'];

    $sql = "ALTER TABLE $tableName DROP COLUMN $pubColumn[hitCount]";

    $dbconn->execute($sql);
  
    if ($dbconn->errorNo() != 0)
      $ok = pagesetterInitError(__FILE__, __LINE__, "Upgrade from $oldVersion to $newVersion failed: " . $dbconn->errorMsg() 
                                                    . " while executing: $sql");
  }

  // Ignore errors (hitCount may not exist already)

  return true;
}


function pagesetter_upgrade_to_6_2_0_3($oldVersion)
{
  $newVersion = '6.2.0.3';

  list($dbconn) = pnDBGetConn();
  $pntable = pnDBGetTables();

  $pubTypesTable  = $pntable['pagesetter_pubtypes'];
  $pubTypesColumn = $pntable['pagesetter_pubtypes_column'];

  if (!pagesetterAddDatabaseField($dbconn, $oldVersion, $newVersion, $pubTypesTable, 
                                  "pg_defaultFolderTopic INT"))
    return false;

  return true;
}


function pagesetter_upgrade_to_6_2_0_4($oldVersion)
{
  $newVersion = '6.2.0.4';

  list($dbconn) = pnDBGetConn();
  $pntable = pnDBGetTables();

  $pubFieldsTable  = $pntable['pagesetter_pubfields'];
  $pubFieldsColumn = $pntable['pagesetter_pubfields_column'];

  if (!pagesetterAddDatabaseField($dbconn, $oldVersion, $newVersion, $pubFieldsTable, 
                                  "pg_ismandatory TINYINT DEFAULT 0"))
    return false;

  return true;
}

function pagesetter_upgrade_to_6_2_0_5($oldVersion)
{
	$newVersion = '6.2.0.5';
	
	list($dbconn) = pnDBGetConn();
	$pntable = pnDBGetTables();

	$pubFieldsTable  = $pntable['pagesetter_pubfields'];
	$pubFieldsColumn = $pntable['pagesetter_pubfields_column'];

	if (!pagesetterInitRelations($dbconn,$pntable))
		return false;
		
	return true;
}

function pagesetterAddDatabaseField(&$dbconn, $oldVersion, $newVersion, $tableName, $fieldInitString)
{
  $sql = "ALTER TABLE $tableName ADD $fieldInitString";

  $dbconn->Execute($sql);

  if ($dbconn->errorNo() != 0)
    return pagesetterInitError(__FILE__, __LINE__, "Upgrade from $oldVersion to $newVersion failed: " . $dbconn->errorMsg() . " while executing: $sql");

  return true;
}


function pagesetterAddDatabaseTypeField(&$dbconn, $oldVersion, $newVersion, $pubTypes, $fieldInitString)
{
  foreach ($pubTypes as $pubType)
  {
    $tid = $pubType['id'];

    $tableName = pagesetterGetPubTableName($tid);

    if (!pagesetterAddDatabaseField($dbconn, $oldVersion, $newVersion, $tableName, $fieldInitString))
      return false;
  }

  return true;
}


// -----------------------------------------------------------------------
// Module delete
// -----------------------------------------------------------------------
function pagesetter_delete()
{
  if (!pnModAPILoad('pagesetter', 'admin', true))
    return pagesetterInitError(__FILE__, __LINE__, 'Failed to load Pagesetter admin API');

    // Get pubType list before it is deleted
  $pubTypes = pnModAPIFunc('pagesetter',
                           'admin',
                           'getPublicationTypes');

  list($dbconn) = pnDBGetConn();
  $pntable = pnDBGetTables();

    // Drop the publication types table

  $sql = "DROP TABLE $pntable[pagesetter_pubtypes]";
  $dbconn->Execute($sql);

    // Check for an error with the database code
  if ($dbconn->ErrorNo() != 0)
    pnSessionSetVar('errormsg', 'Drop pubTypes table failed: ' . 
                    $dbconn->ErrorMsg() . ". While executing $sql\n");


    // Drop the publication fields table

  $sql = "DROP TABLE $pntable[pagesetter_pubfields]";
  $dbconn->Execute($sql);

    // Check for an error with the database code
  if ($dbconn->ErrorNo() != 0)
    pnSessionSetVar('errormsg', 'Drop pubFields table failed: ' . 
                    $dbconn->ErrorMsg() . ". While executing $sql\n");


    // Drop the publication header table

  $sql = "DROP TABLE $pntable[pagesetter_pubheader]";
  $dbconn->Execute($sql);

    // Check for an error with the database code
  if ($dbconn->ErrorNo() != 0)
    pnSessionSetVar('errormsg', 'Drop pubHeader table failed: ' . 
                    $dbconn->ErrorMsg() . ". While executing $sql\n");


    // Drop the revisions table

  $sql = "DROP TABLE $pntable[pagesetter_revisions]";
  $dbconn->Execute($sql);

    // Check for an error with the database code
  if ($dbconn->ErrorNo() != 0)
    pnSessionSetVar('errormsg', 'Drop revisions table failed: ' . 
                    $dbconn->ErrorMsg() . ". While executing $sql\n");


    // Drop the counters table

  $sql = "DROP TABLE $pntable[pagesetter_counters]";
  $dbconn->Execute($sql);

    // Check for an error with the database code
  if ($dbconn->ErrorNo() != 0)
    pnSessionSetVar('errormsg', 'Drop counters table failed: ' . 
                    $dbconn->ErrorMsg() . ". While executing $sql\n");


    // Drop the workflow cfg. table

  $sql = "DROP TABLE $pntable[pagesetter_wfcfg]";
  $dbconn->Execute($sql);

    // Check for an error with the database code
  if ($dbconn->ErrorNo() != 0)
    pnSessionSetVar('errormsg', 'Drop wfcfg table failed: ' . 
                    $dbconn->ErrorMsg() . ". While executing $sql\n");


    // Drop the session table

  $sql = "DROP TABLE $pntable[pagesetter_session]";
  $dbconn->Execute($sql);

    // Check for an error with the database code
  if ($dbconn->ErrorNo() != 0)
    pnSessionSetVar('errormsg', 'Drop session table failed: ' . 
                    $dbconn->ErrorMsg() . ". While executing $sql\n");

                    
    // Drop the relations table
    
  $sql = "DROP TABLE $pntable[pagesetter_relations]";
  $dbconn->Execute($sql);

    // Check for an error with the database code
  if ($dbconn->ErrorNo() != 0)
    pnSessionSetVar('errormsg', 'Drop relations table failed: ' . 
                    $dbconn->ErrorMsg() . ". While executing $sql\n");


    // Drop publication data tables

  foreach ($pubTypes as $pubType)
  {
    $tid = $pubType['id'];
    $dataTable = pagesetterGetPubTableName($tid);

    $sql = "DROP TABLE $dataTable";
    $dbconn->Execute($sql);

      // Check for an error with the database code
    if ($dbconn->ErrorNo() != 0)
      pnSessionSetVar('errormsg', 'Drop data table failed: ' . 
                      $dbconn->ErrorMsg() . ". While executing $sql\n");
  }

    // Drop the list table

  $sql = "DROP TABLE $pntable[pagesetter_lists]";
  $dbconn->Execute($sql);

    // Check for an error with the database code
  if ($dbconn->ErrorNo() != 0)
    pnSessionSetVar('errormsg', 'Drop lists table failed: ' . 
                    $dbconn->ErrorMsg() . ". While executing $sql\n");


    // Drop the list items table

  $sql = "DROP TABLE $pntable[pagesetter_listitems]";
  $dbconn->Execute($sql);

    // Check for an error with the database code
  if ($dbconn->ErrorNo() != 0)
    pnSessionSetVar('errormsg', 'Drop list items table failed: ' . 
                    $dbconn->ErrorMsg() . ". While executing $sql\n");


    // Delete smarty cache
  $smarty = new pnRender('pagesetter');
  $smarty->clear_all_cache();

    // Delete module vars
  pnModDelVar('pagesetter', 'frontpagePubType');
  pnModDelVar('pagesetter', 'uploadDir');
  pnModDelVar('pagesetter', 'autofillPublishDate');

    // Deletion always successful
  return true;
}


// -----------------------------------------------------------------------
// Error handling
// -----------------------------------------------------------------------

function pagesetterInitError($file, $line, $msg)
{
  pnSessionSetVar('errormsg', "$file($line): $msg");

  return false;
}

?>