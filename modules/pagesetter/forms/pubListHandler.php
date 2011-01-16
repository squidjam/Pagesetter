<?php
// $Id: pubListHandler.php,v 1.35 2006/05/16 21:05:10 jornlind Exp $
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

require_once 'modules/pagesetter/common-edit.php';
require_once 'modules/pagesetter/forms/adminToolbarHandler.php';

// This handler is also used as the "findPub" handler

class pubListHandler extends MenuHandler
{
  function button($event)
  {
    $data    = $event['data']['pubListHeader']['rows'][0];
    $tid     = $data['tid'];
    $orderBy = $event['extra']['orderBy'];
    $update  = true;

    $pageno = pnSessionGetVar("pagesetterEditPageNo-$tid");
    if ($pageno == '')
      $pageno = 0;

      // Check access
    if (!pnSecAuthAction(0, 'pagesetter::', "$tid::", pagesetterAccessAuthor))
      return $this->commander->errorMessage( pagesetterErrorPage(__FILE__, __LINE__, _PGNOAUTH) );

    $language = $data['language'];

    if ($event['action']['button'] == 'update'  ||  $event['action']['button'] == 'next'  ||  $event['action']['button'] == 'prev')
    {
      $restrictions = array();

        // Scan for non-empty restrictions and create restriction set with those

      $pntable   = pnDBGetTables();
      $pubColumn = $pntable['pagesetter_pubdata_column'];

      if (!($data['topic'] === null))
        $restrictions['topic'] = $data['topic'];

      if (!($data['approvalState'] === null))
        $restrictions['approvalState']= $data['approvalState'];
      //echo "Type: " . gettype($data['approvalState']); exit(0);

      if ($data['author'] != '')
        $restrictions['author']= $data['author'];

      if ($data['title'] != '')
        $restrictions['title']= $data['title'];

      if ($event['action']['button'] == 'next')
        ++$pageno;
      else if ($event['action']['button'] == 'prev'  &&  $pageno > 0)
        --$pageno;
      else
        $pageno = 0;
    }
    else if ($event['action']['button'] == 'clear')
    {
      $restrictions = array();
      $data['showDeleted'] = false;
      $pageno = 0;

      $this->commander->update('pubListHeader', array('tid' => $tid));
    }
    else if ($event['action']['button'] == 'new')
    {
      $update = false;
      $this->commander->close(pnModURL('pagesetter', 'user', 'pubedit',
                                       array('tid' => $tid)));
    }
    else if ($event['action']['button'] == 'cancel') // When used as publication selector
    {
      $update = false;

      $url = pnModURL('pagesetter', 'user', 'pubedit',
                      array( 'tid'    => $tid,
                             'pid'    => $pid,
                             'action' => 'edit' ));

      $this->commander->closeWindow();
    }

    if ($update)
    {
      $pubList =  pnModAPIFunc( 'pagesetter',
                                'user',
                                'getPubList',
                                array('tid'               => $tid,
                                      'useRestrictions'   => false,
                                      'getOwners'         => !pnSecAuthAction(0, 'pagesetter::', "$tid::", pagesetterAccessEditor),
                                      'getTextual'        => true,
                                      'noOfItems'         => pagesetterEditRowsPerPage,
                                      'offsetItems'       => $pageno * pagesetterEditRowsPerPage,
                                      'orderBy'           => $orderBy,
                                      'filter'            => $restrictions,
                                      'language'          => $language,
                                      'getApprovalState'  => true,
                                      'showDeleted'       => $data['showDeleted'],
                                      'hideDepot'         => !$data['showDeleted']) );

      if ($pubList === false)
        return $this->commander->errorMessage(pagesetterErrorAPIGet());

      $this->commander->setActionEnabling( 'pubList', array('prev' => $pageno > 0,
                                                            'next' => $pubList['more']) );

      $this->commander->setRows('pubList', $pubList['publications']);
    }

    pnSessionSetVar("pagesetterEditPageNo-$tid", $pageno);
  }


  function action($event)
  {
    $rowIndex = $event['action']['rowIndex'];
    $data     = &$event['data']['pubListHeader']['rows'][0];
    $tid      = $data['tid'];
    $pubData  = &$event['data']['pubList']['rows'][$rowIndex];
    $pid      = $pubData['pid'];
    $id       = $pubData['id'];

      // Check access
    if (!pnSecAuthAction(0, 'pagesetter::', "$tid:$pid:", pagesetterAccessAuthor))
      return $this->commander->errorMessage( pagesetterErrorPage(__FILE__, __LINE__, _PGNOAUTH) );

    if ($event['action']['action'] == 'view')
    {
      $url = pnModURL('pagesetter', 'user', 'viewpub',
                      array('tid' => $tid,
                            'id'  => $id));

      $this->commander->openWindow($url, "");
    }
    else if ($event['action']['action'] == 'edit')
    {
        // Optional topic access check for opening existing pub. for edit
      if (!pagesetterHasTopicAccessByTidId($tid,$id,'write'))
        return $this->commander->errorMessage('You do not have write access to the selected topic');

      $url = pnModURL('pagesetter', 'user', 'pubedit',
                      array( 'tid'    => $tid,
                             'id'     => $id,
                             'action' => 'edit' ));

      $this->commander->close($url);
    }
    else if ($event['action']['action'] == 'history')
    {
      $url = pnModURL('pagesetter', 'user', 'history',
                      array( 'tid'    => $tid,
                             'pid'     => $pid ));

      $this->commander->close($url);
    }
    else if ($event['action']['action'] == 'move')
    {
      if (!pnModAPILoad('pagesetter', 'edit'))
        return $this->commander->errorMessage(pagesetterErrorPage(__FILE__, __LINE__, 'Failed to load Pagesetter edit API'));

      $ok = pnModAPIFunc( 'pagesetter',
                          'edit',
                          'moveToDepot',
                          array( 'tid' => $tid,
                                 'pid' => $pid,
                                 'id'  => $id ) );

      if (!$ok)
        return $this->commander->errorMessage( pagesetterErrorAPIGet() );
      else
        $this->commander->deleteRow('pubList', $rowIndex);
    }
    else if ($event['action']['action'] == 'insert') // Insert link to publication (when used as "find publication" browser)
    {
      $title = $event['data']['pubList']['rows'][$rowIndex]['title'];

      $url = pnModURL('pagesetter', 'user', 'viewpub',
                      array( 'tid'    => $tid,
                             'pid'    => $pid ));
      $url = htmlspecialchars($url);

      $this->commander->raw("<script>pagesetterPasteLink('absolute', 'a', '$title', '$url', '" 
                            . $event['extra']['targetID'] . "', 'htmlArea30');</script>\n");
      $this->commander->close();
    }
  }


  function clickHeader($event)
  {
      // A header was clicked - make sure we can sort by it

    $tid = $event['data']['pubListHeader']['rows'][0]['tid'];
    $column = $event['action']['column'];

      // Check access
    if (!pnSecAuthAction(0, 'pagesetter::', "$tid::", pagesetterAccessAuthor))
      return $this->commander->errorMessage( pagesetterErrorPage(__FILE__, __LINE__, _PGNOAUTH) );

    if (!pnModAPILoad('pagesetter', 'user'))
      return $this->commander->errorMessage(pagesetterErrorPage(__FILE__, __LINE__, 'Failed to load Pagesetter user API'));

    if (!pnModAPILoad('pagesetter', 'admin'))
      return $this->commander->errorMessage(pagesetterErrorPage(__FILE__, __LINE__, 'Failed to load Pagesetter admin API'));

      // Detect direction

    $currentSortField = &$event['extra']['sortField'];
    $currentSortDir   = &$event['extra']['sortDir'];

    if ($currentSortField == $column)
    {
        // Clicked same field again -> change direction
      if ($currentSortDir == 'desc')
        $currentSortDir = 'asc';
      else
        $currentSortDir = 'desc';
    }
    else
    {
        // Clicked a new field -> reset to that field
      $currentSortField = $column;
      $currentSortDir   = 'asc';
    }

      // Convert column name to database column name

    if ($column == 'title')
    {
      // The title field is special => we need to get the pub. type spec. to find it
      $pubInfo =  pnModAPIFunc( 'pagesetter',
                                'admin',
                                'getPubTypeInfo',
                                array('tid' => $tid) );

      if ($pubInfo === false)
        return $this->commander->errorMessage(pagesetterErrorAPIGet());

      $column = pagesetterGetPubColumnName($pubInfo['publication']['titleFieldID']);
    }
    else
    {
        // The pubList API function requires column names to be database names, so do a conversion
      $pntable   = pnDBGetTables();
      $pubColumn = $pntable['pagesetter_pubdata_column'];
      $column = $pubColumn[$column];
    }

    $orderBy = array( array('name' => $column, $currentSortDir => true) );

    $event['extra']['orderBy'] = $orderBy;

    $pubList =  pnModAPIFunc( 'pagesetter',
                              'user',
                              'getPubList',
                              array('tid'             => $tid,
                                    'useRestrictions' => false,
                                    'getOwners'       => !pnSecAuthAction(0, 'pagesetter::', "$tid::", pagesetterAccessEditor),
                                    'getTextual'      => true,
                                    'getApprovalState'  => true,
                                    'noOfItems'       => pagesetterEditRowsPerPage,
                                    'orderBy'         => $orderBy) );

    if ($pubList === false)
      return $this->commander->errorMessage(pagesetterErrorAPIGet());

    $this->commander->setActionEnabling( 'pubList', array('prev' => $pageno > 0,
                                                          'next' => $pubList['more']) );

    $this->commander->setRows('pubList', $pubList['publications']);
  }
}


?>
