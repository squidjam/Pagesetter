<?php

// This function is used to render the admin interface of the extra type parameter
// for the "Relation" plugin. It returns HTML that allows the admin to select
// a publication type ID.

// Experimental!

function typeextra_relation_render($args)
{
  // Fetch previous data
  $typeData = explode(':',$args['typeData'],9);
  list($tid,$ftid,$targetTid,$targetField,$oldTargetTid,$oldTargetField,$style,$popup,$filter) = $typeData;
  
  // echo $args['typeData'];

  if (!pnModAPILoad('pagesetter', 'admin'))
    return pagesetterErrorPage(__FILE__, __LINE__, 'Failed to load Pagesetter admin API');

  // Fetch all publication typs
  $pubTypes = pnModAPIFunc('pagesetter', 'admin', 'getPublicationTypes');

  if ($pubTypes === false) 
    return pagesetterErrorAPIGet();

  // Generate HTML for a <select> element based on the pubtype list and the
  // currently selected value.

  $html = "<label for=\"typeextra_publication_targetTid\">" . _PGREL_PUBLICATION_SELECT . "</label>: <select id=\"typeextra_publication_targetTid\" name=\"typeextra_publication_targetTid\" onChange=\"d.updateOptions(this, 'typeextra_publication_targetField')\">\n";
  
  if ($targetTid == '' && count($pubTypes) > 0)
  	$targetTid = $pubTypes[0]['id'];
  	
  foreach ($pubTypes as $pubType)
  {
  	// FIXME: Check permissions
    if ($pubType['id'] == $targetTid)
      $selected = ' selected="1"';
    else
      $selected = '';

    $html .= "<option value=\"$pubType[id]\"$selected>" . pnVarPrepForDisplay($pubType['title']) . "</option>\n";
  }

  $html .= "</select><br />\n";

  //  echo $html;
  // Generate HTML for a <select> element based on the relations fields for the pubtypes and the
  // currently selected value.
  
    // Get all relation fields (I get them directly from the database)
  list ($dbconn) = pnDBGetConn();
  $pntable = pnDBGetTables();
  
  $pubFieldsTable = $pntable['pagesetter_pubfields'];
  $pubFieldsColumn = &$pntable['pagesetter_pubfields_column'];

  $sql = "SELECT $pubFieldsColumn[id],
                 $pubFieldsColumn[title],
                 $pubFieldsColumn[tid]
          FROM   $pubFieldsTable
          WHERE  $pubFieldsColumn[type] = 'relation'
          ORDER BY $pubFieldsColumn[lineno]";

  $result = $dbconn->execute($sql);

  if ($dbconn->errorNo() != 0)
    return  '"getPubFields" failed: '
                                                  . $dbconn->errorMsg() . " while executing: $sql";

  $pubFields = array();
  
  for (; !$result->EOF; $result->MoveNext())
  {
    $pubFields[$result->fields[2]][] = $result->fields;
  }
  
  if ('' == $targetTid)
    $readonly = ' readonly="1"';
  else
    $readonly = '';

  $html .= "<label for=\"typeextra_publication_targetField\">" . _PGREL_FIELD_SELECT . "</label>: <select id=\"typeextra_publication_targetField\" name=\"typeextra_publication_targetField\"$readonly>\n";

  if ('' == $targetField)
    $selected = ' selected="1"';
  else
    $selected = '';

  $html .= "<option value=\"\"$selected>- " . _NONE . " -</option>\n";

  if (array_key_exists($targetTid,$pubFields))
  {
    foreach ($pubFields[$targetTid] as $field)
    {
      if ($field[0] == $targetField)
        $selected = ' selected="1"';
      else
        $selected = '';

      $html .= "<option value=\"$field[0]\"$selected>" . pnVarPrepForDisplay($field[1]) . "</option>\n";
    }
  }

  $html .= "</select><br />\n";
  
  // Select style of input field
  
  $html .= "<label for=\"typeextra_publication_style\">" . _PGREL_STYLE_SELECT . "</label>: <select id=\"typeextra_publication_style\" name=\"typeextra_publication_style\">\n";

  
  for ($styleoptions = array(_PGREL_STYLE_SELECTLIST,_PGREL_STYLE_ADVANCEDSELECT,_PGREL_STYLE_CHECKBOX,_PGREL_STYLE_HIDDEN);$option=each($styleoptions);) {
  	if ($option[0] == $style)
  	  $selected = ' selected="1"';
  	else
  	  $selected = '';
  	  
  	$html .= "<option value=\"$option[0]\"$selected>$option[1]</option>\n";
  }
  
  $html .= "</select>\n";
  
  $html .= "<label for=\"typeextra_publication_popup\">" . _PGREL_STYLE_ASPOPUP . "</label>: <input type=\"checkbox\" id=\"typeextra_publication_popup\" name=\"typeextra_publication_popup\" value=\"1\"" . ( $popup ? " checked=\"1\"" : "" ) . " /><br /><br />\n";
  
  $html .= "<label for=\"typeextra_publication_filter\">" . _PGREL_FILTER_INPUT . "</label>: <input type=\"text\" name=\"typeextra_publication_filter\" id=\"typeextra_publication_filter\" size=\"30\" maxlength=\"255\" value=\"$filter\" />\n";
  
  // Save old data in hidden fields.
  $html .= "<input type=\"hidden\" id=\"typeextra_publication_oldTid\" value=\"" . ((int)$oldTargetTid = -1 ? $targetTid : $oldTargetTid) . "\" /> \n";
  $html .= "<input type=\"hidden\" id=\"typeextra_publication_oldFieldId\" value=\"" . ((int)$oldTargetField = -1 ? $targetField : $oldTargetField) . "\" /> \n";
  
  // Save field id and type id
  $html .= "<input type=\"hidden\" id=\"typeextra_publication_tid\" value=\"$tid\" /> \n";
  $html .= "<input type=\"hidden\" id=\"typeextra_publication_ftid\" value=\"$ftid\" /> \n";
  
  // Add a javascript which changes the options of the second select field depending on 
  // the selected tid
  
  $html .= " 
<script>
	var d = new dynamicSelect();

	d.addSelect('typeextra_publication_targetTid');
  ";
  
  foreach ($pubFields as $pub => $fields) {
  	if (empty ($pub))
  		continue;
  	$html .= "d.selects['typeextra_publication_targetTid'].addOption('$pub'); \n";
    $html .= "d.selects['typeextra_publication_targetTid'].options['$pub'].createOption(' - "._NONE." - ',''); \n";
  	foreach ($fields as $field) {
  		$html .= "d.selects['typeextra_publication_targetTid'].options['$pub'].createOption('$field[1]','$field[0]'); \n";
  	}
  }
  $html .= "

	function dynamicSelect()
	{
	  this.selects = new Array();
	  this.addSelect = function(name)
		{
		  this.selects[name] = new selectObj();
		}



	 this.updateOptions = function(source, target)
	 {
	  var form = source.form;
	  var target = form.elements[target];
	  var value = source.options[source.selectedIndex].value;
  
	  while(target.options.length) target.remove(0);
  
	  if(!this.selects[source.name].options[value])
	  {
	   //alert('Invalid selection.'); //For debugging while you set it up
	   return;
	  }
  
	  var data = this.selects[source.name].options[value].options;
  
	  for(var x=0; x<data.length; x++)
	  {
	   try
	   {
	    target.add(data[x]);
	   }
	   catch(e)
	   {
	    target.add(data[x], null);
	   }
	  }
  
	  target.selectedIndex = 0;
	 }

	}



	function selectObj()
	{
	 this.options = new Array();
 
	 this.addOption = function(value)
	 {
	  this.options[value] = new optionObj();
	 }
	}



	function optionObj()
	{
	 this.options = new Array();
 
	 this.createOption = function(name, value)
	 {
	  this.options[this.options.length] = new Option(name, value);
	 }
	}
  </script>
  ";

  // VERY IMPORTANT
  // Implement a JavaScript function that reads the selected publication type ID
  // and returns. The name of the function "typeextra_submit" is required by the
  // surrounding code.
  $html .= "
<script>
function typeextra_submit()
{
  var targetTid = document.getElementById('typeextra_publication_targetTid');
  var targetField = document.getElementById('typeextra_publication_targetField');
  var tid = document.getElementById('typeextra_publication_tid');
  var ftid = document.getElementById('typeextra_publication_ftid');
  var oldTid = document.getElementById('typeextra_publication_oldTid');
  var oldFieldId = document.getElementById('typeextra_publication_oldFieldId');
  var style = document.getElementById('typeextra_publication_style');		
  var popup = document.getElementById('typeextra_publication_popup');	
  var popupchecked = (popup.checked) ? 1 : 0;	
  var filter = document.getElementById('typeextra_publication_filter');		
  return tid.value + ':' + ftid.value + ':' + targetTid.value + ':' + targetField.value + ':' + oldTid.value + ':' + oldFieldId.value + ':' + style.value + ':' + popupchecked + ':' + filter.value;
}
</script>
";
  return $html;
}

?>