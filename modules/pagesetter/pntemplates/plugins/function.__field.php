<?php
function smarty_function___field($params, &$smarty)
{
  if (empty($params['field']))
  {
    $smarty->trigger_error("var: missing 'name' parameter");
    return;
  }

  $field = $params['field'];

  if ($field['isPageable'])
    return   "<!--[if \$core.pageCount > 1 ]-->\n"
           . "Page: <!--[pagesetter_pager]-->\n"
           . "<!--[/if]--><br/>\n"
           . "<!--[\$$field[name]" . "[\$core.page]]-->";

  switch ($field['type'])
  {
    case pagesetterFieldTypeString:
    case pagesetterFieldTypeText:
    case pagesetterFieldTypeHTML:
    case pagesetterFieldTypeBool:
    case pagesetterFieldTypeInt:
    case pagesetterFieldTypeReal:
    case pagesetterFieldTypeDate:
    case pagesetterFieldTypeTime:
      return "<!--[\$$field[name]]-->";
    break;

    case pagesetterFieldTypeImage:
      return "<img src=\"<!--[\$$field[name]]-->\">";
    break;

    case pagesetterFieldTypeImageUpload:
      return "<img src=\"<!--[\$$field[name].url]-->\">";
    break;

    case pagesetterFieldTypeUpload:
      return "<a href=\"<!--[\$$field[name].url]-->\">download</a>";
    break;

    case 'publication':
      $ptid = $field['typeData'];
      return "
<!--[pnmodapifunc modname=pagesetter func=getPub tid=$ptid pid=\$$field[name] assign=p]-->
<!--[\$p.title]-->";
    break;

    case 'relation':
      $typeData = explode(':',$field['typeData'],9);
      list($rtid,$rftid,$rtargetTid,$rtargetField,$roldTargetTid,$roldTargetField,$rstyle,$rpopup,$rfilter) = $typeData;
      $targetTypeInfo = pnModAPIFunc('pagesetter', 'admin', 'getPubTypeInfo',
                                     array('tid' => $rtargetTid) );
      //var_dump($targetTypeInfo); exit(0);
      $targetTypeName = $targetTypeInfo['publication']['filename'];
      $targetFieldIndex = $targetTypeInfo['fieldIdIndex'][$rtargetField];
      $targetField = $targetTypeInfo['fields'][$targetFieldIndex];
      $targetFieldName = $targetField ['name'];
      return "
YOU MUST CREATE A LIST TEMPLATE FOR THE TARGET PUB TYPE NAMED \"$targetTypeName-inlineList.html\"<br/>
<!--[nocache]-->
<!--[pagesetter_createFilter filter=\"$targetFieldName:rel:`\$core.pid`\" assign=f]-->
<!--[pagesetter_inlinePubList tid=$rtargetTid filter=\$f]-->
<!--[/nocache]-->";
    break;

    default:
      return "<!--[\$$field[name].title]-->";
    break;
  }
}

?>
