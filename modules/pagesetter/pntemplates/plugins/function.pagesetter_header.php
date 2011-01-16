<?php

/** 
 *
 * Type: Function
 * Author: Jorn Lind-Nielsen
 *
 * Allows the user to place something in any header tags in page's header.
 * Works with both Xantia and non-Xantia themes.
 *@param params['header'] complete header data, including tags, to put in page header.
 *@return nothing
 */
function smarty_function_pagesetter_header($params, &$smarty)
{
  if (!array_key_exists('header', $params))
  {
    $smarty->trigger_error( "smarty_function_pagesetter_header: missing parameter 'header'" );
    return false;
  }

  global $additional_header;

  if (!is_array($additional_header))
    $additional_header = array();
  $additional_header[] = $params['header'];

  return "";
}

?>
