<?php
// $Id: listmenu.php,v 1.13 2007/02/08 21:30:42 jornlind Exp $
// =======================================================================
// Pagesetter by Jorn Lind-Nielsen (C) 2003.
// Thanks to Joerg Napp for some serious bugfixes in this plugin.
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
function pagesetter_listmenublock_init()
{
    // Security
  pnSecAddSchema('pagesetter:Listmenublock:', 'Block title:Publication Type ID:Type ID');
}


/**
 * get information on block
 */
function pagesetter_listmenublock_info()
{
    // Values
  return array('text_type'      => 'pagesetterListMenu',
               'module'         => 'pagesetter',
               'text_type_long' => 'Pagesetter menu based on list field',
               'allow_multiple' => true,
               'form_content'   => false,
               'form_refresh'   => false,
               'show_preview'   => true);
}


function pagesetter_listmenublock_display($blockinfo)
{
    // Get variables from content block
  $vars = pnBlockVarsFromContent($blockinfo['content']);

  $tid = array_key_exists('tid',$vars) ? $vars['tid'] : pnModGetVar('pagesetter','frontpagePubType');
  if (!isset($tid))
  {
    $blockinfo['content'] = 'No type ID set for this block.';
    return themesideblock($blockinfo);
  }

    // Security check
  if (!pnSecAuthAction(0, 'pagesetter:Listmenublock:', "$blockinfo[title]:$blockinfo[bid]:$tid", ACCESS_READ))
    return;

  $listID = array_key_exists('listID',$vars) ? $vars['listID'] : null;
  if (!isset($listID))
  {
    $blockinfo['content'] = 'No category ID set for this block.';
    return themesideblock($blockinfo);
  }

  $field     = $vars['field'];
  $topValue  = $vars['topValue'];
  $template  = $vars['template'];
  $listClass = $vars['listClass'];
  $level     = (empty($vars['level']) && $vars['level'] != "0" ? 1000 : $vars['level']);

  $compare   = (empty($vars['compare']) ? "sub" : $vars['compare']);
  
  if (!pnModAPILoad('pagesetter', 'admin'))
  {
    $blockinfo['content'] = "Failed to load Pagesetter admin API";
    return themesideblock($blockinfo);
  }


  $listInfo = pnModAPIFunc( 'pagesetter',
                            'admin',
                            'getList',
                             array('lid'            => $listID,
                                   'topListValueID' => $topValue) );

  if ($listInfo === false)
    return pagesetterErrorAPIGet();

  // build the parameters for the URL.
  // these are the same for every item.
  $url_parameters = array();
  $url_parameters['tid'] = $tid;
  if (!empty($template)) {
    $url_parameters['tpl'] = $template;
  }

  $items  = $listInfo['items'];
  $indent = $items[0]['indent'];
  
  // the indentation of the first item
  $base_indent = $indent;

  $html = "<ul" . (empty($listClass) ? '' : " class=\"$listClass\"") . ">\n";
  foreach ($items as $item)
  {
    // check if the current item is to be displayed
    // (i.e. if it is within the range of sublevels to show)
    if ($item['indent'] - $base_indent <= $level) {
        // open or close as many levels as necessary.
        $diff = $item['indent'] - $indent;
        $html .= str_repeat('<ul>',  ($diff < 0 ? 0 : $diff));
        $html .= str_repeat('</ul>', ($diff > 0 ? 0 : -$diff));

        // set parameter for the URL
        $url_parameters['filter'] = "$field^$compare^$item[id]";
        $url = pnModUrl('pagesetter', 
                        'user', 
                        'view',
                        $url_parameters);
        $url = htmlspecialchars($url);

        $html .= "<li><a href=\"$url\">$item[title]</a></li>\n";
        
        // keep the new indentation for the next round        
        $indent = $item['indent'];
    }
  }
  $html .= "</ul>\n";


  $blockinfo['content'] = $html;

  return themesideblock($blockinfo);
}


/**
 * modify block settings
 */
function pagesetter_listmenublock_modify($blockinfo)
{
  $output = new pnHTML();

    // Get current content
  $vars = pnBlockVarsFromContent($blockinfo['content']);

    // Defaults
  if (!isset($vars['tid']))
    $vars['tid'] = pnModGetVar('pagesetter','frontpagePubType');

  $tid       = $vars['tid'];
  $field     = $vars['field'];
  $topValue  = $vars['topValue'];
  $listClass = $vars['listClass'];
  $template  = $vars['template'];
  $level     = $vars['level'];
  $compare   = $vars['compare'];
  
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
    if ($pubType['id'] == $tid)
      $pubTypes[count($pubTypes)-1]['selected'] = 1;
  }

  $row = array();
  $output->SetOutputMode(_PNH_RETURNOUTPUT);
  $row[] = $output->Text(_PGBLOCKLISTMENUPUBTYPE);
  $row[] = $output->FormSelectMultiple('tid', $pubTypes);
  $output->SetOutputMode(_PNH_KEEPOUTPUT);

    // Add row
  $output->SetInputMode(_PNH_VERBATIMINPUT);
  $output->TableAddRow($row, 'left');
  $output->SetInputMode(_PNH_PARSEINPUT);

    // Add field name

  $row = array();
  $output->SetOutputMode(_PNH_RETURNOUTPUT);
  $row[] = $output->Text(_PGBLOCKLISTMENUFIELD);
  $row[] = $output->FormText('field', $field);
  $output->SetOutputMode(_PNH_KEEPOUTPUT);

    // Add row
  $output->SetInputMode(_PNH_VERBATIMINPUT);
  $output->TableAddRow($row, 'left');
  $output->SetInputMode(_PNH_PARSEINPUT);

    // Add top list value

  $row = array();
  $output->SetOutputMode(_PNH_RETURNOUTPUT);
  $row[] = $output->Text(_PGBLOCKLISTMENUTOPVALUE);
  $row[] = $output->FormText('topValue', $topValue);
  $output->SetOutputMode(_PNH_KEEPOUTPUT);

    // Add row
  $output->SetInputMode(_PNH_VERBATIMINPUT);
  $output->TableAddRow($row, 'left');
  $output->SetInputMode(_PNH_PARSEINPUT);

    // Add indent level

  $row = array();
  $output->SetOutputMode(_PNH_RETURNOUTPUT);
  $row[] = $output->Text(_PGBLOCKLISTMENULEVEL);
  $row[] = $output->FormText('level', $level);
  $output->SetOutputMode(_PNH_KEEPOUTPUT);

    // Add row
  $output->SetInputMode(_PNH_VERBATIMINPUT);
  $output->TableAddRow($row, 'left');
  $output->SetInputMode(_PNH_PARSEINPUT);

    // Add comparison

  $row = array();
  $output->SetOutputMode(_PNH_RETURNOUTPUT);
  $row[] = $output->Text(_PGBLOCKLISTMENUCOMPARE);
  $row[] = $output->FormText('compare', $compare);
  $output->SetOutputMode(_PNH_KEEPOUTPUT);

    // Add row
  $output->SetInputMode(_PNH_VERBATIMINPUT);
  $output->TableAddRow($row, 'left');
  $output->SetInputMode(_PNH_PARSEINPUT);

    // Add target template

  $row = array();
  $output->SetOutputMode(_PNH_RETURNOUTPUT);
  $row[] = $output->Text(_PGBLOCKLISTMENUTEMPLATE);
  $row[] = $output->FormText('template', $template);
  $output->SetOutputMode(_PNH_KEEPOUTPUT);

    // Add row
  $output->SetInputMode(_PNH_VERBATIMINPUT);
  $output->TableAddRow($row, 'left');
  $output->SetInputMode(_PNH_PARSEINPUT);

    // Add list class name

  $row = array();
  $output->SetOutputMode(_PNH_RETURNOUTPUT);
  $row[] = $output->Text(_PGBLOCKLISTMENULISTCLASS);
  $row[] = $output->FormText('listClass', $listClass);
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
function pagesetter_listmenublock_update($blockinfo)
{
  $tid       = pnVarCleanFromInput('tid');
  $field     = pnVarCleanFromInput('field');
  $topValue  = pnVarCleanFromInput('topValue');
  $listClass = pnVarCleanFromInput('listClass');
  $template  = pnVarCleanFromInput('template');
  $level     = pnVarCleanFromInput('level');
  $compare   = pnVarCleanFromInput('compare');

  if (!pnModAPILoad('pagesetter', 'admin'))
    return pagesetterErrorPage(__FILE__, __LINE__, 'Failed to load Pagesetter admin API');

    // Fetch category ID, so we don't have to calulate that every time block is displayed
  $listID = pnModAPIFunc( 'pagesetter',
                          'admin',
                          'getListIDByFieldName',
                          array('tid'   => $tid,
                                'field' => $field) );

  if ($listID === false)
  {
    pnSessionSetVar('errormsg', _PGBLOCKLISTMENUBADCATFIELD . ': ' . $field);
    return false;
  }

  $vars = array('tid'       => $tid,
                'field'     => $field,
                'listID'    => $listID,
                'topValue'  => $topValue,
                'template'  => $template,
                'listClass' => $listClass,
                'level'     => $level,
                'compare'   => $compare );

  $blockinfo['content'] = pnBlockVarsToContent($vars);

  return $blockinfo;
}


?>
