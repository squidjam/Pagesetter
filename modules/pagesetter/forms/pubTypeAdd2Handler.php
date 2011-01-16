<?php
// $Id: pubTypeAdd2Handler.php,v 1.4 2006/07/12 21:06:57 jornlind Exp $
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

class publicationTypeAdd2Handler extends MenuHandler
{
  function button($event)
  {
    $pubTypeData = $event['data']['publicationType']['rows'][0];
    $tid         = $pubTypeData['id'];

      // Check access
    if (!pnSecAuthAction(0, 'pagesetter::', "$tid::", ACCESS_ADMIN))
      return $this->commander->errorMessage( pagesetterErrorPage(__FILE__, __LINE__, _PGNOAUTH) );

    if ($event['action']['button'] == 'cancel')
    {
      $this->commander->close(pnModURL('pagesetter', 'admin', 'pubtypes'));
    }
    if ($event['action']['button'] == 'commit')
    {
      if (!pnModAPILoad('pagesetter', 'admin'))
        return $this->commander->errorMessage( pagesetterErrorPage(__FILE__, __LINE__, 'Failed to load Pagesetter admin API') );

      $user = pnUserGetVar('uid');

        // Update pub. type

      $ok = pnModAPIFunc( 'pagesetter', 'admin', 'updatePublicationType',
                          array('publication'    => $pubTypeData,
                                'fields'         => array(),
                                'tid'            => $tid) );

      if (!$ok)
      {
        $this->commander->errorMessage( pagesetterErrorApiGet() );
        return;
      }


        // Create templates

      $filename = $pubTypeData['filename'];

      if ($pubTypeData['listGenerate'])
      {
        $ok = pnModAPIFunc('pagesetter', 'admin', 'createTemplateFile',
                           array('tid'      => $tid,
                                 'format'   => 'list-header',
                                 'filename' => $filename));
        
        $ok = $ok && pnModAPIFunc('pagesetter', 'admin', 'createTemplateFile',
                                  array('tid'      => $tid,
                                        'format'   => 'list',
                                        'filename' => $filename));

        $ok = $ok && pnModAPIFunc('pagesetter', 'admin', 'createTemplateFile',
                                  array('tid'      => $tid,
                                        'format'   => 'list-footer',
                                        'filename' => $filename));
        if (!$ok)
          return $this->commander->errorMessage( pagesetterErrorApiGet() );
      }

      if ($pubTypeData['fullGenerate'])
      {
        $ok = pnModAPIFunc('pagesetter', 'admin', 'createTemplateFile',
                           array('tid'      => $tid,
                                 'format'   => 'full',
                                 'filename' => $filename));
        if (!$ok)
          return $this->commander->errorMessage( pagesetterErrorApiGet() );
      }

      if ($pubTypeData['printGenerate'])
      {
        $ok = pnModAPIFunc('pagesetter', 'admin', 'createTemplateFile',
                           array('tid'      => $tid,
                                 'format'   => 'print',
                                 'filename' => $filename));
        if (!$ok)
          return $this->commander->errorMessage( pagesetterErrorApiGet() );
      }

      if ($pubTypeData['rssGenerate'])
      {
        $ok = pnModAPIFunc('pagesetter', 'admin', 'createTemplateFile',
                           array('tid'      => $tid,
                                 'format'   => 'rss-header',
                                 'filename' => $filename));
        
        $ok = $ok && pnModAPIFunc('pagesetter', 'admin', 'createTemplateFile',
                                  array('tid'      => $tid,
                                        'format'   => 'rss',
                                        'filename' => $filename));

        $ok = $ok && pnModAPIFunc('pagesetter', 'admin', 'createTemplateFile',
                                  array('tid'      => $tid,
                                        'format'   => 'rss-footer',
                                        'filename' => $filename));
        if (!$ok)
          return $this->commander->errorMessage( pagesetterErrorApiGet() );
      }

      if ($pubTypeData['blockGenerate'])
      {
        $ok = pnModAPIFunc('pagesetter', 'admin', 'createTemplateFile',
                           array('tid'      => $tid,
                                 'format'   => 'block-list',
                                 'filename' => $filename));
        if (!$ok)
          return $this->commander->errorMessage( pagesetterErrorApiGet() );
      }

      $this->commander->close( pnModURL('pagesetter', 'user', 'publist',
                                        array('tid' => $tid)) );
    }
  }
}


?>
