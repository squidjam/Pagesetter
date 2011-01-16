<?php
/** 
 *
 * Type: Function
 * Author: Frank Schummertz (frank.schummertz@landseer-stuttgart.de)
 *
 * allows the users t post a comment to a news item using EZComments
 *@param params['tid'] int 
 *@param params['pid'] int 
 *@return string processed smarty output
 */
function smarty_function_pagesetter_EZComments($params, &$smarty)
{
    extract($params);
    unset( $params );

    if( !$tid )
    {
        $smarty->trigger_error( "pagesetter_EZComments: missing parameter 'tid'" );
        return false;
    }
    if( !$pid )
    {
        $smarty->trigger_error( "pagesetter_EZComments: missing parameter 'pid'" );
        return false;
    }
    $uniqueID = pagesetterGetPublicationUniqueID($tid,$pid);
    
    if( !pnModLoad( 'EZComments', 'user' ) )
    {
        // silently return
        return false;
    }
    $url = pnModURL ( 'pagesetter', 'user', 'viewpub',
                      array( 'tid' => $tid,
                             'pid' => $pid ) );
    return pnModFunc( 'EZComments', 'user', 'view', array( 'objectid' => $uniqueID,
                                                           'extrainfo' => $url  ) );

}
?>