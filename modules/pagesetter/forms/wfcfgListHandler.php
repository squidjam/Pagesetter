<?php
// $Id: wfcfgListHandler.php,v 1.5 2005/01/16 18:56:31 jornlind Exp $
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


class wfcfgListHandler extends MenuHandler
{

  function button($event)
  {
    if ($event['action']['button'] == 'back')
    {
      $url = pnModURL('pagesetter', 'admin', 'pubtypes');

      $this->commander->close($url, "");
    }
    else if ($event['action']['button'] == 'edit')
    {
      $url = pnModURL('pagesetter', 'admin', 'wfcfg');

      $this->commander->close($url, "");
    }
  }


  function action($event)
  {
    $rowIndex = $event['action']['rowIndex'];
    $data     = &$event['data']['wfcfgList']['rows'][$rowIndex];

    if ($event['action']['action'] == 'edit')
    {
      $url = pnModURL('pagesetter', 'admin', 'wfcfg', array('tid' => $data['id']));

      $this->commander->close($url, "");
    }
  }
}


?>