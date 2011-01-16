<?php

/**
 *
 * Type: Function
 * Author: Drak
 *
 * @return menu
 */
function smarty_function_pagesetter_adminmenu($params, &$smarty)
{
    guppy_open (array('specFile'    => 'modules/pagesetter/forms/adminToolbarSpec.xml',
                      'layoutFile'  => 'modules/pagesetter/forms/adminToolbarLayout.xml',
                      'data'        => '',
                      'actionURL'   => pnModUrl('pagesetter','admin',''),
                      'toolbarFile' => 'modules/pagesetter/forms/adminToolbar.xml'));
    return guppy_output();
}

?>
