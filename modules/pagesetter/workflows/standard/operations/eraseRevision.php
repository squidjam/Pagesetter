<?php

  // Used to completely remove non-approved content
function pagesetter_operation_eraseRevision($publication, $core, $args)
{
  $ok = pnModAPIFunc( 'pagesetter',
                      'edit',
                      'eraseRevision',
                      array( 'tid' => $core['tid'],
                             'pid' => $core['pid'],
                             'id'  => $core['id']) );

    // Inform Folder module of deleted publication
  if ($ok  &&  isset($core['folderId'])  &&  pnModAPILoad('folder', 'user'))
  {
    $tid = $core['tid'];
    $pid = $core['pid'];

    if (!pnModAPILoad('pagesetter', 'user'))
      return pagesetterWFOperationError;

    // Check if more revisions exists - delete only from folder if none exists
    $pubSet = pnModApiFunc('pagesetter','user','getPubSet',
                           array('tid' => $tid,
                                 'pid' => $pid));
    if ($pubSet === false)
      pagesetterWFOperationError;

    if (count($pubSet) == 0 || $pubSet === true)
    {
      $ok = pnModApiFunc('folder', 'user', 'deleteItem',
                         array('itemId' => $core['folderId']));
      if ($ok === false)
        return pagesetterWFOperationError;
    }
  }

  return $ok ? pagesetterWFOperationOk : pagesetterWFOperationError;
}

?>
