<?php

function pagesetter_operation_updatePublication(&$publication, &$core, &$args)
{
  $ok = pnModAPIFunc( 'pagesetter',
                      'edit',
                      'updatePub',
                      array( 'tid'     => $core['tid'],
                             'id'      => $core['id'],
                             'pubData' => $publication) );

    // Inform Folder module of updated publication
  if ($ok  &&  isset($core['folderId'])  &&  pnModAPILoad('folder', 'user'))
  {
    $tid = $core['tid'];

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

  return $ok ? pagesetterWFOperationOk : pagesetterWFOperationError;
}

?>
