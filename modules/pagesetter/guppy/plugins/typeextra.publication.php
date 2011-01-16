<?php

// This function is used to render the admin interface of the extra type parameter
// for the "Publication" plugin. It returns HTML that allows the admin to select
// a publication type ID.
function typeextra_publication_render($args)
{
  // Fetch previous data
  $typeData = $args['typeData'];

  if (!pnModAPILoad('pagesetter', 'admin'))
    return pagesetterErrorPage(__FILE__, __LINE__, 'Failed to load Pagesetter admin API');

  // Fetch all publication typs
  $pubTypes = pnModAPIFunc('pagesetter', 'admin', 'getPublicationTypes');

  if ($pubTypes === false) 
    return pagesetterErrorAPIGet();

  // Generate HTML for a <select> element based on the pubtype list and the
  // currently selected value.

  $html = "<label for=\"typeextra_publication\">" . _PGTS_PUBLICATION_SELECT . "</label>: <select id=\"typeextra_publication\">\n";

  foreach ($pubTypes as $pubType)
  {
    if ($pubType['id'] == $typeData)
      $selected = ' selected="1"';
    else
      $selected = '';

    $html .= "<option value=\"$pubType[id]\"$selected>" . pnVarPrepForDisplay($pubType['title']) . "</option>\n";
  }

  $html .= "</select>\n";

  // VERY IMPORTANT
  // Implement a JavaScript function that reads the selected publication type ID
  // and returns. The name of the function "typeextra_submit" is required by the
  // surrounding code.
  $html .= "
<script>
function typeextra_submit()
{
  var selector = document.getElementById('typeextra_publication');
  return selector.value;
}
</script>
";

  return $html;
}


?>