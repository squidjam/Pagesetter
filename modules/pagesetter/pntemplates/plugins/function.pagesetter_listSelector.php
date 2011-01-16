<?php
function smarty_function_pagesetter_listSelector($args, &$smarty)
{
  if (!isset($args['field']))
    return "Missing 'field' argument in Smarty plugin 'pagesetter_listSelector'";

  $field = $args['field'];
  $name = isset($args['name']) ? $args['name'] : $field;

  if (!pnModAPILoad('pagesetter', 'admin'))
    return pagesetterErrorPage(__FILE__, __LINE__, 'Failed to load Pagesetter admin API');

  if (isset($args['tid']))
  {
    $tid = $args['tid'];
  }
  else
  {
    $core = $smarty->get_template_vars('core');
    $tid = $core['tid'];
  }

  $listID = pnModAPIFunc( 'pagesetter',
                            'admin',
                          'getListIDByFieldName',
                          array('tid'   => $tid,
                                'field' => $field) );

  if ($listID === false)
    return pagesetterErrorAPIGet();

  $listInfo =  pnModAPIFunc( 'pagesetter',
                             'admin',
                             'getList',
                              array('lid' => $listID) );

  if ($listInfo === false)
    return pagesetterErrorAPIGet();

  $items = $listInfo['items'];

  $html = "<select name=\"$name\">\n<option value=\"top\"></option>\n";
  foreach ($items as $item)
    $html .= "<option value=\"$item[id]\">$item[fullTitle]</option>\n";
  $html .= "</select>\n";

  return $html;
}

?>
