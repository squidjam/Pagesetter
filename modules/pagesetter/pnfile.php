<?php
// $Id: pnfile.php,v 1.8 2007/02/08 21:30:41 jornlind Exp $
// =======================================================================
// Pagesetter by Jorn Lind-Nielsen (C) 2003-2005.
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


function pagesetter_file_get()
{
  $tid = (int)pnVarCleanFromInput('tid');
  $pid = pnVarCleanFromInput('pid');
  $id  = pnVarCleanFromInput('id');
  $fid = pnVarCleanFromInput('fid');
  $thumbnail = pnVarCleanFromInput('tmb');
  $download = pnVarCleanFromInput('download');

  $download = ($download != '');


  if (!pnModAPILoad('pagesetter', 'user'))
    return pagesetterErrorPage(__FILE__, __LINE__, 'Failed to load Pagesetter user API');

  if (!pnModAPILoad('pagesetter', 'admin'))
    return pagesetterErrorPage(__FILE__, __LINE__, 'Failed to load Pagesetter admin API');


  $args = array( 'tid'    => $tid,
                 'format' => 'database' );
  if (isset($id))
    $args['id'] = (int)$id;
  if (isset($pid))
    $args['pid'] = (int)$pid;

  $pub = pnModAPIFunc( 'pagesetter', 'user', 'getPub', $args );

  if ($pub === false)
    return pagesetterErrorAPIGet();
  if ($pub === true)
    return pagesetterErrorPage(__FILE__, __LINE__, _PGUNKNOWNPUB);

    // Some users have reported this to make their setup work. The buffer may contain something
    // due to a buggy template or block
  while (@ob_end_clean());

  if (pnConfigGetVar('UseCompression') == 1)
  {
    // With the "while (@ob_end_clean());" stuff above we are guranteed that no z-buffering is done
    // But(!) the "ob_start("ob_gzhandler");" made by pnAPI.php means a "Content-Encoding: gzip" is set.
    // So we need to reset this header since no compression is done
    header("Content-Encoding: identity");
  }

  $uploadDir = pnModGetVar('pagesetter','uploadDirDocs');
  $uploadFilename = $pub[$fid]['tmpname'];

  if ($thumbnail)
  {
    $uploadFilename = str_replace('.dat', '-tmb.dat', $uploadFilename);
  }

  $uploadFilePath = $uploadDir . '/' . $uploadFilename;

    // Check cached versus modified date

  $lastModifiedDate = date('D, d M Y H:i:s T', $pub['core_lastUpdated']);
  $currentETag = $pub['core_lastUpdated'];

  global $HTTP_SERVER_VARS;
  $cachedDate = $HTTP_SERVER_VARS['HTTP_IF_MODIFIED_SINCE'];
  $cachedETag = $HTTP_SERVER_VARS['HTTP_IF_NONE_MATCH'];

    // If magic quotes are on then all query/post variables are escaped - so strip slashes to make a compare possible
    // - only cachedETag is expected to contain quotes
  if (get_magic_quotes_gpc())
    $cachedETag = stripslashes($cachedETag);

  if (    (empty($cachedDate) || $lastModifiedDate == $cachedDate)
      &&  '"'.$currentETag.'"' == $cachedETag)
  {
    header("HTTP/1.1 304 Not Modified");
    header("Status: 304 Not Modified");
    header("Expires: " . date('D, d M Y H:i:s T', time()+180*24*3600)); // My PHP insists on Expires in 1981 as default!
  	header('Pragma: cache'); // My PHP insists on putting a pragma "no-cache", so this is an attempt to avoid that
    header("ETag: \"$pub[core_lastUpdated]\"");
    return true;
  }

  header("Expires: " . date('D, d M Y H:i:s T', time()+180*24*3600)); // My PHP insists on Expires in 1981 as default!
	header('Pragma: cache'); // My PHP insists on putting a pragma "no-cache", so this is an attempt to avoid that
  header("ETag: \"$pub[core_lastUpdated]\"");

 	header("Content-Type: " . $pub[$fid]['type']);
  if ($download)
    header("Content-Disposition: attachment; filename=\"" . $pub[$fid]['name'] . "\"");
  else
    header("Content-Disposition: inline; filename=\"" . $pub[$fid]['name'] . "\"");
	header("Last-Modified: $lastModifiedDate");

  header("Content-Length: " . filesize($uploadFilePath));
  readfile($uploadFilePath);

  return true;
}


function pagesetter_file_preview()
{
  require_once 'modules/pagesetter/guppy/guppy.php';

    // Some users have reported this to make their setup work. The buffer may contain something
    // due to a buggy template or block
  while (@ob_end_clean());

  if (pnConfigGetVar('UseCompression') == 1)
  {
    // With the "while (@ob_end_clean());" stuff above we are guranteed that no z-buffering is done
    // But(!) the "ob_start("ob_gzhandler");" made by pnAPI.php means a "Content-Encoding: gzip" is set.
    // So we need to reset this header since no compression is done
    header("Content-Encoding: identity");
  }

  $id     = pnVarCleanFromInput('id');
  $field  = pnVarCleanFromInput('field');
  $filePath = guppy_getUploadDir() . '/' . pnVarPrepForOS("tmp{$id}.{$field}");

  header("Content-Disposition: inline; filename=\"preview\"");
  header("Content-Length: " . filesize($filePath));
  readfile($filePath);

  return true;
}

?>
