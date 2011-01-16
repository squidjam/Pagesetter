<?php
// $Id: pubTypeEditHandler.php,v 1.9 2006/03/14 01:30:38 pndrak Exp $
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

class publicationTypeEditHandler extends MenuHandler
{
  function insertRow($event)
  {
      // No authorization - wait until commit

    $componentName = $event['action']['component'];
    $rowIndex = $event['action']['rowIndex'];
    $record = $event['data'][$componentName]['rows'][$rowIndex];

    $this->commander->insertRow($componentName, $rowIndex, array( 'edit' => 'AAA' ));
  }


  function deleteRow(&$event)
  {
      // No authorization - wait until commit

    $componentName = $event['action']['component'];
    $rowIndex = $event['action']['rowIndex'];
    $record = $event['data'][$componentName]['rows'][$rowIndex];

      // Only do database delete of existing fields (this field may not have been commited yet)
    if (isset($record['id']))
    {
      $tid = $event['data']['publicationType']['rows'][0]['id'];

      $event['extra']['deletedFields'][] = array('ftid' => $record['id'],
                                                 'tid'  => $tid);
    }

    $this->commander->deleteRow($event['action']['component'], $event['action']['rowIndex']);
  }


  function button($event)
  {
    $pubTypeData    = $event['data']['publicationType']['rows'][0];
    $pubFieldsData  = $event['data']['publicationFields']['rows'];
    $tid            = $pubTypeData['id'];

      // Check access
    if (!pnSecAuthAction(0, 'pagesetter::', "$tid::", ACCESS_ADMIN))
      return $this->commander->errorMessage( pagesetterErrorPage(__FILE__, __LINE__, _PGNOAUTH) );

    if ($event['action']['button'] == 'cancel')
    {
      $this->commander->close(pnModURL('pagesetter', 'admin', 'pubtypes'));
    }
    if ($event['action']['button'] == 'commit')
    {
      // Hack: split "type" field into two separate fields (by '|') and save as 'type' and 'typeData'
      for ($i=0,$s=count($pubFieldsData); $i<$s; ++$i)
      {
        $field = $pubFieldsData[$i];
        $typeInfo = $field['type'];
        $pos = strpos($typeInfo, '|');
        $type = substr($typeInfo, 0, $pos);
        $typedata = substr($typeInfo, $pos+1);
        $pubFieldsData[$i]['type'] = $type;
        $pubFieldsData[$i]['typeData'] = $typedata;
      }

      if (!pnModAPILoad('pagesetter', 'admin'))
        return $this->commander->errorMessage( pagesetterErrorPage(__FILE__, __LINE__, 'Failed to load Pagesetter admin API') );

      $deletedFields  = $event['extra']['deletedFields'];
      $user           = pnUserGetVar('uid');

      if ($event['extra']['action'] == 'new')
      {
        $ok =  pnModAPIFunc( 'pagesetter',
                             'admin',
                             'createPublicationType',
                             array('publication'  => $pubTypeData,
                                   'fields'       => $pubFieldsData,
                                   'authorID'     => $user) );
      }
      else
      {
        $ok =  pnModAPIFunc( 'pagesetter',
                             'admin',
                             'updatePublicationType',
                             array('publication'    => $pubTypeData,
                                   'fields'         => $pubFieldsData,
                                   'deletedFields'  => $deletedFields,
                                   'tid'            => $tid) );
      }

      if ($ok)
      {
        $this->commander->close(pnModURL('pagesetter', 'admin', 'pubtypes'));
      }
      else
        echo $this->commander->errorMessage( pagesetterErrorApiGet() );
    }
  }
}


?>
