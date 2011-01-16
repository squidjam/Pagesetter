<?php

/**
 * Smarty function to create a templated list of publications.
 *
 * Parameters:
 *   - tid: Pagesetter type ID used to specify which kind of publications to show.
 *   - topic: PostNuke topic ID used to filter the lister for this specific topic.
 *   - pubcnt: number of publications to show. Defaults to 5.
 *   - orderby: string of comma separated fields names to order by (as in a Pagesetter URL)
 *   - filter: array of filter strings with the same syntax as used on a pagesetter URL. The array can be build
 *             using the pagesetter_createFilter plugin.
 *   - tpl: list-template name to render each selected publication. Defaults to "inlineList".
 *   - assign: name of a Smarty variable to place the output in.
 *
 * The list template is applied to each and every selected publication, but this plugin does <em>not</em> use a
 * header/footer template.
 */
function smarty_function_pagesetter_inlinePubList($args, &$smarty)
{
  $smarty->caching = 0; // No caching since we refer to a list that will get extended without clearing this cached version

  if (!isset($args['tid']))
    return "Missing 'tid' argument in Smarty plugin 'pagesetter_inlinePubList'";

  if (!isset($language))
    $language = pnUserGetLang();

  $tid          = $args['tid'];
  $topic        = $args['topic'];
  $noOfItems    = (empty($args['pubcnt']) ? 5 : $args['pubcnt']);
  $offsetPage  = (empty($args['offsetPage'])) ? 0 : $args['offsetPage'];
  $orderBy      = $args['orderby'];
  $filterStrSet = $args['filter'];
  $format       = (empty($args['tpl']) ? 'inlineList' : $args['tpl']);

  if (!pnModAPILoad('pagesetter', 'user'))
    return pagesetterErrorPage(__FILE__, __LINE__, 'Failed to load Pagesetter user API');

  $pubList =  pnModAPIFunc( 'pagesetter',
                            'user',
                            'getPubList',
                            array('tid'        => $tid,
                                  'topic'      => $topic,
                                  'noOfItems'  => $noOfItems,
                                  'offsetPage' => $offsetPage,
                                  'language'   => $language,
                                  'filterSet'  => $filterStrSet,
                                  'orderByStr' => $orderBy) );
  
  if ($pubList === false)
    return pagesetterErrorAPIGet();

  $output = '';

  foreach ($pubList['publications'] as $pub)
  {
    $pubFormatted = pnModAPIFunc( 'pagesetter',
                                  'user',
                                  'getPubFormatted',
                                  array('tid'            => $tid,
                                        'pid'            => $pub['pid'],
                                        'format'         => $format,
                                        'updateHitCount' => false,
                                        'coreExtra'      => array('format'    => $format,
                                                                  'page'      => $page)) );

      // Ignore non-existing publications (it may just have been removed from the cache)
    if (!($pubFormatted === false))
      $output .= $pubFormatted;
  }

  if (isset($args['assign']))
    $smarty->assign($args['assign'], $output);
  else
    return $output;
}
?>
