
// Function for handling type selection changes
// When users selects a new type then this function is called.
// The purpose is to enable/disable the popup button that allows
// the user to add extra type information
function handleOnChangeTypeSelect(select, buttonId)
{
  // Lookup what to to with the selected type
  actionInfo = typeSelectAction[select.selectedIndex];

  // Fetch popup button

  // Disable or enable based on type information
  var button = document.getElementById(buttonId);
  if (actionInfo.enableButton)
  {
    button.disabled = false;
  }
  else
  {
    button.disabled = true;
  }
}


// Function for opening window with extra type information
function pagesetterOpenTypeExtra(selectId, hiddenId)
{
  // Lookup action information based on selected type
  var select = document.getElementById(selectId);
  var hidden = document.getElementById(hiddenId);
  var typeData = hidden.value;
  
  // alert(typeData);
  actionInfo = typeSelectAction[select.selectedIndex];

  // Create URL with ID of destination input where the extra
  // type information should be stored. The URL is stored
  // with "xxx" where the input ID should be inserted
  var url = actionInfo.popupUrl;
  url = url.replace(/xidx/, hiddenId);
  url = url.replace(/xtypex/, escape(typeData));

  window.open(url, "typeextra", "width=500,height=400,status=1");
}


// Function for pasting selected extra type information into
// parent window's hidden "typeextra" input field.
function handleOnTypeExtraSubmit(hiddenInputId)
{
  // Fetch extra information through a predefined function name
  // that the popup window *must* implement.
  var extraInfo = typeextra_submit();

  var hiddenInput = window.opener.document.getElementById(hiddenInputId);
  // alert(hiddenInputId);

  hiddenInput.value = extraInfo;

  return true;
}
