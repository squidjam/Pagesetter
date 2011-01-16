<?php

class GuppyInput_string extends GuppyInput
{
  function render($guppy)
  {
    $htmlClass = 's';

    if ($this->mandatory)
      if ($this->data == '')
        $htmlClass .= " mde";
      else
        $htmlClass .= " mdt";

    $style = $this->getHtmlStyle();
    if ($style != '')
      $style = " style=\"$style\"";
    return "<input type=\"text\" name=\"" . $this->name . "\" id=\"" . $this->ID . "\" class=\"$htmlClass\" $style value=\"" . htmlspecialchars($this->value) . "\"/>";
  }


  function decode()
  {
    $this->value = $_POST[$this->name];

      // If magic quotes are on then all query/post variables are escaped - so strip slashes
    if (get_magic_quotes_gpc())
      $this->value = stripslashes($this->value);

    return $this->value;
  }


  function validate()
  {
    if ($this->value == ''  ||  $this->value == null)
    {
      if ($this->mandatory)
      {
        $this->error = _GUPPYMISSINGMANDATORY;
        return false;
      }
    }
    return true;
  }


  function getErrorMessage()
  {
    return $this->error;
  }


  // ===[ Pagesetter interface ]==============================================

  function active()
  {
    return false;
  }

  function getTitle()
  {
    return 'Base string';
  }

  function getDefaultWidth()
  {
    return 200;
  }

  function getDefaultHeight()
  {
    return NULL;
  }

  function getSqlType()
  {
    return 'VARCHAR(255)';
  }

  function getSqlFormat()
  {
    return null;
  }
}

?>
