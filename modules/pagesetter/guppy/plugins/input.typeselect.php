<?php

class GuppyInput_typeselect extends GuppyInput
{
  function render($guppy)
  {
    if ($this->readonly)
      $readonly = ' readonly="1"';
    else
      $readonly = '';

    $typeInfo = $this->value;
    
      // Hack: split value by '|'
    $pos = strpos($typeInfo, '|');
    $type = substr($typeInfo, 0, $pos);
    $typeData = substr($typeInfo, $pos+1);
    $selectedValue = $type;

    $id = $this->ID;
    $buttonId = $id . '_button';
    $hiddenId = $id . '_hidden';

    $name = $this->name;
    $hiddenName = $name . '_hidden';

    $html = "<input type=\"hidden\" name=\"$hiddenName\" id=\"$hiddenId\" value=\"$typeData\"/>
             <span style=\"white-space: nowrap\">
             <select name=\"$name\" id=\"$id\" onchange=\"handleOnChangeTypeSelect(this,'$buttonId')\">";

    $options = pagesetterFieldTypesGetOptionList();
//var_dump($this);
//var_dump($options);

    $buttonEnabled = ' disabled="1"';

    foreach ($options as $option)
    {
      $value = $option['value'];
      $selected = ($value === $selectedValue ? ' selected="1"' : '');
      $optionTitle = guppy_translate($option['title']);

      if ($selected != ''  &&  array_key_exists('usesExtra',$option) && $option['usesExtra'])
      {
        $buttonEnabled = '';
      }

      $html .= "<option value=\"$value\"$selected>" . htmlspecialchars($optionTitle) . "</option>\n";
    }
    
    $html .= "</select>\n";

    $popupHtml = "<button type=\"button\" class=\"popup-button\" id=\"$buttonId\" $buttonEnabled title=\"" . guppy_translate('_PGBTTYPEEXTRA') . "\" onclick=\"pagesetterOpenTypeExtra('$id','$hiddenId')\">...</button></span>\n";
    $html .= $popupHtml;

    if (!guppy_pageBlockExists('typeselect'))
      guppy_registerPageBlock('typeselect', $this->generateActionHtml($options));

    return $html;
  }


  function generateActionHtml($options)
  {
    global $guppyBaseURL;
    $script = $guppyBaseURL . '/plugins/typeselect.js';
    $actionsHtml = "<script src=\"$script\"></script>
                    <script>\nvar typeSelectAction = [\n";

    $i = 0;
    foreach ($options as $option)
    {
      if (guppy_fetchAttribute($option, 'usesExtra', false))
      {
        $popupUrl = pnModUrl('pagesetter', 'admin', 'typeselect', 
                             array('plugintype' => $option['value'], 
                                   'inputid'    => 'xidx',
                                   'typedata'   => 'xtypex'));
        
        $actionsHtml .= "{ enableButton: true, popupUrl: '$popupUrl' },\n";
      }
      else
      {
        $actionsHtml .= "{ enableButton: false, popupUrl: null },\n";
      }

      ++$i;
    }

    $actionsHtml .= "null ]\n";

    $actionsHtml .= "</script>\n";

    return $actionsHtml;
  }


  function decode()
  {
    $name = $this->name;
    $hiddenName = $name . '_hidden';

    $this->value = $_POST[$name] . '|' . $_POST[$hiddenName];

      // If magic quotes are on then all query/post variables are escaped - so strip slashes
    if (get_magic_quotes_gpc())
      $this->value = stripslashes($this->value);

    return $this->value;
  }


  function validate()
  {
    return true;
  }


  // ===[ Pagesetter interface ]==============================================

  function active()
  {
    return false;
  }
}

?>