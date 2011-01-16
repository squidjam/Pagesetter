  // Load various plugins on load of the setup script
HTMLArea.loadPlugin("ContextMenu");
HTMLArea.loadPlugin("CSS");


  // This function is called (if it exists) after the editor configuration is created,
  // but before the editor itself is created
function HTMLAreaConfigSetup(config)
{
    // Here you can call config.registerButton, change the toolbar, and much more ... see HTMLArea's own documentation
}


  // This is called with the editor right after it has been created
function HTMLAreaEditorSetup(editor)
{
    // Register the plugins
  editor.registerPlugin(ContextMenu);
  editor.registerPlugin(CSS, { combos: [{label:"CSS", options:{a:"classA", b:"classB"}}] } );
}
