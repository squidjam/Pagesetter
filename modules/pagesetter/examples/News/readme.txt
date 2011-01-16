News Example
============

This is an example of how Pagesetter can be used for news handling.

Installation:

1) Import the XML schema file (admin : pagesetter : tools : import).
   This will create the publication type named "News" and setup a few category
   items related to news handling (News, Sports, Life Style etc.)

2) There is no need to install any templates. The field names for this example
   matches those in the tutorial, so you will be able to use the default news
   templates (News-list.html etc.)

3) Now add a Pagesetter list menu block. This block will generate a menu based
   on the category items. Base the menu block on you new publicaton type, the
   list field named "category", ignore the top level value, max indentation,
   and target class name, and use "newsMenu" as the CSS class name.

4) At first the menu won't look very interesting. So add the styles found in
   "style.css" to your theme's "style.css" file.

5) To show your news on the front page you should select Pagesetter as
   default module (admin : settings) and then select News as the default
   publication type (admin : pagesetter : configuration : general).

6) You can choose to restrict the fields that your authors are allowed to enter
   something into. To do so you must install a custom layout for your News
   type. The file "newFormLayout.xml" is such a layout file. It is an XML file
   that describes which fields to put where on the screen. You install it by 
     1) creating a directory named "News" in modules/pagesetter/publications and
     2) copy the file "newFormLayout.xml" into the new directory.
  Next time you create a publication you will only see a subset of the fields.

7) You can also choose to setup the HTMLArea editor with a few more features.
   Copy editorsetup.js into publications/PN-News
