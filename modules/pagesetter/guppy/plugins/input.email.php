<?php

require_once 'modules/pagesetter/guppy/plugins/input.string.php';


class GuppyInput_email extends GuppyInput_string
{
  function render($guppy)
  {
    $htmlClass = 'em';

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

    $email = htmlspecialchars($this->value);
    
    $html = "<input type=\"text\" name=\"" . $this->name . "\" id=\"" . $this->ID . "\" class=\"$htmlClass\" $style value=\"$email\" maxlength=\"120\"$readonly/>";

    $id = $this->ID;
    $mailHtml = "&nbsp; <button onClick=\"window.open('mailto:'+document.getElementById('$id').value)\">" . _GUPPYEMAILMAIL . "</button>";

    return $html . $mailHtml;
  }


  function validate()
  {
    if (!parent::validate())
      return false;

    if (!$this->mandatory  &&  $this->value == '')
      return true;

    if (($i=strpos($this->value,'@')) === false)
    {
      $this->error = _GUPPYEMAILMISSINGAT;
      return false;
    }

    $name = substr($this->value, 0, $i);
    $host = substr($this->value, $i+1);

    if ($name == '')
    {
      $this->error = _GUPPYEMAILMISSINGNAME;
      return false;
    }

    if ($host == '')
    {
      $this->error = _GUPPYEMAILMISSINGHOST;
      return false;
    }

    return true;
  }


  // ===[ Pagesetter interface ]==============================================

  function active()
  {
    return true;
  }

  function getTitle()
  {
    return _GUPPYEMAILEMAIL;
  }

  function getSqlType()
  {
    return 'VARCHAR(120)';
  }
}

?>
