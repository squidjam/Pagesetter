PostCalendar Import
===================

This is an example of how Pagesetter can replace PostCalendar for some
purposes. It can be used with a block that lists your upcoming calendar entries
but requires the "Pagesetter Archive" module for month based browsing. No
templates are supplied for main list of entries - only the list block is
supported.

1) Import your PostCalendar items via the "Tools - Import" menu entry, or 
   import the XML schema file in this directory. Edit the imported 
   "PostCalendar" publication type and make sure the default sorting is by 
   "startDate" - descending.

2) Copy all PostCalendar-*.html template files into your theme directory in
   "themes/YourTheme/templates/modules/pagesetter".

3) Insert the CSS styles in "styles.css" in your theme's style sheet.

4) Install the Pagesetter Archive module

5) Start the archive with a url pointing to the publication type ID of
   your calendar type. Beware that the current version (1.1) is hardwired
   for sorting descending - earliest entries last :-(.

6) Add a block with the upcomming events. Do this with a "Pagesetter list" 
   block. Set the filter expression to be "startDate:ge:@now" - this ensures 
   only the upcomming events show up.

Have fun =:o)
