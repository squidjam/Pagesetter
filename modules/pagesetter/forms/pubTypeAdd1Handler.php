<?php
// $Id: pubTypeAdd1Handler.php,v 1.5 2006/07/12 21:06:57 jornlind Exp $
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

class publicationTypeAdd1Handler extends MenuHandler
{
  function button($event)
  {
    $pubTypeData  = $event['data']['publicationType']['rows'][0];

      // Add default data
    $pubTypeData['workflow'] = 'standard';
    $pubTypeData['enableHooks'] = true;
    $pubTypeData['defaultFolder'] = -1;

      // Check access
    if (!pnSecAuthAction(0, 'pagesetter::', "::", ACCESS_ADMIN))
      return $this->commander->errorMessage( pagesetterErrorPage(__FILE__, __LINE__, _PGNOAUTH) );

    if ($event['action']['button'] == 'cancel')
    {
      $this->commander->close(pnModURL('pagesetter', 'admin', 'pubtypes'));
    }
    else if ($event['action']['button'] == 'commit')
    {
      if (!pnModAPILoad('pagesetter', 'admin'))
        return $this->commander->errorMessage( pagesetterErrorPage(__FILE__, __LINE__, 'Failed to load Pagesetter admin API') );

      $user = pnUserGetVar('uid');

      $tid =  pnModAPIFunc( 'pagesetter',
                            'admin',
                            'createPublicationType',
                            array('publication'   => $pubTypeData,
                                  'fields'        => array(),
                                  'authorID'      => $user) );

      if ($tid === false)
      {
        echo $this->commander->errorMessage( pagesetterErrorApiGet() );
      }
      else
      {
        $this->commander->close( pnModURL('pagesetter', 'admin', 'pubtypeadd1b',
                                          array('tid' => $tid)) );
      }
    }
  }
}


?>
