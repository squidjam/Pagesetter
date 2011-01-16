<?php
// $Id: pnuser.php,v 1.120 2008/03/18 20:28:08 jornlind Exp $
// =======================================================================
// Pagesetter by Jorn Lind-Nielsen (C) 2003.
// ----------------------------------------------------------------------
// For POST-NUKE Content Management System
// Copyright (C) 2002 by the PostNuke Development Team.
// http://www.postnuke.com/
// ----------------------------------------------------------------------
// LICENSE
//
// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License (GPL)
// as published by the Free Software Foundation; either version 2
// of the License, or (at your option) any later version.
//
// This program is distributed in the hope that it will be useful,
// but WithOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// To read the license please visit http://www.gnu.org/copyleft/gpl.html
// =======================================================================

require_once("modules/pagesetter/common.php");


function pagesetterSetupBaseURL(&$baseURLArgs)
{
    // These are the query values that we need to pass on to comming actions
  static $pagesetterVars = array('topic', 'lang', 'tpl', 'pubcnt', 'orderby');

  foreach ($pagesetterVars as $v)
    if (isset($_REQUEST[$v]))
      $baseURLArgs[$v] = pnVarCleanFromInput($v);
}


  // Fetch array of all filters on URL
function pagesetterGetFilters($args, &$baseURLArgs)
{
  $i = 1;
  $filter = array();

    // Get unnumbered filter string
  $filterStr = isset($args['filter']) ? $args['filter'] : pnVarCleanFromInput('filter');
  if (isset($filterStr))
  {
    $filter[] = pagesetterReplaceFilterVariable($filterStr);
    $baseURLArgs['filter'] = $filterStr;
  }

    // Get filter1 ... filterN
  while (true)
    {
    $filterURLName = "filter$i";
    $filterStr     = isset($args[$filterURLName]) ? $args[$filterURLName] : pnVarCleanFromInput($filterURLName);

    if (empty($filterStr))
      break;

    $filter[] = pagesetterReplaceFilterVariable($filterStr);
    $baseURLArgs[$filterURLName] = $filterStr;

    ++$i;
  }

  return $filter;
}


function pagesetter_user_view($args)
{
  return pagesetter_user_main($args);
}


function pagesetter_user_main($args)
{
  $tid = pagesetterGetTID($args);
  if ($tid === false)
    return pagesetterErrorPage(__FILE__, __LINE__, 'No default publication type specified (go to admin :: pagesetter :: configuration :: general)');

    // Check access
  if (!pnSecAuthAction(0, 'pagesetter::', "$tid::", ACCESS_READ))
    return pagesetterErrorPage(__FILE__, __LINE__, _PGNOAUTH);

  $topic        = isset($args['topic']) ? $args['topic'] : pnVarCleanFromInput('topic');
  $language     = isset($args['lang']) ? $args['lang'] : pnVarCleanFromInput('lang');
  $urlTemplate  = isset($args['tpl']) ? $args['tpl'] : pnVarCleanFromInput('tpl');
  $page         = isset($args['page']) ? $args['page'] : pnVarCleanFromInput('page'); // Offset 1 (to avoid page=0 on the URL)
  $noOfItems    = isset($args['pubcnt']) ? $args['pubcnt'] : pnVarCleanFromInput('pubcnt');
  $orderBy      = isset($args['orderby']) ? $args['orderby'] : pnVarCleanFromInput('orderby');
  $enableHooks  = isset($args['enableHooks']) ? $args['enableHooks'] : true;
  
  $filterStrSet = pagesetterGetFilters($args, $baseURLArgs);

  $page      = (isset($page)      ? $page-1    : 0);
  $noOfItems = (isset($noOfItems) ? $noOfItems : null);

  if (!isset($language))
    $language = pnUserGetLang();

  if (isset($urlTemplate))
    $format = $urlTemplate;
  else
    $format = 'list';
  
  if (!pnModAPILoad('pagesetter', 'user'))
    return pagesetterErrorPage(__FILE__, __LINE__, 'Failed to load Pagesetter user API');

  if (!pnModAPILoad('pagesetter', 'admin'))
    return pagesetterErrorPage(__FILE__, __LINE__, 'Failed to load Pagesetter admin API');

    // Build baseURL for comming actions
  $baseURLArgs = array( 'tid' => $tid );
  pagesetterSetupBaseURL($baseURLArgs);

  $baseURL = pnModURL('pagesetter',
                      'user',
                      'main',
                      $baseURLArgs);
  $baseURL = htmlspecialchars($baseURL);

    // Fetch publication info
  $pubInfo = pnModAPIFunc('pagesetter', 'admin', 'getPubTypeInfo',
                           array('tid' => $tid) );
  if ($pubInfo === false)
    return pagesetterErrorAPIGet();

    // fetch the list of publications
  $pubList =  pnModAPIFunc( 'pagesetter',
                            'user',
                            'getPubList',
                            array('tid'                => $tid,
                                  'topic'              => $topic,
                                  'noOfItems'          => $noOfItems,
                                  'language'           => $language,
                                  'offsetPage'         => $page,
                                  'filterSet'          => $filterStrSet,
                                  'allowDefaultFilter' => true,
                                  'orderByStr'         => $orderBy) );

  if ($pubList === false)
    return pagesetterErrorAPIGet();

  $output = "";

  $cacheID = pagesetterGetPublicationUniqueID($tid, $format, pnUserGetLang());

  if (pnModLoad('photoshare'))
  {
      // Use PostNuke variable for insertion of special HTML header tags.
      // - in this case the JavaScript used for Photoshare popup images.
    global $additional_header;
    $additional_header[] = "<script type=\"text/javascript\" src=\"modules/photoshare/pnjavascript/showimage.js\"></script>";
  }

  $smarty = new pnRender('pagesetter');
  $smarty->caching = false; // Cannot handle caching of page numbers
  
    // Add a simplified $core (the "core" core :-). This is for the headers and footers
  $core = array('tid'       => $tid, 
                'format'    => $format, 
                'baseURL'   => $baseURL,
                'page'      => $page,
                'morePages' => $pubList['more']);

    // Look for the existence of "xxx-list-header.html". If it exists then be backwards compatible
    // otherwise display all publications through a single template.
  $templateHeaderFile = pagesetterSmartyGetTemplateFilename($smarty, $tid, "$format-header", $expectedName);

  if ($templateHeaderFile != 'Default.html')
  {
      // Print header
    $smarty->assign('core', $core);

    $output .= $smarty->fetch($templateHeaderFile, "H_$cacheID");

    $listItemNo = 0;

      // Print publications
    foreach ($pubList['publications'] as $pub)
    {
      $pubFormatted = pnModAPIFunc( 'pagesetter',
                                    'user',
                                    'getPubFormatted',
                                    array('tid'            => $tid,
                                          'pid'            => $pub['pid'],
                                          'format'         => $format,
                                          'updateHitCount' => false,
                                          'useTransformHooks' => $enableHooks,
                                          'coreExtra'      => array('format'     => $format,
                                                                    'page'       => $page,
                                                                    'listItemNo' => $listItemNo,
                                                                    'baseURL'    => $baseURL)) );

        // Ignore non-existing publications
      if (!($pubFormatted === false))
        $output .= $pubFormatted;

      ++$listItemNo;
    }

      // Print footer
    $smarty->assign('core', $core);
    $templateFooterFile = pagesetterSmartyGetTemplateFilename($smarty, $tid, "$format-footer", $expectedName);
    $output .= $smarty->fetch($templateFooterFile, "F_$cacheID");
  }
  else 
  {
    // tjreo - use one template when no list-header template can be found.

    $format = (isset($urlTemplate) ? $urlTemplate : 'list-single');

    pagesetterSmartyGetTemplateFilename($smarty, $tid, "$format-header", $expectedName1);
    pagesetterSmartyGetTemplateFilename($smarty, $tid, "$format", $expectedName2);
    pagesetterSmartyGetTemplateFilename($smarty, $tid, "$format-footer", $expectedName3);
    $core['templateFilename'] = "$expectedName1 / $expectedName2 / $expectedName3";
    
    $output =  pnModAPIFunc( 'pagesetter',
                             'user',
                             'getPubArrayFormatted',
                             array('tid'       => $tid,
                                   'pubList'   => $pubList,
                                   'format'    => $format,
                                   'useTransformHooks' => $enableHooks,
                                   'coreExtra' => $core) );
    if ($output === false)
      return pagesetterErrorAPIGet();
  }

  if (trim($output) == "")
    return _PGNOPUBLICATIONS;

  return $output;
}


function pagesetter_user_printlist()
{
  pagesetterPrintNoPNFrames( pagesetter_user_main() );

  return true;
}


function pagesetter_user_dumplist()
{
  echo pagesetter_user_main();

  return true;
}


function pagesetter_user_xmllist()
{
  header("Content-type: text/xml");
  echo pagesetter_user_main( array('enableHooks' => false) );

  return true;
}


function pagesetter_user_publist()
{
  $tid = pnVarCleanFromInput('tid');
  $state = pnVarCleanFromInput('state');

  require_once "modules/pagesetter/forms/pubListHandler.php";
  $handler = new pubListHandler();

  if (!pnModAPILoad('pagesetter', 'user'))
    return pagesetterErrorPage(__FILE__, __LINE__, 'Failed to load Pagesetter user API');

  if (!guppy_decode($handler))
  {
    require_once 'modules/pagesetter/guppy/guppy_parser.php';

    if (!is_numeric($tid))
      return pagesetterErrorPage(null, null, _PGILLURL);

    if (!pnModAPILoad('pagesetter', 'admin'))
      return pagesetterErrorPage(__FILE__, __LINE__, 'Failed to load Pagesetter admin API');

    if (!pnModAPILoad('pagesetter', 'workflow'))
      return pagesetterErrorPage(__FILE__, __LINE__, 'Failed to load Pagesetter workflow API');

      // Check access at this point where the IDs are available
    if (!pnSecAuthAction(0, 'pagesetter::', "$tid::", pagesetterAccessAuthor))
      return pagesetterErrorPage(__FILE__, __LINE__, _PGNOAUTH);

    $pubInfo = pnModAPIFunc('pagesetter', 'admin', 'getPubTypeInfo',
                             array('tid' => $tid) );

    if ($pubInfo === false)
      return pagesetterErrorAPIGet();

      // Now check for "edit access to own" being enabled, otherwise "editor" access is required
    if (!($pubInfo['publication']['enableEditOwn']  ||  pnSecAuthAction(0, 'pagesetter::', "$tid::", pagesetterAccessEditor)))
      return pagesetterErrorPage(__FILE__, __LINE__, _PGNOAUTH);

    $isEditor = pnSecAuthAction(0, 'pagesetter::', "$tid::", pagesetterAccessEditor);

    $pubTypes = pnModAPIFunc('pagesetter', 'admin', 'getPublicationTypes',
                             array('getForGuppyDropdown' => true));

    if ($pubTypes === false) 
      return pagesetterErrorAPIGet();

    $filter = array();
    if (isset($state))
      $filter['approvalState'] = $state;

    $pageno = pnSessionGetVar("pagesetterEditPageNo-$tid");

    $pubList = pnModAPIFunc( 'pagesetter', 'user', 'getPubList',
                             array('tid'               => $tid,
                                   'useRestrictions'   => false,
                                   'noOfItems'         => pagesetterEditRowsPerPage,
                                   'offsetItems'       => $pageno * pagesetterEditRowsPerPage,
                                   'getTextual'        => true,
                                   'getOwners'         => !$isEditor,
                                   'getApprovalState'  => true,
                                   'filter'            => $filter) );

    if ($pubList === false)
      return pagesetterErrorAPIGet();

    $workflowName = $pubInfo['publication']['workflow'];
    $workflow = pnModAPIFunc('pagesetter', 'workflow', 'load', array('workflow' => $workflowName) );
    if ($workflow === false)
      return pagesetterErrorAPIGet();

    $approvalStates = array();
    foreach ($workflow->getStates() as $stateName => $state)
      $approvalStates[] = array('title' => $state->getTitle(),
                                'value' => $stateName);

    $language = pagesetterPNGetLanguages();

    $restrictions = array('tid'           => $tid,
                          'title'         => null,
                          'topic'         => null,
                          'approvalState' => null,
                          'language'      => null);

    $depotEnabled = (bool)$pubInfo['publication']['enableRevisions'];

    $data = array( 'pubListHeader' => array( 'rows' => array( $restrictions ) ),
                   'pubList'       => array( 'rows'    => $pubList['publications'],
                                             'actions' => array( 'prev'    => $pageno > 0,
                                                                 'next'    => $pubList['more'],
                                                                 'move'    => $depotEnabled,
                                                                 'history' => $depotEnabled) ) );

    $topics = pagesetterPNGetTopics($pubInfo['publication']['enableTopicAccess'], null, null);
    if ($topics === false)
      return pagesetterErrorAPIGet();

    $options = array('topics' => $topics, 
                     'pubTypes' => $pubTypes, 
                     'approvalStates' => $approvalStates,
                     'language'       => $language);

    if ($isEditor)
    {
      $layoutFile  = 'modules/pagesetter/forms/pubListLayout.xml';
      $toolbarFile = 'modules/pagesetter/forms/adminToolbar.xml';                       
    }
    else
    {
      $layoutFile  = 'modules/pagesetter/forms/pubListAuthorLayout.xml';
      $toolbarFile = 'modules/pagesetter/forms/authorToolbar.xml';                       
    }

    guppy_open( array( 'specFile'       => 'modules/pagesetter/forms/pubListSpec.xml',
                       'layoutFile'     => $layoutFile,
                       'toolbarFile'    => $toolbarFile,
                       'options'        => $options,
                       'data'           => $data,
                       'actionURL'      => pnModUrl('pagesetter','user','publist'),
                       'onBeforeRender' => "pagesetterHideIntroText") );
  }

  return guppy_output();
}


function pagesetterHideIntroText(&$spec, &$layout, &$data)
{
  $textElement = &guppy_getLayoutElement('introtext');
  if (count($data['pubList']['rows']) > 0)
    $textElement['visible'] = false;
  else
    $textElement['visible'] = true;
}


function pagesetter_user_pubfind($args)
{
  $tid = pagesetterGetTID($args);

  if ($tid === false)
    return pagesetterErrorPage(__FILE__, __LINE__, 'No default publication type specified (go to admin :: pagesetter :: configuration :: general)');

  if (!pnModAPILoad('pagesetter', 'user'))
    return pagesetterErrorPage(__FILE__, __LINE__, 'Failed to load Pagesetter user API');

  if (!pnModAPILoad('pagesetter', 'admin'))
    return pagesetterErrorPage(__FILE__, __LINE__, 'Failed to load Pagesetter admin API');

  require_once "modules/pagesetter/forms/pubListHandler.php";
  $handler = new pubListHandler();

  if (!guppy_decode($handler))
  {
      // Check access at this point where the IDs are available
    if (!pnSecAuthAction(0, 'pagesetter::', "$tid::", ACCESS_READ))
      return pagesetterErrorPage(__FILE__, __LINE__, _PGNOAUTH);

    $targetID = pnVarCleanFromInput('targetID');
    if (!isset($targetID))
      return pagesetterErrorPage(__FILE__, __LINE__, 'No targetID specified on URL in Pagesetter-find-publication');

    $pubTypes = pnModAPIFunc('pagesetter',
                             'admin',
                             'getPublicationTypes',
                             array('getForGuppyDropdown' => true));

    if ($pubTypes === false) 
      return pagesetterErrorAPIGet();

    $pubList =  pnModAPIFunc( 'pagesetter',
                              'user',
                              'getPubList',
                              array('tid'              => $tid,
                                    'useRestrictions'  => true,
                                    'getApprovalState' => true,
                                    'noOfItems'        => pagesetterEditRowsPerPage,
                                    'getTextual'       => true  ) );

    if ($pubList === false)
      return pagesetterErrorAPIGet();

    $restrictions = array('tid'           => $tid,
                          'title'         => '',
                          'topic'         => null,
                          'approvalState' => null);

    $data = array( 'pubListHeader' => array( 'rows' => array( $restrictions ) ),
                   'pubList'       => array( 'rows'    => $pubList['publications'],
                                             'actions' => array( 'prev' => false,
                                                                 'next' => $pubList['more']) ) );

    $topics = pagesetterPNGetTopics($pubInfo['publication']['enableTopicAccess'], null, null);
    if ($topics === false)
      return pagesetterErrorAPIGet();

    $extra = array( 'targetID' => $targetID );

    guppy_open( array( 'specFile'    => 'modules/pagesetter/forms/pubFindSpec.xml',
                       'layoutFile'  => 'modules/pagesetter/forms/pubFindLayout.xml',
                       //'toolbarFile' => 'modules/pagesetter/forms/adminToolbar.xml',
                       'options'     => array('topics' => $topics, 'pubTypes' => $pubTypes),
                       'data'        => $data,
                       'extra'       => $extra,
                       'actionURL'   => pnModUrl('pagesetter','user','pubfind') ) );
  }

  pagesetterPrintNoPNFrames( guppy_output() );

  return true;
}


function pagesetter_user_pubedit()
{
  // 'pid' is used if 'id' is missing (which is the case of an "edit this" on a cached page)
  $tid        = pnVarCleanFromInput('tid');
  $id         = pnVarCleanFromInput('id');
  $pid        = pnVarCleanFromInput('pid'); 
  $action     = pnVarCleanFromInput('action');
  $goback     = pnVarCleanFromInput('goback');
  $goBackUrl  = pnVarCleanFromInput('backurl');
  $folderId   = pnVarCleanFromInput('folderid');

  if (!isset($action))
    $action = 'new';

  if (!pnModAPILoad('pagesetter', 'user'))
    return pagesetterErrorPage(__FILE__, __LINE__, 'Failed to load Pagesetter user API');

  if (!pnModAPILoad('pagesetter', 'edit'))
    return pagesetterErrorPage(__FILE__, __LINE__, 'Failed to load Pagesetter edit API');

  if (!pnModAPILoad('pagesetter', 'admin'))
    return pagesetterErrorPage(__FILE__, __LINE__, 'Failed to load Pagesetter admin API');

  if (!pnModAPILoad('pagesetter', 'workflow'))
    return pagesetterErrorPage(__FILE__, __LINE__, 'Failed to load Pagesetter workflow API');

  require_once "modules/pagesetter/forms/pubEditHandler.php";
  $handler = new publicationPubEditHandler();

  if (!guppy_decode($handler))
  {
    if (!is_numeric($tid) && isset($tid)  ||  !is_numeric($id) && isset($id))
      return pagesetterErrorPage(null, null, _PGILLURL);

    if ($action != 'new')
      $action = 'update';

      // Check access at this point where the IDs are available (there's a second check later below)
    if (!pnSecAuthAction(0, 'pagesetter::', "$tid::", pagesetterAccessAuthor))
      return pagesetterErrorPage(__FILE__, __LINE__, _PGNOAUTH);

      // Get publication type information
    $pubInfo = pnModAPIFunc('pagesetter', 'admin', 'getPubTypeInfo',
                             array('tid' => $tid) );
    if ($pubInfo === false)
      return pagesetterErrorAPIGet();

      // If only having "author" access then redirect to referer on finish (since authors
      // do not have access to the pub. list shown afterwards).
      // Same applies if a "backurl" is supplied
    if ($goBackUrl != null  ||  !pnSecAuthAction(0, 'pagesetter::', "$tid::", pagesetterAccessEditor))
      $goback = true;


      // Load/setup publication

    if ($action == 'new')
    {
      $pub = pnModAPIFunc( 'pagesetter', 'edit', 'getEmptyPub',
                           array('tid' => $tid) );
    }
    else
    {
      if (!empty($id))
      {
        $pub = pnModAPIFunc( 'pagesetter', 'user', 'getPub',
                             array('tid'              => $tid,
                                   'id'               => $id,
                                   'getApprovalState' => true,
                                   'format'           => 'database') );
        $pid = $pub['core_pid'];
      }
      else
      {
        $pub = pnModAPIFunc( 'pagesetter', 'user', 'getPub',
                             array('tid'              => $tid,
                                   'pid'              => $pid,
                                   'getApprovalState' => true,
                                   'useRestrictions'  => false,
                                   'notInDepot'       => true,
                                   'format'           => 'database') );
        $id = $pub['core_id'];
      }

        // Optional topic access check for opening existing pub. for edit
      if (!pagesetterHasTopicAccess($pubInfo, $pub['core_topic'], 'write'))
        return pagesetterErrorPage(__FILE__, __LINE__, 'You do not have write access to the selected topic');
    }

    if ($pub === false)
      return pagesetterErrorAPIGet();
    if ($pub === true)
      return pagesetterErrorPage(__FILE__, __LINE__, "Unknown publication '$id$pid'");


    $workflowName = $pubInfo['publication']['workflow'];

      // Now check for edit access to own being enabled, otherwise "editor" access is required - that is, if we are editing, not adding
    if ($action != 'new')
      if (!($pubInfo['publication']['enableEditOwn']  ||  pnSecAuthAction(0, 'pagesetter::', "$tid::", pagesetterAccessEditor)))
        return pagesetterErrorPage(__FILE__, __LINE__, _PGNOAUTH);

      // Calculate dynamic workflow actions

    if ($action == 'new')
      $state = null;
    else
      $state = $pub['core_approvalState'];

    $workflow = pnModAPIFunc('pagesetter', 'workflow', 'load', array('workflow' => $workflowName) );
    if ($workflow === false)
      return pagesetterErrorAPIGet();

    $env = array('isOwner'    => $pub['core_creatorID'] == pnUserGetVar('uid'),
                 'permission' => pagesetterGetCurrentPermission($tid,$pid) );

    $enabledActions = $workflow->getEnabledActions($state, $tid, $id, $env);

      // If no actions are enabled then user do not have access
    if (count($enabledActions) == 0)
      return pagesetterErrorPage(__FILE__, __LINE__, _PGNOACTIONS);


      // Check existence of workflow state specific layout

    if ($action == 'new')
      $workflowStateName = 'new';
    else
      $workflowStateName = $pub['core_approvalState'];

    $layoutFileName = "modules/pagesetter/publications/{$pubInfo['publication']['formname']}/{$workflowStateName}FormLayout.xml";
    if (!is_readable($layoutFileName)  ||  !is_file($layoutFileName))
    {
      $layoutFileName = 'modules/pagesetter/forms/pubEditLayout.xml';
      $isUserLayout = false;
    }
    else
      $isUserLayout = true;

      // Calculate dynamic fields specifications and layout

    $typeGuppySpec = pnModAPIFunc('pagesetter', 'edit', 'getPubGuppySpec',
                                  array('tid' => $tid,
                                        'isUserLayout' => $isUserLayout) );

    if ($typeGuppySpec === false)
      return pagesetterErrorAPIGet();


      // Load hardcoded parts of spec and layout

    if (($spec=guppy_loadfile('pagedit', 'modules/pagesetter/forms/pubEditSpec.xml')) === false)
      return guppy_output();

    if (($layout=guppy_loadfile('pagedit', $layoutFileName)) === false)
      return guppy_output();

    require_once 'modules/pagesetter/guppy/guppy_parser.php';

    $topics = pagesetterPNGetTopics($pubInfo['publication']['enableTopicAccess'], $pubInfo['publication']['filename'], 'write');

    if ($topics === false)
      return pagesetterErrorAPIGet();

    $langList = pagesetterPNGetLanguages();

      // Depend on globally passed value since I don't know how to return a variable reference
    guppy_parseXMLSpec($spec, array('topics' => $topics, 'language' => $langList));
    global $guppyParsedSpec;
    $spec = &$guppyParsedSpec;

      // Insert dynamic spec and layout

    $fieldSpec = &$spec['components']['pubedit']['fields'];
    $fieldSpec = array_merge($fieldSpec, $typeGuppySpec['fieldSpec']);

    $layout = guppy_parseXMLLayout($layout);

    if (!$isUserLayout)
      $layout['layout'][0][0]['layout'][0][0]['layout'] = $typeGuppySpec['fieldLayout'];

    $actionSpec = &$spec['components']['pubedit']['actions'];
    $buttonsBottom = array();

    foreach ($enabledActions as $action)
    {
      $actionSpec[$action['id']] = array('name'  => $action['id'], 
                                         'title' => $action['title'],
                                         'kind'  => 'submit',
                                         'hint'  => $action['description']);

      $buttonsBottom[] = array('name' => $action['id'], 'kind' => 'button');
    }

      // Insert buttons in button group
    $buttonsBottom = array( array('title' => _PGFTACTIONS, 'buttons' => $buttonsBottom, 'kind' => 'group') );

    $layout['layout'][0][0]['buttonsBottom'] = array_merge($buttonsBottom, $layout['layout'][0][0]['buttonsBottom']);

      // Add remaining minor Guppy setup stuff and load the form

    $data = array( 'pubedit' => array( 'rows' => array( $pub ) ) );

    $extraJavaScriptFilename = 'modules/pagesetter/publications/' . $pubInfo['publication']['formname'] . '/editorsetup.js';

    if ($goBackUrl == null)
      $goBackUrl = $_SERVER['HTTP_REFERER'];
    if ($goBackUrl == null)
      $goBackUrl = "index.php";

    $extra = array('action'      => $action,
                   'tid'         => $tid,
                   'pid'         => $pid,
                   'id'          => $id,
                   'state'       => $state,
                   'workflow'    => $workflowName,
                   'httpReferer' => ($goback ? $goBackUrl : null));

    if (!empty($folderId))
      $extra['folderId'] = $folderId;

    guppy_open( array( 'rawSpec'     => $spec,
                       'rawLayout'   => $layout,
                       'data'        => $data,
                       'extra'       => $extra,
                       'editorsetup' => $extraJavaScriptFilename,
                       'actionURL'   => pnModUrl('pagesetter','user','pubedit') ) );
  }

  return guppy_output();
}


function pagesetterPrintNoPNFrames($text)
{
  echo "<html>\n";

  echo "<head>\n";

  echo "<link rel=\"StyleSheet\" href=\"themes/" . pnUserGetTheme() . "/style/style.css\" type=\"text/css\" />\n";

  if (_CHARSET != "")
    echo "<meta http-equiv=\"Content-Type\" content=\"text/html; charset="._CHARSET."\">\n";

  global $additional_header;
  if (is_array($additional_header))
  {
    foreach ($additional_header as $header)
      echo "$header\n";
  }

  echo "</head>\n";
  echo "<body>\n";
  echo $text;
  echo "</body>\n";
  echo "</html>\n";
}


function pagesetterGetPubFormatted($format = 'full', 
                                   $useDisplayHooks = false, 
                                   $useTransformHooks = false,
                                   $func = 'viewpub',
                                   $args = array())
{
  $tid  = pnVarCleanFromInput('tid');
  $pid  = pnVarCleanFromInput('pid');
  $key  = pnVarCleanFromInput('key');
  $id   = pnVarCleanFromInput('id');
  $page = pnVarCleanFromInput('page');
  $tpl  = pnVarCleanFromInput('tpl');

  extract($args);

  // If "key" is set then convert that to tid/pid
  pagesetterSplitKey($key, $tid, $pid);

  if (!is_numeric($tid)  ||  !is_numeric($pid) && isset($pid)  ||  !is_numeric($id) && isset($id))
    return pagesetterErrorPage(null, null, _PGILLURL);

    // Access check in API getPub

  if (!pnModAPILoad('pagesetter', 'user'))
    return pagesetterErrorPage(__FILE__, __LINE__, 'Failed to load Pagesetter user API');

  if (pnModLoad('photoshare'))
  {
      // Use PostNuke variable for insertion of special HTML header tags.
      // - in this case the JavaScript used for Photoshare popup images.
    global $additional_header;
    $additional_header[] = "<script type=\"text/javascript\" src=\"modules/photoshare/pnjavascript/showimage.js\"></script>";
  }

  if (isset($tpl))
    $format = $tpl;

  if (isset($page))
    --$page;  // Offset is from 1 in URL, but zero based in core
  else
    $page = 0;

  $args = array( 'tid' => $tid );
  if (isset($id))
    $args['id'] = $id;
  if (isset($pid))
    $args['pid'] = $pid;
  if (isset($tpl))
    $args['tpl'] = $tpl;

  $url = pnModURL('pagesetter', 'user', $func, $args);
  $url = htmlspecialchars($url);

  $pubInfo = pnModAPIFunc( 'pagesetter',
                           'admin',
                           'getPubTypeInfo',
                           array('tid' => $tid) );
  if ($pubInfo === false)
    return pagesetterErrorAPIGet();

  $useDisplayHooks   = $useDisplayHooks   && $pubInfo['publication']['enableHooks'];
  $useTransformHooks = $useTransformHooks && $pubInfo['publication']['enableHooks'];

  $pub = pnModAPIFunc( 'pagesetter',
                       'user',
                       'getPubFormatted',
                       $args + array('format'    => $format,
                                     'useTransformHooks' => $useTransformHooks,
                                     'coreExtra' => array('page'    => $page,
                                                          'baseURL' => $url,
                                                          'format'  => $format,
                                                          'useDisplayHooks'   => $useDisplayHooks,
                                                          'uniqueId' => pagesetterGetPublicationUniqueID($tid,$pid)) ) );
  if ($pub === false)
    return pagesetterErrorAPIGet();

  if (!pnModAPILoad('pagesetter', 'admin'))
    return pagesetterErrorPage(__FILE__, __LINE__, 'Failed to load Pagesetter admin API');

  return $pub;
}


function pagesetter_user_viewpub($args)
{
  $output = pagesetterGetPubFormatted('full', true, true, 'viewpub',$args);

  return $output;
}


function pagesetter_user_printpub($args)
{
  pagesetterPrintNoPNFrames( pagesetterGetPubFormatted('print', false, true, 'printpub',$args) );

  return true;
}


function pagesetter_user_dumppub($args)
{
  echo pagesetterGetPubFormatted('print', false, true, 'dumppub',$args);

  return true;
}


function pagesetter_user_xmlpub($args)
{
  header("Content-type: text/xml");
  echo pagesetterGetPubFormatted('xml', false, false, 'printpub',$args);

  return true;
}


function pagesetter_user_preview()
{
  if (!pnModAPILoad('pagesetter', 'edit'))
    return pagesetterErrorPage(__FILE__, __LINE__, 'Failed to load Pagesetter edit API');

  $result = pnModAPIFunc('pagesetter', 'edit', 'getFormattedPreview',
                         array());

  if ($result === false)
    return pagesetterErrorAPIGet();

  return $result;
}


function pagesetter_user_sendpub()
{
  require_once "modules/pagesetter/forms/mailHandler.php";
  $handler = new MailHandler();

  if (!guppy_decode($handler))
  {
    $tid = pnVarCleanFromInput('tid');
    $pid = pnVarCleanFromInput('pid');

    if (!is_numeric($tid) && isset($tid)  ||  !is_numeric($pid) && isset($pid))
      return pagesetterErrorPage(null, null, _PGILLURL);

      // Check access
    if (!pnSecAuthAction(0, 'pagesetter::', "$tid:$pid:", ACCESS_READ))
      return pagesetterErrorPage(__FILE__, __LINE__, _PGNOAUTH);

    if (pnUserGetVar('uname') == '')
      return pagesetterErrorPage(null, null, _PGMAILMUSTBELOGGEDIN);

    if (!pnModAPILoad('pagesetter', 'user'))
      return pagesetterErrorPage(__FILE__, __LINE__, 'Failed to load Pagesetter user API');

    $pub = pnModAPIFunc('pagesetter', 
                        'user', 
                        'getPub', 
                        array( 'tid'    => $tid, 
                               'pid'    => $pid,
                               'format' => 'user' ));

    $title = $pub['core']['title'];

    $url = pnModURL('pagesetter', 'user', 'viewpub',
                    array('tid' => $tid, 'pid' => $pid));

    $mailData = array( 'mailFrom' => pnUserGetVar('email'),
                       'nameFrom' => pnUserGetVar('name'),
                       'subject'  => $title,
                       'text'     => "URL: $url" );

    $data = array( 'mail' => array( 'rows' => array( $mailData ) ) );

    $extra = array( 'tid' => $tid,
                    'pid' => $pid );

    guppy_open( array( 'specFile'    => 'modules/pagesetter/forms/mailSpec.xml',
                       'layoutFile'  => 'modules/pagesetter/forms/mailLayout.xml',
                       'data'        => $data,
                       'extra'       => $extra,
                       'options'     => array(),
                       'actionURL'   => pnModUrl('pagesetter','user','sendpub') ) );
  }

  return guppy_output();
}


function pagesetter_user_history()
{
  $tid = pnVarCleanFromInput('tid');
  $pid = pnVarCleanFromInput('pid');

  if (!pnModAPILoad('pagesetter', 'user'))
    return pagesetterErrorPage(__FILE__, __LINE__, 'Failed to load Pagesetter user API');

  if (!pnModAPILoad('pagesetter', 'edit'))
    return pagesetterErrorPage(__FILE__, __LINE__, 'Failed to load Pagesetter edit API');

  require_once "modules/pagesetter/forms/historyHandler.php";
  $handler = new historyHandler();

  if (!guppy_decode($handler))
  {
    if (!is_numeric($tid) && isset($tid)  ||  !is_numeric($pid) && isset($pid))
      return pagesetterErrorPage(null, null, _PGILLURL);

      // Check access at this point where the IDs are available
    if (!pnSecAuthAction(0, 'pagesetter::', "$tid::", pagesetterAccessEditor))
      return pagesetterErrorPage(__FILE__, __LINE__, _PGNOAUTH);

      // Get publication type information

    if (!pnModAPILoad('pagesetter', 'admin'))
      return pagesetterErrorPage(__FILE__, __LINE__, 'Failed to load Pagesetter admin API');

    $pubInfo = pnModAPIFunc('pagesetter', 'admin', 'getPubTypeInfo',
                             array('tid' => $tid) );

    if ($pubInfo === false)
      return pagesetterErrorAPIGet();

    $header = array('pubType' => $pubInfo['publication']['title'],
                    'tid'     => $tid,
                    'pid'     => $pid);

    $revisions = pnModAPIFunc( 'pagesetter', 'edit', 'getRevisions',
                               array('tid' => $tid,
                                     'pid' => $pid) );


    if ($revisions === false)
      return pagesetterErrorAPIGet();

    $extra = array();

    $data = array( 'historyHeader' => array( 'rows' => array($header) ),
                   'historyList'   => array( 'rows' => $revisions ) );

    guppy_open( array( 'specFile'    => 'modules/pagesetter/forms/historySpec.xml',
                       'layoutFile'  => 'modules/pagesetter/forms/historyLayout.xml',
                       'toolbarFile' => 'modules/pagesetter/forms/adminToolbar.xml',
                       'options'     => array(),
                       'data'        => $data,
                       'extra'       => $extra,
                       'actionURL'   => pnModUrl('pagesetter','user','history') ) );
  }

  return guppy_output();
}


?>