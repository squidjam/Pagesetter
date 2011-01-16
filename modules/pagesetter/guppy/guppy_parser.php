<?php
// $Id: guppy_parser.php,v 1.28 2006/07/12 21:06:57 jornlind Exp $
// =======================================================================
// Guppy by Jorn Lind-Nielsen (C) 2003.
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

require_once 'modules/pagesetter/guppy/guppy_common.php';

/*========================================================================
  Specification parser
========================================================================*/

function guppy_parseXMLSpec($specXML, $options = array())
{
  global $guppyParserOptions;
  $guppyParserOptions = $options;

  // Instantiate parser
  $parser = xml_parser_create();
  xml_set_element_handler($parser, "guppy_XMLSpecStartElementHandler", "guppy_XMLSpecEndElementHandler");
  xml_set_character_data_handler($parser, "guppy_XMLSpecCharacterHandler");

  if (!xml_parse($parser, $specXML, true))
  {
    guppy_generateError('guppy_parseSpec',
                        "Unable to parse XML Guppy specification (line "
                        . xml_get_current_line_number($parser) . ","
                        . xml_get_current_column_number($parser) . "): "
                        . xml_error_string($parser));
    xml_parser_free($parser);    
    return false;
  }

  xml_parser_free($parser);

  global $guppyParsedSpec;
  //print_r($guppyParsedSpec); exit(0);

  return $guppyParsedSpec;
}


function guppy_XMLSpecStartElementHandler($parser, $name, $attribs)
{
  //echo "B: $name<br>\n"; print_r($attribs);
  global $guppyXMLState;
  global $guppyParsedSpec;
  global $guppyParsedComponentName;
  global $guppyParsedFieldName;

  if ($guppyXMLState == ''  &&  $name == 'COMPONENTS')
  {
    $guppyParsedSpec = array( 'components' => array() );
  }
  else if ($guppyXMLState == ':COMPONENTS'  &&  $name == 'COMPONENT')
  {
    $clickOnHeaders = guppy_parseBoolean($attribs, 'CLICKONHEADERS', false);
    $lineno = guppy_parseBoolean($attribs, 'LINENO', false);

    $component = array( 'name'           => guppy_fetchAttribute($attribs, 'NAME'),
                        'kind'           => guppy_fetchAttribute($attribs, 'KIND'),
                        'title'          => guppy_fetchAttribute($attribs, 'TITLE'),
                        'titleField'     => guppy_fetchAttribute($attribs, 'TITLEFIELD'),
                        'fields'         => array(),
                        'actions'        => array(),
                        'clickOnHeaders' => $clickOnHeaders,
                        'lineno'         => $lineno,
                        'rowOperations'  => array('insert' => true,
                                                  'delete' => true) );
    
    $guppyParsedComponentName = $attribs['NAME'];
    $guppyParsedSpec['components'][$guppyParsedComponentName] = $component;
  }
  else if ($guppyXMLState == ':COMPONENTS:COMPONENT:FIELDS'  &&  $name == 'FIELD')
  {
    $fieldName   = guppy_fetchAttribute($attribs, 'NAME');
    $fieldTitle  = guppy_fetchAttribute($attribs, 'TITLE');
    $fieldHint   = guppy_fetchAttribute($attribs, 'HINT');
    $fieldKind   = guppy_fetchAttribute($attribs, 'KIND', 'input');
    $fieldType   = guppy_fetchAttribute($attribs, 'TYPE', 'string');
    $mandatory   = guppy_parseBoolean($attribs, 'MANDATORY', false);
    $readonly    = guppy_parseBoolean($attribs, 'READONLY', false);

    $field = array( 'name'         => $fieldName,
                    'title'        => $fieldTitle,
                    'kind'         => $fieldKind,
                    'type'         => $fieldType,
                    'mandatory'    => $mandatory,
                    'readonly'     => $readonly,
                    'hint'         => $fieldHint);
    
    if ($fieldType == "select")
    {
      $field['options'] = array( array( 'title' => ($mandatory ? '*' : ''), 'value' => null) );  // Always add empty option

      if (isset($attribs['OPTIONS']))
      {
        global $guppyParserOptions;
        $options = guppy_fetchAttribute($attribs, 'OPTIONS');
        $options = guppy_fetchAttribute($guppyParserOptions, $options, array());
        $field['options'] = array_merge($field['options'], $options);
      }
    }

    global $guppyParsedSpec;
    $guppyParsedSpec['components'][$guppyParsedComponentName]['fields'][$fieldName] = $field;
    $guppyParsedFieldName = $fieldName;
  }
  else if ($guppyXMLState == ':COMPONENTS:COMPONENT:FIELDS:FIELD'  &&  $name == 'OPTION')
  {
    $value = (isset($attribs['VALUE']) 
              ? $attribs['VALUE'] 
              : count($guppyParsedSpec['components'][$guppyParsedComponentName]['fields'][$guppyParsedFieldName]['options'])-1);

    $guppyParsedSpec['components'][$guppyParsedComponentName]['fields'][$guppyParsedFieldName]['options'][]
      = array( 'title' => $attribs['TITLE'],
               'value' => $value);
  }
  else if ($guppyXMLState == ':COMPONENTS:COMPONENT:ACTIONS'  &&  $name == 'ACTION')
  {
    $actionName  = (isset($attribs['NAME']) ? $attribs['NAME'] : NULL);
    $actionTitle = (isset($attribs['TITLE']) ? $attribs['TITLE'] : NULL);
    $actionKind  = (isset($attribs['KIND']) ? $attribs['KIND'] : "submit");
    $actionHint  = (isset($attribs['HINT']) ? $attribs['HINT'] : NULL);

    $action = array( 'name'  => $actionName,
                     'title' => $actionTitle,
                     'kind'  => $actionKind,
                     'hint'  => $actionHint );
    
    $guppyParsedSpec['components'][$guppyParsedComponentName]['actions'][$actionName] = $action;
  }
  else if ($guppyXMLState == ':COMPONENTS:COMPONENT'  &&  $name == 'ROWOPERATIONS')
  {
    $insert = guppy_parseBoolean($attribs, 'INSERT', true);
    $delete = guppy_parseBoolean($attribs, 'DELETE', true);

    $operations = array( 'insert'         => $insert,
                         'delete'         => $delete,
                         'insertConfirm'  => guppy_fetchAttribute($attribs, 'INSERTCONFIRM'),
                         'deleteConfirm'  => guppy_fetchAttribute($attribs, 'DELETECONFIRM'));
    
    $guppyParsedSpec['components'][$guppyParsedComponentName]['rowOperations'] = $operations;
  }

  $guppyXMLState .= ":$name";
}


function guppy_XMLSpecEndElementHandler($parser, $name)
{
  global $guppyXMLState;
  //echo "E: $name<br>\n";

  // Pop last ":TAG"
  $guppyXMLState = substr($guppyXMLState, 0, strrpos($guppyXMLState, ":"));
}


function guppy_XMLSpecCharacterHandler($parser, $data)
{
  //echo $data;
}


/*========================================================================
  Layout parser
========================================================================*/

function guppy_parseXMLLayout($layoutXML)
{
  // Instantiate parser
  $parser = xml_parser_create();
  xml_set_element_handler($parser, "guppy_XMLLayoutStartElementHandler", "guppy_XMLLayoutEndElementHandler");
  xml_set_character_data_handler($parser, "guppy_XMLLayoutCharacterHandler");

  if (!xml_parse($parser, $layoutXML, true))
  {
    guppy_generateError('guppy_parseLayout',
                        "Unable to parse XML Guppy layout (line "
                        . xml_get_current_line_number($parser) . ","
                        . xml_get_current_column_number($parser) . "): "
                        . xml_error_string($parser));
    xml_parser_free($parser);    
    return false;
  }

  xml_parser_free($parser);

  global $guppyParsedLayout;
  //print_r($guppyParsedLayout); exit(0);
  
  //global $guppyParsedSpec;
  //print_r($guppyParsedSpec); exit(0);
  //var_dump($guppyParsedLayout);

  return $guppyParsedLayout;
}


function guppy_XMLLayoutStartElementHandler($parser, $name, $attribs)
{
  //echo "B: $name<br>\n"; print_r($attribs);
  global $guppyXMLState;
  global $guppyParsedLayout;
  global $guppyParsedComponentName;
  global $guppyParsedLayoutStack;

  if (count($guppyParsedLayoutStack) > 0)
    $currentLayout = &$guppyParsedLayoutStack[count($guppyParsedLayoutStack)-1];
  else
    $currentLayout = null;

  if (count($currentLayout) > 0)
    $currentRow = &$currentLayout[count($currentLayout)-1];

  if ($guppyXMLState == ''  &&  $name == 'LAYOUT')
  {
    $guppyParsedLayout = array( 'layout' => array(),
                                'idmapping' => array() );
    
    $guppyParsedLayoutStack = array( &$guppyParsedLayout['layout'] );
  }
  else if ((guppy_XMLStateHasTail(':LAYOUT')  ||  guppy_XMLStateHasTail(':ISLAND') )&&  $name == 'ROW')
  {
    $currentLayout[] = array();
  }
  else if ((guppy_XMLStateHasTail(':LAYOUT')  ||  guppy_XMLStateHasTail(':CELL') )&&  $name == 'ROW')
  {
    $currentLayout[] = array();
  }
  else if (guppy_XMLStateHasTail(':ROW')  &&  $name == 'COMPONENT')
  {
    global $guppyCurrentComponent;
    $guppyCurrentComponent = guppy_fetchAttribute($attribs, 'NAME');

    $currentRow[] = array( 'kind'           => 'component',
                           'name'           => guppy_fetchAttribute($attribs, 'NAME'),
                           'title'          => guppy_fetchAttribute($attribs, 'TITLE'),
                           'layout'         => array(),
                           'buttonsTop'     => array(),
                           'buttonsBottom'  => array() );

    if (isset($attribs['ID']))
    {
      $guppyParsedLayout['idmapping'][$attribs['ID']] = &$currentRow[count($currentRow)-1];
    }
  }
  else if (guppy_XMLStateHasTail(':COMPONENT')  &&  $name == 'LAYOUT')
  {
    $guppyParsedLayoutStack[] = &$currentRow[count($currentRow)-1]['layout'];
  }
  else if (guppy_XMLStateHasTail(':ROW')  &&  $name == 'ISLAND')
  {
    $currentRow[] = array( 'kind'     => 'island',
                           'title'    => guppy_fetchAttribute($attribs, 'TITLE'),
                           'colspan'  => guppy_fetchAttribute($attribs, 'COLSPAN'),
                           'layout'   => array() );

    $guppyParsedLayoutStack[] = &$currentRow[count($currentRow)-1]['layout'];
  }
  else if (guppy_XMLStateHasTail(':ROW')  &&  $name == 'CELL')
  {
    $currentRow[] = array( 'kind'     => 'cell',
                           'colspan'  => guppy_fetchAttribute($attribs, 'COLSPAN'),
                           'layout'   => array() );

    $guppyParsedLayoutStack[] = &$currentRow[count($currentRow)-1]['layout'];
  }
  else if (guppy_XMLStateHasTail(':COMPONENT')  &&  $name == 'COLUMNS')
  {
    $guppyParsedLayoutStack[] = &$currentRow[count($currentRow)-1]['layout'];
  }
  else if (guppy_XMLStateHasTail(':ROW')  &&  $name == 'TITLE')
  {
    $currentRow[] = array( 'kind'     => 'title',
                           'colspan'  => guppy_fetchAttribute($attribs, 'COLSPAN'),
                           'name'     => guppy_fetchAttribute($attribs, 'NAME') );
    if (isset($attribs['ID']))
    {
      $guppyParsedLayout['idmapping'][$attribs['ID']] = &$currentRow[count($currentRow)-1];
    }
  }
  else if (guppy_XMLStateHasTail(':ROW')  &&  $name == 'TEXT')
  {
    $currentRow[] = array( 'kind'     => 'text',
                           'colspan'  => guppy_fetchAttribute($attribs, 'COLSPAN'),
                           'id'       => guppy_fetchAttribute($attribs, 'ID'),
                           'text'     => '');
    if (isset($attribs['ID']))
    {
      $guppyParsedLayout['idmapping'][$attribs['ID']] = &$currentRow[count($currentRow)-1];
    }
  }
  else if (guppy_XMLStateHasTail(':ROW')  &&  $name == 'HINT')
  {
    $currentRow[] = array( 'kind'    => 'hint',
                           'name'    => guppy_fetchAttribute($attribs, 'NAME'),
                           'colspan' => guppy_fetchAttribute($attribs, 'COLSPAN'),
                           'height'  => guppy_fetchAttribute($attribs, 'HIEGHT'),
                           'width'   => guppy_fetchAttribute($attribs, 'WIDTH') );
  }
  else if (guppy_XMLStateHasTail(':ROW')  &&  $name == 'FIELD')
  {
    $kind = guppy_fetchAttribute($attribs, 'KIND', 'input');
    $insertTitle = guppy_parseBoolean($attribs, 'TITLE', true);
    $fieldFocus  = guppy_parseBoolean($attribs, 'INITIALFOCUS', false);

    if ($kind != 'title'  &&  $insertTitle)
      $currentRow[] = array( 'kind'     => 'title',
                             'name'     => guppy_fetchAttribute($attribs, 'NAME'), );

    $fieldName = guppy_fetchAttribute($attribs, 'NAME');
    $mandatory = guppy_parseBoolean($attribs, 'MANDATORY', false);
    $readonly = guppy_parseBoolean($attribs, 'READONLY', false);

    $currentRow[] = array( 'kind'         => $kind,
                           'name'         => $fieldName,
                           'width'        => guppy_fetchAttribute($attribs, 'WIDTH'),
                           'height'       => guppy_fetchAttribute($attribs, 'HEIGHT'),
                           'colspan'      => guppy_fetchAttribute($attribs, 'COLSPAN'),
                           'view'         => guppy_fetchAttribute($attribs, 'VIEW'),
                           'hint'         => guppy_fetchAttribute($attribs, 'HINT'),
                           'mandatory'    => $mandatory,
                           'readonly'     => $readonly,
                           'initialFocus' => $fieldFocus );

      // Transfer mandatory and readonly values to spec.
    global $guppyParsedSpec;
    global $guppyCurrentComponent;
    if (isset($attribs['MANDATORY']))
      $guppyParsedSpec['components'][$guppyCurrentComponent]['fields'][$fieldName]['mandatory'] = $mandatory;
    if (isset($attribs['READONLY']))
      $guppyParsedSpec['components'][$guppyCurrentComponent]['fields'][$fieldName]['readonly'] = $readonly;

      // Mark field as used
    if ($kind == 'input'  &&  array_key_exists($fieldName, $guppyParsedSpec['components'][$guppyCurrentComponent]['fields']))
      $guppyParsedSpec['components'][$guppyCurrentComponent]['fields'][$fieldName]['inUse'] = true;
  }
  else if (guppy_XMLStateHasTail(':COLUMNS')  &&  $name == 'FIELD')
  {
    $kind = (isset($attribs['KIND']) ? $attribs['KIND'] : 'input');
    $fieldName = guppy_fetchAttribute($attribs, 'NAME');
    $mandatory = guppy_parseBoolean($attribs, 'MANDATORY', false);
    $readonly = guppy_parseBoolean($attribs, 'READONLY', false);

    $currentLayout[] = array( 'kind'      => $kind,
                              'name'      => $fieldName,
                              'width'     => guppy_fetchAttribute($attribs, 'WIDTH'),
                              'hint'      => guppy_fetchAttribute($attribs, 'HINT'),
                              'mandatory' => $mandatory,
                              'readonly'  => $readonly );

    global $guppyParsedSpec;
    global $guppyCurrentComponent;
    if (isset($attribs['MANDATORY']))
      $guppyParsedSpec['components'][$guppyCurrentComponent]['fields'][$fieldName]['mandatory'] = $mandatory;
    if (isset($attribs['READONLY']))
      $guppyParsedSpec['components'][$guppyCurrentComponent]['fields'][$fieldName]['readonly'] = $readonly;

      // Mark field as used
    if ($kind == 'input')
      $guppyParsedSpec['components'][$guppyCurrentComponent]['fields'][$fieldName]['inUse'] = true;
  }
  else if (guppy_XMLStateHasTail(':COMPONENT')  &&  $name == 'BUTTONSTOP')
  {
    $guppyParsedLayoutStack[] = &$currentRow[count($currentRow)-1]['buttonsTop'];
  }
  else if (guppy_XMLStateHasTail(':COMPONENT')  &&  $name == 'BUTTONSBOTTOM')
  {
    $guppyParsedLayoutStack[] = &$currentRow[count($currentRow)-1]['buttonsBottom'];
  }
  else if ((guppy_XMLStateHasTail(':BUTTONSTOP')  ||  guppy_XMLStateHasTail(':BUTTONSBOTTOM'))  &&  $name == 'BUTTON')
  {
    $currentLayout[] = array( 'name'  => guppy_fetchAttribute($attribs, 'NAME'),
                              'kind'  => 'button',
                              'hint'  => guppy_fetchAttribute($attribs, 'HINT'),
                              'title' => guppy_fetchAttribute($attribs, 'TITLE') );
  }
  else if ((guppy_XMLStateHasTail(':BUTTONSTOP:GROUP')  ||  guppy_XMLStateHasTail(':BUTTONSBOTTOM:GROUP'))  &&  $name == 'BUTTON')
  {
    $group = &$currentLayout[count($currentLayout)-1];
    $group['buttons'][] = array( 'name'  => guppy_fetchAttribute($attribs, 'NAME'),
                                 'kind'  => 'button',
                                 'hint'  => guppy_fetchAttribute($attribs, 'HINT'),
                                 'title' => guppy_fetchAttribute($attribs, 'TITLE') );
  }
  else if ((guppy_XMLStateHasTail(':BUTTONSTOP')  ||  guppy_XMLStateHasTail(':BUTTONSBOTTOM'))  &&  $name == 'GROUP')
  {
    $currentLayout[] = array( 'buttons' => array(),
                              'kind'    => 'group',
                              'title'   => guppy_fetchAttribute($attribs, 'TITLE') );
  }

  $guppyXMLState .= ":$name";
}


function guppy_XMLLayoutEndElementHandler($parser, $name)
{
  global $guppyXMLState;
  global $guppyParsedLayoutStack;

    // Pop last ":TAG"
  $guppyXMLState = substr($guppyXMLState, 0, strrpos($guppyXMLState, ":"));

  if (guppy_XMLStateHasTail(':LAYOUT')  &&  $name == 'ROW')
  {
    //array_pop($guppyParsedLayoutStack);
  }
  else if (guppy_XMLStateHasTail(':COMPONENT')  &&  ($name == 'LAYOUT'  ||  $name == 'COLUMNS'))
  {
    array_pop($guppyParsedLayoutStack);

    global $guppyCurrentComponent;
    $guppyCurrentComponent = null;
  }  
  else if (guppy_XMLStateHasTail(':ROW')  &&  $name == 'ISLAND')
  {
    array_pop($guppyParsedLayoutStack);
  }  
  else if (guppy_XMLStateHasTail(':ROW')  &&  $name == 'CELL')
  {
    array_pop($guppyParsedLayoutStack);
  }  
  else if (guppy_XMLStateHasTail(':COMPONENT')  &&  $name == 'BUTTONSTOP')
  {
    array_pop($guppyParsedLayoutStack);
  }  
  else if (guppy_XMLStateHasTail(':COMPONENT')  &&  $name == 'BUTTONSBOTTOM')
  {
    array_pop($guppyParsedLayoutStack);
  }  
}


function guppy_XMLLayoutCharacterHandler($parser, $data)
{
  global $guppyXMLState;
  global $guppyParsedLayout;
  global $guppyParsedComponentName;
  global $guppyParsedLayoutStack;

  if (count($guppyParsedLayoutStack) > 0)
    $currentLayout = &$guppyParsedLayoutStack[count($guppyParsedLayoutStack)-1];
  if (count($currentLayout) > 0)
    $currentRow = &$currentLayout[count($currentLayout)-1];

  if (guppy_XMLStateHasTail(':TEXT'))
  {
    $currentCell = &$currentRow[count($currentRow)-1];
    $currentCell['text'] .= $data;
  }  
  //echo $data;
}


/*========================================================================
  Toolbar parser
========================================================================*/

function guppy_parseXMLToolbar($toolbarXML)
{
  // Instantiate parser
  $parser = xml_parser_create();
  xml_set_element_handler($parser, "guppy_XMLToolbarStartElementHandler", "guppy_XMLToolbarEndElementHandler");
  xml_set_character_data_handler($parser, "guppy_XMLToolbarCharacterHandler");

  if (!xml_parse($parser, $toolbarXML, true))
  {
    guppy_generateError('guppy_parseToolbar',
                        "Unable to parse XML Guppy toolbar (line "
                        . xml_get_current_line_number($parser) . ","
                        . xml_get_current_column_number($parser) . "): "
                        . xml_error_string($parser));
    xml_parser_free($parser);    
    return false;
  }

  xml_parser_free($parser);

  global $guppyParsedToolbar;

  return $guppyParsedToolbar;
}


function guppy_XMLToolbarStartElementHandler($parser, $name, $attribs)
{
  //echo "B: $name<br>\n"; print_r($attribs);
  global $guppyXMLState;
  global $guppyParsedToolbar;
  global $guppyParsedMenu;
  global $guppyParsedMenuStack;

  if (count($guppyParsedMenuStack) > 0)
    $currentMenu = &$guppyParsedMenuStack[count($guppyParsedMenuStack)-1];

  if ($guppyXMLState == ''  &&  $name == 'TOOLBAR')
  {
    $guppyParsedToolbar = array( 'menu' => array(),
                                 'tools' => array() );
  }
  else if (guppy_XMLStateHasTail(':TOOLBAR')  &&  $name == 'MENU')
  {
    $guppyParsedMenuStack = array( &$guppyParsedToolbar['menu'] );
  }
  else if ($name == 'SUBMENU')
  {
    $currentMenu[] = array( 'title'   => $attribs['TITLE'],
                            'subMenu' => array() );

    $guppyParsedMenuStack[] = &$currentMenu[count($currentMenu)-1]['subMenu'];
  }
  else if (guppy_XMLStateHasTail(':SUBMENU')  &&  $name == 'ITEM')
  {
    $currentMenu[] = array( 'action'  => $attribs['ACTION'],
                            'title'   => $attribs['TITLE'] );
  }

  $guppyXMLState .= ":$name";
}


function guppy_XMLToolbarEndElementHandler($parser, $name)
{
  global $guppyXMLState;
  global $guppyParsedMenuStack;

    // Pop last ":TAG"
  $guppyXMLState = substr($guppyXMLState, 0, strrpos($guppyXMLState, ":"));

  if ($name == 'SUBMENU')
  {
    array_pop($guppyParsedMenuStack);
  }  
}


function guppy_XMLToolbarCharacterHandler($parser, $data)
{
  //echo $data;
}



/*========================================================================
  Helpers
========================================================================*/

function guppy_XMLStateHasTail($tail)
{
  global $guppyXMLState;
  $offset = strlen($guppyXMLState) - strlen($tail);
  if ($offset < 0)
    return false;
  return !(strpos($guppyXMLState, $tail, $offset) === false);
}



function guppy_parseBoolean(&$attrib, $name, $defaultValue)
{
  if (!array_key_exists($name,$attrib))
    return $defaultValue;

  $val = $attrib[$name];
  if ($val == '0' || $val == 'false' || $val == 'no')
    return false;
  if ($val != '0' || $val == 'true' || $val == 'yes')
    return true;

  return false;
}


?>
