<?php
// Generates a pager for browsing through the individual pages in a multi-paged publication.
// Use "pagesetter_pubPager" if you want to browse through a list of publications.
function smarty_function_pagesetter_pager($args, &$smarty)
{
  $core = $smarty->get_template_vars('core');

    // Get stuff that exists in core
  $page      = isset($args['page']) ? intval($args['page']) : intval($core['page']);
  $pageCount = isset($args['pageCount']) ? intval($args['pageCount']) : intval($core['pageCount']);
  $baseURL   = isset($args['baseURL']) ? $args['baseURL'] : $core['baseURL'];

    // Get stuff for presentation
  $prev          = isset($args['prev']) ? $args['prev'] : '&lt;';
  $next          = isset($args['next']) ? $args['next'] : '&gt;';
  $separator     = isset($args['separator']) ? $args['separator'] : '&nbsp;';
  $pageClass     = isset($args['pageClass']) ? $args['pageClass'] : null;
  $thisPageClass = isset($args['thisPageClass']) ? $args['thisPageClass'] : null;

  $html = '';

  if ($page > 0)
    $html .= "<a href=\"$baseURL&amp;page=" . $page . "\">$prev</a>&nbsp;";

  for ($i=0; $i<$pageCount; ++$i)
  {
    if ($i != 0)
      $html .= $separator;

    if ($i == $page)
      $html .= ($thisPageClass != null ? "<span class=\"$thisPageClass\">" : '') . ($i+1) . ($thisPageClass != null ? "</span>" : '');
    else
      $html .= "<a href=\"$baseURL&amp;page=" . ($i+1) . "\""
               . ($pageClass != null ? " class=\"$pageClass\"" : '') . ">" . ($i+1) . "</a>";
  }

  if ($page < $pageCount - 1)
    $html .= "&nbsp;<a href=\"$baseURL&amp;page=" . ($page+2) . "\">$next</a>";

  return $html;
}

?>
