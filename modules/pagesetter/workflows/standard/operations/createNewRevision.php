<?php

function pagesetter_operation_createNewRevision(&$publication, &$core, &$args)
{
  if (!isset($args['NEXTSTATE']))
    return;

  $nextState = $args['NEXTSTATE'];
  $online    = (isset($args['ONLINE']) ? $args['ONLINE'] : 0);

  $tid = $core['tid'];
  $pid = $core['pid'];

  $publication['core_approvalState'] = $nextState;
  $publication['core_online']        = $online;

  $newInfo = pnModAPIFunc( 'pagesetter', 'edit', 'createNewRevision',
                           array( 'tid'             => $tid,
                                  'id'              => $core['id'],
                                  'copyCreatedDate' => true,
                                  'pubData'         => $publication) );

  if ($newInfo === false)
    return pagesetterWFOperationError;

  $ok = pnModAPIFunc('pagesetter', 'edit', 'updateOnlineStatus',
                     array('tid' => $tid,
                           'pid' => $pid,
                           'id'  => $newInfo['id'],
                           'online' => $online));
  if ($ok === false)
    return pagesetterWFOperationError;

  $core['id'] = $newInfo['id'];

    // Inform Folder module of new publication
  if ($ok  &&  $online  &&  isset($core['folderId'])  &&  pnModAPILoad('folder', 'user'))
  {
    $pubInfo = pnModAPIFunc( 'pagesetter', 'admin', 'getPubTypeInfo',
                             array('tid' => $tid) );
    if ($pubInfo === false)
      return pagesetterWFOperationError;
    $titleFieldId = $pubInfo['publication']['titleFieldID'];
    foreach ($pubInfo['fields'] as $field)
      if ($field['id'] == $titleFieldId)
        $title = $publication[$field['name']];

    $ok = pnModApiFunc('folder', 'user', 'updateItem',
                       array('itemId' => $core['folderId'],
                             'title'  => $title));
    if ($ok === false)
      return pagesetterWFOperationError;
  }

  return pagesetterWFOperationOk;
}

?>
