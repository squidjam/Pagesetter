<?php
// $Id: pnfeprocapi.php,v 1.5 2004/08/11 21:27:19 jornlind Exp $
// =======================================================================
// Pagesetter by Jorn Lind-Nielsen (C) 2003.
// This file contains API functions for Feproc.
// - see http://noc.postnuke.com/projects/feproc/
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

require_once("modules/pagesetter/common.php");


function pagesetter_feprocapi_feprochandlerindex()
{
    // Define which functions this module makes available
  return array( array('type' => 'transmit', 'apifunc' => 'submit') );
}


function pagesetter_feprocapi_submit($args)
{
  $action = $args['action'];
  $info   = $args['info'];

  $handlerInfo = 
    array(
      'name'        => _PGFEP_SUBMIT,
      'description' => _PGFEP_SUBMITDESCR,
      'type'        => 'transmit',
      'version'     => '1.0',
      'attributes'  => array
        (
          'tid'    => array('type' => 'string', 'description' => _PGFEP_SUBMITTID),
          'topic'  => array('type' => 'string', 'description' => _PGFEP_SUBMITTOPIC),
          'author' => array('type' => 'string', 'description' => _PGFEP_SUBMITAUTHOR),
          'state'  => array('type' => 'string', 'description' => _PGFEP_SUBMITSTATE, 'default' => 'approved')
        )
    );

  if ($action == 'info')
    return $handlerInfo;

  if ($action == 'help')
    return _PGFEP_SUBMITHELP;

  if ($action == 'execute')
  {
      // Get selected publication type ID and fetch publication info

    $attributes = $args['info']['attributes'];
    $tid = $attributes['tid'];

    if (!pnModAPILoad('pagesetter', 'admin'))
      return array( 'result' => false, 
                    'messages' => array('error' => 'Unable to load Pagesetter admin API') );

    if (!pnModAPILoad('pagesetter', 'user'))
      return array( 'result' => false, 
                    'messages' => array('error' => 'Unable to load Pagesetter user API') );

    if (!pnModAPILoad('pagesetter', 'edit'))
      return array( 'result' => false, 
                    'messages' => array('error' => 'Unable to load Pagesetter edit API') );

    $pubInfo =  pnModAPIFunc( 'pagesetter',
                              'admin',
                              'getPubTypeInfo',
                              array('tid' => $tid) );

    if ($pubInfo === false)
      return array( 'result' => false, 
                    'messages' => array('error' => "Unable to get Pagesetter publication info for type ID '$tid'") );

      // Set standard core data based on handler configuration

    $pubData = 
      array(
        'core_approvalState' => $attributes['state'],
        'core_online'        => true,
        'core_topic'         => $attributes['topic'],
        'core_showInMenu'    => false,
        'core_showInList'    => true,
        'core_author'        => $attributes['author'],
        'core_creatorID'     => pnUserGetVar('uid'),
        'core_language'      => 'x_all'
      );

      // Iterate through publication fields and fetch from (hopefully existing) FormExpress data

    $fields = $pubInfo['fields'];
    $formData = $info['form'];

    foreach ($fields as $fieldSpec)
    {
      $fieldName = $fieldSpec['name'];
      $columnName = pagesetterGetPubColumnName($fieldSpec['id']);

      $pubData[$fieldName] = $formData[$fieldName];
    }

      // Create publication

    $ok = pnModAPIFunc( 'pagesetter',
                        'edit',
                        'createPub',
                        array('tid'      => $tid,
                              'pubData'  => $pubData) );

    if (!$ok)
      return array( 'result' => false, 
                    'messages' => array('error' => "Unable to create Pagesetter publication of type ID '$tid'. "
                                                   . pagesetterErrorAPIGet()) );

    return array('result' => true);
  }

    // No default handler
  return false;
}

?>
