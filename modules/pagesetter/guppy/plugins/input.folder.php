<?php

class GuppyInput_folder extends GuppyInput
{
  function render($guppy)
  {
    if (!pnModAPILoad('folder','user'))
      return _PGFOLDERNOTINSTALLED;

    $htmlClass = 'folder';

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

    $folders = pnModAPIFunc('folder', 'user', 'getAllFolders');
    //print_r($folders);

    $html = "<select name=\"" . $this->name . "\" id=\"$id\" class=\"$htmlClass\" $style$readonly/>";

    $html .= "<option value=\"-1\">" . _PGFOLDERNONE . "</option>\n";

    foreach ($folders as $folder)
    {
      if ($folder['value'] == $this->value)
        $selected = ' selected="1"';
      else
        $selected = '';

      $html .= "<option value=\"$folder[value]\"$selected>$folder[title]</option>\n";
    }

    $html .= "</select>\n";

    return $html;
  }


  function decode()
  {
    if (isset($_POST[$this->name]))
    {
      $this->value = $_POST[$this->name];

        // If magic quotes are on then all query/post variables are escaped - so strip slashes
      if (get_magic_quotes_gpc())
        $this->value = stripslashes($this->value);

      $this->value = (int)$this->value;
    }
    else
      $this->value = '';

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
    return 'Folder';
  }

  function getSqlType()
  {
    return 'INT';
  }
}

?>
