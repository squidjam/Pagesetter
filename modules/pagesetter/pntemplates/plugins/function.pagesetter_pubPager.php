<?php

require_once 'modules/pagesetter/pnuser.php'; 

// Generates a pager for browsing through a list of publications.
// Use "pagesetter_pager" if you want to browse through the pages in a multi-paged publication.
function smarty_function_pagesetter_pubPager($args, &$smarty)
{
  $core = &$smarty->get_template_vars('core');

    // Get stuff that exists in core
  $page      = isset($args['page']) ? intval($args['page']) : intval($core['page']);
  $baseURL   = isset($args['baseURL']) ? $args['baseURL'] : $core['baseURL'];

    //start filter patch 2005-01-19 Claus Parkhoi
    //Build filter string
    //this will get filter, filter1,...,filtern from the url
    //note: the filter string will use filters starting from filter1
    $filterStrSet = pagesetterGetFilters(array(), $dummyArgs);
    if(count($filterStrSet) != 0)
    {
      $temp = array();
      foreach( $filterStrSet as $key => $item )
      {
        $i = $key + 1;
        $temp[] = "filter$i=" . $item;
      }
      $filterStr = "&amp;" . implode("&amp;", $temp);
    } else $filterStr = "";
    $baseURL .= $filterStr;
    //end filter patch 2005-01-19
    
    // Get stuff for presentation
  $prev          = isset($args['prev']) ? $args['prev'] : _PGPREV;
  $next          = isset($args['next']) ? $args['next'] : _PGNEXT;
  $separator     = isset($args['separator']) ? $args['separator'] : ' ';

  $html = '';

  if ($page > 0)
    $html .= "<a href=\"$baseURL&amp;page=" . $page . "\">$prev</a>&nbsp;";

  if ($core['morePages'])
    $html .= "$separator<a href=\"$baseURL&amp;page=" . ($page+2) . "\">$next</a>";

  return $html;
}

?>
