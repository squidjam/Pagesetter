<?php

// Thanks to Joerg Napp for this plugin.

function smarty_function_pagesetter_CreateFilter($args, &$smarty)
{
    //$smarty->caching = 0; // Nice for debugging ...
     
    // pagesetter_userapi_getPubList
    if (!isset($args['filter']))
        return "Missing 'filter' argument in Smarty plugin 'pagesetter_CreateFilter'";

    if (!pnModAPILoad('pagesetter', 'admin'))
        return pagesetterErrorPage(__FILE__, __LINE__, 'Failed to load Pagesetter admin API');

    $filter = array(); 

    // Get unnumbered filter string
    $filterStr = $args['filter'];

    if (isset($filterStr))
        $filter[] = pagesetterReplaceFilterVariable($filterStr); 

    // Get filter1 ... filterN
    $i = 1;
    while (true) {
        $filterURLName = "filter$i";
        $filterStr = $args[$filterURLName];

        if (empty($filterStr))
            break;

        $filter[] = pagesetterReplaceFilterVariable($filterStr);
        $i++;
    } 

    if (isset($args['assign'])) {
        $smarty->assign($args['assign'], $filter);
    } else {
        return $filter;        
    }      
} 

?>
