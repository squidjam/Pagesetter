<?php

function pagesetter_operation_createPublication(&$publication, &$core, &$args)
{
  $online = (isset($args['ONLINE']) ? $args['ONLINE'] : 0);

  $publication['core_online'] = $online;

  $result = pnModAPIFunc( 'pagesetter',
                          'edit',
                          'createPub',
                          array( 'tid'     => $core['tid'],
                                 'pubData' => $publication) );
  if ($result === false)
    return pagesetterWFOperationError;

  $id = $core['id'] = $result['id'];

    // Inform Folder module of new publication
    // - place either in selected folder when called from folder module
    //   or in default folder
  if (pnModAPILoad('folder', 'user'))
  {
    $tid = $result['tid'];
    $pid = $result['pid'];

    // Get complete publication info - not only what was edited.
    $publication = pnModAPIFunc( 'pagesetter', 'user', 'getPub',
                                 array('tid'    => $tid, 
                                       'id'     => $id,
                                       'format' => 'user') );
    if ($publiation === false)
      return pagesetterWFOperationError;

    $pubInfo = pnModAPIFunc( 'pagesetter', 'admin', 'getPubTypeInfo',
                             array('tid' => $tid) );
    if ($pubInfo === false)
      return pagesetterWFOperationError;

    $titleFieldId = $pubInfo['publication']['titleFieldID'];
    foreach ($pubInfo['fields'] as $field)
      if ($field['id'] == $titleFieldId)
        $title = $publication[$field['name']];

    if (isset($core['folderId']))
    {
      $folderId = $core['folderId'];
    }
    else
    {
      $folderId = $pubInfo['publication']['defaultFolder'];
      if ($pubInfo['publication']['defaultSubFolder'] != '')
      {
        $subFolder = $pubInfo['publication']['defaultSubFolder'];
        $subFolder = pagesetterExpandSubFolder($subFolder, $publication, $tid);

        $folderId = pnModApiFunc('folder', 'user', 'ensureFolder',
                                 array('parentId' => $folderId,
                                       'path'     => $subFolder,
                                       'topicId'  => $pubInfo['publication']['defaultFolderTopic']));
        if ($folderId === false)
          return pagesetterWFOperationError;
      }
    }

    if ($folderId != ''  &&  $folderId != -1)
    {
      $ok = pnModApiFunc('folder', 'user', 'addItem',
                         array('folderId' => $folderId,
                               'module'   => 'pagesetter',
                               'type'     => $pubInfo['publication']['filename'],
                               'title'    => $title,
                               'key'      => "$tid.$pid"));
      if ($ok === false)
      {
        $msg = pnModAPIFunc('folder', 'user', 'errorAPIGet');
        pagesetterErrorAPI(__FILE__, __LINE__, $msg);
        return pagesetterWFOperationError;
      }
    }
  }
  
  return $id !== false ? pagesetterWFOperationOk : pagesetterWFOperationError;
}

?>
