<?php

require_once 'modules/pagesetter/guppy/plugins/input.string.php';


class GuppyInput_url extends GuppyInput_string
{
  function render($guppy)
  {
    $htmlClass = 'url';

    if ($this->mandatory)
      if ($this->data == '')
        $htmlClass .= " mde";
      else
        $htmlClass .= " mdt";

    $style = $this->getHtmlStyle();
    if ($style != '')
      $style = " style=\"$style\"";
    
    if ($this->readonly)
      $readonly = " readonly=\"1\"";

    $url = htmlspecialchars($this->value);
    
    $html = "<input type=\"text\" name=\"" . $this->name . "\" id=\"" . $this->ID . "\" class=\"$htmlClass\" $style value=\"$url\" maxlength=\"255\"$readonly/>";

    $id = $this->ID;
    $checkHtml = "&nbsp; <button onClick=\"window.open(document.getElementById('$id').value)\">" . _GUPPYURLCHECK . "</button>";

    return $html . $checkHtml;
  }


  function validate()
  {
    if (!parent::validate())
      return false;

    return true;
  }


  // ===[ Pagesetter interface ]==============================================

  function active()
  {
    return true;
  }

  function getTitle()
  {
    return 'Url';
  }

  function getSqlType()
  {
    return 'VARCHAR(255)';
  }
}

?>
