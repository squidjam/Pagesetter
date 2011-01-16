<?php

// Thanks to Joerg Napp for some serious bugfixes in this plugin.

function smarty_function_pagesetter_listBrowser($args, &$smarty)
{
  //$smarty->caching = 0; // Nice for debugging ...

  if (!isset($args['field']))
    return "Missing 'field' argument in Smarty plugin 'pagesetter_listBrowser'";

  $field        = $args['field'];
  $listClass    = $args['listClass'];
  $level        = (empty($args['level']) ? 1000 : $args['level']);
  $topValue     = $args['topValue']; 
  $currentValue = (isset($args['currentValue']) ? $args['currentValue'] : pnVarCleanFromInput('cv'));
  $template     = $args['tpl'];

  if (isset($args['tid']))
  {
    $tid = $args['tid'];
  }
  else
  {
    $core = $smarty->get_template_vars('core');
    $tid = $core['tid'];
  }


  if (!pnModAPILoad('pagesetter', 'admin'))
    return pagesetterErrorPage(__FILE__, __LINE__, 'Failed to load Pagesetter admin API');

  $listID = pnModAPIFunc( 'pagesetter',
                          'admin',
                          'getListIDByFieldName',
                          array('tid'   => $tid,
                                'field' => $field) );

  if ($listID === false)
    return pagesetterErrorAPIGet();

  $listInfo = pnModAPIFunc( 'pagesetter',
                            'admin',
                            'getList',
                             array('lid' => $listID,
                                   'topListValueID' => $topValue) );

  if ($listInfo === false)
    return pagesetterErrorAPIGet();

  // build the parameters for the URL.
  // these are the same for every item.
  $url_parameters = array();
  $url_parameters['tid'] = $tid;
  if (!empty($template)) {
    $url_parameters['tpl'] = $template;
  }

  $items  = $listInfo['items'];
  $indent = $items[0]['indent'];

  $i = 0;
  $setup = array('currentValue' => $currentValue,
                 'listClass'    => $listClass,
                 'field'        => $field);
  $html = pagesetter_listBrowser_rec($items, $i, $indent, count($items), $url_parameters, $setup);

  return $html;
}



function pagesetter_listBrowser_rec(&$items, &$i, $indent, $size, &$url_parameters, &$setup)
{
  if (isset($setup['listClass'])  &&  $i == 0)
    $html = "<ul class=\"$setup[listClass]\">\n";
  else
    $html = "<ul>\n";


  while ($i < $size)
  {
    $item = $items[$i];

    if ($item['indent'] < $indent)
      break;

    $url_parameters['filter'] = "$setup[field]^sub^$item[id]";
    $url_parameters['cv']     = $item['id'] ;

    $url = pnModUrl('pagesetter', 'user', '', $url_parameters);

    if ($setup['currentValue'] == $item['id'])
      $html .= "<li><span class=\"current\"><a href=\"$url\">$item[title]</a></span>\n";
    else
      $html .= "<li$className><a href=\"$url\">$item[title]</a>\n";


    ++$i;

    if ($items[$i]['indent'] > $indent)
    {
      $html .= pagesetter_listBrowser_rec($items, $i, $indent+1, $size, $url_parameters, $setup);
    }

    $html .= "</li>\n";
  }

  $html .= "</ul>\n";

  return $html;
}


?>
