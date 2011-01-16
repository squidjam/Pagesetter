<?php
function smarty_function_varFull($params, &$smarty)
{
    $extra = '';
    extract($params);

    if (empty($name)) {
        $smarty->trigger_error("var: missing 'name' parameter");
        return;
    }

    if ($pageable)
      return   "<!--[foreach from=$$name item=p]-->\n"
             . "<!--[\$p]-->\n"
             . "<!--[/foreach]-->\n";

    return "<!--[$$name]-->";
}

/* vim: set expandtab: */

?>
