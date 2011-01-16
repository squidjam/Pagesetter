<?php
// $Id: pubTypeAdd1bHandler.php,v 1.2 2006/07/12 21:06:57 jornlind Exp $
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

class publicationTypeAdd1bHandler extends MenuHandler
{
  function insertRow($event)
  {
      // No authorization - wait until commit

    $componentName = $event['action']['component'];
    $rowIndex = $event['action']['rowIndex'];

    $this->commander->insertRow($componentName, $rowIndex, array( 'edit' => 'AAA' ));
  }


  function deleteRow(&$event)
  {
      // No authorization - wait until commit

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
    else if ($event['action']['button'] == 'commit')
    {
      // Check for existence of at least one input field
      if (count($pubFieldsData) == 0)
        return $this->commander->errorMessage(_PGMISSINGFIELDROW);

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

      $user = pnUserGetVar('uid');

      $ok = pnModAPIFunc( 'pagesetter', 'admin', 'updatePublicationType',
                          array('publication'    => $pubTypeData,
                                'fields'         => $pubFieldsData,
                                'tid'            => $tid) );

      if (!$ok)
      {
        $this->commander->errorMessage( pagesetterErrorApiGet() );
        return;
      }

      if ($tid === false)
      {
        echo $this->commander->errorMessage( pagesetterErrorApiGet() );
      }
      else
      {
        $this->commander->close( pnModURL('pagesetter', 'admin', 'pubtypeadd2',
                                          array('tid' => $tid)) );
      }
    }
  }
}


?>
