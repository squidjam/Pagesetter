This directory is for Smarty templates.

Here you put your own templates. 

Templates must be named like "<publicationType>-<format>.html" where <publicationType> 
is the name of your publication (for instance "News") and format is an output format
from the following list:

"list"        : short view used in lists (used once for each publication in the list).
"list-header" : list header.
"list-footer" : list footter.
"full"        : full view of publication.
"print"       : like full but used when printing wihtout Postnuke frames.


The templates may refer to various fields in each publication. Either a core field 
refered as "{$core.NNN}" or a publication specific field "{$NNN}". User fields uses the
name specified for the publication. The core fields are:

author
id
created
lastUpdated
printThis
printThisURL
sendThis
sendThisURL
fullURL
...
and more.

Use the "News" templates as an example. If you want to use the templates your must create
a publication type named "News" with the following fields:

headline (string)
teaser (text)
text (html)
image (image)
imagetext (string)
