<?php

function pagesetter_operation_moveOthersToDepot(&$publication, $core, $args)
{
  $ok = pnModAPIFunc( 'pagesetter',
                      'edit',
                      'moveToDepot',
                      array( 'tid'        => $core['tid'],
                             'pid'        => $core['pid'],
                             'id'         => $core['id'],
                             'moveOthers' => true) );

  return $ok ? pagesetterWFOperationOk : pagesetterWFOperationError;
}

?>
