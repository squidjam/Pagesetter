<?php
function smarty_function_pagesetter_ezcommentsCount($args, &$smarty)
{
  $core = &$smarty->get_template_vars('core');

    // Get stuff that exists in core
  $tid = $core['tid'];
  $pid = $core['pid'];
  $uniqueID = pagesetterGetPublicationUniqueID($tid,$pid);

  if (!pnModAPILoad('EZComments', 'user'))
    return pagesetterErrorPage(__FILE__, __LINE__, 'Failed to load EZComments user API');

  $comments = pnModAPIFunc('EZComments',
                           'user',
                           'getall',
                           array('modname' => 'pagesetter',
                                 'objectid' => $uniqueID));  

  if (isset($args['assign']))
    $smarty->assign($args['assign'], count($comments));
  else
    return count($comments);
}

?>