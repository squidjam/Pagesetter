//=============================================================================
// Stand alone link selector for Pagesetter
// (C) Jorn Lind-Nielsen
//=============================================================================

  // htmlArea 3.0 editor for access in selector window
var currentPagesetterEditor = null;

//=============================================================================
// External interface functions
//=============================================================================

  // onClick handler for "find publication" button in external program
function pagesetterFindPub(inputID, pagesetterURL)
{
  window.open(pagesetterURL+"&targetID="+inputID, "", "width=750,height=315,resizable");
}


function pagesetterFindPubHtmlArea30(editor, pagesetterURL)
{
    // Save editor for access in selector window
  currentPagesetterEditor = editor;

    // Inform publication selector of how it should paste the resulting HTML
  pagesetterURL += "&target=htmlArea30";
  
  window.open(pagesetterURL, "", "width=750,height=450,resizable");
}


//=============================================================================
// Paste link into parent input field
//=============================================================================

function pagesetterPasteLink(URLMode, HTMLMode, title, url, targetInputID, targetMode)
{
  var html = url;

  if (targetMode == 'htmlArea30')
  {
    var selectedHTML = window.opener.currentPagesetterEditor.getSelectedHTML();

    if (typeof selectedHTML != "undefined"  &&  selectedHTML != '')
    {
      title = selectedHTML;
    }
  }

    // Strip absolute part of URL if requested
  if (URLMode != "absolute")
  {
    var startPos = url.indexOf("index.php?");
    url = url.substr(startPos);
  }

    // Add <A> tag around url if requested
  if (HTMLMode == 'a')
  {
    html = "<a href=\"" + url + "\"/>" + title + "</a>";
  }
  else
    html = url;

    // Paste link data into original input/textarea element

  if (targetMode == 'htmlArea30')
  {
    window.opener.currentPagesetterEditor.focusEditor(); 
    window.opener.currentPagesetterEditor.insertHTML(html);
  }
  else
  {
      // Where to insert the calculate URL
    var targetInputElement = window.opener.document.getElementById(targetInputID);

    if (targetInputElement.tagName == 'INPUT')
    {
        // Simply overwrite value of input elements
      targetInputElement.value = html;
    }
    else if (targetInputElement.tagName == 'TEXTAREA')
    {
        // Try to paste into textarea - technique depends on browser (and Pagesetter framework)

      if (typeof document.selection != "undefined")
      {
        if (targetInputElement.style.display == 'none' &&           // Someone has hidden the original textarea
            typeof window.opener.editor_insertHTML != "undefined")  // ... and this function is defined => guess we are using htmlArea
        {
            // IE: using htmlArea for editing
          window.opener.editor_insertHTML(targetInputID, html);
        }
        else
        {
            // IE: Move focus to textarea (which fortunately keeps its current selection) and overwrite selection
          targetInputElement.focus();
          window.opener.document.selection.createRange().text = html;
        }
      }
      else if (typeof targetInputElement.selectionStart != "undefined")
      {
          // Mozilla: Get start and end points of selection and create new value based on old value
        var startPos = targetInputElement.selectionStart;
        var endPos = targetInputElement.selectionEnd;
        targetInputElement.value = targetInputElement.value.substring(0, startPos)
                                   + html
                                   + targetInputElement.value.substring(endPos, targetInputElement.value.length);
      } 
      else 
      {
          // Others: just append to the current value
        targetInputElement.value += html;
      }
    }
  }

  window.close();
}


