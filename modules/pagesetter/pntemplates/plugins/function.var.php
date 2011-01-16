<?php
function smarty_function_var($params, &$smarty)
{
    $extra = '';
    extract($params);

    if (empty($name)) {
        $smarty->trigger_error("var: missing 'name' parameter");
        return;
    }

    if (isset($noDollar)  &&  $noDollar)
    {
        return "<!--[$name]-->";
    }
    else
    {
        if (isset($pageable)  &&  $pageable)
          return "<!--[$$name" . "[\$core.page]]-->";

        return "<!--[$$name]-->";
    }
}

/* vim: set expandtab: */

?>
