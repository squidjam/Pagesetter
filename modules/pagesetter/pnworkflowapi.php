<?php
// $Id: pnworkflowapi.php,v 1.25 2006/07/12 21:06:57 jornlind Exp $
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
// but WIthOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// To read the license please visit http://www.gnu.org/copyleft/gpl.html
// =======================================================================

require_once("modules/pagesetter/common.php");
require_once("modules/pagesetter/common-edit.php");


class WorkflowState
{
  function WorkflowState($id, $title, $description)
  {
    $this->id          = $id;
    $this->title       = $title;
    $this->description = $description;
  }

  function getID()
  {
    return $this->id;
  }


  function getTitle()
  {
    return $this->title;
  }


  function getDescription()
  {
    return $this->description;
  }

  
  var $id;
  var $title;
  var $description;
}



class Workflow
{
  function Workflow($id, $title, &$description, &$states, &$actions, &$configurations)
  {
    $this->id             = $id;
    $this->title          = $title;
    $this->description    = $description;
    $this->configurations = $configurations;

      // Convert state array into mapping from state ID to state object instance
    $this->stateMap = array();
    foreach ($states as $state)
    {
      $stateObj = new WorkflowState($state['id'], $state['title'], $state['description']);
      $this->stateMap[$state['id']] = $stateObj;
    }

    $this->actions = $actions;
  }


  function getID()
  {
    return $this->id;
  }


  function getTitle()
  {
    return $this->title;
  }


  function getDescription()
  {
    return $this->description;
  }

  
  function getState($name)
  {
    if (!isset($this->stateMap[$name]))
      return pagesetterErrorAPI(__FILE__, __LINE__, "Unknown state name '$name' in workflow '" . $this->getTitle() ."'");

    return $this->stateMap[$name];
  }


  function getStates()
  {
    return $this->stateMap;
  }


  function getEnabledActions($state, $tid, $pid, &$env)
  {
    $enabledActions = array();

    foreach ($this->actions as $action)
      if ($this->actionEnabled($action, $state, $tid, $pid, $env))
        $enabledActions[] = $action;

    return $enabledActions;
  }


  function actionEnabled($action, $state, $tid, $pid, &$env)
  {
    //echo "Check $state vs. $action[state]<br>\n";

      // Check state
    if ($action['state'] != $state)
      return false;

      // Check permission
    $parser = new PagesetterPermissionParser($env);

    $ok = $parser->evaluate($action['permission']);
    return $ok;
  }


  function executeAction($actionID, $stateID, &$core, &$pubData)
  {
      // Locate action
    $enabledAction = null;
    $env = pagesetterGetPermissionEnv($core);

    foreach ($this->actions as $action)
      if ($action['id'] == $actionID  &&  $this->actionEnabled($action, $stateID, $core['tid'], $core['pid'], $env))
        $enabledAction = $action;

    if ($enabledAction == null)
      return pagesetterErrorAPI(__FILE__, __LINE__, "Action '$actionID' not enabled in state '$stateID'");

    if (!pnModAPILoad('pagesetter', 'edit'))
      return pagesetterErrorAPI(__FILE__, __LINE__, 'Failed to load Pagesetter edit API');

    if (!pnModAPILoad('pagesetter', 'user'))
      return pagesetterErrorAPI(__FILE__, __LINE__, 'Failed to load Pagesetter user API');

    $tid = $core['tid'];
    $pid = $core['pid'];
    $id  = $core['id'];

      // Update "next state" if required
    if (isset($enabledAction['nextState']))
      $pubData['core_approvalState'] = $enabledAction['nextState'];

    $result = $this->executeOperations($enabledAction['operations'], $core, $pubData);
    if ($result != pagesetterWFOperationOk)
      return $result;

      // Update "online" if required - and do it after the operations so they won't overwrite the result
    if (isset($enabledAction['online']))
    {
      $ok = pnModAPIFunc('pagesetter', 'edit', 'updateOnlineStatus',
                         array('tid' => $tid,
                               'pid' => $pid,
                               'id'  => $id,
                               'online' => $enabledAction['online']));
      if (!$ok)
        return false;
    }

      // Make sure changes are visible.
    pagesetterSmartyClearCache($tid, $pid);

    return true;
  }


  function executeOperations($operations, &$core, &$pubData)
  {
    foreach ($operations as $operation)
    {
      $result = $this->executeOperation($operation, $core, $pubData);
      if ($result != pagesetterWFOperationOk)
        return $result;
    }

    return pagesetterWFOperationOk;
  }


  function executeOperation($operation, &$core, &$pubData)
  {
    $operationName = $operation['name'];

      // Locate custom/standard operation file
    
    $operationFilename = "modules/pagesetter/workflows/custom/operations/$operationName.php";
    if (!is_readable($operationFilename))
      $operationFilename = "modules/pagesetter/workflows/standard/operations/$operationName.php";

    if (!is_readable($operationFilename))
      return pagesetterErrorAPI(__FILE__, __LINE__, "Unknown operation '$operationName'");


      // Convert $-values in parameters to corresponding setting

    $parameters = &$operation['parameters'];
    $settings = pnModAPIFunc('pagesetter', 'workflow', 'getSettings',
                             array('workflow' => $this->id,
                                   'tid'      => $core['tid']) );

    foreach ($parameters as $name => $value)
      if (substr($value,0,1) == '$')
      {
        $settingName = substr($value,1);
        $parameters[$name] = $settings[$settingName];
      }
      else
        $parameters[$name] = $value;

      // Load the file and call operation function

    require_once($operationFilename);

    $operationFunction = "pagesetter_operation_$operation[name]";
    if (function_exists($operationFunction))
      $ok = $operationFunction($pubData, $core, $parameters);
    else
      return pagesetterErrorAPI(__FILE__, __LINE__, "Unknown operation function '$operationFunction'");

    return $ok;
  }


  function validate()
  {
    return $this->validateActions();
  }


  function validateActions()
  {
    foreach ($this->actions as $action)
    {
      $stateName = $action['state'];
      
      if ($stateName != null)
      {
        if (!isset($this->stateMap[$stateName]))
          return pagesetterErrorAPI(__FILE__, __LINE__, "Unknown state name '$stateName' in action '" . $action['title'] ."'");
      }

      $nextStateName = pagesetter_fetchAttribute($action, 'nextState');
      
      if (isset($nextStateName))
      {
        if (!isset($this->stateMap[$nextStateName]))
          return pagesetterErrorAPI(__FILE__, __LINE__, "Unknown next-state name '$nextStateName' in action '" . $action['title'] ."'");
      }

      foreach ($action['operations'] as $operation)
      {
        if (isset($operation['parameters']['NEXTSTATE']))
        {
          $stateName = $operation['parameters']['NEXTSTATE'];
          if (!isset($this->stateMap[$stateName]))
            return pagesetterErrorAPI(__FILE__, __LINE__, "Unknown state name '$stateName' in action '" . $action['title']
                                                          . "' -  operation '$operation[name]'");
        }
      }
    }

    return true;
  }


  function getConfigurationsForGuppy()
  {
    $fieldSpec = array();
    $fieldLayout = array();
    $first = true;

    foreach ($this->configurations as $configuration)
    {
      switch ($configuration['type'])
      {
        case 'string':
          $specType = 'string';
          $viewType = 'string';
        break;

        case 'text':
          $specType = 'string';
          $viewType = 'text';
        break;

        case 'html':
          $specType = 'string';
          $viewType = 'html';
        break;

        default:
          $specType = $configuration['type'];
          $viewType = null;
        break;
      }

      $width  = (!empty($configuration['width']) ? $configuration['width'] : 300);
      $height = (!empty($configuration['height']) ? $configuration['height'] : null);

      $spec = array('kind'  => 'input',
                    'type'  => $specType,
                    'name'  => $configuration['id'],
                    'title' => $configuration['title'],
                    'inUse' => true);

      $layout =  array( array('kind'   => 'title',
                              'title'  => $configuration['title']),
                        array('kind'   => 'input',
                              'width'  => $width,
                              'height' => $height,
                              'view'   => $viewType,
                              'name'   => $configuration['id']) );

      if ($first)
        $layout[1]['initialFocus'] = true;
      $first = false;

      $fieldSpec[$configuration['id']] = $spec;
      $fieldLayout[] = $layout;
    }

    return array('fieldSpec'   => $fieldSpec,
                 'fieldLayout' => $fieldLayout);
  }

  var $id;
  var $title;
  var $description;
  var $stateMap;
  var $actions;
}


function pagesetter_workflowapi_getWorkflows($args)
{
  $workflows = array();

  pagesetterGetWorkflows('modules/pagesetter/workflows/custom', $workflows);
  pagesetterGetWorkflows('modules/pagesetter/workflows/standard', $workflows);

  return $workflows;
}


function pagesetterGetWorkflows($dir, &$workflows)
{
  $dh = opendir($dir);

    // Scan directory for all .xml files

  while (($filename=readdir($dh)) !== false)
  {
    if (substr($filename,-4) == '.xml')
    {
      $workflowName = str_replace('.xml', '', $filename);
      $workflow = pnModAPIFunc( 'pagesetter', 'workflow', 'load', array('workflow' => $workflowName) );
      
      if ($workflow !== false)
        $workflows[$workflow->getID()] = $workflow;
    }
  }

  closedir($dh);
}


function pagesetter_workflowapi_load($args)
{
  static $loadedWorkflows = array();

  if (!isset($args['workflow']))
    return pagesetterErrorApi(__FILE__, __LINE__, "Missing argument 'workflow' in 'pagesetter_workflowapi_load'");

  $workflowName = $args['workflow'];

  if (isset($loadedWorkflows[$workflowName]))
    return $loadedWorkflows[$workflowName];

  $filename = "modules/pagesetter/workflows/custom/$workflowName.xml";
  if (!is_readable($filename))
    $filename = "modules/pagesetter/workflows/standard/$workflowName.xml";

  if (!is_readable($filename))
    return pagesetterErrorApi(__FILE__, __LINE__, _PGWF_FILENOTREADABLE . $workflowName);

  return $loadedWorkflows[$workflowName] = pagesetterParseXMLWorkflow($filename, $workflowName);
}


function pagesetter_workflowapi_getEnabledActions($args)
{
  if (!isset($args['workflow']))
    return pagesetterErrorApi(__FILE__, __LINE__, "Missing argument 'workflow' in 'pagesetter_workflowapi_getEnabledActions'");

  $workflowName = $args['workflow'];
  $state        = $args['state'];
  $tid          = $args['tid'];
  $pid          = $args['pid'];

  $workflow = pnModAPIFunc( 'pagesetter', 'workflow', 'load', array('workflow' => $workflowName) );
  if ($workflow === false)
    return false;

  $env = pagesetterGetPermissionEnv($core);
  return $workflow->getEnabledActions($state, $tid, $pid, $env);
}


function pagesetter_workflowapi_executeAction($args)
{
  if (!isset($args['workflow']))
    return pagesetterErrorApi(__FILE__, __LINE__, "Missing argument 'workflow' in 'pagesetter_workflowapi_executeAction'");
  if (!isset($args['action']))
    return pagesetterErrorApi(__FILE__, __LINE__, "Missing argument 'action' in 'pagesetter_workflowapi_executeAction'");
  if (!isset($args['core']))
    return pagesetterErrorApi(__FILE__, __LINE__, "Missing argument 'core' in 'pagesetter_workflowapi_executeAction'");
  if (!isset($args['pubData']))
    return pagesetterErrorApi(__FILE__, __LINE__, "Missing argument 'pubData' in 'pagesetter_workflowapi_executeAction'");

  $workflowName = $args['workflow'];
  $state        = $args['state'];
  $action       = $args['action'];
  $core         = $args['core'];
  $pubData      = $args['pubData'];


  if (!pnModAPILoad('pagesetter', 'admin'))
    return pagesetterErrorApi(__FILE__, __LINE__, 'Failed to load Pagesetter admin API');

  $pubInfo = pnModAPIFunc( 'pagesetter', 'admin', 'getPubTypeInfo',
                           array('tid' => $core['tid']) );
  if ($pubInfo === false)
    return false;

  if (pagesetterHasTopicAccess($pubInfo, $pubData['core_topic'], 'write'))
  {
    pnModApiFunc('pagesetter','edit','startWorkflow', array());

    $workflow = pnModAPIFunc( 'pagesetter', 'workflow', 'load', array('workflow' => $workflowName) );
    if ($workflow === false)
      return false;

    $ok = $workflow->executeAction($action, $state, $core, $pubData);

    pnModApiFunc('pagesetter','edit','endWorkflow', array());
  }
  else
  {
    pagesetterErrorApi(__FILE__, __LINE__, 'You do not have write access to the selected topic');
    $ok = pagesetterWFOperationError;
  }

  return $ok;
}


function pagesetter_workflowapi_getSettings($args)
{
  if (!isset($args['workflow']))
    return pagesetterErrorApi(__FILE__, __LINE__, "Missing argument 'workflow' in 'pagesetter_workflowapi_getSettings'");
  if (!isset($args['tid']))
    return pagesetterErrorApi(__FILE__, __LINE__, "Missing argument 'tid' in 'pagesetter_workflowapi_getSettings'");

  $workflowName = $args['workflow'];
  $tid          = $args['tid'];

  list($dbconn) = pnDBGetConn();
  $pntable = pnDBGetTables();

  $wfcfgTable = $pntable['pagesetter_wfcfg'];
  $wfcfgColumn = $pntable['pagesetter_wfcfg_column'];

  $sql = "SELECT
            $wfcfgColumn[setting],
            $wfcfgColumn[value]
          FROM
            $wfcfgTable
          WHERE
            $wfcfgColumn[workflow] = '" . pnVarPrepForStore($workflowName) . "' AND
            $wfcfgColumn[tid] = '" . pnVarPrepForStore($tid) . "'";

  $result = $dbconn->execute($sql);

  if ($dbconn->errorNo() != 0)
    return pagesetterErrorApi(__FILE__, __LINE__, '"workflow_getSettings" failed: ' 
                                                  . $dbconn->errorMsg() . " while executing: <pre>$sql</pre>"); 
  
  $settings = array();

  for (; !$result->EOF; $result->MoveNext())
  {
    $settings[$result->fields[0]] = $result->fields[1];
  }

  $result->Close();

  return $settings;
}


function pagesetter_workflowapi_setSettings($args)
{
  if (!isset($args['workflow']))
    return pagesetterErrorApi(__FILE__, __LINE__, "Missing argument 'workflow' in 'pagesetter_workflowapi_getSettings'");
  if (!isset($args['tid']))
    return pagesetterErrorApi(__FILE__, __LINE__, "Missing argument 'tid' in 'pagesetter_workflowapi_getSettings'");

  $workflowName = $args['workflow'];
  $tid          = $args['tid'];
  $settings     = $args['settings'];

  list($dbconn) = pnDBGetConn();
  $pntable = pnDBGetTables();

  $wfcfgTable = $pntable['pagesetter_wfcfg'];
  $wfcfgColumn = $pntable['pagesetter_wfcfg_column'];

  foreach ($settings as $setting => $value)
  {
    $sql = "REPLACE INTO $wfcfgTable
            (
              $wfcfgColumn[workflow],
              $wfcfgColumn[tid],
              $wfcfgColumn[setting],
              $wfcfgColumn[value]
            )
            VALUES
            (
              '" . pnVarPrepForStore($workflowName) . "',
              '" . pnVarPrepForStore($tid) . "',
              '" . pnVarPrepForStore($setting) . "',
              '" . pnVarPrepForStore($value) . "'
            )";

    $result = $dbconn->execute($sql);

    if ($dbconn->errorNo() != 0)
      return pagesetterErrorApi(__FILE__, __LINE__, '"workflow_getSettings" failed: ' 
                                                    . $dbconn->errorMsg() . " while executing: $sql"); 
  }
  
  return true;
}


// =======================================================================
// Permission parser
// =======================================================================

/* TEST
echo "<pre>\n";
pagesetterParsePermission("owner and (author or owner) and author");
pagesetterParsePermission("owner or author and owner");
echo "</pre>\n";

function pagesetterParsePermission($expr)
{
  $parser = new PagesetterPermissionParser();

  $result = $parser->evaluate($expr);

  echo "Res: $result\n";
}
*/

function pagesetterGetPermissionEnv(&$core)
{
  $env = array('isOwner'    => $core['creatorID'] == pnUserGetVar('uid'),
               'permission' => pagesetterGetCurrentPermission($core['tid'], $core['pid']) );
  return $env;
}


class PagesetterPermissionParser
{
  var $expr;
  var $token;
  var $env;

  function PagesetterPermissionParser($env)
  {
    $this->env = $env;
  }


  function evaluate($expr)
  {
    $this->expr = $expr;
    $this->nextToken();
    return $this->evaluateExpr1();
  }


  function evaluateExpr1()
  {
    $left = $this->evaluateExpr2();

    $t = $this->token;
    if ($t == null)
      return $left;

    if ($t == 'or')
    {
      $this->nextToken();
      $right = $this->evaluateExpr1();
      return $left || $right;
    }

    return $left;
  }


  function evaluateExpr2()
  {
    $left = $this->evaluateAtom();

    $t = $this->token;
    if ($t == null)
      return $left;

    if ($t == 'and')
    {
      $this->nextToken();
      $right = $this->evaluateExpr2();
      return $left && $right;
    }

    return $left;
  }


  function evaluateAtom()
  {
    $t = $this->token;

    if ($t == '(')
    {
      $this->nextToken();
      $result = $this->evaluateExpr1();
      $t = $this->token;
      if ($t != ')')
        echo "ERROR - missing ')'";
      $this->nextToken();

      return $result;
    }
    else if ($t == '!')
    {
      $this->nextToken();
      return !$this->evaluateExpr1();
    }
    else if ($t == 'owner')
    {
      $this->nextToken();
      return $this->env['isOwner'];
    }
    else if ($t == 'author')
    {
      $this->nextToken();
      return $this->env['permission'] >= pagesetterAccessAuthor;
    }
    else if ($t == 'editor')
    {
      $this->nextToken();
      return $this->env['permission'] >= pagesetterAccessEditor;
    }
    else if ($t == 'moderator')
    {
      $this->nextToken();
      return $this->env['permission'] >= pagesetterAccessModerator;
    }
    else
      echo "UNKNOWN-atom - $t\n";
  }


  function nextToken()
  {
    $hasWhiteSpace = false;

    do
    {
      if ($this->expr == '')
        return null;

      if (preg_match('/and|or|\(|\)|!|\w+|(\s+)/', $this->expr, $out) > 0)
      {
        $hasWhiteSpace = array_key_exists(1,$out)  &&  count($out[1]) > 0;
        $match = $out[0];

        $l = strlen($match);
        $this->expr = substr($this->expr, $l);

        if (!$hasWhiteSpace)
        {
          $this->token = $match;
          return $match;
        }
      }
      else
      {
        echo "UNKNOWN - " . $this->expr . "\n";
      }
    }
    while ($hasWhiteSpace);
  }
}


// =======================================================================
// XML Workflow parser
// =======================================================================

function pagesetterParseXMLWorkflow($filename, $name)
{
  global $pagesetterXMLWorkflow;
  $pagesetterXMLWorkflow = array( 'state' => 'initial' );

  $xmlData = file_get_contents($filename);

  // Instantiate parser
  $parser = xml_parser_create();
  xml_set_element_handler($parser, "pagesetterWorkflowStartElementHandler", "pagesetterWorkflowEndElementHandler");
  xml_set_character_data_handler($parser, "pagesetterWorkflowCharacterHandler");

  if (!xml_parse($parser, $xmlData, true))
  {
    pagesetterErrorAPI(__FILE__, __LINE__, 
                       "Unable to parse XML Pagesetter workflow (line "
                       . xml_get_current_line_number($parser) . ","
                       . xml_get_current_column_number($parser) . "): "
                       . xml_error_string($parser));
    xml_parser_free($parser);    
    return false;
  }

  xml_parser_free($parser);

  //print_r($pagesetterXMLWorkflow);

  if ($pagesetterXMLWorkflow['state'] == 'error')
    return pagesetterErrorAPI(__FILE__, __LINE__, $pagesetterXMLWorkflow['errorMessage']);

  $workflow = new Workflow($name,
                           $pagesetterXMLWorkflow['workflow']['title'],
                           $pagesetterXMLWorkflow['workflow']['description'],
                           $pagesetterXMLWorkflow['states'],
                           $pagesetterXMLWorkflow['actions'],
                           $pagesetterXMLWorkflow['configurations']);

  if (!$workflow->validate())
    return false;

  return $workflow;
}


function pagesetterWorkflowStartElementHandler($parser, $name, $attribs)
{
  global $pagesetterXMLWorkflow;

  $state = &$pagesetterXMLWorkflow['state'];

  if ($state == 'initial')
  {
    if ($name == 'WORKFLOW')
    {
      $state = 'workflow';
      $pagesetterXMLWorkflow['workflow'] = array();
    }
    else
    {
      $state = 'error';
      $pagesetterXMLWorkflow['errorMessage'] = pagesetterXMLErrorUnexpected($name, $state);
    }
  }
  else if ($state == 'workflow')
  {
    if ($name == 'TITLE'  ||  $name == 'DESCRIPTION')
    {
      $pagesetterXMLWorkflow['value'] = '';
    }
    else if ($name == 'CONFIGURATION')
    {
      $state = 'configuration';
      $pagesetterXMLWorkflow['configurations'] = array();
    }
    else if ($name == 'STATES')
    {
      $state = 'states';
      $pagesetterXMLWorkflow['states'] = array();
    }
    else if ($name == 'ACTIONS')
    {
      $state = 'actions';
      $pagesetterXMLWorkflow['actions'] = array();
    }
    else
    {
      $pagesetterXMLWorkflow['errorMessage'] = pagesetterXMLErrorUnexpected($name, $state);
      $state = 'error';
    }
  }
  else if ($state == 'configuration')
  {
    if ($name == 'SETTING')
    {
      $pagesetterXMLWorkflow['configurations'][] = array('id'     => pagesetter_fetchAttribute($attribs, 'ID'),
                                                         'title'  => pagesetter_fetchAttribute($attribs, 'TITLE'),
                                                         'type'   => pagesetter_fetchAttribute($attribs, 'TYPE'),
                                                         'width'  => pagesetter_fetchAttribute($attribs, 'WIDTH'),
                                                         'height' => pagesetter_fetchAttribute($attribs, 'HEIGHT'));
    }
    else
    {
      $pagesetterXMLWorkflow['errorMessage'] = pagesetterXMLErrorUnexpected($name, $state);
      $state = 'error';
    }
  }
  else if ($state == 'states')
  {
    if ($name == 'STATE')
    {
      $pagesetterXMLWorkflow['stateValue'] = array('id' => trim($attribs['ID']));
      $state = 'state';
    }
    else
    {
      $pagesetterXMLWorkflow['errorMessage'] = pagesetterXMLErrorUnexpected($name, $state);
      $state = 'error';
    }
  }
  else if ($state == 'state')
  {
    if ($name == 'TITLE'  ||  $name == 'DESCRIPTION')
    {
      $pagesetterXMLWorkflow['value'] = '';
    }
    else
    {
      $pagesetterXMLWorkflow['errorMessage'] = pagesetterXMLErrorUnexpected($name, $state);
      $state = 'error';
    }
  }
  else if ($state == 'actions')
  {
    if ($name == 'ACTION')
    {
      $pagesetterXMLWorkflow['action'] = array('id' => trim($attribs['ID']), 'operations' => array(), 'state' => null);
      $state = 'action';
    }
    else
    {
      $pagesetterXMLWorkflow['errorMessage'] = pagesetterXMLErrorUnexpected($name, $state);
      $state = 'error';
    }
  }
  else if ($state == 'action')
  {
    if ($name == 'TITLE'  ||  $name == 'DESCRIPTION'  ||  $name == 'PERMISSION'  ||  $name == 'STATE'  ||  $name == 'NEXTSTATE'
          ||  $name == 'ONLINE')
    {
      $pagesetterXMLWorkflow['value'] = '';
    }
    else
    if ($name == 'OPERATION')
    {
      $pagesetterXMLWorkflow['value'] = '';
      $pagesetterXMLWorkflow['operationParameters'] = $attribs;
    }
    else
    {
      $pagesetterXMLWorkflow['errorMessage'] = pagesetterXMLErrorUnexpected($name, $state);
      $state = 'error';
    }
  }
  else if ($state == '')
  {
    if ($name == '')
    {
      $state = '';
    }
    else
    {
      $pagesetterXMLWorkflow['errorMessage'] = pagesetterXMLErrorUnexpected($name, $state);
      $state = 'error';
    }
  }
  else if ($state == 'error')
    ; // ignore
  else
  {
    $pagesetterXMLWorkflow['errorMessage'] = _PGWF_STATEERROR . " '$state' " . " '$name'";
    $state = 'error';
  }
}


function pagesetterWorkflowEndElementHandler($parser, $name)
{
  global $pagesetterXMLWorkflow;
  $state = &$pagesetterXMLWorkflow['state'];
  //echo "$state: end $name<br>\n";

  if ($state == 'workflow')
  {
    if ($name == 'TITLE')
      $pagesetterXMLWorkflow['workflow']['title'] = $pagesetterXMLWorkflow['value'];
    else if ($name == 'DESCRIPTION')
      $pagesetterXMLWorkflow['workflow']['description'] = $pagesetterXMLWorkflow['value'];
  }
  else if ($state == 'configuration')
  {
    if ($name == 'CONFIGURATION')
      $state = 'workflow';
  }
  else if ($state == 'state')
  {
    if ($name == 'TITLE')
      $pagesetterXMLWorkflow['stateValue']['title'] = $pagesetterXMLWorkflow['value'];
    else if ($name == 'DESCRIPTION')
      $pagesetterXMLWorkflow['stateValue']['description'] = $pagesetterXMLWorkflow['value'];
    else if ($name == 'STATE')
    {
      $pagesetterXMLWorkflow['states'][] = $pagesetterXMLWorkflow['stateValue'];
      $pagesetterXMLWorkflow['stateValue'] = null;
      $state = 'states';
    }
  }
  else if ($state == 'action')
  {
    if ($name == 'TITLE')
      $pagesetterXMLWorkflow['action']['title'] = $pagesetterXMLWorkflow['value'];
    else if ($name == 'DESCRIPTION')
      $pagesetterXMLWorkflow['action']['description'] = $pagesetterXMLWorkflow['value'];
    else if ($name == 'PERMISSION')
      $pagesetterXMLWorkflow['action']['permission'] = trim($pagesetterXMLWorkflow['value']);
    else if ($name == 'STATE')
      $pagesetterXMLWorkflow['action']['state'] = trim($pagesetterXMLWorkflow['value']);
    else if ($name == 'OPERATION')
    {
      $pagesetterXMLWorkflow['action']['operations'][] = array('name'       => trim($pagesetterXMLWorkflow['value']),
                                                               'parameters' => $pagesetterXMLWorkflow['operationParameters']);
      $pagesetterXMLWorkflow['operation'] = null;
    }
    else if ($name == 'NEXTSTATE')
      $pagesetterXMLWorkflow['action']['nextState'] = trim($pagesetterXMLWorkflow['value']);
    else if ($name == 'ONLINE')
      $pagesetterXMLWorkflow['action']['online'] = trim($pagesetterXMLWorkflow['value']);
    else if ($name == 'ACTION')
    {
      $pagesetterXMLWorkflow['actions'][] = $pagesetterXMLWorkflow['action'];
      $pagesetterXMLWorkflow['action'] = null;
      $state = 'actions';
    }
  }
  else if ($state == 'actions')
  {
    if ($name == 'ACTIONS')
    {
      $state = 'workflow';
    }
  }
  else if ($state == 'states')
  {
    if ($name == 'STATES')
    {
      $state = 'workflow';
    }
  }

}


function pagesetterWorkflowCharacterHandler($parser, $data)
{
  global $pagesetterXMLWorkflow;
  //echo "($data)";

  if (array_key_exists('value',$pagesetterXMLWorkflow))
    $pagesetterXMLWorkflow['value'] .= $data;
  else
    $pagesetterXMLWorkflow['value'] = $data;
}


?>