<?php
// $Id: guppy_plugin.php,v 1.4 2006/04/26 06:19:31 jornlind Exp $
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


/*========================================================================
  Plugins placed here to minimize include file size for displaying items
========================================================================*/

  // Helper class to pass into the plugins
class GuppyPluginHelper
{
  // Nothing here yet
};


  // Base class for plugins
class GuppyInput
{
  var $ID;
  var $name;
  var $title;
  var $value;
  var $typeData;
  var $mandatory;
  var $readonly;
  var $hint;
  var $width;
  var $height;
  var $error;


  function getHtmlStyle()
  {
    $s = '';

    if (isset($this->width))
      $s .= "width: " . $this->width . "px;";
    if (isset($this->height))
      $s .= "height: " . $this->height . "px;";

    return $s;
  }
};


function guppy_loadPluginType($pluginType)
{
  static $loadedPlugins = array();

  if (empty($loadedPlugins[$pluginType]))
  {
    $filename = "modules/pagesetter/guppy/plugins/input.$pluginType.php";
    require_once $filename;
    $loadedPlugins[$pluginType] = 1;
  }
}


function guppy_newPlugin($pluginType)
{
  guppy_loadPluginType($pluginType);

  $className = "GuppyInput_$pluginType";
  $plugin = new $className;

  return $plugin;
}


function & guppy_getPluginInstance($name, $pluginType)
{
  static $plugins = array();

  $id = str_replace(':','_',$name);

  if (!empty($plugins[$id]))
    return $plugins[$id];

  $plugin = guppy_newPlugin($pluginType);
  $plugin->name = $name;
  $plugin->ID = $id;

  $plugins[$id] = &$plugin;

  return $plugin;
}


function guppy_getPluginTypeFromFilename($filename)
{
  $i = strpos($filename, '.', 6);
  if ($i === false)
    return false;

  return substr($filename, 6, $i-6);
}


?>
