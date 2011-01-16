<?php

require_once 'modules/pagesetter/guppy/plugins/input.string.php';


class GuppyInput_photosharehtml extends GuppyInput_string
{
  function render($guppy)
  {
    $photoshareInstalled = (pnModLoad('photoshare','user') ? 1 : 0);
    if (!$photoshareInstalled)
      return 'Photoshare must be installed to use this input plugin';

    $htmlClass = 'phhtml';

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

    $imgHtml = htmlspecialchars($this->value);
    
    $html = "<script type=\"text/javascript\" src=\"modules/photoshare/pnjavascript/showimage.js\"></script>\n";

    $html .= "<input type=\"text\" name=\"" . $this->name . "\" id=\"" . $this->ID . "\" class=\"$htmlClass\" $style value=\"$imgHtml\"$readonly/>";

    $id = $this->ID;
    $photoshareThumbnailSize = pnModGetVar('photoshare', 'thumbnailsize');
    if (empty($photoshareThumbnailSize))
      $photoshareThumbnailSize = 80;

    $popupUrl = pnModUrl('photoshare', 'user', 'findimage', 
                         array('url' => 'relative', 'html' => 'img'));
    $popupHtml = "&nbsp; <button type=\"button\" class=\"popup-button\" onclick=\"photoshareFindImage('$id','$popupUrl',$photoshareThumbnailSize)\">...</button>";

    return $html . $popupHtml;
  }


  function validate()
  {
    if (!parent::validate())
      return false;

    if (!$this->mandatory  &&  $this->value == '')
      return true;

    return true;
  }


  // ===[ Pagesetter interface ]==============================================

  function active()
  {
    return true;
  }

  function getTitle()
  {
    return _GUPPYPHOTOSHAREHTML;
  }

  function getSqlType()
  {
    return 'VARCHAR(255)';
  }
}

?>
