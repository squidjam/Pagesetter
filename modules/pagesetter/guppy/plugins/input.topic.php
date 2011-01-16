<?php

class GuppyInput_topic extends GuppyInput
{
  function render($guppy)
  {
    if (!pnModAPILoad('pagesetter','user'))
      return _PGtopicNOTINSTALLED; // FIXME

    $htmlClass = 'topic';

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
    else
      $readonly = '';

    $id = $this->ID;

    $topics = pagesetterPNGetTopics(false, null, null);
    //print_r($topics);

    $html = "<select name=\"" . $this->name . "\" id=\"$id\" class=\"$htmlClass\" $style$readonly/>";

    foreach ($topics as $topic)
    {
      if ($topic['value'] == $this->value)
        $selected = ' selected="1"';
      else
        $selected = '';

      $html .= "<option value=\"$topic[value]\"$selected>$topic[title]</option>\n";
    }

    $html .= "</select>\n";

    return $html;
  }


  function decode()
  {
    $this->value = $_POST[$this->name];

      // If magic quotes are on then all query/post variables are escaped - so strip slashes
    if (get_magic_quotes_gpc())
      $this->value = stripslashes($this->value);

    $this->value = (int)$this->value;

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

  function getTitle()
  {
    return 'topic';
  }

  function getSqlType()
  {
    return 'INT';
  }
}

?>
