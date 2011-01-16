<?php
// $Id: pnfolderapi.php,v 1.4 2005/07/01 07:39:14 jornlind Exp $
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


function pagesetter_folderapi_getItemTypes($args)
{
  if (!pnModAPILoad('pagesetter', 'admin'))
    return pagesetterErrorPage(__FILE__, __LINE__, 'Failed to load Pagesetter admin API');

  $pubTypes = pnModAPIFunc('pagesetter', 'admin', 'getPublicationTypes');
  if ($pubTypes === false) 
    return pagesetterErrorAPIGet();

  $itemTypes = array();

  foreach ($pubTypes as $pubType)
  {
    $pubInfo = pnModAPIFunc('pagesetter', 'admin', 'getPubTypeInfo',
                            array('tid' => $pubType['id']));
    if ($pubInfo === false)
      return false;

    if ($pubInfo['publication']['defaultFolder'] >= 0)
    {
      $addUrl = pnModUrl('pagesetter', 'user', 'pubEdit',
                         array('goback' => 1,
                               'tid'    => $pubInfo['publication']['id']));

      $itemTypes[] = array('type'  => $pubInfo['publication']['filename'],
                           'title' => $pubInfo['publication']['title'],
                           'addUrl' => $addUrl,
                           'viewFunc' => 'viewpub');
    }
  }

  return $itemTypes;
}

?>