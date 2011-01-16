<?php
// $Id: listEditHandler.php,v 1.8 2005/10/28 19:42:50 jornlind Exp $
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

class ListEditHandler extends MenuHandler
{
  function button($event)
  {
    $lid = $event['extra']['lid'];

      // Check access
    if (!pnSecAuthAction(0, 'pagesetter::', "$lid::", ACCESS_ADMIN))
      return $this->commander->errorMessage( pagesetterErrorPage(__FILE__, __LINE__, _PGNOAUTH) );

    if ($event['action']['button'] == 'cancel')
    {
      $this->commander->close(pnModURL('pagesetter', 'admin', 'lists'));
    }
    if ($event['action']['button'] == 'commit')
    {
      $listData      = $event['data']['list']['rows'][0];
      $listItemsData = $event['data']['listItems']['rows'];

      if (!pnModAPILoad('pagesetter', 'admin'))
        return $this->commander->errorMessage( pagesetterErrorPage(__FILE__, __LINE__, 'Failed to load Pagesetter admin API') );

      $listItemsData = pnModAPIFunc( 'pagesetter',
                                     'admin',
                                     'flat2nestedTree',
                                     array('items' => $listItemsData) );

      $deletedItems = $event['extra']['deletedItems'];
      $user         = pnUserGetVar('uid');

      if ($event['extra']['action'] == 'new')
      {
        $ok =  pnModAPIFunc( 'pagesetter',
                             'admin',
                             'createList',
                             array('list'     => $listData,
                                   'items'    => $listItemsData,
                                   'authorID' => $user) );
      }
      else
      {
        $ok =  pnModAPIFunc( 'pagesetter',
                             'admin',
                             'updateList',
                             array('list'         => $listData,
                                   'items'        => $listItemsData,
                                   'deletedItems' => $deletedItems,
                                   'lid'          => $lid) );
      }

      if ($ok === false)
        echo $this->commander->errorMessage( pagesetterErrorApiGet() );
      else
        $this->commander->close(pnModURL('pagesetter', 'admin', 'lists'));
    }
  }


  function treeDelete(&$event)
  {
      // No authorization - someone else must do this!

    $componentName = $event['action']['component'];
    $rowIndex = $event['action']['rowIndex'];
    $record = $event['data']['listItems']['rows'][$rowIndex];

      // Only do database delete of existing items (this item may not have been commited yet)
    if (isset($record['id']))
    {
      $id = $record['id'];

      $event['extra']['deletedItems'][] = $id;
    }
    
    $this->commander->treeDelete($componentName, $rowIndex);
  }


  function treeDeleteRecursive(&$event)
  {
      // No authorization - someone else must do this!

    $componentName = $event['action']['component'];
    $rowIndex = $event['action']['rowIndex'];
    
    $deletedItems = $this->commander->treeDeleteRecursive($componentName, $rowIndex);

    foreach ($deletedItems as $item)
      $event['extra']['deletedItems'][] = $item['id'];
  }
}


?>
