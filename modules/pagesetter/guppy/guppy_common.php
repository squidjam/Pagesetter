<?php
// $Id: guppy_common.php,v 1.6 2007/02/08 21:30:42 jornlind Exp $
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

require_once 'modules/pagesetter/guppy/guppy_postnuke.php';

function guppy_generateError($location, $message)
{
  if ($location != null)
    echo "Guppy error in '$location': $message";
  else
    echo $message;
  return false;
}


function guppy_translate($str)
{
  if (strlen($str)>0 && $str[0] != '_')
    return $str;
  return constant($str);
}


function guppy_fetchAttribute(&$attrib, $name, $defaultValue=null)
{
  if (!array_key_exists($name,$attrib))
    return $defaultValue;
  
  return $attrib[$name];
}

?>