<?php
function smarty_modifier_bool_format($string, $yes = 'yes', $no = 'no')
{
  if (intval($string) == 0)
    return $yes;
  else
    return $no;
}

?>
