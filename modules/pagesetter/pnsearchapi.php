<?php
// $Id: pnsearchapi.php,v 1.4 2007/12/30 15:40:17 jornlind Exp $
// =======================================================================
// Pagesetter search API by Jorn Wildt (C) 2007.
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

//require_once 'modules/pagesetter/common.php';

/**
 * Search plugin info
 **/
function pagesetter_searchapi_info()
{
  return array('title' => 'pagesetter', 
               'functions' => array('Documents' => 'search'));
}


/**
 * Search form component
 **/
function pagesetter_searchapi_options($args)
{
  if (SecurityUtil::checkPermission('pagesetter::', '::', ACCESS_READ)) 
  {
    $pnRender = pnRender::getInstance('pagesetter');
    $pnRender->assign('active',(isset($args['active']) && isset($args['active']['pagesetter'])) || (!isset($args['active'])));
    return $pnRender->fetch('pagesetter_search_options.html');
  }

  return '';
}


function pagesetter_searchapi_search($args)
{
  pnModAPILoad('pagesetter', 'admin');

  pnModDBInfoLoad('pagesetter');
  pnModDBInfoLoad('Search');
  $dbconn  = pnDBGetConn(true);
  $pntable = pnDBGetTables();

  $searchTable = &$pntable['search_result'];
  $searchColumn = &$pntable['search_result_column'];

  $sessionId = session_id();

  $pubTypes = null; // FIXME - select from User Interface

  if (count($pubTypes) == 0)
  {
    // If no pub types has been selected then assume using all types.
    // This is to enable "one line google like search" without
    // pub. type selector.
    $pubTypes = array();
    $pubTypes2 = pnModAPIFunc( 'pagesetter',
                               'admin',
                               'getPublicationTypes' );
    foreach ($pubTypes2 as $pubType)
      $pubTypes[] = $pubType['id'];
  }


  $insertSql = 
"INSERT INTO $searchTable
  ($searchColumn[title],
   $searchColumn[text],
   $searchColumn[extra],
   $searchColumn[module],
   $searchColumn[created],
   $searchColumn[session])
VALUES ";

  foreach ($pubTypes as $pubType)
  {
    $tid = $pubType;

    $searchableColumns = pnModAPIFunc( 'pagesetter',
                                       'admin',
                                       'getSearchableColumns',
                                       array('tid' => $tid) );

    if (count($searchableColumns) > 0)
    {
      $pubInfo = pnModAPIFunc( 'pagesetter',
                               'admin',
                               'getPubTypeInfo',
                               array('tid' => $tid) );

      $titleFieldID = $pubInfo['publication']['titleFieldID'];
      $titleColumn = pagesetterGetPubColumnName($titleFieldID);
      $pubTitle = $pubInfo['publication']['title'];

      $where = search_construct_where($args, $searchableColumns);

      $pubTable = pagesetterGetPubTableName($tid);
      $pubColumn = $pntable['pagesetter_pubdata_column'];

      if ($where != '')
        $where = "AND ($where)";

      $pubTable = pagesetterGetPubTableName($tid);
      $pubColumn = $pntable['pagesetter_pubdata_column'];

      $lang = pnVarPrepForStore( pnUserGetLang() );

      $textColumns = '';
      foreach ($searchableColumns as $column)
        if ($column != $titleColumn)
          $textColumns .= (empty($textColumns) ? '' : ',') . $column . ',\' \'';

      if (empty($textColumns))
        $textColumns = "''"; // Select empty text

      $sql = "
SELECT $pubColumn[pid],
       $titleColumn,
       CONCAT($textColumns),
       $pubColumn[created]
FROM $pubTable
WHERE     ($pubColumn[publishDate] <= NOW() || $pubColumn[publishDate] IS NULL)
      AND (NOW() < $pubColumn[expireDate] || $pubColumn[expireDate] IS NULL)
      AND NOT $pubColumn[inDepot]
      AND $pubColumn[online]
      AND ($pubColumn[language] = '$lang' OR $pubColumn[language] = 'x_all')
      $where";

      $result = $dbconn->execute($sql);

      if ($dbconn->errorNo() != 0) // FIXME, not correct
        return pagesetterErrorApi(__FILE__, __LINE__, '"search" failed: '
                                                      . $dbconn->errorMsg() . " while executing: $sql");

      for (; !$result->EOF; $result->MoveNext())
      {
        $pid   = $result->fields[0];
        $title = $result->fields[1];
        $text = $result->fields[2];
        $created = $result->fields[3];

        if (pnSecAuthAction(0, 'pagesetter::', "$tid:$pid:", ACCESS_READ))
        {
            $sql = $insertSql . '(' 
                 . '\'' . DataUtil::formatForStore($title) . ' [' . DataUtil::formatForStore($pubInfo['publication']['title']) . ']\', '
                 . '\'' . DataUtil::formatForStore($text) . '\', '
                 . "'$tid:$pid', "
                 . '\'' . 'pagesetter' . '\', '
                 . '\'' . DataUtil::formatForStore($created) . '\', '
                 . '\'' . DataUtil::formatForStore($sessionId) . '\')';
            $insertResult = DBUtil::executeSQL($sql);
            if (!$insertResult)
              return LogUtil::registerError (_GETFAILED);

        
        $url = pnModUrl('pagesetter','user', 'viewpub',
                          array('tid'    => $tid,
                                'pid'    => $pid));


          //$callback->found($tid, $pid, $pubTitle, $title, $url);
        }
      }

      $result->Close();
    }
  }

  return true;
}


function pagesetter_searchapi_search_check(&$args)
{
  $datarow = &$args['datarow'];
  list($tid, $pid) = explode(':', $datarow['extra']);

  $datarow['url'] = pnModUrl('pagesetter', 'user', 'viewpub', 
                             array('tid' => $tid, 'pid' => $pid));

  return true;
}


?>