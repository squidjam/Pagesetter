<?php
// $Id: adminToolbarHandler.php,v 1.16 2005/11/02 21:00:15 jornlind Exp $
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

require_once 'modules/pagesetter/guppy/guppy.php';

class MenuHandler extends GuppyDecodeHandler
{
  function menuAction($event)
  {
      // No need for access checking

    $close = true;

    switch ($event['action']['action'])
    {
      case 'pubTypesList':
        $url = pnModURL('pagesetter', 'admin', 'pubtypes');
      break;

      case 'pubTypesNew':
        $url = pnModURL('pagesetter', 'admin', 'pubtypeadd1');
      break;

      case 'pubList':
        $url = pnModURL('pagesetter', 'user', 'publist');
      break;

      case 'lists':
        $url = pnModURL('pagesetter', 'admin', 'lists');
      break;

      case 'listNew':
        $url = pnModURL('pagesetter', 'admin', 'listedit', 
                        array('action' => 'new'));
      break;

      case 'configGeneral':
        $url = pnModURL('pagesetter', 'admin', 'config');
      break;

      case 'configWorkflow':
        $url = pnModURL('pagesetter', 'admin', 'wfcfglist');
      break;

      case 'toolsImport':
        $url = pnModURL('pagesetter', 'admin', 'import');
      break;

      case 'toolsExport':
        $url = pnModURL('pagesetter', 'admin', 'export');
      break;

      case 'toolsSetupFolder':
        $url = pnModURL('pagesetter', 'admin', 'setupfolder');
      break;

      case 'toolsDatabase':
        $url = pnModURL('pagesetter', 'admin', 'databaseview');
      break;

      case 'pubTemplates':
        $url = pnModURL('pagesetter', 'admin', 'createtemplates');
      break;

      case 'cacheClear':
      {
        if (!pnModAPILoad('pagesetter', 'edit'))
          return $this->commander->errorMessage( pagesetterErrorPage(__FILE__, __LINE__, 'Failed to load Pagesetter edit API') );

        pnModAPIFunc( 'pagesetter',
                      'edit',
                      'cacheClearAll' );

        $close = false;
      }
      break;
    }

    if ($close)
      $this->commander->close($url);
  }
}


?>
