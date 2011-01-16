<?php
// $Id: configHandler.php,v 1.11 2005/10/28 19:42:50 jornlind Exp $
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

require_once 'modules/pagesetter/forms/adminToolbarHandler.php';

class ConfigHandler extends MenuHandler
{
  function button($event)
  {
      // Check access
    if (!pnSecAuthAction(0, 'pagesetter::', '::', ACCESS_ADMIN))
      return $this->commander->errorMessage( pagesetterErrorPage(__FILE__, __LINE__, _PGNOAUTH) );

    if ($event['action']['button'] == 'save')
    {
      $configData = $event['data']['config']['rows'][0];
      $uploadDir = $configData['uploadDir'];

		  if (!empty($uploadDir)  &&  (!is_dir($uploadDir) || !is_writable($uploadDir)))
        $this->commander->errorMessage(_PGNERROROTACCESSIBLEDIR);
      else
      {
        pnModSetVar('pagesetter', 'frontpagePubType', $configData['pubType']);
        pnModSetVar('pagesetter', 'uploadDir', $configData['uploadDir']);
        pnModSetVar('pagesetter', 'uploadDirDocs', $configData['uploadDirDocs']);
        pnModSetVar('pagesetter', 'autofillPublishDate', $configData['autofillPublishDate']);
        // guppy_setSetting('htmlAreaStyled', $configData['htmlAreaStyled']);
        // guppy_setSetting('htmlAreaUndo', $configData['htmlAreaUndo']);
        // guppy_setSetting('htmlAreaWordKill', $configData['htmlAreaWordKill']);
        // guppy_setSetting('htmlAreaEnabled', $configData['htmlAreaEnabled']);
      }
    }
  }
}


?>
