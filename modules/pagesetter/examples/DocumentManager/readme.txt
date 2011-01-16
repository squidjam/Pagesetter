Document Manager Example
========================

This is an example of how Pagesetter can be used for a simple document
management system.

Installation:

1) Import the XML schema file (admin : pagesetter : tools : import).
   This will create the publication type named "Document Manager" and setup a few
   category items related to the system. You can edit this category afterwards
   to suit your own needs.

2) Copy all DocMan-*.html template files into your theme directory in
   "themes/YourTheme/templates/modules/pagesetter".

3) Insert the CSS styles in "styles.css" in your theme's style sheet.

4) Now point your browser to ".../index.php?module=pagesetter&tid=T" where T
   is the ID for the imported publication type.
