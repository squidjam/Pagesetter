<?php
// $Id: pnuserapi.php,v 1.146 2008/03/18 20:28:08 jornlind Exp $
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
// but WithOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// To read the license please visit http://www.gnu.org/copyleft/gpl.html
// =======================================================================

require_once("modules/pagesetter/common.php");


function pagesetter_userapi_getPubList($args)
{
  if (!isset($args['tid'])  ||  $args['tid']=='')
    return pagesetterErrorApi(__FILE__, __LINE__, "Missing argument 'tid' in 'pagesetter_userapi_getPubList'", false);

    // (using null values here for no particular reason)
  $tid                = $args['tid'];
  $orderBy            = isset($args['orderBy']) ? $args['orderBy'] : null;
  $orderByStr         = !empty($args['orderByStr']) ? $args['orderByStr'] : null;
  $topic              = isset($args['topic']) ? (int)$args['topic'] : -1;
  $useRestrictions    = isset($args['useRestrictions']) ? $args['useRestrictions'] : true;
  $hideDepot          = isset($args['hideDepot']) ? $args['hideDepot'] : true;
  $showDeleted        = isset($args['showDeleted']) ? $args['showDeleted'] : false;
  $getTextual         = isset($args['getTextual']) ? $args['getTextual'] : true;
  $language           = isset($args['language']) ? $args['language'] : null;
  $filter             = isset($args['filter']) ? $args['filter'] : null; // Field names in API notation!
  $filterStrSet       = isset($args['filterSet']) ? $args['filterSet'] : null; // Field names in user notation
  $allowDefaultFilter = isset($args['allowDefaultFilter']) ? $args['allowDefaultFilter'] : false;
  $noOfItems          = isset($args['noOfItems']) ? $args['noOfItems'] : null; // using null here makes zero items possible
  $offsetItems        = isset($args['offsetItems']) ? $args['offsetItems'] : null;
  $offsetPage         = isset($args['offsetPage']) ? $args['offsetPage'] : null;
  $getOwners          = isset($args['getOwners']) ? $args['getOwners'] : false;
  $getApprovalState   = isset($args['getApprovalState']) ? $args['getApprovalState'] : false;
  $countOnly          = isset($args['countOnly']) ? $args['countOnly'] : false;

    // Check access
  if (!pnSecAuthAction(0, 'pagesetter::', "$tid::", ACCESS_READ))
    return pagesetterErrorApi(__FILE__, __LINE__, _PGNOAUTH);

  if (!pnModAPILoad('pagesetter', 'admin'))
    return pagesetterErrorApi(__FILE__, __LINE__, 'Failed to load Pagesetter admin API');

    // Get publication info for title field name

  $pubInfo =  pnModAPIFunc( 'pagesetter',
                            'admin',
                            'getPubTypeInfo',
                            array('tid' => $tid) );

  if ($pubInfo === false)
    return false;

    // Get default number of items of none is specified
  if ($noOfItems === null)
    $noOfItems = $pubInfo['publication']['listCount'];

    // Calculate item offset, if not specified, based on current page
  if ($offsetItems == null)
  {
    if ($offsetPage == null)
      $offsetItems = 0;
    else
      $offsetItems = $offsetPage * $noOfItems;
  }

    // Get filter set expression
  if (    (empty($filterStrSet) || count($filterStrSet)==0)
      &&  !empty($pubInfo['publication']['defaultFilter'])
      &&  $allowDefaultFilter
      &&  $topic < 0)
  {
    $filterStrSet = explode('&', $pubInfo['publication']['defaultFilter']);
  }

  if (empty($filterStrSet))
  {
    $filterSetSQL = null;
  }
  else
    $filterSetSQL = pnModAPIFunc( 'pagesetter',
                                  'user',
                                  'parseFilter',
                                  array('tid'    => $tid,
                                        'filter' => $filterStrSet) );

  if ($filterSetSQL === false)
    return false;

  $joinCategoryColumns = $filterSetSQL['joinCategoryColumns'];
  $joinPlugins = $filterSetSQL['join'];

    // Get default ordering if not in args
  if ($orderBy === null  &&  $orderByStr === null  &&  !$countOnly)
  {
    $orderBy = array();

    if ($pubInfo['publication']['sortField1'] != NULL)
    {
      $fieldName = $pubInfo['publication']['sortField1'];
      $fieldIndex = array_key_exists($fieldName,$pubInfo['columnIndex']) ?  $pubInfo['columnIndex'][$fieldName] : null;
      $orderBy[] = array( 'name'        => $pubInfo['publication']['sortField1'],
                          'fieldIndex'  => isset($fieldIndex) ? $fieldIndex : -1,
                          'desc'        => $pubInfo['publication']['sortDesc1'] );
    }
    if ($pubInfo['publication']['sortField2'] != NULL)
    {
      $fieldName = $pubInfo['publication']['sortField2'];
      $fieldIndex = array_key_exists($fieldName,$pubInfo['columnIndex']) ?  $pubInfo['columnIndex'][$fieldName] : null;
      $orderBy[] = array( 'name'        => $pubInfo['publication']['sortField2'],
                          'fieldIndex'  => isset($fieldIndex) ? $fieldIndex : -1,
                          'desc'        => $pubInfo['publication']['sortDesc2'] );
    }
    if ($pubInfo['publication']['sortField3'] != NULL)
    {
      $fieldName = $pubInfo['publication']['sortField3'];
      $fieldIndex = array_key_exists($fieldName,$pubInfo['columnIndex']) ?  $pubInfo['columnIndex'][$fieldName] : null;
      $orderBy[] = array( 'name'        => $pubInfo['publication']['sortField3'],
                          'fieldIndex'  => isset($fieldIndex) ? $fieldIndex : -1,
                          'desc'        => $pubInfo['publication']['sortDesc3'] );
    }
  }
  else if ($orderByStr !== null  &&  !$countOnly)
  {
      // Get order by string expression
    $orderBy = pnModAPIFunc( 'pagesetter',
                             'user',
                             'parseOrderBy',
                             array('tid'        => $tid,
                                   'orderByStr' => $orderByStr) );

    if ($orderBy === false)
      return false;
  }

  $titleFieldId = $pubInfo['publication']['titleFieldID'];
  $titleFieldIndex = $pubInfo['fieldIdIndex'][$titleFieldId];
  $titleColumn = pagesetterGetPubColumnName($titleFieldId);

    // Make database query
  pnModDBInfoLoad('Topics');
  list($dbconn) = pnDBGetConn();
  $pntable = pnDBGetTables();

  $listItemsTable = $pntable['pagesetter_listitems'];
  $listItemsColumn = &$pntable['pagesetter_listitems_column'];
  $pubTable        = pagesetterGetPubTableName($tid);
  $pubColumn       = $pntable['pagesetter_pubdata_column'];
  $pubHeaderTable  = $pntable['pagesetter_pubheader'];
  $pubHeaderColumn = $pntable['pagesetter_pubheader_column'];
  $topicsTable     = $pntable['topics'];
  $topicsColumn    = $pntable['topics_column'];

  if ($pubInfo['publication']['enableTopicAccess'] || !empty($joinPlugins))
    $distinct = 'DISTINCT';
  else
    $distinct = '';

  // if titleColumn is a category field prepare for left join
  // and change titleColumn to the fullTitle
  if ($pubInfo['fields'][$titleFieldIndex]['type'] >= pagesetterFieldTypeListoffset)
  {
    $joinCategoryColumns[$titleColumn] = true;
	$titleColumn .= "_table" . stristr($listItemsColumn['fullTitle'],'.');
  }

  if ($countOnly)
  {
    $sql = "SELECT COUNT(*)\n";
  }
  else
  {
    $sql = "SELECT $distinct
                   $pubTable.$pubColumn[id],
                   $pubTable.$pubColumn[pid],
                   $pubColumn[author],
                   UNIX_TIMESTAMP($pubColumn[created]) as $pubColumn[created],
                   $pubColumn[approvalState],
                   $topicsColumn[topicname],
                   $pubColumn[revision],
                   $pubColumn[online],
                   $titleColumn\n";
  }

  $sql .= "FROM   $pubTable
           LEFT JOIN $topicsTable ON $pubColumn[topic] = $topicsColumn[tid]
           LEFT JOIN $pubHeaderTable ON $pubHeaderColumn[tid] = " . (int)$tid . " AND
                                              $pubHeaderTable.$pubHeaderColumn[pid] = $pubTable.$pubColumn[pid]";

  if ($pubInfo['publication']['enableTopicAccess'])
  {
    $join = pagesetterTopicAccessJoin($pntable, $pubColumn['topic'], $pubInfo['publication']['filename']);

    if ($join === false)
      return false;

    $sql .= "\n$join\n";
  }


  // Add left joins for category fields if any in the orderBy array
  if ($orderBy != null)
  {
    while (list($field) = each($orderBy))
    {
      $fieldIndex = $orderBy[$field]['fieldIndex'];
      if (($fieldIndex > -1) && ($pubInfo['fields'][$fieldIndex]['type'] >= pagesetterFieldTypeListoffset))
      {
        $joinCategoryColumns[$orderBy[$field]['name']] = true;
        $property = isset($orderBy[$field]['property']) ? $orderBy[$field]['property'] : 'lineno';
        $orderBy[$field]['name'] .= "_table" . stristr($listItemsColumn[$property],'.');
      }
    }
  }

  // Append the left joins to sql
  if (!empty($joinCategoryColumns))
  {
    while (list($columnName) = each($joinCategoryColumns))
    {
        $sql .= "\nLEFT JOIN $listItemsTable as {$columnName}_table ON {$columnName}_table.pg_id = $columnName";
    }
  }

  $sql .= "$joinPlugins\n";

  if ($filterSetSQL != null)
    $sql .= $filterSetSQL['joinCategoryIDstr'];


  $whereSQL = '';

    // Add publish restrictions

  if ($language != null)
    $languageRestriction = "($pubColumn[language] = '" . pnVarPrepForStore($language) . "' OR $pubColumn[language] = 'x_all')";

  if ($useRestrictions)
  {
    $whereSQL = "\nWHERE ($pubColumn[publishDate] <= NOW() || $pubColumn[publishDate] IS NULL)"
                . "\nAND  (NOW() < $pubColumn[expireDate] || $pubColumn[expireDate] IS NULL)"
                . "\nAND $pubColumn[online]"
                . "\nAND $pubColumn[showInList]";

    if ($language != null)
      $whereSQL .= "\nAND $languageRestriction";

    if ($topic >= 0)
      $whereSQL .= "\nAND $pubColumn[topic] = " . pnVarPrepForStore($topic);
  }
  else
  {
    if ($language != null)
      $whereSQL = "\nWHERE $languageRestriction";
  }

    // Add filter restrictions
    // These filters are a bit simpler than the filter set and always uses "LIKE" operator.
    // Used solely for filtering in the editor's publication list

  if ($filter != null)
  {
    $first = true;

    foreach ($filter as $field => $value)
    {
      if (isset($value)  &&  $value !== '')
      {
        if ($first)
        {
          if ($whereSQL == '')
            $whereSQL = "\nWHERE";
          $first = false;
        }
        else
        {
          $whereSQL .= " AND";
        }

        if ($field == 'author')
          $whereSQL .= " " . $pubColumn[$field] . " LIKE '%" . pnVarPrepForStore($value) . "%'";
        else if ($field == 'title')
          $whereSQL .= " " . $titleColumn . " LIKE '%" . pnVarPrepForStore($value) . "%'";
        else
          $whereSQL .= " " . $pubColumn[$field] . " = '" . pnVarPrepForStore($value) . "'";
      }
    }
  }

  if ($filterSetSQL != null)
  {
    if ($whereSQL == '')
      $whereSQL = "\nWHERE (";
    else
      $whereSQL .= "\nAND (";

    $whereSQL .= $filterSetSQL['sql'] . ")";
  }

  if ($hideDepot)
  {
    if ($whereSQL == '')
      $whereSQL .= "\nWHERE $pubColumn[inDepot] = 0";
    else
      $whereSQL .= "\nAND NOT $pubColumn[inDepot]";
  }

  if ($getOwners)
  {
    if ($whereSQL == '')
      $whereSQL .= "\nWHERE $pubColumn[creatorID] = " . pnUserGetVar('uid');
    else
      $whereSQL .= "\nAND $pubColumn[creatorID] = " . pnUserGetVar('uid');
  }

  $sql .= $whereSQL;

  if ($showDeleted  &&  !$countOnly)
    $sql .= "\nGROUP BY $pubTable.$pubColumn[pid]";

    // Add ordering

  if (count($orderBy) > 0  &&  !$countOnly)
  {
    $first = true;
    $sql .= "\nORDER BY ";
    foreach ($orderBy as $field)
    {
      if (!$first)
        $sql .= ", ";
      $first = false;

        // assume name is stored as SQL column name
      if ($field['name'] == $pubColumn['topic']) // Don't sort by topic ID - sort by name
        $sql .= $topicsColumn['topicname'];
      else
        $sql .= $field['name'];
      if ($field['desc'])
        $sql .= " DESC";
    }

    $sql .= ", $pubColumn[revision]";
  }
  else if (!$countOnly)
    $sql .= "\nORDER BY $pubColumn[revision]";

  //echo "<pre>$sql</pre>"; //exit(0);

  if ($noOfItems > 0  ||  $offsetItems > 0)
      // Select one extra to see if more is available
    $result = $dbconn->selectLimit($sql, $noOfItems+1, $offsetItems);
  else
    $result = $dbconn->execute($sql);

  if ($dbconn->errorNo() != 0)
    return pagesetterErrorApi(__FILE__, __LINE__, '"getPubList" failed: '
                                                  . $dbconn->errorMsg() . " while executing: $sql");

    // Get workflow information in order to show state titles
    // - that's a rather slow feature, so do it only if enabled
  if ($getApprovalState)
  {
    if (!pnModAPILoad('pagesetter', 'workflow'))
      return pagesetterErrorAPI(__FILE__, __LINE__, 'Failed to load Pagesetter workflow API');

    $workflowName = $pubInfo['publication']['workflow'];
    $workflow = pnModAPIFunc('pagesetter', 'workflow', 'load', array('workflow' => $workflowName) );
    if ($workflow === false)
      return false;
  }

  $pubList = array();
  $moreItems = false;
  $count = 0;

  if ($countOnly)
  {
    $count = $result->fields[0];
  }
  else
  {
    for ($cou=1; !$result->EOF; $result->MoveNext(), ++$cou)
    {
      $id  = $result->fields[0];
      $pid = $result->fields[1];

      if (pnSecAuthAction(0, 'pagesetter::', "$tid:$pid:", ACCESS_READ))
      {
        if ($noOfItems > 0  &&  $cou == $noOfItems+1)
        {
          $moreItems = true;
        }
        else
        {
          if ($getApprovalState)
          {
            if ($getTextual)
              $approval = pagesetterApprovalState2String($result->fields[4], $workflow);
            else
              $approval = $result->fields[4];
          }
          else
            $approval = 'UNKNOWN';

          $pubList[] = array('id'             => $id,
                             'pid'            => $pid,
                             'author'         => $result->fields[2],
                             'created'        => $result->fields[3],
                             'approvalState'  => $approval,
                             'topic'          => $result->fields[5],
                             'revision'       => $result->fields[6],
                             'online'         => $result->fields[7],
                             'title'          => $result->fields[8]);
        }
      }
    }
  }

  $result->Close();

  if ($countOnly)
  {
    return $count;
  }
  else
  {
    return array( 'publications' => &$pubList,
                  'more'         => $moreItems );
  }
}


/* Fetch one publication (by pid) and all versions of it that are not in the depot.
 * Return only id,title for a simple list */
function pagesetter_userapi_getPubSet($args)
{
  if (!isset($args['tid'])  ||  $args['tid']=='')
    return pagesetterErrorApi(__FILE__, __LINE__, "Missing argument 'tid' in 'pagesetter_userapi_getPubSet'");
  if (!isset($args['pid']))
    return pagesetterErrorApi(__FILE__, __LINE__, "Missing argument 'pid' in 'pagesetter_userapi_getPubSet'");

  $tid = (int)$args['tid'];
  $pid = (int)$args['pid'];

  if (!pnModAPILoad('pagesetter', 'admin'))
    return pagesetterErrorApi(__FILE__, __LINE__, 'Failed to load Pagesetter admin API');

    // Get publication type info

  $pubInfo =  pnModAPIFunc( 'pagesetter',
                            'admin',
                            'getPubTypeInfo',
                            array('tid' => $tid) );

  if ($pubInfo === false)
    return false;

  if (!pnSecAuthAction(0, 'pagesetter::', "$tid:$pid:", ACCESS_READ))
    return pagesetterErrorApi(__FILE__, __LINE__, _PGNOAUTH);

  list($dbconn) = pnDBGetConn();
  $pntable = pnDBGetTables();

  $pubTable        = pagesetterGetPubTableName($tid);
  $pubColumn       = $pntable['pagesetter_pubdata_column'];
  $pubHeaderTable  = $pntable['pagesetter_pubheader'];
  $pubHeaderColumn = $pntable['pagesetter_pubheader_column'];

  $titleFieldID = $pubInfo['publication']['titleFieldID'];
  $titleColumn = pagesetterGetPubColumnName($titleFieldID);

  $sql = "SELECT $pubColumn[id],
                 $pubColumn[author],
                 UNIX_TIMESTAMP($pubColumn[created]) as $pubColumn[created],
                 UNIX_TIMESTAMP($pubColumn[lastUpdated]) as $pubColumn[lastUpdated],
                 $pubColumn[approvalState],
                 $pubColumn[revision],
                 $pubColumn[online],
                 $titleColumn
          FROM   $pubTable
          WHERE      $pubColumn[pid] = $pid
                 AND $pubColumn[inDepot] = 0
          ORDER BY $pubColumn[revision] DESC";

  $result = $dbconn->execute($sql);

  if ($dbconn->errorNo() != 0)
    return pagesetterErrorApi(__FILE__, __LINE__, '"getPubSet" failed: '
                                                  . $dbconn->errorMsg() . " while executing: $sql");

  if ($result->EOF)
    return true;

  $revisions = array();

  for (; !$result->EOF; $result->MoveNext())
  {
    $revisions[] = array('tid'           => $tid,
                         'pid'           => $pid,
                         'id'            => $result->fields[0],
                         'author'        => $result->fields[1],
                         'created'       => $result->fields[2],
                         'lastUpdated'   => $result->fields[3],
                         'approvalState' => $result->fields[4],
                         'revision'      => $result->fields[5],
                         'online'        => $result->fields[6],
                         'title'         => $result->fields[7]);
  }

  $result->close();

  return $revisions;
}


// Get a subset of the data that pagesetter_userapi_getPub() returns.
// Include optional topic access control.
function pagesetter_userapi_getSimplePub($args)
{
  if (!isset($args['tid'])  ||  $args['tid']=='')
    return pagesetterErrorApi(__FILE__, __LINE__, "Missing argument 'tid' in 'pagesetter_userapi_getSimplePub'");
  if ((string)$args['pid'] == ''  &&  (string)$args['id'] == '')
    return pagesetterErrorApi(__FILE__, __LINE__, "Missing argument 'pid' in 'pagesetter_userapi_getSimplePub'");

  $tid = (int)$args['tid'];
  $pid = (int)$args['pid'];

  if (!pnModAPILoad('pagesetter', 'admin'))
    return pagesetterErrorApi(__FILE__, __LINE__, 'Failed to load Pagesetter admin API');

  $pubInfo = pnModAPIFunc( 'pagesetter',
                           'admin',
                           'getPubTypeInfo',
                           array('tid' => $tid) );
  if ($pubInfo === false)
    return false;

  list($dbconn) = pnDBGetConn();
  $pntable = pnDBGetTables();

  $pubTable        = pagesetterGetPubTableName($tid);
  $pubColumn       = &$pntable['pagesetter_pubdata_column'];
  $pubHeaderTable  = $pntable['pagesetter_pubheader'];
  $pubHeaderColumn = &$pntable['pagesetter_pubheader_column'];

  $titleFieldID = $pubInfo['publication']['titleFieldID'];
  $titleColumn = pagesetterGetPubColumnName($titleFieldID);

  if ($pubInfo['publication']['enableTopicAccess'])
  {
    $distinct = 'DISTINCT';

    $accessJoin = pagesetterTopicAccessJoin($pntable, $pubColumn['topic'], $pubInfo['publication']['filename']);
    if ($accessJoin === false)
      return false;
  }
  else
  {
    $distinct = '';
    $accessJoin = '';
  }

  $sql = "SELECT $distinct
                 $pubColumn[id],
                 $titleColumn,
                 $pubColumn[author],
                 UNIX_TIMESTAMP($pubColumn[created]),
                 UNIX_TIMESTAMP($pubColumn[lastUpdated]),
                 $pubColumn[approvalState],
                 $pubColumn[topic],
                 $pubColumn[revision],
                 $pubHeaderColumn[hitCount]
          FROM   $pubTable
          $accessJoin
          LEFT JOIN $pubHeaderTable ON $pubHeaderColumn[tid] = $tid
                                       AND $pubHeaderTable.$pubHeaderColumn[pid] = $pubTable.$pubColumn[pid]
          WHERE     $pubTable.$pubColumn[pid] = $pid
                AND $pubColumn[online]";

  //echo "<pre>$sql</pre>"; exit(0);
  $result = $dbconn->execute($sql);

  if ($dbconn->errorNo() != 0)
    return pagesetterErrorApi(__FILE__, __LINE__, '"getSimplePub" failed: '
                                                  . $dbconn->errorMsg() . " while executing: $sql");

  if ($result->EOF)
    return true;

  $core = array('id'             => $result->fields[0],
                'title'          => $result->fields[1],
                'author'         => $result->fields[2],
                'created'        => $result->fields[3],
                'lastUpdated'    => $result->fields[4],
                'approvalState'  => $result->fields[5],
                'topic'          => $result->fields[6],
                'revision'       => $result->fields[7],
                'hitCount'       => $result->fields[8]);

    // Same format as getPub() uses
  $pub = array('core' => $core);

  $result->Close();

  return $pub;
}


function pagesetter_userapi_getPub($args)
{
  if (!isset($args['tid'])  ||  $args['tid']=='')
    return pagesetterErrorApi(__FILE__, __LINE__, "Missing argument 'tid' in 'pagesetter_userapi_getPub'");
  if (!isset($args['pid'])  &&  !isset($args['id']))
    return pagesetterErrorApi(__FILE__, __LINE__, "Missing argument 'pid' or 'id' in 'pagesetter_userapi_getPub'");
  if (isset($args['pid'])  &&  isset($args['id']))
    return pagesetterErrorApi(__FILE__, __LINE__, "Cannot use arguments 'pid' and 'id' simultaneous in 'pagesetter_userapi_getPub'");

  $tid                = $args['tid'];
  $pid                = (isset($args['pid']) ? $args['pid'] : null);
  $id                 = (isset($args['id']) ? $args['id'] : null);
  $format             = isset($args['format']) ? $args['format'] : 'database';
  $getApprovalState   = isset($args['getApprovalState']) ? $args['getApprovalState'] : false;
  $useRestrictions    = isset($args['useRestrictions']) ? $args['useRestrictions'] : null;
  $notInDepot         = isset($args['notInDepot']) ? $args['notInDepot'] : false;
  $useTransformHooks  = isset($args['useTransformHooks']) ? $args['useTransformHooks'] : true;

  if (!pnModAPILoad('pagesetter', 'admin'))
    return pagesetterErrorApi(__FILE__, __LINE__, 'Failed to load Pagesetter admin API');

    // Get publication type info (also used for access control)

  $pubInfo =  pnModAPIFunc( 'pagesetter',
                            'admin',
                            'getPubTypeInfo',
                            array('tid' => $tid) );

  if ($pubInfo === false)
    return false;

  $getOwnOnly = false;

  if (!pnSecAuthAction(0, 'pagesetter::', "$tid:$pid:", pagesetterAccessEditor))
    if ($useRestrictions === false)
      $useRestrictions = true;

  if ($id == null)
  {
    if (!pnSecAuthAction(0, 'pagesetter::', "$tid:$pid:", ACCESS_READ))
      return pagesetterErrorApi(__FILE__, __LINE__, _PGNOAUTH);

    if ($useRestrictions === null)
      $useRestrictions = true;

    $referenceType = 'pid';
  }
  else
  {
    if (!pnSecAuthAction(0, 'pagesetter::', "$tid::", pagesetterAccessAuthor))
      return pagesetterErrorApi(__FILE__, __LINE__, _PGNOAUTH);

    if ($pubInfo['publication']['enableEditOwn'])
    {
      if (!pnSecAuthAction(0, 'pagesetter::', "$tid::", pagesetterAccessEditor))
        $getOwnOnly = true;
    }
    else
    {
      if (!pnSecAuthAction(0, 'pagesetter::', "$tid::", pagesetterAccessEditor))
        return pagesetterErrorApi(__FILE__, __LINE__, _PGNOAUTH);
    }

    if ($useRestrictions === null)
      $useRestrictions = false;

    $referenceType = 'id';
  }

  if (!pnModAPILoad('pagesetter', 'admin'))
    return pagesetterErrorApi(__FILE__, __LINE__, 'Failed to load Pagesetter admin API');

    // Make database query
  pnModDBInfoLoad('Topics');
  list($dbconn) = pnDBGetConn();
  $pntable = pnDBGetTables();

  $pubTable        = pagesetterGetPubTableName($tid);
  $pubColumn       = $pntable['pagesetter_pubdata_column'];
  $pubHeaderTable  = $pntable['pagesetter_pubheader'];
  $pubHeaderColumn = $pntable['pagesetter_pubheader_column'];
  $listItemsTable  = $pntable['pagesetter_listitems'];
  $topicsTable     = $pntable['topics'];
  $topicsColumn    = $pntable['topics_column'];

  if (($p=strpos($topicsColumn['topicname'], '.')) > 0)
    $topicsColumn['topicname'] = substr($topicsColumn['topicname'],$p+1);
  if (($p=strpos($topicsColumn['topictext'], '.')) > 0)
    $topicsColumn['topictext'] = substr($topicsColumn['topictext'],$p+1);
  if (($p=strpos($topicsColumn['topicimage'], '.')) > 0)
    $topicsColumn['topicimage'] = substr($topicsColumn['topicimage'],$p+1);
  if (($p=strpos($topicsColumn['tid'], '.')) > 0)
    $topicsColumn['tid'] = substr($topicsColumn['tid'],$p+1);

  $fieldSQL = "";
  $joinSQL = "";
  foreach ($pubInfo['fields'] as $field)
  {
    $fieldID    = $field['id'];
    $columnName = pagesetterGetPubColumnName($fieldID);

    if (!is_numeric($field['type']))
    {
      require_once 'modules/pagesetter/guppy/guppy_plugin.php';
      $plugin = guppy_newPlugin($field['type']);
      $pluginFormat = $plugin->getSqlFormat();
      if ($pluginFormat != null)
      {
        $columnName = eval("return \"$pluginFormat\";") . "as $columnName";
      }
    }
    else if ($field['type'] >= pagesetterFieldTypeListoffset)
    {
        // Add extra fields for title, fullTitle, and value of list item
      $fieldSQL .=   ",\n{$columnName}_table.pg_title as {$columnName}_title,\n"
                   . "{$columnName}_table.pg_fullTitle as {$columnName}_fullTitle,\n"
                   . "{$columnName}_table.pg_description as {$columnName}_description,\n"
                   . "{$columnName}_table.pg_value as {$columnName}_value";

        // and add extra JOIN statement
      $joinSQL .=   "\nLEFT JOIN $listItemsTable as {$columnName}_table\n"
                  .   "       ON {$columnName}_table.pg_id = $columnName";
    }

    $fieldSQL .= ",\n" . $columnName;
  }

  if ($id != null)
    $pubKeySQL = "pubTable.$pubColumn[id] = " . pnVarPrepForStore($id);
  else
    $pubKeySQL = "pubTable.$pubColumn[pid] = " . pnVarPrepForStore($pid);

  if ($pubInfo['publication']['enableTopicAccess'])
  {
    $accessJoin = pagesetterTopicAccessJoin($pntable, $pubColumn['topic'], $pubInfo['publication']['filename']);
    if ($accessJoin === false)
      return false;
  }
  else
    $accessJoin = '';

  $sql = "SELECT pubTable.$pubColumn[id],
                 pubTable.$pubColumn[pid],
                 pubTable.$pubColumn[approvalState],
                 pubTable.$pubColumn[online],
                 pubTable.$pubColumn[revision],
                 pubTable.$pubColumn[topic],
                 top.$topicsColumn[topicname] as core_topic_name,
                 top.$topicsColumn[topictext] as core_topic_text,
                 top.$topicsColumn[topicimage] as core_topic_image,
                 pubTable.$pubColumn[showInMenu],
                 pubTable.$pubColumn[showInList],
                 pubTable.$pubColumn[author],
                 pubTable.$pubColumn[creatorID],
                 UNIX_TIMESTAMP(pubTable.$pubColumn[created]) as $pubColumn[created],
                 $pubHeaderTable.$pubHeaderColumn[hitCount] as hitCount,
                 UNIX_TIMESTAMP(pubTable.$pubColumn[lastUpdated]) as $pubColumn[lastUpdated],
                 UNIX_TIMESTAMP(pubTable.$pubColumn[publishDate]) as $pubColumn[publishDate],
                 UNIX_TIMESTAMP(pubTable.$pubColumn[expireDate]) as $pubColumn[expireDate],
                 pubTable.$pubColumn[language]
                 $fieldSQL
          FROM   $pubTable as pubTable
          $accessJoin
          $joinSQL
          LEFT JOIN $topicsTable top ON top.$topicsColumn[tid] = pubTable.$pubColumn[topic]
          LEFT JOIN $pubHeaderTable ON pubTable.$pubColumn[pid] = $pubHeaderTable.$pubHeaderColumn[pid] AND
                                       $pubHeaderColumn[tid] = " . (int)$tid . "
          WHERE  $pubKeySQL";

  if ($useRestrictions)
  {
    $sql .=   "\nAND ($pubColumn[publishDate] <= NOW() || $pubColumn[publishDate] IS NULL)"
            . "AND  (NOW() < $pubColumn[expireDate] || $pubColumn[expireDate] IS NULL)"
            . "\nAND $pubColumn[online]"
            . "\nAND NOT $pubColumn[inDepot]";
  }
  else if ($notInDepot)
  {
    $sql .= "\nAND NOT $pubColumn[inDepot] AND $pubColumn[online]";
  }

  if ($getOwnOnly)
  {
    $sql .= "\nAND $pubColumn[creatorID] = " . pnUserGetVar('uid');
  }

    // Get title column name
  $titleFieldId = $pubInfo['publication']['titleFieldID'];
  $titleFieldIndex = $pubInfo['fieldIdIndex'][$titleFieldId];
  $titleColumn = pagesetterGetPubColumnName($titleFieldId);
  if ($pubInfo['fields'][$titleFieldIndex]['type'] >= pagesetterFieldTypeListoffset)
  {
    $titleColumn .= "_fullTitle";
  }

  global $ADODB_FETCH_MODE;
  $oldMode = $ADODB_FETCH_MODE;
  $dbconn->SetFetchMode(ADODB_FETCH_ASSOC);
  $result = $dbconn->execute($sql);
  $dbconn->SetFetchMode($oldMode);

  if ($dbconn->errorNo() != 0)
    return pagesetterErrorApi(__FILE__, __LINE__, '"getPub" failed: '
                                                  . $dbconn->errorMsg() . " while executing: $sql");

  if ($result->EOF)
    return true;


  if ($format == 'user')
    $topic = array( 'id'    => intval($result->fields[$pubColumn['topic']]),
                    'name'  => $result->fields['core_topic_name'],
                    'text'  => $result->fields['core_topic_text'],
                    'image' => $result->fields['core_topic_image'] );
  else
    $topic = intval($result->fields[$pubColumn['topic']]);

  if ($getApprovalState)
  {
    if (!pnModAPILoad('pagesetter', 'workflow'))
      return pagesetterErrorApi(__FILE__, __LINE__, 'Failed to load Pagesetter workflow API');

    $workflowName = $pubInfo['publication']['workflow'];
    $workflow = pnModAPIFunc('pagesetter', 'workflow', 'load', array('workflow' => $workflowName) );
    if ($workflow === false)
      return false;

    $approvalState = $result->fields[$pubColumn['approvalState']];
    $approvalTitle = pagesetterApprovalState2String($approvalState, $workflow);
  }
  else
  {
    $approvalState = 'UNKNOWN';
    $approvalTitle = 'UNKNOWN';
  }

  $pubCore = array( 'id' => $result->fields[$pubColumn['id']],
                    'pid' => $result->fields[$pubColumn['pid']],
                    'tid' => $tid,
                    'approvalState' => $approvalState,
                    'approvalTitle' => $approvalTitle,
                    'online' => intval($result->fields[$pubColumn['online']]),
                    'revision' => intval($result->fields[$pubColumn['revision']]),
                    'topic' => $topic,
                    'showInMenu' => intval($result->fields[$pubColumn['showInMenu']]),
                    'showInList' => intval($result->fields[$pubColumn['showInList']]),
                    'author' => $result->fields[$pubColumn['author']],
                    'creatorID' => $result->fields[$pubColumn['creatorID']],
                    'creator' => pnUserGetVar('uname',$result->fields[$pubColumn['creatorID']]),
                    'created' => $result->fields[$pubColumn['created']],
                    'hitCount' => intval($result->fields['hitCount']),
                    'lastUpdated' => $result->fields[$pubColumn['lastUpdated']],
                    'publishDate' => $result->fields[$pubColumn['publishDate']],
                    'expireDate' => $result->fields[$pubColumn['expireDate']],
                    'language' => $result->fields[$pubColumn['language']],
                    'title' => $result->fields[$titleColumn]  );


  $pid = $pubCore['pid'];
  $id = $pubCore['id'];

  if ($format != 'user')
  {
      // Convert all core field names "X" to "coreX"
    pagesetterFlattenPubData($pubCore);
  }

  if ($format == 'user')
    $pub = array('core' => $pubCore);
  else
    $pub = &$pubCore;

  foreach ($pubInfo['fields'] as $field)
  {
    $fieldID    = $field['id'];
    $columnName = pagesetterGetPubColumnName($fieldID);
    $fieldName = $field['name'];

    if ($fieldID == $pubInfo['publication']['pageableFieldID']  &&  $format == 'user')
    {
        // Split multi-page field by <hr class="pagebreak"/> tag
      $pages = pagesetterSplitPages($result->fields[$columnName]);
      $pub[$fieldName] = $pages;

        // Add page count to core
      $pub['core']['pageCount'] = count($pages);
    }
    else if ($field['type'] == pagesetterFieldTypeImageUpload  ||  $field['type'] == pagesetterFieldTypeUpload)
    {
      if ($result->fields[$columnName] != null)
      {
        $fileInfo = explode('|', $result->fields[$columnName]);

        $imgArgs = array('tid' => $tid,
                         'fid' => $fieldName);

        if ($referenceType == 'id')
          $imgArgs['id'] = $id;
        else
          $imgArgs['pid'] = $pid;

        $url = pnModUrl('pagesetter','file','get', $imgArgs);

        $downloadArgs = $imgArgs;
        $downloadArgs['download'] = 1;
        $downloadUrl = pnModUrl('pagesetter','file','get', $downloadArgs);

        $pub[$fieldName] = array('type'         => $fileInfo[0],
                                 'size'         => $fileInfo[1],
                                 'name'         => $fileInfo[3],
                                 'url'          => $url,
                                 'downloadUrl'  => $downloadUrl);

        if ($field['type'] == pagesetterFieldTypeImageUpload)
        {
          $imgArgs['tmb'] = 1;
          $thumbnailUrl = pnModUrl('pagesetter','file','get', $imgArgs);
          $pub[$fieldName]['thumbnailUrl'] = $thumbnailUrl;
        }

        if ($format == 'database')
          $pub[$fieldName]['tmpname'] = $fileInfo[2];
      }
      else
        $pub[$fieldName] = null;
    }
    else if ($field['type'] >= pagesetterFieldTypeListoffset)
    {
        // Add various list properties to list field
      if ($format == 'user')
        $pub[$fieldName] = array( 'id'          => intval($result->fields[$columnName]),
                                  'value'       => $result->fields[$columnName . '_value'],
                                  'title'       => $result->fields[$columnName . '_title'],
                                  'description' => $result->fields[$columnName . '_description'],
                                  'fullTitle'   => $result->fields[$columnName . '_fullTitle'] );
      else
        $pub[$fieldName] = intval($result->fields[$columnName]);
    }
    else
      $pub[$fieldName] = $result->fields[$columnName];
  }

  $result->Close();

    // Add template variable for "print this" and such like

  $printThisURL = pnModUrl('pagesetter','user','printpub',
                           array('tid'    => $tid,
                                 'pid'    => $pid));
  $printThisURL = htmlspecialchars($printThisURL);
  $printThisHTML = "<a href=\"$printThisURL\">" . _PGPRINTTHIS . "</a>";
  $pub['core']['printThisURL'] = $printThisURL;
  $pub['core']['printThis'] = $printThisHTML;

  $sendThisURL = pnModUrl('pagesetter','user','sendpub',
                          array('tid'    => $tid,
                                'pid'    => $pid));
  $sendThisURL = htmlspecialchars($sendThisURL);
  $sendThisHTML = "<a href=\"$sendThisURL\">" . _PGSENDTHIS . "</a>";
  $pub['core']['sendThisURL'] = $sendThisURL;
  $pub['core']['sendThis'] = $sendThisHTML;

  $fullURL = pnModUrl('pagesetter','user','viewpub',
                      array('tid'    => $tid,
                            'pid'    => $pid));
  $fullURL = htmlspecialchars($fullURL);
  $pub['core']['fullURL'] = $fullURL;

  // Check access to "edit this"
  // "edit this" insertion depending on permissions
  $editInfoHTML = '';
  $editThisHTML = '';
  if (pnSecAuthAction(0, 'pagesetter::', "$tid:$pid:", pagesetterAccessAuthor))
  {
    if (   $pubInfo['publication']['enableEditOwn'] == 0
        || $pubInfo['publication']['enableEditOwn'] == 1 && $pub['core']['creatorID'] == pnUserGetVar('uid')
        || pnSecAuthAction(0, 'pagesetter::', "$tid:$pid:", ACCESS_ADMIN))
    {
      $editInfoHTML = pagesetterGenerateEditInfoHTML($pub,$tid,$pid,$id);
      $editThisHTML = pagesetterGenerateEditThisHTML($tid,$pid,$id);
    }
  }

  $pub['core']['editThis'] = $editThisHTML;
  $pub['core']['editInfo'] = $editInfoHTML;

  //echo "<pre>"; print_r($pub); echo "</pre>\n";
  return $pub;
}


function pagesetterTopicAccessJoin(&$pntable, $topicColumn, $category)
{
  if (!pnModAPILoad('topicaccess', 'user'))
    return pagesetterErrorApi(__FILE__, __LINE__, 'Failed to load Topic Access user API');

  $sql = pnModAPIFunc('topicaccess','user','accessJoin',
                      array('topicColumn' => $topicColumn,
                            'module'    => 'pagesetter',
                            'category'  => $category,
                            'access'    => 'read'));
  if ($sql === false)
    return pagesetterErrorApi(__FILE__, __LINE__, 'Failed to fetch topic access SQL join');

  return $sql;
}


function pagesetter_userapi_getPubFormatted($args)
{
  if (!isset($args['tid'])  ||  $args['tid']=='')
    return pagesetterErrorApi(__FILE__, __LINE__, "Missing argument 'tid' in 'pagesetter_userapi_getPubFormatted'");
  if (   (!array_key_exists('pid',$args) || (string)$args['pid'] == '')
      && (!array_key_exists('id',$args) || (string)$args['id'] == ''))
    return pagesetterErrorApi(__FILE__, __LINE__, "Missing argument 'pid' or 'id' in 'pagesetter_userapi_getPubFormatted'");

  $tid                = $args['tid'];
  $pid                = (isset($args['pid']) ? $args['pid'] : null);
  $id                 = (isset($args['id']) ? $args['id'] : null);
  $format             = (isset($args['format']) ? $args['format'] : 'full');
  $coreExtra          = (isset($args['coreExtra']) ? $args['coreExtra'] : array());
  $updateHitCount     = (isset($args['updateHitCount']) ? $args['updateHitCount'] : true);
  $template           = (isset($args['template']) ? $args['template'] : null);
  $useRestrictions    = (isset($args['useRestrictions']) ? $args['useRestrictions'] : null);
  $page               = (isset($coreExtra['page']) ? $coreExtra['page'] : 0);
  $disablecCache      = (isset($args['disablecCache']) ? $args['disablecCache'] : false);
  $useTransformHooks  = isset($args['useTransformHooks']) ? $args['useTransformHooks'] : true;

    // Access check depends on how the publication is viewed - see later

    // Check for cached version

  $smarty = new pnRender('pagesetter');

    // Ignore caching when fetching specific version(id)
  $useCache = empty($id)  &&  !$disablecCache;

    // Some of the extra core elements influence caching!
  $cacheID = ($useCache ? pagesetterGetPublicationUniqueID($tid, $pid, pnUserGetLang(), $page) : null);
  if ($template === null)
  {
    $templateFile = pagesetterSmartyGetTemplateFilename($smarty, $tid, $format, $expectedName);
  }
  else
  {
    $templateFile = $template;
    $expectedName = $templateFile;
  }

  // Disable caching if required. Otherwise depend on pnRender's own cache settings
  if (!$useCache)
    $smarty->caching = false;

  if (!$useCache || !$smarty->is_cached($templateFile, $cacheID) || true)
  {
      // No cache - generate page
      // Permission control in API getPub()

    if (!pnModAPILoad('pagesetter', 'admin'))
      return pagesetterErrorApi(__FILE__, __LINE__, 'Failed to load Pagesetter admin API');


      // Get publication data and type info

    $pub = pnModAPIFunc( 'pagesetter', 'user', 'getPub',
                         array('tid'    => $tid,
                               'pid'    => $pid,
                               'id'     => $id,
                               'format' => 'user',
                               'useRestrictions'    => $useRestrictions,
                               'notInDepot'         => !$useRestrictions,
                               'useTransformHooks'  => $useTransformHooks) );
    if ($pub === false)
      return false;
    if ($pub === true)
      return pagesetterErrorApi(__FILE__, __LINE__, _PGUNKNOWNPUB);

    $pubInfo =  pnModAPIFunc( 'pagesetter', 'admin', 'getPubTypeInfo',
                              array('tid' => $tid) );

      // May perhaps not be specified on URL
    $pid = $pub['core']['pid'];

    if ($pubInfo === false)
      return false;

    // Add additional core values from caller
    $pub['core'] += $coreExtra;

      // Escape necessary values
    escapePublicationForHTML($pubInfo, $pub, $useTransformHooks, pagesetterGetPublicationUniqueID($tid,$pid));

      // Create page from data and type info

    $smarty->assign($pub);

    if (!$smarty->template_exists($templateFile))
      return _PGSORRYNOTEMPLATE . ": $expectedName<p>";

    $hitCount = $pub['core']['hitCount'];

      // Ensure cache expires when the publication expires
    if (isset($pub['core']['expireDate']))
    {
      $expireTime = $pub['core']['expireDate'];
      $smarty->cache_lifetime =  $expireTime - time();
      //echo "LT: " . $smarty->cache_lifetime . ". ";
    }
  }
  else
  {
      // We must get the pid of the id to perform access control
    if ((string)$pid == '')
      return pagesetterErrorApi(__FILE__, __LINE__, "Unexpected empty 'pid' in getPubFormatted");


      // For the cached version we do our own permission control - in this case $id doesn't matter
    if (!pnSecAuthAction(0, 'pagesetter::', "$tid:$pid:", ACCESS_READ))
      return pagesetterErrorApi(__FILE__, __LINE__, _PGUNKNOWNPUB);

      // Fetch hitcount and more - with optional topic access
    $pub = pnModAPIFunc( 'pagesetter',
                         'user',
                         'getSimplePub',
                         array('tid' => $tid,
                               'pid' => $pid) );
    if ($pub === false)
      return false;
    if ($pub === true)
      return pagesetterErrorApi(__FILE__, __LINE__, _PGUNKNOWNPUB);

    $hitCount = $pub['core']['hitCount'];
  }
  //echo "Cache fetch: $templateFile,$cacheID<br>\n";

  $pubFormatted = $smarty->fetch($templateFile, $cacheID);

  if ($updateHitCount)
    if (!pnModAPIFunc( 'pagesetter', 'user', 'incrementHitCount', array('tid' => $tid, 'pid' => $pid)))
      return false;

  return $pubFormatted;
}


function pagesetter_userapi_getPubArrayFormatted($args)
{
  if (!isset($args['tid'])  ||  $args['tid']=='')
    return pagesetterErrorApi(__FILE__, __LINE__, "Missing argument 'tid' in 'pagesetter_userapi_getPubArrayFormatted'");

  $tid                = $args['tid'];
  $pubList            = $args['pubList'];
  $format             = (isset($args['format']) ? $args['format'] : 'list-single');
  $coreExtra          = (isset($args['coreExtra']) ? $args['coreExtra'] : array());
  $updateHitCount     = (isset($args['updateHitCount']) ? $args['updateHitCount'] : false);
  $getApprovalState   = (isset($args['getApprovalState']) ? $args['getApprovalState'] : false);
  $useTransformHooks  = isset($args['useTransformHooks']) ? $args['useTransformHooks'] : true;

  if (!pnModAPILoad('pagesetter', 'admin'))
    return pagesetterErrorApi(__FILE__, __LINE__, 'Failed to load Pagesetter admin API');

  $smarty = new pnRender('pagesetter');
  $smarty->caching = false;
  $templateFile = pagesetterSmartyGetTemplateFilename($smarty, $tid, $format, $expectedName);

  // get pub type info
  $pubTypeInfo =  pnModAPIFunc( 'pagesetter', 'admin', 'getPubTypeInfo',
                                  array('tid' => $tid) );
  if ($pubTypeInfo === false)
    return false;

  // get detailed information on each publication
  foreach ($pubList['publications'] as $pub)
  {
    $pid = $pub['pid'];

    // get publication
    $pubInfo = pnModAPIFunc('pagesetter',
                            'user',
                            'getPub',
                            array('tid'              => $tid,
                                  'pid'              => $pid,
                                  'format'           => 'user',
                                  'getApprovalState' => $getApprovalState) );

      // Escape necessary values
    escapePublicationForHTML($pubTypeInfo, $pubInfo, $useTransformHooks, pagesetterGetPublicationUniqueID($tid,$pid));

    // add print this, send this, and full URLs
    $printThisURL = pnModUrl('pagesetter','user','printpub',
                             array('tid'    => $tid,
                                   'pid'    => $pid));
    $printThisURL = htmlspecialchars($printThisURL);
    $printThisHTML = "<a href=\"$printThisURL\">" . _PGPRINTTHIS . "</a>";
    $pubInfo['core']['printThisURL'] = $printThisURL;
    $pubInfo['core']['printThis'] = $printThisHTML;

    $sendThisURL = pnModUrl('pagesetter','user','sendpub',
                            array('tid'    => $tid,
                                  'pid'    => $pid));
    $sendThisURL = htmlspecialchars($sendThisURL);
    $sendThisHTML = "<a href=\"$sendThisURL\">" . _PGSENDTHIS . "</a>";
    $pubInfo['core']['sendThisURL'] = $sendThisURL;
    $pubInfo['core']['sendThis'] = $sendThisHTML;

    $editThisURL = pnModUrl('pagesetter','user','pubedit',
                            array('tid'    => $tid,
                                  'id'     => $id,
                                  'action' => 'edit',
                                  'goback' => '1'));
    $editThisURL = htmlspecialchars($editThisURL);
    $editThisHTML = pagesetterGenerateEditThisHTML($tid,$pid,$id);
    $pubInfo['core']['editThisURL'] = $editThisURL;
    $pubInfo['core']['editThis'] = $editThisHTML;

    $fullURL = pnModUrl('pagesetter','user','viewpub',
                        array('tid'    => $tid,
                              'pid'    => $pid));
    $fullURL = htmlspecialchars($fullURL);
    $pubInfo['core']['fullURL'] = $fullURL;

    // Add additional core values from caller
    $pubInfo['core'] += $coreExtra;

    // if the publication has information, add it to the array of publications.
    if (!($pubInfo === false))
      $pubsArray[] = $pubInfo;

    // update hit counts if required
    if ($updateHitCount)
      if (!pnModAPIFunc( 'pagesetter', 'user', 'incrementHitCount', array('tid' => $tid, 'pid' => $pid)))
        return false;
  }

  //echo "<br>pubsArray = "; print_r($pubsArray);
  $smarty->assign('publications', $pubsArray);

  // Add core data
  $smarty->assign('core', $coreExtra);

  if (!$smarty->template_exists($templateFile))
    return _PGSORRYNOTEMPLATE . ": $expectedName<p>";

  $allFormatted = $smarty->fetch($templateFile);

  return $allFormatted;
}


function escapePublicationForHTML(&$pubInfo, &$pub, $useTransformHooks, $uniqueId)
{
  foreach ($pub as $key => $value)
  {
    if (is_string($value))
    {
      $pub[$key] = pnVarPrepHTMLDisplay($value);
      if ($useTransformHooks)
      {
        list($pub[$key]) = pnModCallHooks('item', 'transform', $uniqueId, array($pub[$key]));
      }
    }
    else if ($key != 'core'  &&  $pubInfo['fields'][ $pubInfo['fieldIndex'][$key] ]['isPageable'])
    {
      $pub[$key] = array_map('pnVarPrepHTMLDisplay', $value);
      $pub[$key] = pnModCallHooks('item', 'transform', $uniqueId, $pub[$key]);
    }
  }
        
  list($pub['core']['title']) = pnModCallHooks('item', 'transform', $uniqueId, array($pub['core']['title']));
}


function pagesetterGenerateEditInfoHTML(&$pub, $tid, $pid, $id)
{
  $html = '';

  $html .= "<script type=\"text/javascript\" src=\"modules/pagesetter/pnjavascript/editthis.js\"></script>\n";
  $html .= "<script type=\"text/javascript\" src=\"modules/pagesetter/guppy/psmenu.js\"></script>\n";
  $html .= "<a href=\"\" onclick=\"handleOnClickEditThis(this,event,$tid,$pid); return false\">" . _PGEDITTHIS . "</a>\n";

  if (!empty($id))
  {
    $editThisURL = pnModUrl('pagesetter','user','pubedit',
                            array('tid'    => $tid,
                                  'id'     => $id,
                                  'action' => 'edit',
                                  'goback' => '1'));
  }
  else
  {
    $editThisURL = pnModUrl('pagesetter','user','pubedit',
                            array('tid'    => $tid,
                                  'pid'    => $pid,
                                  'action' => 'edit',
                                  'goback' => '1'));
  }
  $editThisURL = htmlspecialchars($editThisURL);

  $newURL = pnModUrl('pagesetter', 'user', 'pubedit',
                     array('tid' => $tid));
  $newURL = htmlspecialchars($newURL);

  $updatedDate = strftime(_PGDATEFORMAT, $pub['core']['lastUpdated']);
  $createdDate = strftime(_PGDATEFORMAT, $pub['core']['created']);

  $html .= "<table style=\"position: absolute; visibility: hidden;\" class=\"pubInfoBox\" id=\"pubInfoBox$tid-$pid\" onmouseout=\"psmenu.onMouseOutDiv(this)\" onmouseover=\"psmenu.cancelDelayedCloseMenu()\">\n";

  $html .=   "<tr><td>" . _PGFTPUBTITLE . ":</td><td>" . pnVarPrepHTMLDisplay($pub['core']['title']) . "</td></tr>\n"
           . "<tr><td>" . _PGAUTHOR . ":</td><td>" . pnVarPrepHTMLDisplay($pub['core']['author']) . "</td></tr>\n"
           . "<tr><td>" . _PGREVISION . ":</td><td>{$pub['core']['revision']}</td></tr>\n"
           . "<tr><td>" . _PGFTUPDATEDDATE . ":</td><td>$updatedDate</td></tr>\n"
           . "<tr><td>" . _PGFTCREATEDDATE . ":</td><td>$createdDate</td></tr>\n"
           . "<tr><td colspan=\"2\">\n"
           . "<a href=\"$editThisURL\">" . _PGEDITTHIS . "</a>\n"
           . " | <a href=\"$newURL\">" . _PGMENUPUBNEW . "</a>\n"
           . "</td></tr>\n"
           . "</table>\n";

  return $html;
}


function pagesetterGenerateEditThisHTML($tid, $pid, $id)
{
  if (!empty($id))
  {
    $editThisURL = pnModUrl('pagesetter','user','pubedit',
                            array('tid'    => $tid,
                                  'id'     => $id,
                                  'action' => 'edit',
                                  'goback' => '1'));
  }
  else
  {
    $editThisURL = pnModUrl('pagesetter','user','pubedit',
                            array('tid'    => $tid,
                                  'pid'    => $pid,
                                  'action' => 'edit',
                                  'goback' => '1'));
  }
  $editThisURL = htmlspecialchars($editThisURL);

  $html = "<a href=\"$editThisURL\">" . _PGEDITTHIS . "</a>";

  return $html;
}


function pagesetter_userapi_getHitCount($args)
{
  if (!isset($args['tid'])  ||  $args['tid']=='')
    return pagesetterErrorApi(__FILE__, __LINE__, "Missing argument 'tid' in 'pagesetter_userapi_getHitCount'");
  if (!isset($args['pid'])  ||  $args['pid']=='')
    return pagesetterErrorApi(__FILE__, __LINE__, "Missing argument 'pid' in 'pagesetter_userapi_getHitCount'");

  $tid = $args['tid'];
  $pid = $args['pid'];

  // No reason to check permissions - nobody cares about the hitcount alone. So no need for the overhead.

  list($dbconn) = pnDBGetConn();
  $pntable = pnDBGetTables();

  $pubHeaderTable = $pntable['pagesetter_pubheader'];
  $pubHeaderColumn = $pntable['pagesetter_pubheader_column'];

  $sql = "SELECT $pubHeaderColumn[hitCount]
          FROM   $pubHeaderTable
          WHERE  $pubHeaderColumn[pid] = " . (int)$pid . " AND
                 $pubHeaderColumn[tid] = " . (int)$tid;

  //echo "<pre>$sql</pre>"; exit(0);
  $result = $dbconn->execute($sql);

  if ($dbconn->errorNo() != 0)
    return pagesetterErrorApi(__FILE__, __LINE__, '"getHitCount" failed: '
                                                  . $dbconn->errorMsg() . " while executing: $sql");
  $hitCount = $result->fields[0];
  $result->Close();

  return $hitCount;
}


function pagesetter_userapi_incrementHitCount($args)
{
  if (!isset($args['tid']))
    return pagesetterErrorApi(__FILE__, __LINE__, "Missing argument 'tid' in 'pagesetter_userapi_incrementHitCount'");
  if (!isset($args['pid']))
    return pagesetterErrorApi(__FILE__, __LINE__, "Missing argument 'pid' in 'pagesetter_userapi_incrementHitCount'");

  $tid = $args['tid'];
  $pid = $args['pid'];

    // Check access
  if (!pnSecAuthAction(0, 'pagesetter::', "$tid:$pid:", ACCESS_READ))
    return pagesetterErrorApi(__FILE__, __LINE__, _PGNOAUTH);

  list($dbconn) = pnDBGetConn();
  $pntable = pnDBGetTables();

  $pubHeaderTable = $pntable['pagesetter_pubheader'];
  $pubHeaderColumn = $pntable['pagesetter_pubheader_column'];

  $sql = "UPDATE $pubHeaderTable SET
            $pubHeaderColumn[hitCount] = $pubHeaderColumn[hitCount] + 1
          WHERE $pubHeaderColumn[pid] = " . (int)$pid . " AND
                $pubHeaderColumn[tid] = " . (int)$tid;

  //echo "<pre>$sql</pre>"; exit(0);
  $result = $dbconn->execute($sql);

  if ($dbconn->errorNo() != 0)
    return pagesetterErrorApi(__FILE__, __LINE__, '"incrementHitCount" failed: '
                                                  . $dbconn->errorMsg() . " while executing: $sql");

  return true;
}

function pagesetterFieldUsePluginFilter($fieldID,$fieldDef)
{
	require_once('modules/pagesetter/guppy/guppy.php');
	return guppy_usePluginFilter($fieldID,$fieldDef);
}

function pagesetterFieldGetPluginFilterSQL($fieldID,$fieldDef,$operator,$value, $tableName, &$tableColumns)
{
	require_once('modules/pagesetter/guppy/guppy.php');
	return guppy_getPluginFilterSQL ($fieldDef,$operator,$value, $tableName, $tableColumns);
}

function pagesetter_userapi_parseFilter($args)
{
  if (!isset($args['tid']))
    return pagesetterErrorApi(__FILE__, __LINE__, "Missing argument 'tid' in 'pagesetter_userapi_parseFilter'");
  if (!isset($args['filter']))
    return pagesetterErrorApi(__FILE__, __LINE__, "Missing argument 'filter' in 'pagesetter_userapi_ParseFilter'");

  $tid          = $args['tid'];
  $filterStrSet = $args['filter'];

  if (!pnModAPILoad('pagesetter', 'admin'))
    return pagesetterErrorApi(__FILE__, __LINE__, 'Failed to load Pagesetter admin API');

  $pubInfo =  pnModAPIFunc( 'pagesetter',
                            'admin',
                            'getPubTypeInfo',
                            array('tid' => $tid) );

  $fields        = $pubInfo['fields'];
  $fieldIndexMap = $pubInfo['fieldIndex'];

  $pntable        = pnDBGetTables();
  $pubTable       = pagesetterGetPubTableName($tid);
  $pubColumn      = $pntable['pagesetter_pubdata_column'];
  $listItemsTable = $pntable['pagesetter_listitems'];

    // Iterate through each of the filter strings (the disjunctions)

  $firstFilter = true;
  $sql = '';
  $join = '';
  $listTableCount = 1;
  $joinCategoryColumns = null;
  $joinCategoryIDstr = '';

  foreach ($filterStrSet as $filterStr)
  {
      // Split filter string into terms and iterate over those

    $terms = preg_split("/\s*,\s*/", $filterStr);

    $firstTerm = true;

    if (!$firstFilter)
      $sql .= ' OR ';
    $firstFilter = false;

    foreach ($terms as $termStr)
    {
        // Split term into operands

      $atoms = preg_split("/\s*(:|\^)\s*/", $termStr);

      if (count($atoms) >= 2)
      {
        $fieldName = $atoms[0];
        $operator  = $atoms[1];
        $value     = $atoms[2];

        $usePluginFilter = false;

          // Check field names and convert to column names

        if (substr($fieldName,0,5) == 'core.')
        {
          $columnName = $pubColumn[substr($fieldName,5)];
          if (!isset($columnName))
            return pagesetterErrorApi(__FILE__, __LINE__, "Unknown field name '$fieldName'");
          $columnName = $pubTable . "." . $columnName;
        }
        else
        {
          $fieldIndex = $fieldIndexMap[$fieldName];
          if (!isset($fieldIndex))
            return pagesetterErrorApi(__FILE__, __LINE__, "Unknown field name '$fieldName'");

          $fieldDef = &$fields[$fieldIndex];
          $fieldID = $fieldDef['id'];
          $usePluginFilter = pagesetterFieldUsePluginFilter ($fieldID,$fieldDef);
          $columnName = pagesetterGetPubColumnName($fieldID);
        }

        if (!$firstTerm)
          $sql .= ' AND ';
        $firstTerm = false;

        if ($usePluginFilter) {
        	  $pluginFilter = pagesetterFieldGetPluginFilterSQL($fieldID,$fieldDef,$operator,$value,$pubTable,$pubColumn);
        	  if ($pluginFilter === false)
        	  	return false;
        	  $join .= "\n$pluginFilter[join]";
        	  $sql .= "$pluginFilter[sql]";
        }
        else if ($operator == 'sub')
        {
          if ($value != 'top')
          {
            $joinCategoryColumns[$columnName] = true;

            $joinCategoryIDstr .= "\nLEFT JOIN $listItemsTable as listTable{$listTableCount}\n"
                    .   " ON listTable{$listTableCount}.pg_id = " . (int)$value;


            $sql .= "({$columnName}_table.pg_lval >= listTable{$listTableCount}.pg_lval AND {$columnName}_table.pg_rval <= listTable{$listTableCount}.pg_rval)";

            ++$listTableCount;
          }
          else
            $sql .=" 1=1";
        }
        else if ($operator == 'like')
        {
          $sql .= "$columnName LIKE '%" . pnVarPrepForStore($value) . "%'";
        }
        else
        {

          $sql .= $columnName;

          switch ($operator)
          {
            case 'eq':      $sql .= ' = '; break;
            case 'ne':      $sql .= ' != '; break;
            case 'lt':      $sql .= ' < '; break;
            case 'gt':      $sql .= ' > '; break;
            case 'le':      $sql .= ' <= '; break;
            case 'ge':      $sql .= ' >= '; break;
            case 'null':    $sql .= ' is null'; break;
            case 'notnull': $sql .= ' is not null'; break;

            default: return pagesetterErrorApi(__FILE__, __LINE__, "Unknown filter operator '$operator'.");
          }

          if ($value == '@now')
            $value = date('Y-m-d');

          if ($operator != 'null'  &&  $operator != 'notnull')
            $sql .= "'" . pnVarPrepForStore($value) . "'";
        }
      }
    }
  }

  // echo "<pre>JOIN:\n$join\nSQL:\n$sql</pre>\n"; exit(0);

  return array( 'sql'                 => $sql,
  				'join'				  => $join,
                'joinCategoryIDstr'   => $joinCategoryIDstr,
                'joinCategoryColumns' => $joinCategoryColumns );
}


function pagesetter_userapi_parseOrderBy($args)
{
  if (!isset($args['tid']))
    return pagesetterErrorApi(__FILE__, __LINE__, "Missing argument 'tid' in 'pagesetter_userapi_parseOrderBy'");
  if (!isset($args['orderByStr']))
    return pagesetterErrorApi(__FILE__, __LINE__, "Missing argument 'orderByStr' in 'pagesetter_userapi_ParseOrderBy'");

  $tid        = $args['tid'];
  $orderByStr = $args['orderByStr'];

  if (!pnModAPILoad('pagesetter', 'admin'))
    return pagesetterErrorApi(__FILE__, __LINE__, 'Failed to load Pagesetter admin API');

  $pubInfo =  pnModAPIFunc( 'pagesetter',
                            'admin',
                            'getPubTypeInfo',
                            array('tid' => $tid) );

  $fields        = $pubInfo['fields'];
  $fieldIndexMap = $pubInfo['fieldIndex'];

  $pntable         = pnDBGetTables();
  $pubTable       = pagesetterGetPubTableName($tid);
  $pubColumn       = $pntable['pagesetter_pubdata_column'];
  $pubHeaderTable  = $pntable['pagesetter_pubheader'];
  $pubHeaderColumn = $pntable['pagesetter_pubheader_column'];

  $orderBy = array();

    // Split orderBy statement by commas and iterate over those
  $terms = preg_split("/\s*,\s*/", $orderByStr);
  $first = true;

  foreach ($terms as $termStr)
  {
      // Split term into field and modifer
    $atoms = preg_split("/\s*:\s*/", $termStr);

    if (count($atoms) > 0)
    {
      $fieldName = $atoms[0];
      $desc      = false;

      if (count($atoms) > 1)
      {
        if ($atoms[1] == 'desc')
          $desc = true;
      }

      list($fldName,$fldProperty) = explode(".",$fieldName,2);

      $fieldIndex = $fieldIndexMap[$fldName];
      if (!isset($fieldIndex))
      {
        if ($fldName == 'core')
        {
          if ($fldProperty == 'hitCount')
          {
            $columnName = $pubHeaderTable . '.' . $pubHeaderColumn['hitCount'];
          }
          else
          {
            $columnName = $pubColumn[$fldProperty];
            if (!isset($columnName))
              return pagesetterErrorApi(__FILE__, __LINE__, "Unknown field name '$fieldName'.");
            $columnName = $pubTable . "." . $columnName;
          }
        }
        else
        {
          if (array_key_exists($fieldName,$pubColumn))
            return pagesetterErrorApi(__FILE__, __LINE__, "'$fieldName' is a core field name. Syntax must be 'core.$fieldName'");
          else
            return pagesetterErrorApi(__FILE__, __LINE__, "Unknown field name '$fieldName'.");
        }

        $fieldIndex = -1;
      }
      else
      {
        $fieldID = $fields[$fieldIndex]['id'];
        $columnName = pagesetterGetPubColumnName($fieldID);
        if (isset($fldProperty))
        {
          if (!in_array($fldProperty,array('title','fullTitle','value')))
          {
            return pagesetterErrorApi(__FILE__, __LINE__, "Syntax error in '$fieldName'. Unknown property '$fldProperty'");
          }
          else
            if ($fields[$fieldIndex]['type'] < pagesetterFieldTypeListoffset)
              return pagesetterErrorApi(__FILE__, __LINE__, "Syntax error in '$fieldName'. Property '$fldProperty' not allowed.");
        }
      }
      $orderBy[] = array( 'name'       => $columnName,
                          'property'   => $fldProperty,
                          'desc'       => $desc,
                          'fieldIndex' => $fieldIndex);
    }
  }

  return $orderBy;
}


function pagesetter_userapi_search(&$args)
{
  if (!isset($args['query']))
    return pagesetterErrorApi(__FILE__, __LINE__, "Missing argument 'query' in 'pagesetter_userapi_search'");
  if (!isset($args['callback']))
    return pagesetterErrorApi(__FILE__, __LINE__, "Missing argument 'callback' in 'pagesetter_userapi_search'");

  $query    = $args['query'];
  $match    = $args['match'];
  $pubTypes = $args['pubTypes'];
  $callback = &$args['callback'];

  if (!pnModAPILoad('pagesetter', 'admin'))
    return pagesetterErrorApi(__FILE__, __LINE__, 'Failed to load Pagesetter admin API');

  list($dbconn) = pnDBGetConn();
  $pntable      = pnDBGetTables();

  $words = array();
  $count = preg_match_all('/"[^"]+"|[^" ]+/', $query, $words);
  $words = $words[0];

  for ($i=0; $i<$count; ++$i)
    if ($words[$i][0] == '"')
      $words[$i] = substr($words[$i], 1, strlen($words[$i])-2);

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

  $callback->start();

    // Iterate through all publication types - if any search terms was supplied
  if ($count > 0)
  {
    foreach ($pubTypes as $pubType)
    {
      $tid = pnVarPrepForStore($pubType);

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

        $where = '';
        foreach ($searchableColumns as $column)
        {
          if ($where != '')
            $where .= " OR ";

          $where .= "(";

          $firstWord = true;
          foreach ($words as $word)
          {
            if (!$firstWord)
              $where .= ($match == 'AND' ? ' AND ' : ' OR ');
            $firstWord = false;

            $where .= "$column LIKE '%" . pnVarPrepForStore($word) . "%'";
          }

          $where .= ")";
        }

        $pubTable = pagesetterGetPubTableName($tid);
        $pubColumn = $pntable['pagesetter_pubdata_column'];

        if ($where != '')
          $where = "AND ($where)";

        $lang = pnVarPrepForStore( pnUserGetLang() );

        $sql = "SELECT
                  $pubColumn[pid],
                  $titleColumn
                FROM
                  $pubTable
                WHERE
                      ($pubColumn[publishDate] <= NOW() || $pubColumn[publishDate] IS NULL)
                  AND (NOW() < $pubColumn[expireDate] || $pubColumn[expireDate] IS NULL)
                  AND NOT $pubColumn[inDepot]
                  AND $pubColumn[online]
                  AND ($pubColumn[language] = '$lang' OR $pubColumn[language] = 'x_all')
                  $where
                ORDER BY $titleColumn";

        //echo "<pre>$sql</pre>\n"; exit(0);

        $result = $dbconn->execute($sql);

        if ($dbconn->errorNo() != 0) // FIXME, not correct
          return pagesetterErrorApi(__FILE__, __LINE__, '"search" failed: '
                                                        . $dbconn->errorMsg() . " while executing: $sql");

        for (; !$result->EOF; $result->MoveNext())
        {
          $pid   = $result->fields[0];
          $title = $result->fields[1];

          if (pnSecAuthAction(0, 'pagesetter::', "$tid:$pid:", ACCESS_READ))
          {
            $url = pnModUrl('pagesetter','user', 'viewpub',
                            array('tid'    => $tid,
                                  'pid'    => $pid));


            $callback->found($tid, $pid, $pubTitle, $title, $url);
          }
        }

        $result->Close();
      }
    }
  }

  $callback->stop();

  return true;
}


function pagesetter_userapi_getPubTableName($args)
{
  if (!isset($args['tid']))
    return pagesetterErrorAPI(__FILE__, __LINE__, "Missing argument 'tid' in 'pagesetter_userapi_getPubTableName'");

  $tid = $args['tid'];

  return pagesetterGetPubTableName($tid);
}


function pagesetter_userapi_errorAPIGet($args)
{
  return pagesetterErrorAPIGet();
}


?>
