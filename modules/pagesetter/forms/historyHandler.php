<?php
// $Id: historyHandler.php,v 1.8 2005/10/28 19:42:50 jornlind Exp $
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

class historyHandler extends MenuHandler
{
  function button($event)
  {
    $data    = $event['data']['historyHeader']['rows'][0];
    $tid     = $data['tid'];
    
    if ($event['action']['button'] == 'back')
    {
      $this->commander->close(pnModURL('pagesetter', 'user', 'pubList',
                                       array('tid' => $tid)));
    }
  }


  function action($event)
  {
    $rowIndex = $event['action']['rowIndex'];
    $data     = &$event['data']['historyHeader']['rows'][0];
    $tid      = $data['tid'];
    $pubData  = &$event['data']['historyList']['rows'][$rowIndex];
    $pid      = $data['pid'];
    $id       = $pubData['id'];

    if ($event['action']['action'] == 'view')
    {
      $url = pnModURL('pagesetter', 'user', 'viewpub',
                      array('tid' => $tid,
                            'id'  => $id));

      $this->commander->openWindow($url, "");
    }
    else if ($event['action']['action'] == 'move')
    {
      if (!pnModAPILoad('pagesetter', 'edit'))
        return $this->commander->errorMessage(pagesetterErrorPage(__FILE__, __LINE__, 'Failed to load Pagesetter edit API'));

        // Move the revision
      $ok = pnModAPIFunc( 'pagesetter', 'edit', 'moveFromDepot',
                          array( 'tid' => $tid,
                                 'pid' => $pid,
                                 'id'  => $id ) );

      if (!$ok)
        return $this->commander->errorMessage( pagesetterErrorAPIGet() );

        // Make sure it is not marked as deleted
      $ok = pnModAPIFunc( 'pagesetter', 'edit', 'unDeletePub',
                          array( 'tid' => $tid,
                                 'pid' => $pid ) );

      if (!$ok)
        return $this->commander->errorMessage( pagesetterErrorAPIGet() );

      $this->commander->close(pnModURL('pagesetter', 'user', 'pubList',
                                       array('tid' => $tid)));
    }
    else if ($event['action']['action'] == 'erase')
    {
      if (!pnSecAuthAction(0, 'pagesetter::', '$tid:$pid:', ACCESS_ADMIN))
        return $this->commander->errorMessage(pagesetterErrorPage(__FILE__, __LINE__, _PGNOAUTH));

      if (!pnModAPILoad('pagesetter', 'edit'))
        return $this->commander->errorMessage(pagesetterErrorPage(__FILE__, __LINE__, 'Failed to load Pagesetter edit API'));

      pnModApiFunc('pagesetter','edit','startWorkflow');

      $ok = pnModAPIFunc( 'pagesetter',
                          'edit',
                          'eraseRevision',
                          array( 'tid' => $tid,
                                 'pid' => $pid,
                                 'id'  => $id ) );

      pnModApiFunc('pagesetter','edit','endWorkflow');

      if (!$ok)
        return $this->commander->errorMessage( pagesetterErrorAPIGet() );

      $this->commander->deleteRow('historyList', $rowIndex);
    }
  }
}

?>
