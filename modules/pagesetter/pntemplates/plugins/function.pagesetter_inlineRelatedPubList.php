<?php

/**
 * Smarty function to create a templated list of publications, related to other publications.
 *
 * Parameters:
 *   - tid: Pagesetter type ID used to specify which kind of publications to show.
 *   - topic: PostNuke topic ID used to filter the lister for this specific topic.
 *   - pubcnt: number of publications to show. Defaults to 5.
 *   - orderby: string of comma separated fields names to order by (as in a Pagesetter URL)
 *   - filter: array of filter strings with the same syntax as used on a pagesetter URL. The array can be build
 *             using the pagesetter_createFilter plugin.
 *   - relatedStr: related publications in user notification (tid1:pid1[,tid2:pid2[,...]])
 *   - related: related publications as an array of arrays. If both omitted, list will be related to actual publication
 *   - tpl: list-template name to render each selected publication. Defaults to "inlineList".
 *   - assign: name of a Smarty variable to place the output in.
 *
 * The list template is applied to each and every selected publication, but this plugin does <em>not</em> use a
 * header/footer template.
 */
function smarty_function_pagesetter_inlineRelatedPubList($args, &$smarty)
{
  $smarty->caching = 0; // No caching since we refer to a list that will get extended without clearing this cached version

  if (!isset($args['tid']))
    return "Missing 'tid' argument in Smarty plugin 'pagesetter_inlineRelatedPubList'";

  if (!isset($language))
    $language = pnUserGetLang();


  $tid = $args['tid'];
  $topic        = $args['topic'];
  $noOfItems    = (empty($args['pubcnt']) ? 5 : $args['pubcnt']);
  $offsetPage  = (empty($args['offsetPage'])) ? 0 : $args['offsetPage'];
  $orderBy      = $args['orderby'];
  $filterStrSet = $args['filter'];
  $relatedStr	= $args['relatedStr'];
  $related		= $args['related'];
  $format       = (empty($args['tpl']) ? 'inlineList' : $args['tpl']);

  if (empty($relatedStr) && !is_array($related)) {
	$core = $smarty->get_template_vars('core');
	$related = array(array($core['tid'],$core['pid']));
  }
  
  //get the additional data concerning the related publications from Module Variable
  
  
  if (!pnModAPILoad('pagesetter', 'user'))
    return pagesetterErrorPage(__FILE__, __LINE__, 'Failed to load Pagesetter user API');
  if (!pnModAPILoad('pagesetter', 'relations'))
    return pagesetterErrorPage(__FILE__, __LINE__, 'Failed to load Pagesetter relations API');

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

  $relations = pnModAPIFunc('pagesetter','relations','getRelations', array ('tid' => $tid, 'relatedStr' => $relatedStr, 'related' => $related));  

  $output = '';

  foreach ($pubList['publications'] as $pub)
  { 
  	if (in_array( $pub[pid],$relations)) {
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
  }

  if (isset($args['assign']))
    $smarty->assign($args['assign'], $output);
  else
    return $output;
}
?>
