<?php
// $Id: common-edit.php,v 1.17 2006/05/05 22:22:39 jornlind Exp $
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

require_once ('modules/pagesetter/guppy/guppy.php');


function pagesetter_fetchAttribute(&$attrib, $name, $defaultValue=null)
{
  if (!array_key_exists($name,$attrib))
    return $defaultValue;
  
  return trim($attrib[$name]);
}


function pagesetterGetUploadFilename($pid, $tid, $revision, $fieldName)
{
  return $tid . 'x' . $pid . 'x' . $revision . 'x' . $fieldName . '.dat';
}


function pagesetterGetThumbnailFilename($pid, $tid, $revision, $fieldName)
{
  return $tid . 'x' . $pid . 'x' . $revision . 'x' . $fieldName . '-tmb.dat';
}


function pagesetterFieldTypesGet($type)
{
    // Is this a plugin (defined by having a string type)
  if (!is_numeric($type))
  {
    $plugin = guppy_newPlugin($type);

    return array
    (
      'fieldKind'  => 'input',
      'fieldType'  => $type,
      'layoutKind' => 'input',
      'width'      => $plugin->getDefaultWidth(),
      'height'     => $plugin->getDefaultHeight(),
      'sqlType'    => $plugin->getSqlType()
    );
  }

    // Is this a user defined field type (defined by being a int of specific size)?
  if ($type >= pagesetterFieldTypeListoffset)
  {
      // Get list ID
    $lid = $type - pagesetterFieldTypeListoffset;

    if (!pnModAPILoad('pagesetter', 'admin'))
      return pagesetterErrorApi(__FILE__, __LINE__, 'Failed to load Pagesetter admin API');

    $list = pnModAPIFunc( 'pagesetter',
                          'admin',
                          'getList',
                          array('lid'           => $lid, 
                                'forSelectList' => true) );

    return array
    (
      'fieldKind'  => 'input',
      'fieldType'  => 'select',
      'options'    => $list['items'],
      'layoutKind' => 'input',
      'sqlType'    => 'INT'
    );
  }

  static $fieldTypes = array
  (
      // string
    array
    (
      'fieldKind'  => 'input',
      'fieldType'  => 'string',
      'layoutKind' => 'input',
      'width'      => '400',
      'sqlType'    => 'VARCHAR(255)'
    ),
      // text
    array
    (
      'fieldKind'  => 'input',
      'fieldType'  => 'string',
      'layoutKind' => 'input',
      'view'       => 'text',
      'width'      => '600',
      'height'     => '100',
      'sqlType'    => 'MEDIUMTEXT' 
    ),
      // html
    array
    (
      'fieldKind'  => 'input',
      'fieldType'  => 'string',
      'layoutKind' => 'input',
      'view'       => 'html',
      'width'      => '600',
      'height'     => '300',
      'sqlType'    => 'MEDIUMTEXT'
    ),
      // bool
    array
    (
      'fieldKind'  => 'input',
      'fieldType'  => 'bool',
      'layoutKind' => 'input',
      'width'      => '100',
      'sqlType'    => 'TINYINT'
    ),
      // int
    array
    (
      'fieldKind'  => 'input',
      'fieldType'  => 'int',
      'layoutKind' => 'input',
      'width'      => '100',
      'sqlType'    => 'INT'
    ),
      // real
    array
    (
      'fieldKind'  => 'input',
      'fieldType'  => 'real',
      'layoutKind' => 'input',
      'width'      => '100',
      'sqlType'    => 'FLOAT'
    ),
      // date
    array
    (
      'fieldKind'  => 'input',
      'fieldType'  => 'date',
      'layoutKind' => 'input',
      'width'      => '75',
      'sqlType'    => 'DATE'
    ),
      // time
    array
    (
      'fieldKind'  => 'input',
      'fieldType'  => 'time',
      'layoutKind' => 'input',
      'width'      => '75',
      'sqlType'    => 'TIME'
    ),
      // image
    array
    (
      'fieldKind'  => 'input',
      'fieldType'  => 'image',
      'layoutKind' => 'input',
      'width'      => '200',
      'sqlType'    => 'VARCHAR(255)'
    ),
      // image upload
    array
    (
      'fieldKind'  => 'input',
      'fieldType'  => 'upload',
      'layoutKind' => 'input',
      'width'      => '400',
      'sqlType'    => 'VARCHAR(255)'
    ),
      // any upload
    array
    (
      'fieldKind'  => 'input',
      'fieldType'  => 'upload',
      'layoutKind' => 'input',
      'width'      => '400',
      'sqlType'    => 'VARCHAR(255)'
    ),
      // e-mail
    array
    (
      'fieldKind'  => 'input',
      'fieldType'  => 'email',
      'layoutKind' => 'input',
      'width'      => '200',
      'sqlType'    => 'VARCHAR(100)'
    ),
      // hyperlink
    array
    (
      'fieldKind'  => 'input',
      'fieldType'  => 'url',
      'layoutKind' => 'input',
      'width'      => '200',
      'sqlType'    => 'VARCHAR(255)'
    ),
      // currency
    array
    (
      'fieldKind'  => 'input',
      'fieldType'  => 'real',
      'layoutKind' => 'input',
      'width'      => '75',
      'sqlType'    => 'FLOAT'
    ),
      // publication ID
    array
    (
      'fieldKind'  => 'input',
      'fieldType'  => 'int',
      'layoutKind' => 'input',
      'width'      => '75',
      'sqlType'    => 'INT'
    ),
  );

  return $fieldTypes[$type];
}


function pagesetterFieldTypesGetOptionList()
{
  static $options = array
  (
    array( 'title' => _PGTYPESTRING, 'value' => pagesetterFieldTypeString ),
    array( 'title' => _PGTYPETEXT,   'value' => pagesetterFieldTypeText ),
    array( 'title' => _PGTYPEHTML,   'value' => pagesetterFieldTypeHTML ),
    array( 'title' => _PGTYPEBOOL,   'value' => pagesetterFieldTypeBool ),
    array( 'title' => _PGTYPEINT,    'value' => pagesetterFieldTypeInt ),
    array( 'title' => _PGTYPEREAL,   'value' => pagesetterFieldTypeReal ),
    array( 'title' => _PGTYPEDATE,   'value' => pagesetterFieldTypeDate ),
    array( 'title' => _PGTYPETIME,   'value' => pagesetterFieldTypeTime ),
    array( 'title' => _PGTYPEIMAGE,  'value' => pagesetterFieldTypeImage ),
    array( 'title' => _PGTYPEIMAGEUPLOAD,  'value' => pagesetterFieldTypeImageUpload ),
    array( 'title' => _PGTYPEUPLOAD,       'value' => pagesetterFieldTypeUpload )
  );

  $plugins = pagesetterGetPluginsOptionList();

  if (!pnModAPILoad('pagesetter', 'admin'))
    return pagesetterErrorApi(__FILE__, __LINE__, 'Failed to load Pagesetter admin API');

  $lists = pnModAPIFunc( 'pagesetter',
                         'admin',
                         'getLists' );

  $dynamicOptions = array();
  foreach ($lists as $list)
    $dynamicOptions[] = array( 'title' => $list['title'], 'value' => (string)(pagesetterFieldTypeListoffset+$list['id']) );

  return array_merge(array_merge($options,$plugins), $dynamicOptions);
}


function pagesetterGetPluginsOptionList()
{
  $dir = 'modules/pagesetter/guppy/plugins';

  $plugins = array();

  if ($dh = opendir($dir))
  {
    while (($file = readdir($dh)) !== false)
    {
	  if (substr($file, 0, 6) == "input.")
      {
        $pluginType = guppy_getPluginTypeFromFilename($file);
        $plugin = guppy_newPlugin($pluginType);
        if ($plugin->active())
        {
          $usesExtra = method_exists($plugin,'useExtraTypeInfo') && $plugin->useExtraTypeInfo();

          $plugins[] = array('title'     => $plugin->getTitle(), 
                             'value'     => $pluginType,
                             'usesExtra' => $usesExtra);
        }
      }
    } 
    closedir($dh); 
  }

  return $plugins;
}



function pagesetterHasTopicAccessByTidId($tid, $id, $access)
{
  if (!pnModAPILoad('pagesetter', 'admin'))
    return pagesetterErrorApi(__FILE__, __LINE__, 'Failed to load Pagesetter admin API');

  $pubInfo = pnModAPIFunc( 'pagesetter', 'admin', 'getPubTypeInfo',
                           array('tid' => $tid) );
  if ($pubInfo === false)
    return false;

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

  return true;
}


function pagesetterHasTopicAccess(&$pubInfo, $topic, $access)
{
  if ($pubInfo['publication']['enableTopicAccess'])
  {
    if (!pnModAPILoad('topicaccess', 'user'))
      return pagesetterErrorApi(__FILE__, __LINE__, 'Failed to load Topic Access user API');

    $category = $pubInfo['publication']['filename'];

    $hasAccess = pnModAPIFunc('topicaccess','user','hasTopicAccess',
                              array('topic'     => $topic,
                                    'module'    => 'pagesetter',
                                    'category'  => $category,
                                    'access'    => 'write'));

    return $hasAccess;
  }

  return true;
}


function pagesetterSanitizeIdentifier($id)
{
  return preg_replace('/[^-a-zA-Z0-9_]/', '', $id); 
}

// =======================================================================
// Database locking and pid/revision# generation
// =======================================================================

function pagesetterLockTables(&$dbconn, $tables)
{
  // HOW TO DISABLE LOCKING ... just uncomment the return statement below and
  // do the same in "pagesetterUnlockTables()".

  // return true;

  $sql = null;
  foreach ($tables as $table)
    if ($sql == null)
      $sql = "LOCK TABLES $table WRITE";
    else
      $sql .= ", $table WRITE";

  $result = $dbconn->execute($sql);

  if ($dbconn->errorNo() != 0)
    return pagesetterErrorApi(__FILE__, __LINE__, '"pagesetterLockTables" failed: ' 
                                                  . $dbconn->errorMsg() . " while executing: $sql");

  return true;
}


function pagesetterUnlockTables(&$dbconn)
{
  // HOW TO DISABLE LOCKING ... just uncomment the return statement below.

  // return true;

  $sql = "UNLOCK TABLES";
  $result = $dbconn->execute($sql);

  if ($dbconn->errorNo() != 0)
    return pagesetterErrorApi(__FILE__, __LINE__, '"pagesetterUnlockTables" failed: ' 
                                                  . $dbconn->errorMsg() . " while executing: $sql");

  return true;
}


function pagesetterGetNextCount(&$dbconn, &$pntable, $countName)
{
  $countersTable = $pntable['pagesetter_counters'];
  $countersColumn = $pntable['pagesetter_counters_column'];

    // Lock counter table to avoid concurrence problems
  pagesetterLockTables($dbconn, array($countersTable));

    // Get current count

  $sql = "SELECT $countersColumn[count] FROM $countersTable WHERE $countersColumn[name] = '" . pnVarPrepForStore($countName) . "'";
  $result = $dbconn->execute($sql);

  if ($dbconn->errorNo() != 0)
    return pagesetterErrorApi(__FILE__, __LINE__, '"pagesetterGetNextCount" failed: ' 
                                                  . $dbconn->errorMsg() . " while executing: $sql");

  if ($result->EOF)
    $count = 1;
  else
    $count = intval($result->fields[0]) + 1;

  $result->Close();

    // Update count

  $sql = "REPLACE INTO $countersTable ($countersColumn[count], $countersColumn[name])
          VALUES ($count, '" . pnVarPrepForStore($countName) . "')";
  $dbconn->execute($sql);

  if ($dbconn->errorNo() != 0)
    return pagesetterErrorApi(__FILE__, __LINE__, '"pagesetterGetNextCount" failed: ' 
                                                  . $dbconn->errorMsg() . " while executing: $sql");

  pagesetterUnlockTables($dbconn);

  return $count;
}


function pagesetterGetNextRevision(&$dbconn, &$pubTable, &$pubColumn, $pid)
{
    // Locking of table to avoid concurrency problems must be done by caller

  $sql = "SELECT MAX($pubColumn[revision]) FROM $pubTable WHERE $pubColumn[pid] = $pid";

  $result = $dbconn->execute($sql);

  if ($dbconn->errorNo() != 0)
    return pagesetterErrorApi(__FILE__, __LINE__, '"pagesetterGetNextRevision" failed: ' 
                                                  . $dbconn->errorMsg() . " while executing: $sql");
  if ($result->EOF)
    $revision = 1;
  else
    $revision = intval($result->fields[0]) + 1;

  $result->Close();

  return $revision;
}


?>
