<?php
// $Id: pnfolder.php,v 1.11 2007/06/08 21:28:08 jornlind Exp $
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

require_once("modules/pagesetter/common.php");


function pagesetter_folder_view($args)
{
  $key = $args['key'];

  pagesetterSplitKey($key, $tid, $pid);

  if (!pnModAPILoad('pagesetter', 'user'))
    return pagesetterErrorPage(__FILE__, __LINE__, 'Failed to load Pagesetter user API');

  if (!pnModAPILoad('pagesetter', 'admin'))
    return pagesetterErrorPage(__FILE__, __LINE__, 'Failed to load Pagesetter admin API');

  $pubInfo = pnModAPIFunc( 'pagesetter',
                           'admin',
                           'getPubTypeInfo',
                           array('tid' => $tid) );
  if ($pubInfo === false)
    return pagesetterErrorAPIGet();

  $render = new pnRender('pagesetter');
  //$render->assign('folderId', $folderId);

  $filename = $pubInfo['publication']['filename'];
  $template = "$filename-folder.view.html";
  if (!$render->template_exists($template))
    $template = 'folder.view.html';

  $publication = pnModAPIFunc( 'pagesetter', 'user', 'getPubFormatted',
                               array('tid'             => $tid, 
                                     'pid'             => $pid,
                                     'template'        => $template,
                                     'useRestrictions' => false,
                                     'notInDepot'      => true) );
  if ($publication === false)
    return false;

  return $publication;
}


function pagesetter_folder_new()
{
  $tid      = pnVarCleanFromInput('tid');
  $folderId = pnVarCleanFromInput('folderId');

  $url = pnModUrl('pagesetter','user','pubedit',
                  array('tid'      => $tid,
                        'goback'   => 1,
                        'folderId' => $folderId));

  pnRedirect($url);
  return true;
}


function pagesetter_folder_select($args)
{
  $key = $args['key'];

  pagesetterSplitKey($key, $tid, $pid);

  if (!pnModAPILoad('pagesetter', 'user'))
    return pagesetterErrorPage(__FILE__, __LINE__, 'Failed to load Pagesetter user API');

  if (!pnModAPILoad('pagesetter', 'admin'))
    return pagesetterErrorPage(__FILE__, __LINE__, 'Failed to load Pagesetter admin API');

  $pubInfo = pnModAPIFunc( 'pagesetter',
                           'admin',
                           'getPubTypeInfo',
                           array('tid' => $tid) );
  if ($pubInfo === false)
    return pagesetterErrorAPIGet();

  $render = new pnRender('pagesetter');
  //$render->assign('folderId', $folderId);

  $pubURL = pnModUrl('pagesetter','user','viewPub',
                     array('tid' => $tid,
                           'pid' => $pid));

  $coreExtra = array('pubURL' => $pubURL);

  $filename = $pubInfo['publication']['filename'];
  $template = "$filename-folder.select.html";
  if (!$render->template_exists($template))
    $template = 'folder.select.html';

  $publication = pnModAPIFunc( 'pagesetter', 'user', 'getPubFormatted',
                               array('tid'       => $tid, 
                                     'pid'       => $pid,
                                     'template'  => $template,
                                     'coreExtra' => $coreExtra) );
  if ($publication === false)
    return pagesetterErrorAPIGet();

  return $publication;
}


?>
