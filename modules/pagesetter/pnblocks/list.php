<?php
// $Id: list.php,v 1.18 2006/02/23 21:14:02 jornlind Exp $
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
// but WIthOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// To read the license please visit http://www.gnu.org/copyleft/gpl.html
// =======================================================================

require_once("modules/pagesetter/common.php");

/**
 * initialise block
 */
function pagesetter_listblock_init()
{
    // Security
  pnSecAddSchema('pagesetter:Listblock:', 'Block title:Block Id:Type Id');
}


/**
 * get information on block
 */
function pagesetter_listblock_info()
{
    // Values
  return array('text_type'      => 'pagesetterList',
               'module'         => 'pagesetter',
               'text_type_long' => 'Pagesetter list N publications',
               'allow_multiple' => true,
               'form_content'   => false,
               'form_refresh'   => false,
               'show_preview'   => true);
}


function pagesetter_listblock_display($blockinfo)
{
    // Get variables from content block
  $vars = pnBlockVarsFromContent($blockinfo['content']);
  if (!array_key_exists('tid',$vars))
    return '';

  $tid = $vars['tid'];
  if (!isset($tid))
    $tid = pnModGetVar('pagesetter','frontpagePubType');
  if (!isset($tid))
  {
    $blockinfo['content'] = 'No type ID set for this block.';
    return themesideblock($blockinfo);
  }

  $listCount  = $vars['listCount'];
  $listOffset = $vars['listOffset'];
  $template   = (isset($vars['template']) && $vars['template'] != '' ? $vars['template'] : 'block-list');
  $filterStr  = $vars['filters'];
  $orderBy    = $vars['orderBy'];
  
    // Security check
  if (!pnSecAuthAction(0, 'pagesetter:Listblock:', "$blockinfo[title]:$blockinfo[bid]:$tid", ACCESS_READ))
    return;

  if (empty($filterStr))
    $filters = null;
  else
    $filters = preg_split("/\s*&\s*/", $filterStr);

  if (!pnModAPILoad('pagesetter', 'user'))
    return pagesetterErrorApi(__FILE__, __LINE__, 'Failed to load Pagesetter user API');

  if (!pnModAPILoad('pagesetter', 'admin'))
    return pagesetterErrorApi(__FILE__, __LINE__, 'Failed to load Pagesetter admin API');

  $pubInfo = pnModAPIFunc('pagesetter', 'admin', 'getPubTypeInfo',
                           array('tid' => $tid) );
  if ($pubInfo === false)
    return pagesetterErrorApi(__FILE__, __LINE__, 'Failed to call getPubTypeInfo');

  $pubList = pnModAPIFunc( 'pagesetter',
                           'user',
                           'getPubList',
                           array('tid'             => $tid,
                                 'topic'           => null,
                                 'useRestrictions' => true,
                                 'noOfItems'       => $listCount,
                                 'offsetItems'     => $listOffset,
                                 'language'        => pnUserGetLang(),
                                 'filterSet'       => $filters,
                                 'orderByStr'      => $orderBy));

  $pubList = $pubList['publications']; // No need for the "more" part

  $smarty = new pnRender('pagesetter');
  $html = '';

  // Add simplified core data
  $core = array('tid'        => $tid,
                'title'      => $pubInfo['publication']['title'],
                'blockTitle' => $blockinfo['title']);
  $smarty->assign('core', $core);

  $templateHeaderFile = pagesetterSmartyGetTemplateFilename($smarty, $tid, "$template-header", $expectedName);

  $cacheID = pagesetterGetPublicationUniqueID($tid, $template, pnUserGetLang());

  if ($smarty->template_exists($templateHeaderFile))
    $html .= $smarty->fetch($templateHeaderFile, "H_$cacheID");

  foreach ($pubList as $pub)
  {
    $pubFormatted =  pnModAPIFunc( 'pagesetter',
                                   'user',
                                   'getPubFormatted',
                                   array('tid'            => $tid,
                                         'pid'            => $pub['pid'],
                                         'format'         => $template,
                                         'coreExtra'      => array('format' => $template),
                                         'updateHitCount' => false ) );

    $html .= $pubFormatted;
  }

  // Re-add simplified core data (override what ever was assigned previously)
  $smarty->assign('core', $core);

  $templateFooterFile = pagesetterSmartyGetTemplateFilename($smarty, $tid, "$template-footer", $expectedName);
  if ($smarty->template_exists($templateFooterFile))
    $html .= $smarty->fetch($templateFooterFile, "F_$cacheID");

  if ($pubInfo['publication']['enableHooks'])
    list($html) = pnModCallHooks('item', 'transform', "$tid-list-block", array($html));

  $blockinfo['content'] = $html;

  return themesideblock($blockinfo);
}


/**
 * modify block settings
 */
function pagesetter_listblock_modify($blockinfo)
{
  $output = new pnHTML();

    // Get current content
  $vars = pnBlockVarsFromContent($blockinfo['content']);

    // Defaults
  if (!isset($vars['tid']))
    $vars['tid'] = pnModGetVar('pagesetter','frontpagePubType');
  if (!isset($vars['listCount']))
    $vars['listCount'] = 10;
  if (!isset($vars['listOffset']))
    $vars['listOffset'] = '';
  if (!isset($vars['template']))
    $vars['template'] = '';

  $listCount  = $vars['listCount'];
  $listOffset = $vars['listOffset'];
  $template   = $vars['template'];
  $filters    = array_key_exists('filters',$vars) ? $vars['filters'] : null;
  $orderBy    = array_key_exists('orderBy',$vars) ? $vars['orderBy'] : null;
  if (!pnModAPILoad('pagesetter', 'admin'))
    return pagesetterErrorPage(__FILE__, __LINE__, 'Failed to load Pagesetter admin API');

    // (no table start/end since the block framework takes care of that)

    // Create row for "Publication type"
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
  $row[] = $output->Text(_PGBLOCKLISTPUBTYPE);
  $row[] = $output->FormSelectMultiple('tid', $pubTypes);
  $output->SetOutputMode(_PNH_KEEPOUTPUT);

    // Add row
  $output->SetInputMode(_PNH_VERBATIMINPUT);
  $output->TableAddRow($row, 'left');
  $output->SetInputMode(_PNH_PARSEINPUT);

    // Add filter

  $row = array();
  $output->SetOutputMode(_PNH_RETURNOUTPUT);
  $row[] = $output->Text(_PGBLOCKLISTFILTER);
  $row[] = $output->FormText('filters', $filters);
  $output->SetOutputMode(_PNH_KEEPOUTPUT);

    // Add row
  $output->SetInputMode(_PNH_VERBATIMINPUT);
  $output->TableAddRow($row, 'left');
  $output->SetInputMode(_PNH_PARSEINPUT);

    // Add order by

  $row = array();
  $output->SetOutputMode(_PNH_RETURNOUTPUT);
  $row[] = $output->Text(_PGBLOCKLISTORDERBY);
  $row[] = $output->FormText('orderBy', $orderBy);
  $output->SetOutputMode(_PNH_KEEPOUTPUT);

    // Add row
  $output->SetInputMode(_PNH_VERBATIMINPUT);
  $output->TableAddRow($row, 'left');
  $output->SetInputMode(_PNH_PARSEINPUT);

    // Add no. of publications

  $row = array();
  $output->SetOutputMode(_PNH_RETURNOUTPUT);
  $row[] = $output->Text(_PGBLOCKLISTSHOWCOUNT);
  $row[] = $output->FormText('listCount', $listCount);
  $output->SetOutputMode(_PNH_KEEPOUTPUT);

    // Add row
  $output->SetInputMode(_PNH_VERBATIMINPUT);
  $output->TableAddRow($row, 'left');
  $output->SetInputMode(_PNH_PARSEINPUT);

    // Add no. of publications offset

  $row = array();
  $output->SetOutputMode(_PNH_RETURNOUTPUT);
  $row[] = $output->Text(_PGBLOCKLISTSHOWOFFSET);
  $row[] = $output->FormText('listOffset', $listOffset);
  $output->SetOutputMode(_PNH_KEEPOUTPUT);

    // Add row
  $output->SetInputMode(_PNH_VERBATIMINPUT);
  $output->TableAddRow($row, 'left');
  $output->SetInputMode(_PNH_PARSEINPUT);

    // Add template

  $row = array();
  $output->SetOutputMode(_PNH_RETURNOUTPUT);
  $row[] = $output->Text(_PGBLOCKLISTTEMPLATE);
  $row[] = $output->FormText('template', $template);
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
function pagesetter_listblock_update($blockinfo)
{
  $filters = pnVarCleanFromInput('filters');

  $vars = array('tid'        => pnVarCleanFromInput('tid'),
                'filters'    => $filters,
                'listCount'  => pnVarCleanFromInput('listCount'),
                'listOffset' => pnVarCleanFromInput('listOffset'),
                'template'   => pnVarCleanFromInput('template'),
                'orderBy'    => pnVarCleanFromInput('orderBy'));

  $blockinfo['content'] = pnBlockVarsToContent($vars);

  return $blockinfo;
}


?>
