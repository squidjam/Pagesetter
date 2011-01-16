<?php
// $Id: listsHandler.php,v 1.1 2003/12/09 18:55:51 jornlind Exp $
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
require_once 'modules/pagesetter/common.php';

class ListsHandler extends MenuHandler
{
  function deleteRow($event)
  {
      // Access check in API

    if (!pnModAPILoad('pagesetter', 'admin'))
      return pagesetterErrorPage(__FILE__, __LINE__, 'Failed to load Pagesetter admin API');
  
    $record = $event['data']['lists']['rows'][ $event['action']['rowIndex'] ];

    $ok =  pnModAPIFunc( 'pagesetter',
                         'admin',
                         'deleteList',
                         array('lid' => $record['id']) );

    if ($ok)
      $this->commander->deleteRow($event['action']['component'], $event['action']['rowIndex']);
    else
      $this->commander->message( pagesetterErrorPage(__FILE__, __LINE__, pagesetterErrorApiGet()) );
  }


  function button($event)
  {
      // No need for access check

    if ($event['action']['button'] == 'new')
    {
      $this->commander->close(pnModURL('pagesetter', 'admin', 'listedit', array('action' => 'new')));
    }
  }


  function action($event)
  {
      // No need for access check

    if ($event['action']['action'] == 'edit')
    {
      $record = $event['data']['lists']['rows'][ $event['action']['rowIndex'] ];

      $url = pnModURL('pagesetter', 'admin', 'listedit', 
                      array('action' => 'edit',
                            'lid'    => $record['id']));
    }

    $this->commander->close($url);
  }
}


?>
