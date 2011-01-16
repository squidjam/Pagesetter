<?php
// $Id: wfcfgHandler.php,v 1.4 2005/10/28 19:42:50 jornlind Exp $
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


class wfcfgHandler extends MenuHandler
{

  function button($event)
  {
    $doClose = true;

    if ($event['action']['button'] == 'cancel')
    {
      // Nothing
    }
    else if ($event['action']['button'] == 'save')
    {
      if (!pnModAPILoad('pagesetter', 'workflow'))
        return $this->commander->errorMessage( pagesetterErrorPage(__FILE__, __LINE__, 'Failed to load Pagesetter workflow API') );

      $workflowName = $event['extra']['workflow'];
      $tid          = $event['extra']['tid'];
      $pubData      = $event['data']['wfcfg']['rows'][0];

        // Make sure these don't get set as wf-settings
      unset($pubData['workflow']);
      unset($pubData['pubType']);

      $result = pnModAPIFunc('pagesetter', 'workflow', 'setSettings',
                             array('workflow' => $workflowName,
                                   'tid'      => $tid,
                                   'settings' => $pubData) );

      if ($result === false)
        return $this->commander->errorMessage( pagesetterErrorAPIGet() );
    }

    if ($doClose)
    {
      $url = pnModURL('pagesetter', 'admin', 'wfcfglist');
      $this->commander->close($url, "");
    }
  }
}


?>