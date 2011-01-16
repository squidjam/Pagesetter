<?php
// $Id: pubTypesHandler.php,v 1.8 2005/11/01 22:26:19 jornlind Exp $
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

class PublicationTypesHandler extends MenuHandler
{
  function insertRow($event)
  {
    $this->commander->insertRow($event['action']['component'], $event['action']['rowIndex'], array());
  }


  function deleteRow($event)
  {
      // Access check in API

    if (!pnModAPILoad('pagesetter', 'admin'))
      return pagesetterErrorPage(__FILE__, __LINE__, 'Failed to load Pagesetter admin API');
  
    $record = $event['data']['publicationTypes']['rows'][ $event['action']['rowIndex'] ];

    $ok =  pnModAPIFunc( 'pagesetter',
                         'admin',
                         'deletePublicationType',
                         array('tid' => $record['id']) );

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
      $this->commander->close(pnModURL('pagesetter', 'admin', 'pubtypeadd1'));
    }
  }


  function action($event)
  {
      // No need for access check

    if ($event['action']['action'] == 'edit')
    {
      $record = $event['data']['publicationTypes']['rows'][ $event['action']['rowIndex'] ];

      $url = pnModURL('pagesetter', 'admin', 'pubtypeedit', 
                      array('action' => 'edit',
                            'tid'    => $record['id']));
    }
    else if ($event['action']['action'] == 'newpub')
    {
      $record = $event['data']['publicationTypes']['rows'][ $event['action']['rowIndex'] ];
      $url = pnModURL('pagesetter', 'user', 'pubedit', 
                      array('tid' => $record['id']));
    }
    else if ($event['action']['action'] == 'listpub')
    {
      $record = $event['data']['publicationTypes']['rows'][ $event['action']['rowIndex'] ];
      $url = pnModURL('pagesetter', 'user', 'publist', 
                      array('tid' => $record['id']));
    }

    $this->commander->close($url);
  }
}


?>
