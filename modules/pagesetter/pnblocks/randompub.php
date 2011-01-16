<?php
// $Id: randompub.php,v 1.3 2006/05/30 19:52:10 jornlind Exp $
// based on pub.php 1.3
// =======================================================================
// Original Author of file: tjreo
// Purpose of file: Show a random Pagesetter Publication in a Block
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
// but WIthOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// To read the license please visit http://www.gnu.org/copyleft/gpl.html
// =======================================================================

/**
 * initialise block
 */
function pagesetter_randompubblock_init()
{
    // Security
    pnSecAddSchema('pagesetter:RandomPubblock:', 'Block title:Block Id:Type Id');
}

/**
 * get information on block
 */
function pagesetter_randompubblock_info()
{
    // Values
    return array('text_type' => 'pagesetterPublication',
                 'module' => 'pagesetter',
                 'text_type_long' => 'Display a random pagesetter publication',
                 'allow_multiple' => true,
                 'form_content' => false,
                 'form_refresh' => false,
                 'show_preview' => true);
}

/**
 * random publication id
 */
function pagesetter_randompub_random_index($tid)
{
    $pubCount = pnModAPIFunc('pagesetter', 'user', 'getPubList', 
                             array('tid'        => $tid, 
                                   'countOnly'  => true));
    return mt_rand(0, $pubCount-1);
}


/**
 * display block
 */
function pagesetter_randompubblock_display($blockinfo)
{

    // Get variables from content block
    $vars = pnBlockVarsFromContent($blockinfo['content']);
    
    if (!pnModAPILoad('pagesetter', 'user'))
      return pagesetterErrorPage(__FILE__, __LINE__, 'Failed to load Pagesetter user API');

    // Defaults
    $tid = $vars['tid'];
    $tpl = $vars['tpl'];

    // Fetch pub. pid
    $index = pagesetter_randompub_random_index($tid);
    $pub = pnModAPIFunc('pagesetter', 'user', 'getPubList',
                        array('tid' => $tid,
                              'offsetItems' => $index,
                              'noOfItems'   => 1));
    $pid = $pub['publications'][0]['pid'];
    
    if ( !isset($tid) || !isset($pid) || !isset($tpl) )
    {
        $blockinfo['content'] = _PGRANPUBBLOCKEDITBLOCK;
        return themesideblock($blockinfo);
    }
    
    // Security check
    if (!pnSecAuthAction(0, 'pagesetter:RandomPubblock:', "$blockinfo[title]:$blockinfo[bid]:$tid", ACCESS_READ))
      return;

    $url = pnModURL('pagesetter', 'user', 'viewpub', array('tid' => $tid, 'pid' => $pid));
    $url = htmlspecialchars($url);

    // get the formatted publication
    $pubFormatted = pnModAPIFunc( 'pagesetter',
                                  'user',
                                  'getPubFormatted',
                                  array('tid'      => $tid,
                                        'pid'      => $pid,
                                        'format'   => $tpl,
                                        'coreExtra'       => array( 'page'    => 0,
                                                                    'baseURL' => $url,
                                                                    'format'  => $tpl) ));

    // Populate block info and pass to theme
    $blockinfo['content'] = $pubFormatted;
    return themesideblock($blockinfo);
}


/**
 * modify block settings
 * This is the function that is called to display the Admin / Blocks / Pagesetter Publication block
 */
function pagesetter_randompubblock_modify($blockinfo)
{
    // Create output object
    $output = new pnHTML();

    // Get current content
    $vars = pnBlockVarsFromContent($blockinfo['content']);

    // Defaults
  if (!isset($vars['tid']))
    $vars['tid'] = pnModGetVar('pagesetter','frontpagePubType');
  /* if (!isset($vars['pid']))
    $vars['pid'] = 1; */
  if (!isset($vars['tpl']))
    $vars['tpl'] = 'randompub';

  if (!pnModAPILoad('pagesetter', 'admin'))
    return pagesetterErrorPage(__FILE__, __LINE__, 'Failed to load Pagesetter admin API');

    // Create row for "Publication type"
//  $output->Linebreak();
  $pubTypesData = pnModAPIFunc('pagesetter',
                               'admin',
                               'getPublicationTypes');

  $pubTypes = array();
  foreach ($pubTypesData as $pubType)
  {
    $pubTypes[] = array( 'name' => $pubType['title'],
                         'id'   => $pubType['id'] );
    if ($pubType['id'] == $vars['tid'])
      $pubTypes[count($pubTypes)-1]['selected'] = 1;
  }

  $row = array();
  $output->SetOutputMode(_PNH_RETURNOUTPUT);
  $row[] = $output->Text(_PGRANPUBBLOCKPUBTYPE);
  $row[] = $output->FormSelectMultiple('tid', $pubTypes);
  $output->SetOutputMode(_PNH_KEEPOUTPUT);

    // Add row
  $output->SetInputMode(_PNH_VERBATIMINPUT);
  $output->TableAddRow($row, 'left');
  $output->SetInputMode(_PNH_PARSEINPUT);

/*    // Create row for Publication
  $row = array();
  $output->SetOutputMode(_PNH_RETURNOUTPUT);
  $row[] = $output->Text(_PGPUBBLOCKPUB);
  $row[] = $output->FormText('pid',$vars['pid']);
  $output->SetOutputMode(_PNH_KEEPOUTPUT);

    // Add row
  $output->SetInputMode(_PNH_VERBATIMINPUT);
  $output->TableAddRow($row, 'left');
  $output->SetInputMode(_PNH_PARSEINPUT);
*/    
    // Create row for Template to use
  $row = array();
  $output->SetOutputMode(_PNH_RETURNOUTPUT);
  $row[] = $output->Text(_PGRANPUBBLOCKTEMPLATE);
  $row[] = $output->FormText('tpl',$vars['tpl']);
  $output->SetOutputMode(_PNH_KEEPOUTPUT);

    // Add row
  $output->SetInputMode(_PNH_VERBATIMINPUT);
  $output->TableAddRow($row, 'left');
  $output->SetInputMode(_PNH_PARSEINPUT);

    // Return output
    return $output->GetOutput();
}

/**
 * update block settings
 */
function pagesetter_randompubblock_update($blockinfo)
{
    $vars = array('tid' => pnVarCleanFromInput('tid'),
                  //'pid' => pnVarCleanFromInput('pid'),
                  'tpl' => pnVarCleanFromInput('tpl'));
                  
    $blockinfo['content'] = pnBlockVarsToContent($vars);

    return $blockinfo;
}

?>