<?php
//Examples
//  without filter:   <!--[pagesetter_readpubCount tid=4 assign=pubCount]-->
//                    <!--[if $pubCount > 0]-->
//                      your conditional statements
//                    <!--[/if]-->
//
//  with filter:      <!--[pagesetter_createFilter filter="category:eq:10" assign=listFilter]-->
//                    <!--[pagesetter_readpubCount tid=4 filter=$listFilter assign=pubCount]-->
//                      see previous example
function smarty_function_pagesetter_readpubCount($args, &$smarty)
{
    if( !isset($args['tid']) )
    {
        $smarty->trigger_error( "*** readpubCount: missing parameter 'tid'", E_ERROR );
        return false;
    }
 
    pnModAPILoad('pagesetter', 'user' );
    //Filter added 2005-1-14 Claus Parkhoi
    $cou = pnModAPIFunc( 'pagesetter', 'user', 'getPubList', 
                         array( 'tid'       => $args['tid'], 
                                'filterSet' => $args['filter'],
                                'countOnly' => true ) );
    if (isset($args['assign']))
    {
      $smarty->assign($args['assign'],  $cou);
    }
    else
      return $cou;
}

?>
