// =======================================================================
// Popup handling
// =======================================================================

var editthis =
{
};


editthis.menuClosed = function()
{
}


editthis.popup = function(element, evt, tid, pid)
{
  evt = (evt ? evt : (event ? event : null));
  if (evt == null)
    return true;

  var pos = getPositionOfElement(element);
  pos.left -= 20;
  pos.top -= 20;

  var pubInfoDiv = document.getElementById("pubInfoBox"+tid+"-"+pid);
  psmenu.openMenu(editthis, pubInfoDiv, pos);

  return true;
}


function handleOnClickEditThis(element, evt, tid, pid)
{
  editthis.popup(element, evt, tid, pid);
}

