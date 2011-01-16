<?php

// This input plugin realizes a nXm-relation. It presents a selection
// field of publications of a certain type (told via the extra info).

// TODO: filtering via the extra info

// Experimental!

class GuppyInput_relation extends GuppyInput
{
  var $tid;
  var $pid;
  var $ftid;
  var $targetTid;
  var $targetField;
  var $style;
  var $popup;
  var $filter;
  var $count=0;

  function parseTypeData()
  {
  	// echo "parseTypeData: " . $this->typeData;
  	list ($tid,$ftid,$targetTid,$targetField,$dummy,$dummy,$style,$popup,$filter) = explode (':',$this->typeData,9);

  	if ($tid !== '') {
  		$this->tid = (int)$tid;
  	} else unset($this->tid);
  	if ($ftid !== '') {
  		$this->ftid = (int)$ftid;
  	} else unset($this->ftid);
  	if ($targetTid !== '') {
  		$this->targetTid = (int)$targetTid;
  	} else unset($this->targetTid);
  	if ($targetField !== '') {
  		$this->targetField = (int)$targetField;
  	} else unset($this->targetField);
  	if ($style !== '') {
  		$this->style = (int)$style;
  	} else unset($this->style);
  	$this->popup = (bool)$popup;
	$this->filter = $filter;
  }
  
  function render($guppy)
  {  	
  	$this->parseTypeData();
  	
  	// echo "<pre>"; var_dump($this);echo "</pre>";
  	
    if (!pnModAPILoad('pagesetter','relations'))
      return _MODAPILOADFAILED;

    if (!isset($this->tid)) {
    	$this->tid = pnVarCleanFromInput('tid');
    }
    if (!isset($this->pid)) {
    	$this->pid = pnVarCleanFromInput('pid');
    	  if (!isset($this->pid)) {
    		$id = pnVarCleanFromInput('id');
    		if (isset($id)) 
    			$this->pid = pnModAPIFunc('pagesetter','relations','id2Pid',array('tid' => $this->tid,'id' => pnVarCleanFromInput('id')));
      }
    }
    if (!isset($this->targetTid))
    	return "No type ID for related publications";
    if (!isset($this->tid))
    	return "No type ID for own publication";

    return pnModAPIFunc('pagesetter', 'relations', 'getInputFieldHtml', 
                        array ('tid' => $this->tid, 
                               'fieldId' => $this->ftid, 
                               'targetTid' => $this->targetTid, 
                               'pid' => $this->pid, 
                               'name' => $this->name, 
                               'id' => $this->ID, 
                               'filter' => $this->filter, 
                               'style' => $this->style, 
                               'popup' => $this->popup, 
                               'title' => $this->title, 
                               'selected' => empty($this->value) ? null : explode(':',$this->value),
                               'readonly' => $this->readonly,
                               'width' => $this->width,
                               'height' => $this->height));
  }


  function decode()
  {
    if (isset($_POST[$this->name.'_hidden']))
      $this->value = $_POST[$this->name.'_hidden'];
    else if (isset($_POST[$this->name.'_to']))
      $this->value = implode(':',$_POST[$this->name.'_to']);
    else
      $this->value = implode(':',$_POST[$this->name]);

    /*
    $this->value = ($_POST[$this->name.'_hidden'] === '' 
                    ? ($_POST[$this->name] === '' 
                       ? '' 
                       : implode(':',$_POST[$this->name])) 
                    : $_POST[$this->name.'_hidden']);
     */
    //var_dump($_POST); var_dump($this->value); exit(0);
    return null;
  }


  function validate()
  {
    return true;
  }


  // ===[ Pagesetter interface ]==============================================

  function active()
  {
    return true;
  }

  function useExtraTypeInfo()
  {
    // Inform the framework about the fact that this plugin
    // uses extra type parameters
    return true;
  }
  
  function useFilterHandler()
  {
  	// Inform the framework about the fact that this plugin
  	// supports own filter generator
  	return true;
  }
  
  function useOrderByHandler()
  {
  	// Inform the framework about the fact that this plugin
  	// supports own order by generator
  	return false;
  }
  
  function getFilterSQL($operator,$value,$tableName,&$tableColumns)
  {
  	$this->parseTypeData();

	if (!pnModAPILoad ('pagesetter','relations'))
      return pagesetterErrorApi(__FILE__, __LINE__, 'Failed to load Pagesetter relations API');
      
        $alias = 'rel_table_field'.$this->ftid.'_'.$this->count++;

  	$pntable = pnModAPIFunc('pagesetter','relations','getRelationsTable',array ('sourceTid' => $this->tid, 'targetTid' => $this->targetTid, 'tableAlias' => $alias));
  	  	
  	$relationsTable = $pntable['pagesetter_relations'];
  	$relationsColumn = &$pntable['pagesetter_relations_column']; 
  	
  	$join = "LEFT JOIN $relationsTable AS $alias ON " .
                                "($relationsColumn[sourceTid] = '" . pnVarPrepForStore($this->tid) . "' " .
                             "AND $relationsColumn[sourceField] = '" . pnVarPrepForStore ($this->ftid) . "' " .
                             "AND $relationsColumn[sourcePid] = $tableName.$tableColumns[pid] " .
  			                 "AND $relationsColumn[targetTid] = '" . pnVarPrepForStore($this->targetTid) . "'";
  			                 
    if ($this->targetField != '')
      $join .=  " AND $relationsColumn[targetField] = '" . pnVarPrepForStore($this->targetField) . "'";
    
    $join .= ")";
    
    switch ($operator) {
    	  case 'eq':
    	  case 'rel':
    	    $sql = "$relationsColumn[targetPid] = '" . pnVarPrepForStore($value) . "'";
    	    break;
    	  case 'ne':
    	  case 'nrel':
    	    $sql = "$relationsColumn[targetPid] != '" . pnVarPrepForStore($value) . "'";
    	    break;
          case 'null':
            $sql = "$relationsColumn[targetPid] IS NULL";
            break;
          case 'notnull':
            $sql = "$relationsColumn[targetPid] IS NOT NULL";            
            break; 

    	  default: return pagesetterErrorApi(__FILE__, __LINE__, "Unknown filter operator '$operator'.");
    }
    
  	return compact('join','sql');
  }
  

  function OnPublicationCreated(&$pubData, $action)
  {
  	$this->parseTypeData();
  	
	$this->pid = $pubData['pid'];
	
	// echo "<pre>";print_r($this);print_r($pubData);echo "</pre>";
    
	if ($this->value !== '')
    {
      $targetPids = explode(':',$this->value);
	} 
    else 
      $targetPids = array(); 

	pnModAPILoad ('pagesetter', 'relations');
 	if (!pnModAPIFunc ('pagesetter', 'relations', 'setRelations', 
                       array ('tid' => $this->tid, 
                              'pid' => $this->pid, 
                              'fieldId' => $this->ftid, 
                              'targetTid' => $this->targetTid, 
                              'targetPids' => $targetPids, 
                              'targetField' => $this->targetField))) 
    {
      echo pagesetterErrorAPIGet();
      return;
    }
  }


  function OnPublicationUpdated(&$pubData, $action)
  {
  	return $this->OnPublicationCreated($pubData, $action);
  }
  
  function OnPublicationDeleted(&$pubData, $action)
  {
  	
  	$this->parseTypeData();
  	
	$pid = $pubData['pid'];

	pnModAPILoad ('pagesetter', 'relations');
 	if (!pnModAPIFunc ('pagesetter', 'relations', 'setRelations', array ('tid' => $this->tid, 'pid' => $pid, 'fieldId' => $this->ftid, 'targetTid' => $this->targetTid, 'targetPids' => array(), 'targetField' => $this->targetField))) {
  		return false;
 	}
  }
  
  function OnFieldUpdated($id, &$args)
  {
	list($tid,$ftid,$targetTid,$targetField,$oldTargetTid,$oldTargetField,$style,$popup,$filter) = explode(':',$args['typeData'],9);
	list($tid,$ftid) = explode (':',$id);
	
	if (!pnModAPILoad ('pagesetter','relations'))
    	return pagesetterErrorApi(__FILE__, __LINE__, 'Failed to load Pagesetter relations API');

	if (!pnModAPIFunc ('pagesetter','relations','updateRelations',array ('tid' => $tid, 'fieldId' => $ftid, 'targetTid' => $targetTid, 'targetFieldId' => $targetField, 'oldTargetTid' => $oldTargetTid, 'oldTargetFieldId' => $oldTargetField)))
		return false;
	
	$args['typeData'] = "$tid:$ftid:$targetTid:$targetField:-1:-1:$style:$popup:$filter";
	
	return true;
  }
  
  function OnFieldAdded($id,&$args)
  {
	list($dummy,$dummy,$targetTid,$targetField,$oldTargetTid,$oldTargetField,$style,$popup,$filter) = explode(':',$args['typeData'],9);
	list($tid,$ftid) = explode (':',$id);
	
	if (!pnModAPILoad ('pagesetter','relations'))
    	return pagesetterErrorApi(__FILE__, __LINE__, 'Failed to load Pagesetter relations API');

	if (!pnModAPIFunc ('pagesetter','relations','updateRelations',array ('tid' => $tid, 'fieldId' => $ftid, 'targetTid' => $targetTid, 'targetFieldId' => $targetField, 'oldTargetTid' => $oldTargetTid, 'oldTargetFieldId' => $oldTargetField)))
		return false;
	
	$args['typeData'] = "$tid:$ftid:$targetTid:$targetField:::$style:$popup:$filter";
		
	return true;
  }
  
  function OnFieldDeleted($id,&$args)
  {
    list($tid,$ftid,$targetTid,$targetField,$oldTargetTid,$oldTargetField) = explode(':',$args['typeData']);

	if (!pnModAPILoad ('pagesetter','relations'))
    	return pagesetterErrorApi(__FILE__, __LINE__, 'Failed to load Pagesetter relations API');
    if ($targetField != '') {
      if (!pnModAPIFunc('pagesetter','relations','convertToIndependent',array('tid' => $tid, 'fieldId' => $ftid, 'targetTid' => $targetTid, 'oldTargetFieldId' => $targetField)))
        return false;
    }
    if (!pnModAPIFunc('pagesetter','relations','removeRelations', array ('tid' => $tid, 'fieldId' => $ftid, 'targetTid' => $targetTid))) {
      return false;
    }
    return true;
  }

  function getTitle()
  {
    return 'Relation';
  }

  function getSqlType()
  {
    return 'INT';
  }
  
  function getSqlFormat()
  {
  	return NULL;
  }
  
  function getDefaultWidth()
  {
    return 300;
  }
  
  function getDefaultHeight()
  {
  	return NULL;
  }
  
}

?>
