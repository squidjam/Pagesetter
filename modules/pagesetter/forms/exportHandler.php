<?php
// $Id: exportHandler.php,v 1.4 2006/03/14 01:30:38 pndrak Exp $
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

class ExportHandler extends MenuHandler
{
  function button($event)
  {
      // Check access
    if (!pnSecAuthAction(0, 'pagesetter::', '::', ACCESS_ADMIN))
      return $this->commander->errorMessage( pagesetterErrorPage(__FILE__, __LINE__, _PGNOAUTH) );

    if ($event['action']['button'] == 'cancel') {
      $this->commander->close(pnModURL('pagesetter', 'admin'));
    }

    if ($event['action']['button'] == 'export')
    {
      if (!pnModAPILoad('pagesetter', 'integ'))
        return $this->commander->errorMessage('Failed to load Pagesetter integ API');

      $exportSetup = $event['data']['export']['rows'][0];
      $tid = $exportSetup['tid'];

      $this->commander->close(pnModURL('pagesetter', 'admin', 'exportXML',
                                       array('tid' => $tid)));
    }
  }
}


?>
