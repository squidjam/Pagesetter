<?php
// $Id: pnintegapi.php,v 1.33 2006/09/22 21:57:26 jornlind Exp $
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

require_once("modules/pagesetter/common.php");
require_once("modules/pagesetter/common-edit.php");


function pagesetter_integapi_importNews($args)
{
    // Check access
  if (!pnSecAuthAction(0, 'pagesetter::', "::", ACCESS_ADMIN))
    return pagesetterErrorApi(__FILE__, __LINE__, _PGNOAUTH);

  if (!pnModAPILoad('pagesetter', 'admin'))
    return pagesetterErrorApi(__FILE__, __LINE__, 'Failed to load Pagesetter admin API');

  $addImage = $args['addImage'];

    // Start by creating a publication type for the News items

  $publication = array( 'title'           => 'PN-News',
                        'filename'        => 'PN-News',
                        'formname'        => 'PN-News',
                        'description'     => 'Imported PostNuke News items',
                        'listCount'       => 10,
                        'enableHooks'     => true,
                        'workflow'        => 'standard',
                        'enableRevisions' => true,
                        'enableEditOwn'      => false,
                        'enableTopicAccess'  => false,
                        'defaultFolder'      => -1,
                        'defaultSubFolder'   => '',
                        'defaultFolderTopic' => -1);

  $fields = array( 
              array ( 'name' => 'title' ,
                      'title' => 'Title',
                      'description' => '',
                      'type' => pagesetterFieldTypeString,
                      'isTitle' => true,
                      'isPageable' => false,
                      'isSearchable' => true,
                      'isMandatory' => true,
                      'lineno' => 0 ),
              array ( 'name' => 'teaser' ,
                      'title' => 'Teaser',
                      'description' => '',
                      'type' => pagesetterFieldTypeText,
                      'isTitle' => false,
                      'isPageable' => false,
                      'isSearchable' => true,
                      'isMandatory' => false,
                      'lineno' => 1 ),
              array ( 'name' => 'text' ,
                      'title' => 'Text',
                      'description' => '',
                      'type' => pagesetterFieldTypeHTML,
                      'isTitle' => false,
                      'isPageable' => true,
                      'isSearchable' => true,
                      'isMandatory' => false,
                      'lineno' => 2 ),
              array ( 'name' => 'notes' ,
                      'title' => 'Notes',
                      'description' => '',
                      'type' => pagesetterFieldTypeText,
                      'isTitle' => false,
                      'isPageable' => false,
                      'isSearchable' => false,
                      'isMandatory' => false,
                      'lineno' => 3 ) );

  if ($addImage)
  {
    $mediashareInstalled = (pnModLoad('mediashare','user') ? 1 : 0);

    if ($mediashareInstalled)
      $imgType = 'mediashare';
    else
      $imgType = pagesetterFieldTypeImage;

    $fields[] = array ( 'name' => 'image' ,
                        'title' => 'Image',
                        'description' => '',
                        'type' => $imgType,
                        'isTitle' => false,
                        'isPageable' => false,
                        'isSearchable' => false,
                        'isMandatory' => false,
                        'lineno' => 4 );
    $fields[] = array ( 'name' => 'imageText' ,
                        'title' => 'Image text',
                        'description' => '',
                        'type' => pagesetterFieldTypeString,
                        'isTitle' => false,
                        'isPageable' => false,
                        'isSearchable' => false,
                        'isMandatory' => false,
                        'lineno' => 5 );
  }

  $user = pnUserGetVar('uid');

  $tid =  pnModAPIFunc( 'pagesetter',
                        'admin',
                        'createPublicationType',
                        array('publication'  => $publication,
                              'fields'       => $fields,
                              'authorID'     => $user) );

  if ($tid === false)
    return pagesetterErrorAPIGet();

  $pubInfo =  pnModAPIFunc( 'pagesetter',
                            'admin',
                            'getPubTypeInfo',
                            array('tid' => $tid) );

    // Get title field column name
  $titleField = pagesetterGetPubColumnName($pubInfo['publication']['titleFieldID']);

    // Get other column names
  foreach ($pubInfo['fields'] as $field)
  {
    $fieldID    = $field['id'];
    $columnName = pagesetterGetPubColumnName($fieldID);

    if ($field['name'] == 'teaser')
      $teaserField = pagesetterGetPubColumnName($field['id']);
    else if ($field['name'] == 'text')
      $textField = pagesetterGetPubColumnName($field['id']);
    else if ($field['name'] == 'notes')
      $notesField = pagesetterGetPubColumnName($field['id']);
  }

  list($dbconn) = pnDBGetConn();
  $pntable = pnDBGetTables();

  $pubTable      = pagesetterGetPubTableName($tid);
  $pubColumn     = $pntable['pagesetter_pubdata_column'];
  $newsTable     = $pntable['stories'];
  $newsColumn    = $pntable['stories_column'];
  $usersTable    = $pntable['users'];
  $usersColumn   = $pntable['users_column'];

    // Here we do something ugly - use revision column for hitcount until it is moved to the pub. header

  $sql = "INSERT INTO $pubTable
          (
            $titleField,
            $teaserField,
            $textField,
            $notesField,
            $pubColumn[pid],
            $pubColumn[approvalState],
            $pubColumn[online],
            $pubColumn[created],
            $pubColumn[lastUpdated],
            $pubColumn[topic],
            $pubColumn[showInMenu],
            $pubColumn[showInList],
            $pubColumn[author],
            $pubColumn[creatorID],
            $pubColumn[revision],
            $pubColumn[language]
          )
          SELECT
            $newsColumn[title],
            $newsColumn[hometext],
            $newsColumn[bodytext],
            $newsColumn[notes],
            $newsColumn[sid],
            'approved',
            NOT $newsColumn[ihome],
            $newsColumn[time],
            $newsColumn[time],
            CASE WHEN $newsColumn[topic] = 0 THEN -1 ELSE $newsColumn[topic] END,
            1,
            1,
            $usersColumn[uname],
            $newsColumn[aid],
            $newsColumn[counter],
            IF ($newsColumn[language] = '', 'x_all', $newsColumn[language])
          FROM $newsTable
          LEFT JOIN $usersTable ON $newsColumn[aid] = $usersColumn[uid]";

  //echo "<pre>$sql</pre>"; exit(0);
  $result = $dbconn->execute($sql);

  if ($dbconn->errorNo() != 0)
    return pagesetterErrorApi(__FILE__, __LINE__, '"importNews" failed: '
                                                  . $dbconn->errorMsg() . " while executing: $sql");

  return pagesetterCreateImportedPubHeader($dbconn, $pntable, $tid, $pubTable, $pubColumn);
}


function pagesetter_integapi_importContentExpress($args)
{
    // Check access
  if (!pnSecAuthAction(0, 'pagesetter::', "::", ACCESS_ADMIN))
    return pagesetterErrorApi(__FILE__, __LINE__, _PGNOAUTH);

  if (!pnModAPILoad('pagesetter', 'admin'))
    return pagesetterErrorApi(__FILE__, __LINE__, 'Failed to load pagesetter admin API');

  if (!pnModAPILoad('ContentExpress', 'admin'))
    return pagesetterErrorApi(__FILE__, __LINE__, 'Failed to load ContentExpress admin API');

    // Start by creating a publication type for the CE items

  $publication = array( 'title'           => 'CE',
                        'filename'        => 'CE',
                        'formname'        => 'CE',
                        'description'     => 'Imported ContentExpress items',
                        'listCount'       => 10,
                        'enableHooks'     => true,
                        'workflow'        => 'standard',
                        'enableRevisions' => true,
                        'enableEditOwn'      => false,
                        'enableTopicAccess'  => false,
                        'defaultFolder'      => -1,
                        'defaultSubFolder'   => '',
                        'defaultFolderTopic' => -1);

  $fields = array( 
              array ( 'name' => 'title' ,
                      'title' => 'Title',
                      'description' => '',
                      'type' => pagesetterFieldTypeString,
                      'isTitle' => true,
                      'isPageable' => false,
                      'isSearchable' => true,
                      'isMandatory' => true,
                      'lineno' => 0 ),
              array ( 'name' => 'text' ,
                      'title' => 'Text',
                      'description' => '',
                      'type' => pagesetterFieldTypeHTML,
                      'isTitle' => false,
                      'isPageable' => true,
                      'isSearchable' => true,
                      'isMandatory' => false,
                      'lineno' => 1 ),
              array ( 'name' => 'notes' ,
                      'title' => 'Notes',
                      'description' => '',
                      'type' => pagesetterFieldTypeText,
                      'isTitle' => false,
                      'isPageable' => false,
                      'isSearchable' => false,
                      'isMandatory' => false,
                      'lineno' => 2 ),
              array ( 'name' => 'image' ,
                      'title' => 'Image',
                      'description' => '',
                      'type' => pagesetterFieldTypeImage,
                      'isTitle' => false,
                      'isPageable' => false,
                      'isSearchable' => false,
                      'isMandatory' => false,
                      'lineno' => 3 ),
              array ( 'name' => 'imageText' ,
                      'title' => 'Image text',
                      'description' => '',
                      'type' => pagesetterFieldTypeString,
                      'isTitle' => false,
                      'isPageable' => false,
                      'isSearchable' => false,
                      'isMandatory' => false,
                      'lineno' => 4 ) );

// Enable title => bool
// Background color => string

  $user = pnUserGetVar('uid');

  $tid =  pnModAPIFunc( 'pagesetter',
                        'admin',
                        'createPublicationType',
                        array('publication'  => $publication,
                              'fields'       => $fields,
                              'authorID'     => $user) );

  if ($tid === false)
    return pagesetterErrorAPIGet();

  $pubInfo =  pnModAPIFunc( 'pagesetter',
                            'admin',
                            'getPubTypeInfo',
                            array('tid' => $tid) );

    // Get title field column name
  $titleField = pagesetterGetPubColumnName($pubInfo['publication']['titleFieldID']);

    // Get other column names
  foreach ($pubInfo['fields'] as $field)
  {
    $fieldID    = $field['id'];
    $columnName = pagesetterGetPubColumnName($fieldID);

    if ($field['name'] == 'text')
      $textField = pagesetterGetPubColumnName($field['id']);
    else if ($field['name'] == 'notes')
      $notesField = pagesetterGetPubColumnName($field['id']);
    else if ($field['name'] == 'image')
      $imageField = pagesetterGetPubColumnName($field['id']);
    else if ($field['name'] == 'imageText')
      $imageTextField = pagesetterGetPubColumnName($field['id']);
  }

  list($dbconn) = pnDBGetConn();
  $pntable = pnDBGetTables();

  $pubTable    = pagesetterGetPubTableName($tid);
  $pubColumn   = $pntable['pagesetter_pubdata_column'];
  $CETable     = $pntable['ce_contentitems'];
  $CEColumn    = $pntable['ce_contentitems_column'];

    // Here we do something ugly - use revision column for hitcount until it is moved to the pub. header

  $sql = "INSERT INTO $pubTable
          (
            $titleField,
            $textField,
            $notesField,
            $imageField,
            $imageTextField,
            $pubColumn[pid],
            $pubColumn[approvalState],
            $pubColumn[online],
            $pubColumn[created],
            $pubColumn[lastUpdated],
            $pubColumn[publishDate],
            $pubColumn[expireDate],
            $pubColumn[topic],
            $pubColumn[showInMenu],
            $pubColumn[showInList],
            $pubColumn[author],
            $pubColumn[creatorID],
            $pubColumn[revision],
            $pubColumn[language]
          )
          SELECT
            $CEColumn[title],
            $CEColumn[text],
            $CEColumn[notes],
            $CEColumn[media_url],
            $CEColumn[media_text],
            $CEColumn[id],
            'approved',
            1,
            $CEColumn[last_updated],
            $CEColumn[last_updated],
            $CEColumn[start_date],
            $CEColumn[end_date],
            -1,
            1,
            1,
            $CEColumn[author],
            $user,
            $CEColumn[times_read],
            $CEColumn[language]
          FROM $CETable";

  //echo "<pre>$sql</pre>"; exit(0);
  $result = $dbconn->execute($sql);

  if ($dbconn->errorNo() != 0)
    return pagesetterErrorApi(__FILE__, __LINE__, '"importContentExpress" failed: '
                                                  . $dbconn->errorMsg() . " while executing: $sql");
  
  return pagesetterCreateImportedPubHeader($dbconn, $pntable, $tid, $pubTable, $pubColumn);
}


function pagesetter_integapi_createArticle($args)
{
    // Check access
  if (!pnSecAuthAction(0, 'pagesetter::', "::", ACCESS_ADMIN))
    return pagesetterErrorApi(__FILE__, __LINE__, _PGNOAUTH);

  if (!pnModAPILoad('pagesetter', 'admin'))
    return pagesetterErrorApi(__FILE__, __LINE__, 'Failed to load pagesetter admin API');


  $publication = array( 'title'           => 'Article',
                        'filename'        => 'Article',
                        'formname'        => 'Article',
                        'description'     => 'General purpose article with title, text and image',
                        'listCount'       => 10,
                        'enableHooks'     => true,
                        'workflow'        => 'standard',
                        'enableRevisions' => true,
                        'enableEditOwn'      => false,
                        'enableTopicAccess'  => false,
                        'defaultFolder'      => -1,
                        'defaultSubFolder'   => '',
                        'defaultFolderTopic' => -1);

  $fields = array( 
              array ( 'name' => 'title' ,
                      'title' => 'Title',
                      'description' => 'Title for this article',
                      'type' => pagesetterFieldTypeString,
                      'isTitle' => true,
                      'isPageable' => false,
                      'isSearchable' => true,
                      'isMandatory' => true,
                      'lineno' => 0 ),
              array ( 'name' => 'text' ,
                      'title' => 'Text',
                      'description' => 'Article text (body of the article)',
                      'type' => pagesetterFieldTypeHTML,
                      'isTitle' => false,
                      'isPageable' => true,
                      'isSearchable' => true,
                      'isMandatory' => false,
                      'lineno' => 1 ),
              array ( 'name' => 'image' ,
                      'title' => 'Image',
                      'description' => '',
                      'type' => pagesetterFieldTypeImageUpload,
                      'isTitle' => false,
                      'isPageable' => false,
                      'isSearchable' => false,
                      'isMandatory' => false,
                      'lineno' => 2 ),
              array ( 'name' => 'imageText' ,
                      'title' => 'Image text',
                      'description' => '',
                      'type' => pagesetterFieldTypeString,
                      'isTitle' => false,
                      'isPageable' => false,
                      'isSearchable' => false,
                      'isMandatory' => false,
                      'lineno' => 3 ),
              array ( 'name' => 'notes' ,
                      'title' => 'Notes',
                      'description' => 'Internal notes. Not displayed in the standard template.',
                      'type' => pagesetterFieldTypeText,
                      'isTitle' => false,
                      'isPageable' => false,
                      'isSearchable' => false,
                      'isMandatory' => false,
                      'lineno' => 4 ) );

  $photoshareInstalled = (pnModLoad('photoshare','user') ? 1 : 0);
  $mediashareInstalled = (pnModLoad('mediashare','user') ? 1 : 0);

  if ($mediashareInstalled)
  {
    $fields[2]['type'] = 'mediashare';
  }
  else if ($photoshareInstalled)
  {
    $fields[2]['type'] = pagesetterFieldTypeImage;
  }

  $user = pnUserGetVar('uid');

  $tid =  pnModAPIFunc( 'pagesetter',
                        'admin',
                        'createPublicationType',
                        array('publication'  => $publication,
                              'fields'       => $fields,
                              'authorID'     => $user) );

  if ($tid === false)
    return pagesetterErrorAPIGet();

  return true;
}


function pagesetter_integapi_importFileUpload($args)
{
    // Check access
  if (!pnSecAuthAction(0, 'pagesetter::', "::", ACCESS_ADMIN))
    return pagesetterErrorApi(__FILE__, __LINE__, _PGNOAUTH);

  if (!pnModAPILoad('pagesetter', 'admin'))
    return pagesetterErrorApi(__FILE__, __LINE__, 'Failed to load pagesetter admin API');

  $publication = array( 'title'           => 'File Upload',
                        'filename'        => 'FileUpload',
                        'formname'        => 'FileUpload',
                        'description'     => 'Generic file uploads - mostly for use with Folder module',
                        'listCount'       => 10,
                        'enableHooks'     => true,
                        'workflow'        => 'standard',
                        'enableRevisions' => false,
                        'enableEditOwn'      => false,
                        'enableTopicAccess'  => false,
                        'defaultFolder'      => 0,
                        'defaultSubFolder'   => 'FileUploads',
                        'defaultFolderTopic' => -1);

  $fields = array( 
              array ( 'name' => 'title' ,
                      'title' => 'Title',
                      'description' => '',
                      'type' => pagesetterFieldTypeString,
                      'isTitle' => true,
                      'isPageable' => false,
                      'isSearchable' => true,
                      'isMandatory' => true,
                      'lineno' => 0 ),
              array ( 'name' => 'description' ,
                      'title' => 'Description',
                      'description' => '',
                      'type' => pagesetterFieldTypeText,
                      'isTitle' => false,
                      'isPageable' => false,
                      'isSearchable' => true,
                      'isMandatory' => false,
                      'lineno' => 1 ),
              array ( 'name' => 'file' ,
                      'title' => 'File',
                      'description' => '',
                      'type' => pagesetterFieldTypeUpload,
                      'isTitle' => false,
                      'isPageable' => false,
                      'isSearchable' => false,
                      'isMandatory' => false,
                      'lineno' => 2 ) );

  $user = pnUserGetVar('uid');

  $tid =  pnModAPIFunc( 'pagesetter',
                        'admin',
                        'createPublicationType',
                        array('publication'  => $publication,
                              'fields'       => $fields,
                              'authorID'     => $user) );

  if ($tid === false)
    return pagesetterErrorAPIGet();

  return true;
}


function pagesetter_integapi_importImage($args)
{
    // Check access
  if (!pnSecAuthAction(0, 'pagesetter::', "::", ACCESS_ADMIN))
    return pagesetterErrorApi(__FILE__, __LINE__, _PGNOAUTH);

  if (!pnModAPILoad('pagesetter', 'admin'))
    return pagesetterErrorApi(__FILE__, __LINE__, 'Failed to load pagesetter admin API');

  $publication = array( 'title'           => 'Image',
                        'filename'        => 'Image',
                        'formname'        => 'Image',
                        'description'     => 'Image uploads - mostly for use with Folder module',
                        'listCount'       => 10,
                        'enableHooks'     => true,
                        'workflow'        => 'standard',
                        'enableRevisions' => false,
                        'enableEditOwn'      => false,
                        'enableTopicAccess'  => false,
                        'defaultFolder'      => 0,
                        'defaultSubFolder'   => 'Images',
                        'defaultFolderTopic' => -1);

  $fields = array( 
              array ( 'name' => 'title' ,
                      'title' => 'Title',
                      'description' => '',
                      'type' => pagesetterFieldTypeString,
                      'isTitle' => true,
                      'isPageable' => false,
                      'isSearchable' => true,
                      'isMandatory' => true,
                      'lineno' => 0 ),
              array ( 'name' => 'description' ,
                      'title' => 'Description',
                      'description' => '',
                      'type' => pagesetterFieldTypeText,
                      'isTitle' => false,
                      'isPageable' => false,
                      'isSearchable' => true,
                      'isMandatory' => false,
                      'lineno' => 1 ),
              array ( 'name' => 'image' ,
                      'title' => 'Image',
                      'description' => '',
                      'type' => pagesetterFieldTypeImageUpload,
                      'isTitle' => false,
                      'isPageable' => false,
                      'isSearchable' => false,
                      'isMandatory' => false,
                      'lineno' => 2 ) );

  $user = pnUserGetVar('uid');

  $tid =  pnModAPIFunc( 'pagesetter',
                        'admin',
                        'createPublicationType',
                        array('publication'  => $publication,
                              'fields'       => $fields,
                              'authorID'     => $user) );

  if ($tid === false)
    return pagesetterErrorAPIGet();

  return true;
}


function pagesetter_integapi_importNote($args)
{
    // Check access
  if (!pnSecAuthAction(0, 'pagesetter::', "::", ACCESS_ADMIN))
    return pagesetterErrorApi(__FILE__, __LINE__, _PGNOAUTH);

  if (!pnModAPILoad('pagesetter', 'admin'))
    return pagesetterErrorApi(__FILE__, __LINE__, 'Failed to load pagesetter admin API');

  $publication = array( 'title'           => 'Note',
                        'filename'        => 'Note',
                        'formname'        => 'Note',
                        'description'     => 'A simple HTML note - mostly for use with Folder module',
                        'listCount'       => 10,
                        'enableHooks'     => false,
                        'workflow'        => 'mywiki',
                        'enableRevisions' => false,
                        'enableEditOwn'      => true,
                        'enableTopicAccess'  => true,
                        'defaultFolder'      => 0,
                        'defaultSubFolder'   => 'Notes',
                        'defaultFolderTopic' => -1);

  $fields = array( 
              array ( 'name' => 'title' ,
                      'title' => 'Title',
                      'description' => '',
                      'type' => pagesetterFieldTypeString,
                      'isTitle' => true,
                      'isPageable' => false,
                      'isSearchable' => true,
                      'isMandatory' => true,
                      'lineno' => 0 ),
              array ( 'name' => 'text' ,
                      'title' => 'Text',
                      'description' => '',
                      'type' => pagesetterFieldTypeHTML,
                      'isTitle' => false,
                      'isPageable' => false,
                      'isSearchable' => true,
                      'isMandatory' => false,
                      'lineno' => 1 ) );

  $user = pnUserGetVar('uid');

  $tid =  pnModAPIFunc( 'pagesetter',
                        'admin',
                        'createPublicationType',
                        array('publication'  => $publication,
                              'fields'       => $fields,
                              'authorID'     => $user) );

  if ($tid === false)
    return pagesetterErrorAPIGet();

  return true;
}


function pagesetter_integapi_importPostCalendar($args)
{
    // Check access
  if (!pnSecAuthAction(0, 'pagesetter::', "::", ACCESS_ADMIN))
    return pagesetterErrorApi(__FILE__, __LINE__, _PGNOAUTH);

  if (!pnModAPILoad('pagesetter', 'admin'))
    return pagesetterErrorApi(__FILE__, __LINE__, 'Failed to load pagesetter admin API');

  if (!pnModAPILoad('PostCalendar', 'admin'))
    return pagesetterErrorApi(__FILE__, __LINE__, 'Failed to load PostCalendar admin API');

    // Start by creating a publication type for the PC items

  $publication = array( 'title'           => 'PostCalendar',
                        'filename'        => 'PostCalendar',
                        'formname'        => 'PostCalendar',
                        'description'     => 'Imported PostCalendar items',
                        'listCount'       => 10,
                        'enableHooks'     => true,
                        'workflow'        => 'standard',
                        'enableRevisions' => true,
                        'defaultFilter'   => 'startDate:ge:@now',
                        'enableEditOwn'      => false,
                        'enableTopicAccess'  => false,
                        'defaultFolder'      => -1,
                        'defaultSubFolder'   => '',
                        'defaultFolderTopic' => -1);

  $fields = array( 
              array ( 'name' => 'title' ,
                      'title' => 'Title',
                      'description' => '',
                      'type' => pagesetterFieldTypeString,
                      'isTitle' => true,
                      'isPageable' => false,
                      'isSearchable' => true,
                      'isMandatory' => true,
                      'lineno' => 0 ),
              array ( 'name' => 'text' ,
                      'title' => 'Text',
                      'description' => '',
                      'type' => pagesetterFieldTypeHTML,
                      'isTitle' => false,
                      'isPageable' => false,
                      'isSearchable' => true,
                      'isMandatory' => false,
                      'lineno' => 1 ),
              array ( 'name' => 'startDate' ,
                      'title' => 'Start date',
                      'description' => '',
                      'type' => 'datetime',
                      'isTitle' => false,
                      'isPageable' => false,
                      'isSearchable' => false,
                      'isMandatory' => false,
                      'lineno' => 2 ),
              array ( 'name' => 'endDate' ,
                      'title' => 'End date',
                      'description' => '',
                      'type' => 'datetime',
                      'isTitle' => false,
                      'isPageable' => false,
                      'isSearchable' => false,
                      'isMandatory' => false,
                      'lineno' => 3 ),
              array ( 'name' => 'location' ,
                      'title' => 'Location',
                      'description' => '',
                      'type' => pagesetterFieldTypeString,
                      'isTitle' => false,
                      'isPageable' => false,
                      'isSearchable' => true,
                      'isMandatory' => false,
                      'lineno' => 4 ),
              array ( 'name' => 'address1' ,
                      'title' => 'Address',
                      'description' => '',
                      'type' => pagesetterFieldTypeString,
                      'isTitle' => false,
                      'isPageable' => false,
                      'isSearchable' => true,
                      'isMandatory' => false,
                      'lineno' => 5 ),
              array ( 'name' => 'address2' ,
                      'title' => 'Address',
                      'description' => '',
                      'type' => pagesetterFieldTypeString,
                      'isTitle' => false,
                      'isPageable' => false,
                      'isSearchable' => true,
                      'isMandatory' => false,
                      'lineno' => 6 ),
              array ( 'name' => 'city' ,
                      'title' => 'City/Town',
                      'description' => '',
                      'type' => pagesetterFieldTypeString,
                      'isTitle' => false,
                      'isPageable' => false,
                      'isSearchable' => true,
                      'isMandatory' => false,
                      'lineno' => 7 ),
              array ( 'name' => 'state' ,
                      'title' => 'State/Province',
                      'description' => '',
                      'type' => pagesetterFieldTypeString,
                      'isTitle' => false,
                      'isPageable' => false,
                      'isSearchable' => true,
                      'isMandatory' => false,
                      'lineno' => 8 ),
              array ( 'name' => 'postalcode' ,
                      'title' => 'Zip/Postal code',
                      'description' => '',
                      'type' => pagesetterFieldTypeString,
                      'isTitle' => false,
                      'isPageable' => false,
                      'isSearchable' => false,
                      'isMandatory' => false,
                      'lineno' => 9 ),
              array ( 'name' => 'name' ,
                      'title' => 'Contact name',
                      'description' => '',
                      'type' => pagesetterFieldTypeString,
                      'isTitle' => false,
                      'isPageable' => false,
                      'isSearchable' => true,
                      'isMandatory' => false,
                      'lineno' => 10 ),
              array ( 'name' => 'phonenumber' ,
                      'title' => 'Phone number',
                      'description' => '',
                      'type' => pagesetterFieldTypeString,
                      'isTitle' => false,
                      'isPageable' => false,
                      'isSearchable' => true,
                      'isMandatory' => false,
                      'lineno' => 11 ),
              array ( 'name' => 'email' ,
                      'title' => 'Email',
                      'description' => '',
                      'type' => 'email',
                      'isTitle' => false,
                      'isPageable' => false,
                      'isSearchable' => true,
                      'isMandatory' => false,
                      'lineno' => 12 ),
              array ( 'name' => 'website' ,
                      'title' => 'Website',
                      'description' => '',
                      'type' => 'url',
                      'isTitle' => false,
                      'isPageable' => false,
                      'isSearchable' => false,
                      'isMandatory' => false,
                      'lineno' => 13 ),
              array ( 'name' => 'fee' ,
                      'title' => 'Fee',
                      'description' => '',
                      'type' => pagesetterFieldTypeInt,
                      'isTitle' => false,
                      'isPageable' => false,
                      'isSearchable' => false,
                      'isMandatory' => false,
                      'lineno' => 14 )
                 );


  $user = pnUserGetVar('uid');

  $tid =  pnModAPIFunc( 'pagesetter',
                        'admin',
                        'createPublicationType',
                        array('publication'  => $publication,
                              'fields'       => $fields,
                              'authorID'     => $user) );

  if ($tid === false)
    return pagesetterErrorAPIGet();

  $pubInfo =  pnModAPIFunc( 'pagesetter',
                            'admin',
                            'getPubTypeInfo',
                            array('tid' => $tid) );

    // Get title field column name
  $titleField = pagesetterGetPubColumnName($pubInfo['publication']['titleFieldID']);

    // Get other column names
  foreach ($pubInfo['fields'] as $field)
  {
    $fieldID    = $field['id'];
    $columnName = pagesetterGetPubColumnName($fieldID);

    if ($field['name'] == 'text')
      $textField = pagesetterGetPubColumnName($field['id']);
    else if ($field['name'] == 'startDate')
      $startDateField = pagesetterGetPubColumnName($field['id']);
    else if ($field['name'] == 'endDate')
      $endDateField = pagesetterGetPubColumnName($field['id']);
    else if ($field['name'] == 'location')
      $locationField = pagesetterGetPubColumnName($field['id']);
    else if ($field['name'] == 'address1')
      $address1Field = pagesetterGetPubColumnName($field['id']);
    else if ($field['name'] == 'address2')
      $address2Field = pagesetterGetPubColumnName($field['id']);
    else if ($field['name'] == 'state')
      $stateField = pagesetterGetPubColumnName($field['id']);
    else if ($field['name'] == 'city')
      $cityField = pagesetterGetPubColumnName($field['id']);
    else if ($field['name'] == 'postalcode')
      $postalcodeField = pagesetterGetPubColumnName($field['id']);
    else if ($field['name'] == 'name')
      $nameField = pagesetterGetPubColumnName($field['id']);
    else if ($field['name'] == 'phonenumber')
      $phonenumberField = pagesetterGetPubColumnName($field['id']);
    else if ($field['name'] == 'email')
      $emailField = pagesetterGetPubColumnName($field['id']);
    else if ($field['name'] == 'website')
      $websiteField = pagesetterGetPubColumnName($field['id']);
    else if ($field['name'] == 'fee')
      $feeField = pagesetterGetPubColumnName($field['id']);
  }

  list($dbconn) = pnDBGetConn();
  $pntable = pnDBGetTables();

  $pubTable    = pagesetterGetPubTableName($tid);
  $pubColumn   = $pntable['pagesetter_pubdata_column'];
  $PCTable     = $pntable['postcalendar_events'];
  $PCColumn    = $pntable['postcalendar_events_column'];

    // Here we do something ugly - use revision column for hitcount until it is moved to the pub. header

  $sql = "INSERT INTO $pubTable
          (
            $titleField,
            $textField,
            $startDateField,
            $endDateField,
            $locationField,
            $address1Field,
            $address2Field,
            $stateField,
            $cityField,
            $postalcodeField,
            $nameField,
            $phonenumberField,
            $emailField,
            $websiteField,
            $feeField,
            $pubColumn[pid],
            $pubColumn[approvalState],
            $pubColumn[online],
            $pubColumn[created],
            $pubColumn[lastUpdated],
            $pubColumn[publishDate],
            $pubColumn[expireDate],
            $pubColumn[topic],
            $pubColumn[showInMenu],
            $pubColumn[showInList],
            $pubColumn[author],
            $pubColumn[creatorID],
            $pubColumn[revision],
            $pubColumn[language]
          )
          SELECT
            $PCColumn[title],
            $PCColumn[hometext],
			$PCColumn[eventDate] + INTERVAL $PCColumn[startTime] HOUR_SECOND,
            DATE_ADD($PCColumn[eventDate], INTERVAL $PCColumn[startTime] HOUR_SECOND) + INTERVAL $PCColumn[duration] SECOND,
            $PCColumn[location],
            $PCColumn[location],
            $PCColumn[location],
            $PCColumn[location],
            $PCColumn[location],
            $PCColumn[location],
            $PCColumn[contname],
            $PCColumn[conttel],
            $PCColumn[contemail],
            $PCColumn[website],
            $PCColumn[fee],

            $PCColumn[eid],
            'approved',
            1,
            $PCColumn[time],
            $PCColumn[time],
            NULL,
            NULL,
            $PCColumn[topic],
            1,
            1,
            $PCColumn[informant],
            $user,
            $PCColumn[counter],
            $PCColumn[language]
          FROM $PCTable";

  //echo "<pre>$sql</pre>"; exit(0);
  $result = $dbconn->execute($sql);

  if ($dbconn->errorNo() != 0)
    return pagesetterErrorApi(__FILE__, __LINE__, '"importPostCalendar" failed: '
                                                  . $dbconn->errorMsg() . " while executing: $sql");
  
  $ok = pagesetterCreateImportedPubHeader($dbconn, $pntable, $tid, $pubTable, $pubColumn);
  if (!$ok)
    return false;

  $sql = "SELECT $pubColumn[id], $locationField, $textField, $pubColumn[language] FROM $pubTable";

  $result = $dbconn->execute($sql);

  if ($dbconn->errorNo() != 0)
    return pagesetterErrorApi(__FILE__, __LINE__, '"importPostCalendar" failed: '
                                                  . $dbconn->errorMsg() . " while executing: $sql");

  for (; !$result->EOF; $result->MoveNext())
  {
    $id = $result->fields[0];
    $locationData = unserialize($result->fields[1]);
    $text = substr($result->fields[2],6);
    $language = $result->fields[3];

    if ($language == '')
      $language = 'x_all';

    $sql = "UPDATE $pubTable SET
              $locationField = '" . pnVarPrepForStore($locationData['event_location']) . "',
              $address1Field = '" . pnVarPrepForStore($locationData['event_street1']) . "',
              $address2Field = '" . pnVarPrepForStore($locationData['event_street2']) . "',
              $cityField = '" . pnVarPrepForStore($locationData['event_city']) . "',
              $stateField = '" . pnVarPrepForStore($locationData['event_state']) . "',
              $postalcodeField = '" . pnVarPrepForStore($locationData['event_postal']) . "',
              $textField = '" . pnVarPrepForStore($text) . "',
              $pubColumn[language] = '" . pnVarPrepForStore($language) . "'
            WHERE $pubColumn[id] = $id";

    $dbconn->execute($sql);

    if ($dbconn->errorNo() != 0)
      return pagesetterErrorApi(__FILE__, __LINE__, '"importPostCalendar" failed: '
                                                    . $dbconn->errorMsg() . " while executing: $sql");
  }

  $result->Close();
  
  return true;
}


function pagesetterCreateImportedPubHeader(&$dbconn, &$pntable, $tid, $pubTable, $pubColumn)
{
    // Now update publication header info and transfer hitcount from revision column

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
                 $pubColumn[revision],
                 $pubColumn[pid],
                 0
          FROM $pubTable";

  $dbconn->execute($sql);

  if ($dbconn->errorNo() != 0)
    return pagesetterErrorApi(__FILE__, __LINE__, '"pagesetterCreateImportedPubHeader" failed: '
                                                  . $dbconn->errorMsg() . " while executing: $sql");

  $sql = "UPDATE $pubTable SET $pubColumn[revision] = 1";

  $dbconn->execute($sql);

  if ($dbconn->errorNo() != 0)
    return pagesetterErrorApi(__FILE__, __LINE__, '"pagesetterCreateImportedPubHeader" failed: '
                                                  . $dbconn->errorMsg() . " while executing: $sql");

    // Update pid counter

  $countersTable = $pntable['pagesetter_counters'];
  $countersColumn = $pntable['pagesetter_counters_column'];

  $sql = "REPLACE INTO $countersTable 
          (
            $countersColumn[name], 
            $countersColumn[count]
          )
          SELECT 
            'tid$tid',
            MAX($pubColumn[pid]) FROM $pubTable";

  $dbconn->execute($sql);

  if ($dbconn->errorNo() != 0)
    return pagesetterErrorApi(__FILE__, __LINE__, '"pagesetterCreateImportedPubHeader" failed: '
                                                  . $dbconn->errorMsg() . " while executing: $sql");

  return true;
}


// =======================================================================
// Transfer publications to folder module
// =======================================================================

function pagesetter_integapi_transferPubTypeToFolders($args)
{
  $tid = (int)$args['tid'];

  if (!pnModAPILoad('pagesetter', 'edit'))
    return pagesetterErrorPage(__FILE__, __LINE__, 'Failed to load Pagesetter edit API');

  if (!pnModAPILoad('pagesetter', 'user'))
    return pagesetterErrorPage(__FILE__, __LINE__, 'Failed to load Pagesetter user API');

  if (!pnModAPILoad('folder', 'user'))
    return pagesetterErrorPage(__FILE__, __LINE__, 'Failed to load Folder user API');

  $pubInfo = pnModAPIFunc( 'pagesetter', 'admin', 'getPubTypeInfo',
                           array('tid' => $tid) );
  if ($pubInfo === false)
    return pagesetterErrorAPIGet();

  list($dbconn) = pnDBGetConn();
  $pntable = &pnDBGetTables();

  $pubHeaderTable  = $pntable['pagesetter_pubheader'];
  $pubHeaderColumn = $pntable['pagesetter_pubheader_column'];

  $sql = "SELECT $pubHeaderColumn[pid] 
          FROM $pubHeaderTable
          WHERE $pubHeaderColumn[tid] = $tid";

  $result = $dbconn->execute($sql);

  if ($dbconn->errorNo() != 0)
    return pagesetterErrorApi(__FILE__, __LINE__, '"transferPubTypeToFolders" failed: ' 
                                                  . $dbconn->errorMsg() . " while executing: $sql");

  for (; !$result->EOF; $result->MoveNext())
  {
    $pid = $result->fields[0];
    $key = "$tid.$pid";

    $publication = pnModAPIFunc( 'pagesetter', 'user', 'getPub',
                                 array('tid'    => $tid, 
                                       'pid'    => $pid,
                                       'format' => 'user',
                                       'useRestrictions' => false,
                                       'notInDepot'      => true) );
    if ($publication === false)
      return pnModAPIFunc('pagesetter', 'user', 'errorAPIGet');
    if ($publication === true)
      continue;

    $folderId = $pubInfo['publication']['defaultFolder'];
    if ($pubInfo['publication']['defaultSubFolder'] != '')
    {
      $subFolder = $pubInfo['publication']['defaultSubFolder'];

      $subFolder = pagesetterExpandSubFolder($subFolder, $publication, $tid);

      $folderId = pnModAPIFunc('folder', 'user', 'ensureFolder',
                               array('parentId' => $folderId,
                                     'path'     => $subFolder,
                                     'topicId'  => $pubInfo['publication']['defaultFolderTopic']));
      if ($folderId === false)
        return pnModAPIFunc('folder', 'user', 'errorAPIGet');
    }

    $ok = pnModAPIFunc('folder', 'user', 'addItem',
                       array('folderId' => $folderId,
                             'module'   => 'pagesetter',
                             'type'     => $pubInfo['publication']['filename'],
                             'title'    => $publication['core']['title'],
                             'key'      => $key));
    if (!$ok)
      return pnModAPIFunc('folder', 'user', 'errorAPIGet');
  }

  $result->close();
}


// =======================================================================
// Export Pagesetter schema
// =======================================================================

function pagesetter_integapi_export($args)
{
  if (!isset($args['tid']))
    return pagesetterErrorApi(__FILE__, __LINE__, "Missing argument 'tid' in 'pagesetter_integapi_export'");

  $tid = $args['tid'];

    // Check access
  if (!pnSecAuthAction(0, 'pagesetter::', "$tid::", ACCESS_READ))
    return pagesetterErrorApi(__FILE__, __LINE__, _PGNOAUTH);

  if (!pnModAPILoad('pagesetter', 'admin'))
    return pagesetterErrorApi(__FILE__, __LINE__, 'Failed to load pagesetter admin API');

  $pubInfo =  pnModAPIFunc( 'pagesetter',
                            'admin',
                            'getPubTypeInfo',
                            array('tid' => $tid) );

  if ($pubInfo === false)
    return false;

  $pub    = $pubInfo['publication'];
  $fields = $pubInfo['fields'];

  header('Content-Type: text/xml');
  header('Content-Disposition: attachment; filename="' . htmlspecialchars($pub['title']) . '-schema.xml"');

  echo "<?xml version=\"1.0\" encoding=\"ISO-8859-1\"?>\n"; // <?php - get editor in php mode again :-)
  echo "<publicationSchema xmlns=\"www.postnuke.com/pagesetter\" version=\"1.0\">\n";

    // First export eventual categories
  foreach ($fields as $field)
    if ($field['type']  > pagesetterFieldTypeListoffset)
      pagesetterDumpCategoryXML($field['type']  - pagesetterFieldTypeListoffset);

  $sortField1 = $pub['sortField1'];
  $sortField2 = $pub['sortField2'];
  $sortField3 = $pub['sortField3'];

  echo " <publication>\n";
  echo "  <title>" . htmlspecialchars($pub['title']) . "</title>\n";
  echo "  <template>" . htmlspecialchars($pub['filename']) . "</template>\n";
  echo "  <form>" . htmlspecialchars($pub['formname']) . "</form>\n";
  echo "  <description>" . htmlspecialchars($pub['description']) . "</description>\n";
  echo "  <author>" . htmlspecialchars($pub['author']) . "</author>\n";
  echo "  <created>" . htmlspecialchars($pub['created']) . "</created>\n";
  echo "  <listCount>" . htmlspecialchars($pub['listCount']) . "</listCount>\n";
  echo "  <sortField1>" . $sortField1 . "</sortField1>\n";
  echo "  <sortDesc1>" . htmlspecialchars($pub['sortDesc1']) . "</sortDesc1>\n";
  echo "  <sortField2>" . $sortField2 . "</sortField2>\n";
  echo "  <sortDesc2>" . htmlspecialchars($pub['sortDesc2']) . "</sortDesc2>\n";
  echo "  <sortField3>" . $sortField3 . "</sortField3>\n";
  echo "  <sortDesc3>" . htmlspecialchars($pub['sortDesc3']) . "</sortDesc3>\n";
  echo "  <defaultFilter>" . htmlspecialchars($pub['defaultFilter']) . "</defaultFilter>\n";
  echo "  <enableHooks>" . htmlspecialchars($pub['enableHooks']) . "</enableHooks>\n";
  echo "  <workflow>" . htmlspecialchars($pub['workflow']) . "</workflow>\n";
  echo "  <enableRevisions>" . htmlspecialchars($pub['enableRevisions']) . "</enableRevisions>\n";
  echo "  <enableEditOwn>" . htmlspecialchars($pub['enableEditOwn']) . "</enableEditOwn>\n";
  echo "  <enableTopicAccess>" . htmlspecialchars($pub['enableTopicAccess']) . "</enableTopicAccess>\n";
  echo "  <defaultFolder>" . htmlspecialchars($pub['defaultFolder']) . "</defaultFolder>\n";
  echo "  <defaultSubFolder>" . htmlspecialchars($pub['defaultSubFolder']) . "</defaultSubFolder>\n";
  echo "  <defaultFolderTopic>" . htmlspecialchars($pub['defaultFolderTopic']) . "</defaultFolderTopic>\n";

  echo "  <fields>\n";
  foreach ($fields as $field)
  {
    echo "   <field id=\"$field[id]\">\n";
    echo "    <name>" . htmlspecialchars($field['name']) . "</name>\n";
    echo "    <title>" . htmlspecialchars($field['title']) . "</title>\n";
    echo "    <description>" . htmlspecialchars($field['description']) . "</description>\n";
    echo "    <type>" . htmlspecialchars($field['type']) . "</type>\n";
    echo "    <typeData>" . htmlspecialchars($field['typeData']) . "</typeData>\n";
    echo "    <isTitle>" . htmlspecialchars($field['isTitle']) . "</isTitle>\n";
    echo "    <isPageable>" . htmlspecialchars($field['isPageable']) . "</isPageable>\n";
    echo "    <isSearchable>" . htmlspecialchars($field['isSearchable']) . "</isSearchable>\n";
    echo "    <isMandatory>" . htmlspecialchars($field['isMandatory']) . "</isMandatory>\n";
    echo "   </field>\n";
  }
  echo "  </fields>\n";
  echo " </publication>\n";

  echo "</publicationSchema>\n";

  return true;
}


function pagesetterDumpCategoryXML($categoryID)
{
  echo " <category id=\"$categoryID\">\n";

  $categoryInfo = pnModAPIFunc( 'pagesetter',
                                'admin',
                                'getList',
                                array('lid' => $categoryID) );

  if ($categoryInfo === false)
    return false;

  $category = $categoryInfo['list'];
  $items    = $categoryInfo['items'];

  echo "  <title>" . htmlspecialchars($category['title']) . "</title>\n";
  echo "  <description>" . htmlspecialchars($category['description']) . "</description>\n";

  echo "  <items>\n";
  foreach ($items as $item)
  {
    echo "   <item id=\"$item[id]\">\n";
    echo "    <title>" . htmlspecialchars($item['title']) . "</title>\n";
    echo "    <fullTitle>" . htmlspecialchars($item['fullTitle']) . "</fullTitle>\n";
    echo "    <value>" . htmlspecialchars($item['value']) . "</value>\n";
    echo "    <description>" . htmlspecialchars($item['description']) . "</description>\n";
    echo "    <indent>" . htmlspecialchars($item['indent']) . "</indent>\n";
    echo "   </item>\n";
  }
  echo "  </items>\n";

  echo " </category>\n";
}


// =======================================================================
// Import Pagesetter schema
// =======================================================================

function pagesetter_integapi_importXMLSchema($args)
{
  if (!isset($args['filename']))
    return pagesetterErrorApi(__FILE__, __LINE__, "Missing argument 'filename' in 'pagesetter_integapi_importXMLSchema'");

  $filename = $args['filename'];

    // Check access
  if (!pnSecAuthAction(0, 'pagesetter::', "::", ACCESS_ADMIN))
    return pagesetterErrorApi(__FILE__, __LINE__, _PGNOAUTH);

  if (!pnModAPILoad('pagesetter', 'admin'))
    return pagesetterErrorApi(__FILE__, __LINE__, 'Failed to load pagesetter admin API');

  return pagesetterParseXMLSchema($filename);
}


// Returns false on error, and array('tid' => $tid) on success
function pagesetterParseXMLSchema($filename)
{
  global $pagesetterXMLSchema;
  $pagesetterXMLSchema = array( 'state'         => 'initial',
                                'categoryIDMap' => array(),   // Map XML file category ID to created Pagesetter ID
                                'fieldIDMap'    => array(),   // Map XML file field ID to field index in fields array
                                'category'      => array(),
                                'result'        => array());

  $xmlData = file_get_contents($filename);

  // Instantiate parser
  $parser = xml_parser_create();
  xml_set_element_handler($parser, "pagesetterSchemaStartElementHandler", "pagesetterSchemaEndElementHandler");
  xml_set_character_data_handler($parser, "pagesetterSchemaCharacterHandler");

  if (!xml_parse($parser, $xmlData, true))
  {
    pagesetterErrorAPI(__FILE__, __LINE__, 
                       "Unable to parse XML Pagesetter schema (line "
                       . xml_get_current_line_number($parser) . ","
                       . xml_get_current_column_number($parser) . "): "
                       . xml_error_string($parser));
    xml_parser_free($parser);    
    return false;
  }

  xml_parser_free($parser);

  if ($pagesetterXMLSchema['state'] == 'error')
    return pagesetterErrorAPI(__FILE__, __LINE__, $pagesetterXMLSchema['errorMessage']);

  return $pagesetterXMLSchema['result'];
}


function pagesetterSchemaStartElementHandler($parser, $name, $attribs)
{
  global $pagesetterXMLSchema;

  $state = &$pagesetterXMLSchema['state'];
  //echo "BEGIN $state: $name<br>\n";

  if ($state == 'initial')
  {
    if ($name == 'PUBLICATIONSCHEMA')
    {
      $state = 'schema';
    }
    else
    {
      $pagesetterXMLSchema['errorMessage'] = _PGXML_UNKNOWNTAG . ": $name ($state)";
      $state = 'error';
    }
  }
  else if ($state == 'schema')
  {
    if ($name == 'CATEGORY')
    {
      $state = 'category';
      $pagesetterXMLSchema['category'] = array( 'id' => $attribs['ID'] );
    }
    else if ($name == 'PUBLICATION')
    {
      $state = 'publication';
      $pagesetterXMLSchema['publication'] = array();
    }
    else
    {
      $pagesetterXMLSchema['errorMessage'] = _PGXML_UNKNOWNTAG . ": $name ($state)";
      $state = 'error';
    }
  }
  else if ($state == 'category')
  {
    if ($name == 'TITLE'  ||  $name == 'DESCRIPTION')
    {
      $pagesetterXMLSchema['value'] = '';
    }
    else if ($name == 'ITEMS')
    {
      $state = 'categoryItems';
      $pagesetterXMLSchema['items'] = array();
    }
    else
    {
      $pagesetterXMLSchema['errorMessage'] = _PGXML_UNKNOWNTAG . ": $name ($state)";
      $state = 'error';
    }
  }
  else if ($state == 'categoryItems')
  {
    if ($name == 'ITEM')
    {
      $state = 'categoryItem';
      $pagesetterXMLSchema['item'] = array();
    }
    else
    {
      $pagesetterXMLSchema['errorMessage'] = _PGXML_UNKNOWNTAG . ": $name ($state)";
      $state = 'error';
    }
  }
  else if ($state == 'categoryItem')
  {
    if ($name == 'TITLE'  ||  $name == 'FULLTITLE'  ||  $name == 'VALUE'  ||  $name == 'DESCRIPTION'  ||  $name == 'INDENT')
    {
      $pagesetterXMLSchema['value'] = '';
    }
    else
    {
      $pagesetterXMLSchema['errorMessage'] = _PGXML_UNKNOWNTAG . ": $name ($state)";
      $state = 'error';
    }
  }
  else if ($state == 'publication')
  {
    if ($name == 'TITLE'  ||  $name == 'TEMPLATE'  ||  $name == 'FORM'  
        ||  $name == 'DESCRIPTION'  ||  $name == 'AUTHOR'  ||  $name == 'CREATED'  
        ||  $name == 'LISTCOUNT'  ||  $name == 'SORTFIELD1'  ||  $name == 'SORTDESC1'  ||  $name == 'SORTFIELD2'  
        ||  $name == 'SORTDESC2'  ||  $name == 'SORTFIELD3'  ||  $name == 'SORTDESC3'  ||  $name == 'DEFAULTFILTER'
        ||  $name == 'ENABLEHOOKS'  ||  $name == 'WORKFLOW'  ||  $name == 'ENABLEREVISIONS'
        ||  $name == 'ENABLEEDITOWN'  ||  $name == 'ENABLETOPICACCESS'  ||  $name == 'DEFAULTFOLDER'
        ||  $name == 'DEFAULTSUBFOLDER'  ||  $name == 'DEFAULTFOLDERTOPIC')
    {
      $pagesetterXMLSchema['value'] = '';
    }
    else if ($name == 'FIELDS')
    {
      $state = 'publicationFields';
      $pagesetterXMLSchema['fields'] = array();
    }
    else
    {
      $pagesetterXMLSchema['errorMessage'] = _PGXML_UNKNOWNTAG . ": $name ($state)";
      $state = 'error';
    }
  }
  else if ($state == 'publicationFields')
  {
    if ($name == 'FIELD')
    {
      $state = 'publicationField';
      $pagesetterXMLSchema['fieldIDMap']["pg_field$attribs[ID]"] = count($pagesetterXMLSchema['fields']);
    }
    else
    {
      $pagesetterXMLSchema['errorMessage'] = _PGXML_UNKNOWNTAG . ": $name ($state)";
      $state = 'error';
    }
  }
  else if ($state == 'publicationField')
  {
    if (    $name == 'NAME'  ||  $name == 'TITLE'  ||  $name == 'DESCRIPTION'  ||  $name == 'TYPE'  ||  $name == 'ISTITLE'  
        ||  $name == 'ISPAGEABLE'  ||  $name == 'ISSEARCHABLE'  ||  $name == 'ISMANDATORY'  ||  $name == 'TYPEDATA')
    {
      $pagesetterXMLSchema['value'] = '';
    }
    else
    {
      $pagesetterXMLSchema['errorMessage'] = _PGXML_UNKNOWNTAG . ": $name ($state)";
      $state = 'error';
    }
  }
  else if ($state == 'error')
    ; // ignore
  else
  {
    $pagesetterXMLSchema['errorMessage'] = _PGXML_STATEERROR. ": $state - start $name";
    $state = 'error';
  }
}


function pagesetterSchemaEndElementHandler($parser, $name)
{
  global $pagesetterXMLSchema;
  $state = &$pagesetterXMLSchema['state'];
  //echo "END $state: $name<br>\n";

  if ($state == 'categoryItem')
  {
    if ($name == 'TITLE')
      $pagesetterXMLSchema['item']['title'] = $pagesetterXMLSchema['value'];
    else if ($name == 'FULLTITLE')
      $pagesetterXMLSchema['item']['fullTitle'] = $pagesetterXMLSchema['value'];
    else if ($name == 'VALUE')
      $pagesetterXMLSchema['item']['value'] = $pagesetterXMLSchema['value'];
    else if ($name == 'DESCRIPTION')
      $pagesetterXMLSchema['item']['description'] = $pagesetterXMLSchema['value'];
    else if ($name == 'INDENT')
      $pagesetterXMLSchema['item']['indent'] = $pagesetterXMLSchema['value'];
    else if ($name == 'ITEM')
    {
      //echo "END item!<br>\n";
      //print_r($pagesetterXMLSchema['item']);
      $pagesetterXMLSchema['item']['lineno'] = count($pagesetterXMLSchema['items']);
      $pagesetterXMLSchema['items'][] = $pagesetterXMLSchema['item'];
      $pagesetterXMLSchema['item'] = null;
      $state = 'categoryItems';
    }
  }
  else if ($state == 'categoryItems')
  {
    if ($name == 'ITEMS')
    {
      //echo "END items!<br>\n";
      //print_r($pagesetterXMLSchema['items']);
      $state = 'category';
    }
  }
  else if ($state == 'category')
  {
    if ($name == 'TITLE')
      $pagesetterXMLSchema['category']['title'] = $pagesetterXMLSchema['value'];
    else if ($name == 'DESCRIPTION')
      $pagesetterXMLSchema['category']['description'] = $pagesetterXMLSchema['value'];
    else if ($name == 'CATEGORY')
    {
      $items    = $pagesetterXMLSchema['items'];
      $category = $pagesetterXMLSchema['category'];
      $authorID = pnUserGetVar('uid');

      $items = pnModAPIFunc( 'pagesetter',
                             'admin',
                             'flat2nestedTree',
                             array('items' => $items) );

      $cid = pnModAPIFunc( 'pagesetter',
                           'admin',
                           'createList',
                           array('list'     => $category,
                                 'items'    => $items,
                                 'authorID' => $authorID) );

      //echo "END category!<br>\n";
      //print_r($pagesetterXMLSchema['category']);

        // Create mapping from XML ID to Pagesetter imported ID
      $pagesetterXMLSchema['categoryIDMap'][$category['id']] = $cid;

      $pagesetterXMLSchema['items'] = null;
      $pagesetterXMLSchema['category'] = null;
      $state = 'schema';
    }
  }
  else if ($state == 'publicationField')
  {
    if ($name == 'NAME')
      $pagesetterXMLSchema['field']['name'] = $pagesetterXMLSchema['value'];
    else if ($name == 'TITLE')
      $pagesetterXMLSchema['field']['title'] = $pagesetterXMLSchema['value'];
    else if ($name == 'DESCRIPTION')
      $pagesetterXMLSchema['field']['description'] = $pagesetterXMLSchema['value'];
    else if ($name == 'TYPE')
    {
      $type = $pagesetterXMLSchema['value'];

        // Is this a plugin? (defined by having a string type)
      if (!is_numeric($type))
      {
        $pagesetterXMLSchema['field']['type'] = $type;
      }
      else
      {
        $type = intval($pagesetterXMLSchema['value']);
        
        if ($type > pagesetterFieldTypeListoffset)
        {
            // This is a category ID refering to one of the imported categories.
            // Map this ID to newly created ID during import

          $oldCategoryID = $type - pagesetterFieldTypeListoffset;

          $categoryID = $pagesetterXMLSchema['categoryIDMap'][$oldCategoryID] + pagesetterFieldTypeListoffset;
        }
        else
          $categoryID = $type;

        $pagesetterXMLSchema['field']['type'] = $categoryID;
      }
    }
    else if ($name == 'ISTITLE')
      $pagesetterXMLSchema['field']['isTitle'] = $pagesetterXMLSchema['value'];
    else if ($name == 'ISPAGEABLE')
      $pagesetterXMLSchema['field']['isPageable'] = $pagesetterXMLSchema['value'];
    else if ($name == 'ISSEARCHABLE')
      $pagesetterXMLSchema['field']['isSearchable'] = $pagesetterXMLSchema['value'];
    else if ($name == 'ISMANDATORY')
      $pagesetterXMLSchema['field']['isMandatory'] = $pagesetterXMLSchema['value'];
    else if ($name == 'TYPEDATA')
      $pagesetterXMLSchema['field']['typeData'] = $pagesetterXMLSchema['value'];
    else if ($name == 'FIELD')
    {
      //echo "END field!<br>\n";
      //print_r($pagesetterXMLSchema['field']);
      $pagesetterXMLSchema['field']['lineno'] = count($pagesetterXMLSchema['fields']);
      $pagesetterXMLSchema['fields'][] = $pagesetterXMLSchema['field'];
      $pagesetterXMLSchema['field'] = null;
      $state = 'publicationFields';
    }
  }
  else if ($state == 'publicationFields')
  {
    if ($name == 'FIELDS')
    {
      $state = 'publication';
    }
  }
  else if ($state == 'publication')
  {
    if ($name == 'TITLE')
      $pagesetterXMLSchema['publication']['title'] = $pagesetterXMLSchema['value'];
    else if ($name == 'TEMPLATE')
      $pagesetterXMLSchema['publication']['filename'] = $pagesetterXMLSchema['value'];
    else if ($name == 'FORM')
      $pagesetterXMLSchema['publication']['formname'] = $pagesetterXMLSchema['value'];
    else if ($name == 'DESCRIPTION')
      $pagesetterXMLSchema['publication']['description'] = $pagesetterXMLSchema['value'];
    else if ($name == 'AUTHOR')
      $pagesetterXMLSchema['publication']['author'] = $pagesetterXMLSchema['value'];
    else if ($name == 'CREATED')
      $pagesetterXMLSchema['publication']['created'] = $pagesetterXMLSchema['value'];
    else if ($name == 'LISTCOUNT')
      $pagesetterXMLSchema['publication']['listCount'] = $pagesetterXMLSchema['value'];
    else if ($name == 'SORTFIELD1')
      $pagesetterXMLSchema['publication']['sortField1'] = $pagesetterXMLSchema['value'];
    else if ($name == 'SORTDESC1')
      $pagesetterXMLSchema['publication']['sortDesc1'] = $pagesetterXMLSchema['value'];
    else if ($name == 'SORTFIELD2')
      $pagesetterXMLSchema['publication']['sortField2'] = $pagesetterXMLSchema['value'];
    else if ($name == 'SORTDESC2')
      $pagesetterXMLSchema['publication']['sortDesc2'] = $pagesetterXMLSchema['value'];
    else if ($name == 'SORTFIELD3')
      $pagesetterXMLSchema['publication']['sortField3'] = $pagesetterXMLSchema['value'];
    else if ($name == 'SORTDESC3')
      $pagesetterXMLSchema['publication']['sortDesc3'] = $pagesetterXMLSchema['value'];
    else if ($name == 'DEFAULTFILTER')
      $pagesetterXMLSchema['publication']['defaultFilter'] = $pagesetterXMLSchema['value'];
    else if ($name == 'ENABLEHOOKS')
      $pagesetterXMLSchema['publication']['enableHooks'] = $pagesetterXMLSchema['value'];
    else if ($name == 'WORKFLOW')
      $pagesetterXMLSchema['publication']['workflow'] = $pagesetterXMLSchema['value'];
    else if ($name == 'ENABLEREVISIONS')
      $pagesetterXMLSchema['publication']['enableRevisions'] = $pagesetterXMLSchema['value'];
    else if ($name == 'ENABLEEDITOWN')
      $pagesetterXMLSchema['publication']['enableEditOwn'] = $pagesetterXMLSchema['value'];
    else if ($name == 'ENABLETOPICACCESS')
      $pagesetterXMLSchema['publication']['enableTopicAccess'] = $pagesetterXMLSchema['value'];
    else if ($name == 'DEFAULTFOLDER')
      $pagesetterXMLSchema['publication']['defaultFolder'] = $pagesetterXMLSchema['value'];
    else if ($name == 'DEFAULTSUBFOLDER')
      $pagesetterXMLSchema['publication']['defaultSubFolder'] = $pagesetterXMLSchema['value'];
    else if ($name == 'DEFAULTFOLDERTOPIC')
      $pagesetterXMLSchema['publication']['defaultFolderTopic'] = $pagesetterXMLSchema['value'];
    else if ($name == 'PUBLICATION')
    {
      $fields      = $pagesetterXMLSchema['fields'];
      $publication = $pagesetterXMLSchema['publication'];
      $authorID    = pnUserGetVar('uid');

        // Create the publication type
      $tid = pnModAPIFunc( 'pagesetter',
                           'admin',
                           'createPublicationType',
                           array('publication' => $publication,
                                 'fields'      => $fields,
                                 'authorID'    => $authorID) );

      if ($tid === false)
      {
        $pagesetterXMLSchema['state'] = 'error';
        $pagesetterXMLSchema['errorMessage'] = pagesetterErrorAPIGet();
        return;
      }

      $pagesetterXMLSchema['result']['tid'] = $tid;

        // Get the specification back with new field IDs
      $pubInfo =  pnModAPIFunc( 'pagesetter',
                                'admin',
                                'getPubTypeInfo',
                                array('tid' => $tid) );

      $publication = $pubInfo['publication'];
      $fields      = $pubInfo['fields'];

        // Set new sorting fields
      $sortFieldIndex = $pagesetterXMLSchema['fieldIDMap'][$publication['sortField1']];
      if (isset($sortFieldIndex))
      {
        $sortFieldID = $fields[$sortFieldIndex]['id'];
        $sortField1  = pagesetterGetPubColumnName($sortFieldID);
      }
      else
        $sortField1 = $publication['sortField1'];

      $sortFieldIndex = $pagesetterXMLSchema['fieldIDMap'][$publication['sortField2']];
      if (isset($sortFieldIndex))
      {
        $sortFieldID = $fields[$sortFieldIndex]['id'];
        $sortField2  = pagesetterGetPubColumnName($sortFieldID);
      }
      else
        $sortField2 = $publication['sortField2'];

      $sortFieldIndex = $pagesetterXMLSchema['fieldIDMap'][$publication['sortField3']];
      if (isset($sortFieldIndex))
      {
        $sortFieldID = $fields[$sortFieldIndex]['id'];
        $sortField3  = pagesetterGetPubColumnName($sortFieldID);
      }
      else
        $sortField3 = $publication['sortField3'];

      $publication['sortField1'] = $sortField1;
      $publication['sortField2'] = $sortField2;
      $publication['sortField3'] = $sortField3;

      $ok = pnModAPIFunc( 'pagesetter',
                          'admin',
                          'updatePublicationType',
                          array('tid'           => $tid,
                                'publication'   => $publication,
                                'fields'        => array(),
                                'deletedFields' => array()) );

      if ($ok === false)
        $pagesetterXMLSchema['error'] = pagesetterErrorAPIGet();

      $pagesetterXMLSchema['fields'] = null;
      $pagesetterXMLSchema['publication'] = null;

      $state = 'schema';
    }
  }
  else if ($state == 'fields')
  {
    $state = 'schema';
  }
  else if ($state == 'field')
  {
    $state = 'fields';
  }
}


function pagesetterSchemaCharacterHandler($parser, $data)
{
  global $pagesetterXMLSchema;
  //echo "($data)<br/>\n";

  $pagesetterXMLSchema['value'] .= $data;
}



?>