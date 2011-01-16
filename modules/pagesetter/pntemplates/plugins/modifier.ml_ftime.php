<?php
function smarty_modifier_ml_ftime($timestamp, $format)
{
  return ml_ftime($format, $timestamp);
}

?>
