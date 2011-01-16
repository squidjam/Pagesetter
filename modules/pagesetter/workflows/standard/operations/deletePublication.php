<?php

function pagesetter_operation_deletePublication($publication, $core, $args)
{
  $ok = pnModAPIFunc( 'pagesetter',
                      'edit',
                      'deletePub',
                      array( 'tid' => $core['tid'],
                             'pid' => $core['pid'],
                             'id'  => $core['id']) );

    // Inform Folder module of deleted publication
  if ($ok  &&  isset($core['folderId'])  &&  pnModAPILoad('folder', 'user'))
  {
    $ok = pnModApiFunc('folder', 'user', 'deleteItem',
                       array('itemId' => $core['folderId']));
    if ($ok === false)
      return pagesetterWFOperationError;
  }

  return $ok ? pagesetterWFOperationOk : pagesetterWFOperationError;
}

?>
