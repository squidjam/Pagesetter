<?php
// $Id: pntopicaccessapi.php,v 1.2 2006/03/12 22:33:54 pndrak Exp $
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

// This API file is integration with the Topic Access module.


require_once("modules/pagesetter/common.php");


// Fetch information about this module.
// Returns associative array with the following keys:
//
//   title => module title (display name)
//
// Called when scanning for topic access dependent modules
function pagesetter_topicaccessapi_getInfo($args)
{
  return array('title' => 'Pagesetter');
}


// Fetch all the access schemes used by this module
// Returns an array of associative arrays with all the schemes:
//
//   array
//   (
//     array
//     (
//       'title'    => displayed title for scheme
//       'category' => name of scheme category
//       'access'   => access type (read, write, ...)
//     ),
//     array ...
//   )
//
// Called when editing access (used for list of access schemes per module).
function pagesetter_topicaccessapi_getSchemes($args)
{
  if (!pnModAPILoad('pagesetter', 'admin'))
    return pagesetterErrorAPI(__FILE__, __LINE__, 'Failed to load Pagesetter admin API');

  $pubTypes = pnModAPIFunc('pagesetter', 'admin', 'getPublicationTypes');
  if ($pubTypes === false)
    return false;

  $schemes = array();

  foreach ($pubTypes as $pubType)
  {
    $tid = $pubType['id'];

    $pubInfo = pnModAPIFunc('pagesetter', 'admin', 'getPubTypeInfo',
                             array('tid' => $tid) );

    if ($pubInfo['publication']['enableTopicAccess'])
    {
      $scheme = array(
                  array('title'    => 'Read access for ' .$pubInfo['publication']['title'],
                        'category' => $pubInfo['publication']['filename'],
                        'access'   => 'read'),
                  array('title'    => 'Write access for ' .$pubInfo['publication']['title'],
                        'category' => $pubInfo['publication']['filename'],
                        'access'   => 'write')
                );

      $schemes = array_merge($schemes , $scheme);
    }
  }

  return $schemes;
}


// If any of the above fails by returning boolean false then this function
// is called to fetch the error reason.
function pagesetter_topicaccessapi_getErrorMessage($args)
{
  return pagesetterErrorAPIGet();
}


?>
