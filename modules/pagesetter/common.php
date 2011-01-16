<?php
// $Id: common.php,v 1.62 2008/03/15 19:26:26 jornlind Exp $
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

  // These three are unused now, but must be saved in order to be able to upgrade correctly
define('pagesetterApprovalPreview',  0);
define('pagesetterApprovalApproved', 1);
define('pagesetterApprovalRejected', 2);

define('pagesetterFieldTypeListoffset', 100);

define('pagesetterEditRowsPerPage', 20);

define('pagesetterAccessNone',      ACCESS_NONE);
define('pagesetterAccessAuthor',    ACCESS_EDIT);
define('pagesetterAccessEditor',    ACCESS_ADD);
define('pagesetterAccessModerator', ACCESS_DELETE);

  // Field types - as strings to enable plugins (for instance of type 'email')
define('pagesetterFieldTypeString',  '0');
define('pagesetterFieldTypeText',    '1');
define('pagesetterFieldTypeHTML',    '2');
define('pagesetterFieldTypeBool',    '3');
define('pagesetterFieldTypeInt',     '4');
define('pagesetterFieldTypeReal',    '5');
define('pagesetterFieldTypeDate',    '6');
define('pagesetterFieldTypeTime',    '7');
define('pagesetterFieldTypeImage',   '8');
define('pagesetterFieldTypeImageUpload',  '9');
define('pagesetterFieldTypeUpload',      '10');

  // Workflow operation results
define('pagesetterWFOperationError',   0);
define('pagesetterWFOperationWarning', 1);
define('pagesetterWFOperationOk',      2);


// =======================================================================
// Error handling
// =======================================================================

function pagesetterErrorPage($file, $line, $msg)
{
  if ($file == null  ||  !pnSecAuthAction(0, 'pagesetter::', ".*", ACCESS_ADMIN))
    $text = $msg;
  else
    $text = "$file($line): $msg";

  $text = pnVarPrepForDisplay($text);

  $smarty = new pnRender('pagesetter');
  $smarty->caching = false;
  $smarty->assign('errorMessage', $text);
  return $smarty->fetch('pagesetter_error.html');
}


function pagesetterErrorAPI($file, $line, $msg, $setSession=true)
{
  global $pagesetterErrorMessageAPI;

  if ($file == null  ||  !pnSecAuthAction(0, 'pagesetter::', ".*", ACCESS_ADMIN))
    $pagesetterErrorMessageAPI = $msg;
  else
    $pagesetterErrorMessageAPI = "$file($line): $msg";

  if ($setSession)
    pnSessionSetVar('errormsg', $pagesetterErrorMessageAPI);

  return false;
}


function pagesetterErrorAPIGet()
{
  global $pagesetterErrorMessageAPI;

  $smarty = new pnRender('pagesetter');
  $smarty->caching = false;
  $smarty->assign('errorMessage', $pagesetterErrorMessageAPI);
  return $smarty->fetch('pagesetter_error.html');
}


function pagesetterWarningWorkflow($msg)
{
  global $pagesetterWarningMessageWorkflow;

  $pagesetterWarningMessageWorkflow = $msg;

  return pagesetterWFOperationWarning;
}


function pagesetterWarningWorkflowGet()
{
  global $pagesetterWarningMessageWorkflow;

  return $pagesetterWarningMessageWorkflow;
}


function pagesetterXMLErrorUnexpected($name, $state)
{
  return "Unexpected $name tag in $state state";
}

// =======================================================================
// Date handling
// =======================================================================

// pass value as readable YYYY-MM-DD formated string
function pagesetterSqlNullCheck($value)
{
  return ($value == NULL ? "NULL" : "'" . pnVarPrepForStore($value) . "'");
}


// Pass value as internal integer representation
function pagesetterFormatSQLDateTime($datetime)
{
  return (empty($datetime) ? "NULL" : "'" . strftime('%Y%m%d%H%M%S',$datetime) . "'");
}


// =======================================================================
// Publication dynamic table handling
// =======================================================================
function pagesetterGetPubTableName($tid)
{
  return pnConfigGetVar('prefix') . '_pagesetter_pubdata' . $tid;
}


function pagesetterGetPubColumnName($ftid)
{
  if (!isset($ftid))
    return 'pg_id';

  return 'pg_field' . $ftid;
}


function pagesetterGetPublicationUniqueID($tid, $pid = '', $lang = null, $page = null)
{
  return $tid . '_' . $pid . (isset($lang) ? "|$lang" : '') . (isset($page) ? "|$page" : '');
}


function pagesetterGetPubTemplateName($tid)
{
  if (!pnModAPILoad('pagesetter', 'admin'))
    return pagesetterErrorApi(__FILE__, __LINE__, 'Failed to load Pagesetter admin API');

  $pubTypeInfo =  pnModAPIFunc( 'pagesetter',
                                'admin',
                                'getPubTypeInfo',
                                array('tid' => $tid) );

  return $pubTypeInfo['publication']['filename'];
}


function pagesetterSmartyGetTemplateFilename(&$smarty, $tid, $template, &$expectedName)
{
  $templateName = pagesetterGetPubTemplateName($tid);
  $filename = $expectedName = "$templateName-$template.html";

  if (!$smarty->template_exists($filename))
    $filename = "Default-$template.html";

  if (!$smarty->template_exists($filename))
    $filename = "Default.html";

  return $filename;
}


function pagesetterSmartyGetTemplates($tid)
{
  $templateName = pagesetterGetPubTemplateName($tid);

  return array( array('name' => 'list',
                      'file' => "$templateName-list.html"),
                array('name' => 'list-header',
                      'file' => "$templateName-list-header.html"),
                array('name' => 'list-footer',
                      'file' => "$templateName-list-footer.html"),
                array('name' => 'full',
                      'file' => "$templateName-full.html"),
                array('name' => 'print',
                      'file' => "$templateName-print.html"),
                array('name' => 'block-list',
                      'file' => "$templateName-block-list.html") );
}


function pagesetterSmartyClearTypeCache($tid)
{
  $templates = pagesetterSmartyGetTemplates($tid);

  $smarty = new pnRender('pagesetter');

  foreach ($templates as $template)
    $smarty->clear_cache($template['file']);
}


function pagesetterSmartyClearCache($tid, $pid)
{
  $cacheID = pagesetterGetPublicationUniqueID($tid, $pid);

  $smarty = new pnRender('pagesetter');
  $smarty->clear_cache(null,$cacheID);
}


// =======================================================================
// Field handling
// =======================================================================

  // A simplified coerce function for converting URL values
  // to values usable for guppy edit. Probably not complete,
  // but at least it works for dropdowns.
function pagesetterCoerceFieldValue($type, $val)
{
  if ($type >= pagesetterFieldTypeListoffset)
    return intval($val);

  return $val;
}


// =======================================================================
// PostNuke interface specialities
// =======================================================================

  // Get array of topics ready to use in Guppy
function pagesetterPNGetTopics($enableTopicAccess, $templateName, $access)
{
  if ($enableTopicAccess)
  {
    if (!pnModAPILoad('topicaccess', 'user'))
      return pagesetterErrorApi(__FILE__, __LINE__, 'Failed to load Topic Access user API');

    $topics = pnModAPIFunc('topicaccess','user','getAccessibleTopics',
                           array('module'    => 'pagesetter',
                                 'category'  => $templateName,
                                 'access'    => $access,
                                 'idField'   => 'value'));
    if ($topics === false)
      return pagesetterErrorAPI(__FILE__, __LINE__, 'Failed to fetch topics from Topic Access module (' . topicAccessErrorAPIGet() . ')');

    array_splice($topics, 0, 0, array(array('value' => -1, 'title' => '- ' ._PGNONE . ' -')));

    return $topics;
  }
  else
  {
    pnModDBInfoLoad('Topics');
    list($dbconn) = pnDBGetConn();
    $pntable = pnDBGetTables();

    $topicsTable  = $pntable['topics'];
    $topicsColumn = $pntable['topics_column'];

    $sql = "SELECT   $topicsColumn[tid],
                     $topicsColumn[topicname]
            FROM     $topicsTable
            ORDER BY $topicsColumn[topicname]";

    $result = $dbconn->execute($sql);

    if ($dbconn->errorNo() != 0)
      return pagesetterErrorApi(__FILE__, __LINE__, '"PNGetTopics" failed: ' . $dbconn->errorMsg() . " while executing: $sql");

    $topics = array();

    $topics[] = array('value'     => -1,
                      'title'     => '- ' . _PGNONE . ' -');

    for (; !$result->EOF; $result->MoveNext())
    {
      $topics[] = array('value' => intval($result->fields[0]),
                        'title' => $result->fields[1]);
    }

    $result->Close();

    return $topics;
  }
}


function pagesetterPNGetLanguages()
{
  modules_get_language();

  $lang = languagelist();
  $languages = array();

  $handle = opendir('language');
  while ($f = readdir($handle))
  {
     if (is_dir("language/$f") && @$lang[$f])
     {
        $languages[$f] = $lang[$f];
     }
  }
  closedir($handle);
  asort($languages);

  $langList = array( array( 'value' => 'x_all',
                            'title' => _ALL) );

  foreach ($languages as $langName => $langTitle)
    if ($langName != 'x_all')
      $langList[] = array('value' => $langName,
                          'title' => $langTitle);

  return $langList;
}


// =======================================================================
// Misc.
// =======================================================================

  // Replace all "$xyz" references in filter with the appropriate
  // URL variable's value.
function pagesetterReplaceFilterVariable($filterStr)
{
  $matches = array();
  if (preg_match_all("/[$]([a-zA-Z_]+)/", $filterStr, $matches))
  {
    foreach ($matches[1] as $match)
    {
      $varName = $match;
      $value = pnVarCleanFromInput($varName);
      $filterStr = str_replace("\$$varName", $value, $filterStr);
    }
  }
  return $filterStr;
}


function pagesetterFlattenPubData(&$pubCore)
{
  $tmp = array();
  foreach ($pubCore as $key => $value)
  {
    $tmp["core_$key"] = $pubCore[$key];
    unset($pubCore[$key]);
  }

  $pubCore += $tmp;
}


function pagesetterUnflattenPubData(&$pubData)
{
  $core = array();
  foreach ($pubData as $key => $value)
  {
    if (substr($key,0,5) == 'core_')
    {
      $core[substr($key,5)] = $pubData[$key];
      unset($pubData[$key]);
    }
  }

  $pubData['core'] = $core;
}


function pagesetterSplitPages($text)
{
  return preg_split('/<hr[ \r\n\t]+class=\"pagebreak\"[ \r\n\t]*\/[ \r\n\t]*>/', $text);
}


function pagesetterApprovalState2String($stateName, &$workflow)
{
  $state = $workflow->getState($stateName);
  if ($state === false)
    return false;

  return $state->getTitle();
}


function pagesetterGetTID($args = array())
{
  $tid = pnVarCleanFromInput('tid');
  extract($args);

  if (!is_numeric($tid))
    $tid = pnModGetVar('pagesetter','frontpagePubType');

  if (!is_numeric($tid))
    return false;

  return $tid;
}


function pagesetterGetCurrentPermission($tid, $pid)
{
  if (pnSecAuthAction(0, 'pagesetter::', "$tid:$pid:", pagesetterAccessModerator))
    return pagesetterAccessModerator;

  if (pnSecAuthAction(0, 'pagesetter::', "$tid:$pid:", pagesetterAccessEditor))
    return pagesetterAccessEditor;

  if (pnSecAuthAction(0, 'pagesetter::', "$tid:$pid:", pagesetterAccessAuthor))
    return pagesetterAccessAuthor;

  return pagesetterAccessNone;
}


function pagesetterSplitKey($key, &$tid, &$pid)
{
  $splitPos = strpos($key,'.');
  if ($splitPos === false)
    return false;

  $tid = (int)substr($key,0,$splitPos);
  $pid = (int)substr($key,$splitPos+1);

  return true;
}

// =======================================================================
// Searching
// =======================================================================

class PagesetterSearchCallback
{
  function start() {}

  function found($tid, $pid, $pubTitle, $title, $url) {}

  function stop() {}
};


// =======================================================================
// Array merge
// =======================================================================
function is_hash( $var ) {
 if( is_array( $var ) ) {
   if (count($var) == 0)
     return true;
   $keys = array_keys( $var );
   for( $i=0; $i<count($keys); $i++ )
     if( is_string($keys[$i] ) ) return true;
 }
 return false;
}

function array_join_merge( $arr1, $arr2 ) {
 //print_r($arr1); echo "IsHash: ".is_hash($arr1)."\n";
 //print_r($arr2); echo "IsHash: ".is_hash($arr2)."\n";
 if( is_array( $arr1 ) and is_array( $arr2 ) ) {
   // the same -> merge
   $new_array = array();
   if( is_hash( $arr1 ) && is_hash( $arr2 ) ) {
     // hashes -> merge based on keys
     $keys = array_merge( array_keys( $arr1 ), array_keys( $arr2 ) );
     foreach( $keys as $key ) {
       //echo "$key: " . array_key_exists($key,$arr1) . array_key_exists($key,$arr2) . "\n";
       if (!array_key_exists($key,$arr1))
         $new_array[$key] = $arr2[$key];
       else if (!array_key_exists($key,$arr2))
         $new_array[$key] = $arr1[$key];
       else
         $new_array[$key] = array_join_merge( $arr1[$key], $arr2[$key] );
     }
   } else {
     // two real arrays -> merge
     $size = max(count($arr1),count($arr2));
     for ($i=0; $i<$size; ++$i) {
       //echo "$i: ";
       $new_array[$i] = array_join_merge($arr1[$i], $arr2[$i]);
     }
   }

   //print("RESULT: \n"); print_r($new_array);
   return $new_array;
 } else {
   // not the same ... take new one if defined, else the old one stays
   return $arr2;
   //return $arr2 ? $arr2 : $arr1;
 }
}


  // Thanks to webmaster at ragnarokonline dot de - found on www.php.net
if (!function_exists("file_get_contents")) {
  function file_get_contents($filename, $use_include_path = 0) {
   $data = ""; // just to be safe. Dunno, if this is really needed
   $file = @fopen($filename, "rb", $use_include_path);
   if ($file) {
     while (!feof($file)) $data .= fread($file, 1024);
     fclose($file);
   }
   return $data;
  }
}

?>
