<?php
// $Id: pubEditHandler.php,v 1.27 2006/07/12 21:06:57 jornlind Exp $
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

require_once 'modules/pagesetter/guppy/guppy.php';


class publicationPubEditHandler extends GuppyDecodeHandler
{
  function button(&$event)
  {
    $tid = $event['extra']['tid'];
    $pid = $event['extra']['pid'];
    $id  = $event['extra']['id'];

    if (!pnModAPILoad('pagesetter', 'edit'))
      return $this->commander->errorMessage( pagesetterErrorPage(__FILE__, __LINE__, 'Failed to load Pagesetter edit API') );

    if (isset($event['extra']['httpReferer']))
      $okURL = $event['extra']['httpReferer'];
    else
      $okURL = pnModURL('pagesetter', 'user', 'publist', array('tid' => $tid));

    if ($event['action']['button'] == 'coreCancel')
    {
      if (!empty($id))
        pnModAPIFunc('pagesetter', 'edit', 'clearPreviewData');

      $this->commander->close($okURL);
    }
    else if ($event['action']['button'] == 'corePreview'  ||  $event['action']['button'] == 'coreUpdate')
    {
      $pubData = $event['data']['pubedit']['rows'][0];

      $result = pnModAPIFunc('pagesetter', 'edit', 'savePreviewData',
                             array('tid'     => $tid, 
                                   'pubData' => $pubData) );

      if ($result === false)
        return $this->commander->errorMessage( pagesetterErrorAPIGet() );

      if ($event['action']['button'] == 'corePreview')
        $this->commander->openWindow(pnModURL('pagesetter', 'user', 'preview',
                                              array()),
                                     'pagesetterPreview',
                                     true);
      return true;
    }
    else // Must be workflow button
    {
      if (!pnModAPILoad('pagesetter', 'workflow'))
        return $this->commander->errorMessage( pagesetterErrorPage(__FILE__, __LINE__, 'Failed to load Pagesetter workflow API') );

      $pubData      = $event['data']['pubedit']['rows'][0];
      $workflowName = $event['extra']['workflow'];
      $state        = $event['extra']['state'];

      if (empty($pubData['core_publishDate'])  &&  pnModGetVar('pagesetter','autofillPublishDate'))
      {
        $pubData['core_publishDate'] = strftime("%Y-%m-%d %H:%M:%S", time());
      }

      $core = array('tid'       => $tid, 
                    'id'        => $id, 
                    'pid'       => $pid, 
                    'creatorID' => $pubData['core_creatorID'],
                    'folderId'  => isset($event['extra']['folderId']) ? $event['extra']['folderId'] : null);

      $result = pnModAPIFunc('pagesetter', 'workflow', 'executeAction',
                             array('workflow' => $workflowName, 
                                   'state'    => $state,
                                   'action'   => $event['action']['button'],
                                   'core'     => &$core,
                                   'pubData'  => $pubData) );

      if ($result === pagesetterWFOperationError  ||  $result === false)
        return $this->commander->errorMessage( pagesetterErrorAPIGet() );
      else if ($result === pagesetterWFOperationWarning)
        $this->commander->alertAndClose( pagesetterWarningWorkflowGet(), $okURL );
      else
      {
        if (!empty($id))
          pnModAPIFunc('pagesetter', 'edit', 'clearPreviewData', array());

        $this->commander->close($okURL);

        return true;
      }
    }
  }
}


?>
