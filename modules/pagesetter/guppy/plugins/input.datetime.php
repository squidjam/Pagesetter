<?php

require_once 'modules/pagesetter/guppy/plugins/input.string.php';


class GuppyInput_datetime extends GuppyInput_string
{
  var $datetime;


  function render($guppy)
  {
    global $guppyBaseURL;

    $name = $this->name;
    $id = $this->ID;

      // Minor hack - values are retrived as a unix timestamp from database, but written back as a formatted string
      // If the input form doesn't validate then the value is the formatted string instead of a timestamp, so
      // this piece of code ensures we always work on a timestamp
    if ($this->value != null  &&  !is_numeric($this->value))
      $this->value = strtotime($this->value);

    if ($this->readonly)
    {
      if ($this->value == NULL)
        $d = '';
      else
        $d = strftime("%Y-%m-%d %H:%M", $this->value);

      $html = "<input type=\"text\" name=\"$name\" id=\"$id\" value=\"$d\" class=\"dt ro\" readonly=\"1\"/>";
    }
    else
    {
      $htmlClass = '';

      if ($this->mandatory)
        if ($this->data == '')
          $htmlClass = " mde";
        else
          $htmlClass = " mdt";

      if ($this->value == NULL)
      {
        $date = '';
        $hour = '';
        $min  = '';
      }
      else
      {
        $date = strftime("%Y-%m-%d", $this->value);
        $hour = strftime("%H", $this->value);
        $min  = strftime("%M", $this->value);
      }
      
      $dstyle = "style=\"width: 5.5em\" class=\"dtd$htmlClass\"";
      $hstyle = "style=\"width: 1.5em\" class=\"dth$htmlClass\"";
      $mstyle = "style=\"width: 1.5em\" class=\"dtm$htmlClass\"";

      $html = "<input type=\"text\" name=\"$name:d\" id=\"{$id}_d\" $dstyle value=\"$date\" maxlength=\"10\"/>";

      $html .= " <img src=\"$guppyBaseURL/jscalendar/img.gif\" id=\"{$id}_db\" class=\"clickable\"/> ";

      $html .= "<input type=\"text\" name=\"$name:h\" id=\"{$id}_h\" $hstyle value=\"$hour\" maxlength=\"2\"/>";

      $html .= ":<input type=\"text\" name=\"$name:m\" id=\"{$id}_m\" $mstyle value=\"$min\" maxlength=\"2\"/>";

      $html .=  "<script type=\"text/javascript\">\nCalendar.setup({"
               . "inputField: \"{$id}_d\", "
               . "ifFormat: \"%Y-%m-%d\", "
               . "button: \"{$id}_db\", "
               . "step: 1, "
               . "singleClick: true})\n"
               . "</script>\n";
    }
    return $html;
  }


  function decode()
  {
    $date = trim($_POST[$this->name . ':d']);
    $hour = trim($_POST[$this->name . ':h']);
    $min  = trim($_POST[$this->name . ':m']);

      // If magic quotes are on then all query/post variables are escaped - so strip slashes
    if (get_magic_quotes_gpc())
    {
      $date = stripslashes($date);
      $hour = stripslashes($hour);
      $min  = stripslashes($min);
    }

    if ($date == '')
    {
      $this->value = NULL;
    }
    else
    {
      list ($year,$month,$day) = split('-', $date);

      if ($hour == '')
        $hour = '00';
      if ($min == '')
        $min = '00';

        // Scan input as date/time and rewrite into correct format
      $this->datetime = mktime($hour,$min,0,$month,$day,$year);
      $this->value = strftime("%Y-%m-%d %H:%M:%S", $this->datetime);
    }

    return $this->value;
  }


  function validate()
  {
    if ($this->datetime == NULL  &&  $this->mandatory)
    {
      $this->error = _GUPPYMISSINGMANDATORY . ': ' . guppy_Translate($fieldTitle);
      return false;
    }

    if ($this->datetime == -1)
    {
      $this->error = _GUPPYINVALIDDATETIME . ': ' . guppy_Translate($fieldTitle);
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
    return 'Date/Time';
  }

  function getSqlType()
  {
    return 'DATETIME';
  }

  function getSqlFormat()
  {
    return 'UNIX_TIMESTAMP($columnName)';
  }
}

?>
