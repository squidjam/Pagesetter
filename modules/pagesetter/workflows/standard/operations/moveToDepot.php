<?php

function pagesetter_operation_moveToDepot(&$publication, $core, $args)
{
  $ok = pnModAPIFunc( 'pagesetter',
                      'edit',
                      'moveToDepot',
                      array( 'tid' => $core['tid'],
                             'pid' => $core['pid'],
                             'id'  => $core['id'] ) );

  return $ok ? pagesetterWFOperationOk : pagesetterWFOperationError;
}

?>
