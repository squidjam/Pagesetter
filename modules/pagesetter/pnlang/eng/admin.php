<?php
// ------------------------------------------------------------------------------------
// Translation for PostNuke Pagesetter module
// Translation by: Jorn Lind-Nielsen
// ------------------------------------------------------------------------------------
require_once 'modules/pagesetter/guppy/lang/eng/global.php';
require_once 'modules/pagesetter/pnlang/eng/common.php';

define('_PGPUBTYPEEDITADDSTEP1', 'STEP 1: Assign Name and Description');
define('_PGPUBTYPEEDITADDSTEP2', 'STEP 2: Define publication fields.');
define('_PGPUBTYPEEDITADDSTEP2NOTE', 'Click [+] to add new row under current row.  Repeat until complete.  Can be edited later from the main menu: Publications -> Types');
define('_PGBACKTOADMIN', 'Back to administration');
define('_PGBTBACKTOPUBLIST', 'Back to publications');
define('_PGBTTYPEEXTRA', 'Click to specify extra parameters for this input type');
define('_PGCANCEL', 'Cancel'); //
define('_PGCOMMIT', 'Commit');
define('_PGCONFIGURATION', 'Pagesetter Configuration');
define('_PGCONFIGAUTOFILLPUBDATE', 'Auto fill publish date');
define('_PGCONFIGEDITOR', 'Editor');
define('_PGCONFIGEDITORENABLED', 'Enable use of htmlArea editor');
define('_PGCONFIGEDITORSTYLED', 'Use theme styles in editor');
define('_PGCONFIGEDITORUNDO', 'Enable undo in editor (removes statusbar)');
define('_PGCONFIGEDITORWORDKILL', 'Enable Word code kill on paste in editor');
define('_PGCONFIGGENERAL', 'General');
define('_PGCONFIGUPLOAD', 'Upload Configuration');
define('_PGCONFIRMLISTDELETE', 'Are you REALLY sure you want to delete this category?');
define('_PGCONFIRMPUBTYPEDELETE', 'Are you REALLY sure you want to delete this publication type - including all publications?');
define('_PGCREATEDDATE', 'Created');
define('_PGCREATETEMPLATES', 'Select templates to auto-generate');
define('_PGDEFAULTFOLDER', 'Default folder'); //
define('_PGDEFAULTPUBTYPE', 'Default publication type (to display on frontpage)');
define('_PGDEFAULTSUBFOLDER', 'Default sub-folder');
define('_PGDESCRIPTION', 'Description');
define('_PGDOWNLOADINGSCHEMA', 'The Pagesetter XML schema is now downloading. If nothing happens after a few seconds then click on this link');
define('_PGDOWNLOAD', 'Download');
define('_PGEDIT', 'Edit');
define('_PGNERROROTACCESSIBLEDIR', 'The specified directory is not writable!');
define('_PGERRORUPLOAD', 'Error in upload of file: ');
define('_PGERRORUPLOADDIREMPTY', 'Temporary upload directory has not been set. Please specify in admin : pagesetter : configuration : general.');
define('_PGERRORUPLOADMOVE', 'Unable to move uploaded file from/to: ');
define('_PGENABLEHOOKS', 'Enable PN-Hooks');
define('_PGENABLEREVISIONS', 'Enable revision control');
define('_PGENABLEEDITOWN', 'Enable editing of own pub.');
define('_PGENABLETOPICACCESS', 'Enable topic access control');
define('_PGEXPORT', 'Export');
define('_PGEXPORTFORM', 'Export Pagesetter Structure');
define('_PGEXPORTSCHEMA', 'Export database schema only');
define('_PGFIELDTYPE', 'Type');
define('_PGFIELDISTITLE', 'Title field');
define('_PGFOLDERNOTINSTALLED', 'Folder module is not installed');
define('_PGFOLDERNONE', 'Do not use folders');
define('_PGFOLDERSETUP','Setup for use with folder module');
define('_PGFOLDERDEFAULT','Default folder');
define('_PGFOLDERDEFAULTTOPIC','Default topic');
define('_PGFOLDERSUBDEFAULT','Default sub-folder');
define('_PGFOLDERSTRANSFERED', 'All publications transferred to Folder module');
define('_PGFTMANDATORY', 'M');
define('_PGFTMULTIPLEPAGES', 'MP');
define('_PGFTPUBLISHDATE', 'Publish date');
define('_PGFTSEARCHABLE', 'S');
define('_PGID', 'id');
define('_PGIMPORTPUBLICATIONS', 'Import Publications');
define('_PGIMPORTARTICLE', 'Create Article');
define('_PGIMPORTARTICLEDESC', 'Creates a new general purpose article publication type with title, text and image.');
define('_PGIMPORTCE', 'Import ContentExpress');
define('_PGIMPORTCEDESC', 'Creates a new publication type named CE and imports all ContentExpress items.');
define('_PGIMPORTFILEUPLOAD', 'Create FileUpload');
define('_PGIMPORTFILEUPLOADDESC', 'Creates a new publication type that handles generic file uploads. The type has been designed for use with the "Folder" module');
define('_PGIMPORTIMAGE', 'Create Image');
define('_PGIMPORTIMAGEDESC', 'Creates a new publication type that handles images. The type has been designed for use with the "Folder" module');
define('_PGIMPORTNEWS', 'Import News');
define('_PGIMPORTNEWSDESC', 'Creates a new publication type named PN-News and imports all PostNuke News items.');
define('_PGIMPORTNEWSEXTRA', 'Add image field');
define('_PGIMPORTNOTE', 'Create Note');
define('_PGIMPORTNOTEDESC', 'Creates a new publication type that handles small notes. The type has been designed for use with the "Folder" module');
define('_PGIMPORTPC', 'Import PostCalendar');
define('_PGIMPORTPCDESC', 'Creates a new publication type named PostCalendar and imports all Postcalendar items.');
define('_PGIMPORTXMLSCHEMA', 'Import XML schema');
define('_PGIMPORTXMLSCHEMADESC', 'Creates a new publication type based on the uploaded Pagesetter XML schema file.');
define('_PGIMPORTXMLSCHEMAFILE', 'XML schema file');
define('_PGINCLUDECAT', 'Include categories');
define('_PGLISTAUTHORHELP', 'This is where you can create new publications and view all the existing ones. Click on "New publication" to create a new instance, or use the filter to find a specific publication you have created already.');
define('_PGLISTEDIT', 'Edit category');
define('_PGLISTITEMS', 'Category items');
define('_PGLISTLIST', 'Categories');
define('_PGLISTSETUP', 'List setup');
define('_PGLISTSHOWCOUNT', 'Number of publications to show in list');
define('_PGLISTTITLE', 'Title');
define('_PGMISSINGFIELDROW', 'You must add at least one publication field');
define('_PGNAME', 'Name');
define('_PGNEWPUBINSTANCE', 'New');
define('_PGNEWLIST', 'New category');
define('_PGNOAUTH', 'You are not authorized to use this feature');
define('_PGNODEFAULTSUBFOLDER', 'No default subfolder'); //
define('_PGNONE', 'None');
define('_PGONLYONEPAGEABLE', 'Only one field can be marked as pageable!');
define('_PGPAGESETTERBASEDIR', 'Pagesetter installation directory');
define('_PGPUBLICATIONFIELDS', 'Publication fields');
define('_PGPUBLICATIONTYPES', 'Publication types');
define('_PGPUBLICATIONTYPEEDIT', 'Publication Type Configuration');
define('_PGPUBLICATIONTYPEADD1', 'Create new publication type');
define('_PGPUBLICATIONTYPEADD2', 'Create templates and setup sorting');
define('_PGPUBLIST', 'List');
define('_PGPUBTYPETITLE', 'Title');
define('_PGPUBTYPEFILENAME', 'Template');
define('_PGPUBTYPEFORMNAME', 'Form name');
define('_PGPUBTYPETEMPLATES', 'Output template creation');
define('_PGPUBTYPELISTGENERATE', 'Generate template for frontpage list');
define('_PGPUBTYPELISTTEMPLATE', 'List template filename');
define('_PGPUBTYPEFULLGENERATE', 'Generate template for full page display');
define('_PGPUBTYPEFULLTEMPLATE', 'Full template filename');
define('_PGPUBTYPEPRINTGENERATE', 'Generate template for printable version');
define('_PGPUBTYPEPRINTTEMPLATE', 'Printable template filename');
define('_PGPUBTYPERSSGENERATE', 'Generate template for RSS');
define('_PGPUBTYPERSSTEMPLATE', 'RSS template filename');
define('_PGPUBTYPEBLOCKGENERATE', 'Generate template for block list');
define('_PGPUBTYPEBLOCKTEMPLATE', 'Block template filename');
define('_PGPUBTYPEEDITCOLINFO', 'M = Mandatory, S = Searchable, MP = Multiple pages');
define('_PGPUBTYPESHELP', '<p>This window is where you can add new publication types (for instance News,
        Recipies, or Articles&mdash;you choose yourself).</p>
        <p>Click on "New Publication Type" to define what database fields your
        publication should consist of (for instance a Title field, an Intro text,
        and a Full text for a News publication).</p>
        <p>You can also install <em>predefined publication types</em>.
        Click on the menu "Tools:Import data" to get an overview.</p>');
define('_PGREL_PUBLICATION_SELECT', 'Publication type');
define('_PGREL_FIELD_SELECT', 'Field');
define('_PGREL_STYLE_SELECT', 'Selector type');
define('_PGREL_STYLE_ASPOPUP', 'Popup window');
define('_PGREL_FILTER_INPUT', 'Standard filter');
define('_PGREL_STYLE_SELECTLIST', 'List');
define('_PGREL_STYLE_ADVANCEDSELECT', 'Split list');
define('_PGREL_STYLE_CHECKBOX', 'Checkbox');
define('_PGREL_STYLE_HIDDEN', 'Hidden (not shown)');
define('_PGSORTCREATED', 'Created date');
define('_PGSORTFIELD1', 'First sorting key');
define('_PGSORTFIELD2', 'Second sorting key');
define('_PGSORTFIELD3', 'Third sorting key');
define('_PGDEFAULTFILTER', 'Standard filter');
define('_PGSETUPFOLDER', 'Transfer Pagesetter publications to Folder module'); //
define('_PGSETUPFOLDERNONESEL', 'No default folder selected. These publications cannot be transfered.'); //
define('_PGSORTID', 'Publication ID');
define('_PGSORTDESC', 'Sort descending');
define('_PGSORTLASTUPDATED', 'Last updated date');
define('_PGTITLE', 'Title');
define('_PGTRANSFER', 'Transfer'); //
define('_PGTS_EXTRATYPEINFO', 'Extra type information');
define('_PGTS_EXTRATYPEINFOFOR', 'Extra type information for');
define('_PGTS_PUBLICATION_SELECT', 'Select publication type');
define('_PGTS_OK', 'Ok');
define('_PGTS_CANCEL', 'Cancel');
define('_PGTYPE', 'Type'); //
define('_PGTYPESTRING', 'string');
define('_PGTYPETEXT', 'text');
define('_PGTYPEHTML', 'html');
define('_PGTYPEBOOL', 'bool');
define('_PGTYPEINT', 'int');
define('_PGTYPEREAL', 'real');
define('_PGTYPETIME', 'time');
define('_PGTYPEDATE', 'date');
define('_PGTYPEIMAGE', 'image (url)');
define('_PGTYPEIMAGEUPLOAD', 'image upload');
define('_PGTYPEUPLOAD', 'any upload');
define('_PGTYPEEMAIL', 'e-mail');
define('_PGTYPEURL', 'hyperlink');
define('_PGTYPECURRENCY', 'currency');
define('_PGTYPEPUBID', 'publication ID');
define('_PGUNKNOWNFOLDER', 'Unknown folder'); //
define('_PGUPDATE', 'Update');
define('_PGUPLOADDIR', 'Temporary upload directory');
define('_PGUPLOADDIRDOCS', 'Document upload directory');
define('_PGVALUE', 'Value');
define('_PGWFCFGLIST', 'Workflow configuration - select publication type');
define('_PGWFCFG', 'Workflow configuration');
define('_PGWFWORKFLOW', 'Workflow');
define('_PGWORKFLOW', 'Workflow');

?>
