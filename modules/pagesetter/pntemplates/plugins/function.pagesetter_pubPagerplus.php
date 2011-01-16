<?php
// $Id: function.pagesetter_pubPagerplus.php,v 1.12 2007/02/08 21:30:43 jornlind Exp $

require_once 'modules/pagesetter/pnuser.php'; 

function smarty_function_pagesetter_pubPagerplus($args, &$smarty)
{
    $core = &$smarty->get_template_vars('core');

    // Get stuff that exists in core
    $page      = isset($args['page']) ? intval($args['page']) : intval($core['page']);
    $baseURL   = isset($args['baseURL']) ? $args['baseURL'] : $core['baseURL'];

    //Build filter array and filter string
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
    
    // how many items do we have to show at all?
    if( !isset( $args['pubCount'] ) )
    {
        if( !pnModAPILoad('pagesetter', 'user' ) )
        {
            $smarty->trigger_error( "*** pubPagerplus: unable to load pagesetter userapi!!", E_ERROR );
            return false;
        }
        $pubCount = pnModAPIFunc('pagesetter', 'user', 'getPubList', 
                                 array('tid'        => $core['tid'], 
                                       'countOnly'  => true,
                                       'filterSet'  => $filterStrSet));
    }
    else
    {
        $pubCount = $args['pubCount'];
    }
    // lets see how many items we have to show per page
    if( !pnModAPILoad('pagesetter', 'admin' ) )
    {
        $smarty->trigger_error( "*** pubPagerplus: unable to load pagesetter adminapi!!", E_ERROR );
        return false;
    }
    $pinfo = pnModAPIFunc( 'pagesetter', 'admin', 'getPubTypeInfo', array( 'tid' => $core['tid'], 'noOfItems' => 999 ) );    
    $perpage = $pinfo['publication']['listCount'];

    // Get stuff for presentation
    $max           = isset($args['max']) ? $args['max'] : "99";
    $prev          = isset($args['prev']) ? $args['prev'] : "<";
    $first         = isset($args['first']) ? $args['first'] : "|<";
    $next          = isset($args['next']) ? $args['next'] : ">";
    $last          = isset($args['last']) ? $args['last'] : ">|";
    $separator     = isset($args['separator']) ? $args['separator'] : "&nbsp;";
    $perpage       = isset($args['pageSize']) ? $args['pageSize'] : $perpage;

    $html = "<p class=\"pagesetterPubPager\">";
    // how many pages will there be
    $pages = ceil($pubCount / $perpage);

    if( $pubCount > $perpage )
    {
        if ($page > 0)
        {
            $html .= "<a href=\"$baseURL&amp;page=1\">$first</a>$separator";
            $html .= "<a href=\"$baseURL&amp;page=$page\">$prev</a>$separator";
        }
        $firstPage = 1;
                $lastPage  = $pages;
                
                if ($lastPage > $max)
                {
                    $firstPage = max($page+1-floor($max/2), 1);
                    $lastPage  = min($firstPage+$max-1, $pages);
                    $firstPage = min($firstPage, $pages-$max+1);
                }
                    
        for( $n=$firstPage;$n<=$lastPage;$n++ )
        {
            if ($page == $n-1)
                $html .= "$separator$n";
            else
                $html .="$separator<a href=\"$baseURL&amp;page=$n\">$n</a>";
        }

        if( $page < $pages-1 )
        {
            $npage = $page+2;
            $npages = $pages+1;
            $html .= "$separator<a href=\"$baseURL&amp;page=$npage\">$next</a>";
            $html .= "$separator<a href=\"$baseURL&amp;page=$pages\">$last</a>";
        }
    }
    $html .= "</p>";
    return $html;
}

?>
