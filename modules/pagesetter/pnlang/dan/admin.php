<?php
// ------------------------------------------------------------------------------------
// Translation for PostNuke Pagesetter module
// Translation by: Jorn Lind-Nielsen
// ------------------------------------------------------------------------------------
require_once 'modules/pagesetter/guppy/lang/dan/global.php';
require_once 'modules/pagesetter/pnlang/dan/common.php';

define('_PGBACKTOADMIN', 'Tilbage til administrationen');
define('_PGBTBACKTOPUBLIST', 'Tilbage til publikationslisten');
define('_PGBTTYPEEXTRA', 'Klik for at specificere ekstra parametre for felttypen');
define('_PGCANCEL', 'Fortryd');
define('_PGCOMMIT', 'Gem');
define('_PGCONFIGURATION', 'Pagesetter konfiguration');
define('_PGCONFIGAUTOFILLPUBDATE', 'Udfyld publiceringsdato automatisk');
define('_PGCONFIGEDITOR', 'Editor');
define('_PGCONFIGEDITORENABLED', 'Tillad brug af htmlArea editor');
define('_PGCONFIGEDITORSTYLED', 'Brug tema styles i editor');
define('_PGCONFIGEDITORUNDO', 'Tillad undo i editor (fjerner statusbj�lke)');
define('_PGCONFIGEDITORWORDKILL', 'Tillad Word-code-kill i editor');
define('_PGCONFIGGENERAL', 'Generelt');
define('_PGCONFIGUPLOAD', 'Upload konfigurering');
define('_PGCONFIRMLISTDELETE', 'Er du helt sikker p� at du vil slette denne kategori?');
define('_PGCONFIRMPUBTYPEDELETE', 'Er du helt sikker p� at du vil slette denne publikationstype - inklusiv alle publikationer?');
define('_PGCREATETEMPLATES', 'V�lg skabeloner til auto-generering');
define('_PGDEFAULTFOLDER', 'Standard mappe'); //
define('_PGDEFAULTPUBTYPE', 'Standard publicationstype (til forside)');
define('_PGDEFAULTSUBFOLDER', 'Standard under-mappe');
define('_PGDESCRIPTION', 'Beskrivelse');
define('_PGDOWNLOADINGSCHEMA', 'Pagesetter XML schemaet downloader. Hvis der ikke sker noget efter et par sekunder, s� klik p� dette link');
define('_PGDOWNLOAD', 'Download');
define('_PGEDIT', 'Rediger');
define('_PGNERROROTACCESSIBLEDIR', 'Den specificerede filmappe er ikke skrivbar!');
define('_PGERRORUPLOAD', 'Fejl i upload af filen: ');
define('_PGERRORUPLOADDIREMPTY', 'Midlertidigt uploadmappe er ikke defineret. Mappen kan specificeres i admin : pagesetter : configuration : general.');
define('_PGERRORUPLOADMOVE', 'Kan ikke flytte uploaded fil fra/til: ');
define('_PGENABLEHOOKS', 'Tillad PN-Hooks');
define('_PGENABLEREVISIONS', 'Tillad revisionskontrol');
define('_PGENABLEEDITOWN', 'Tillad redigering af egen pub.');
define('_PGENABLETOPICACCESS', 'Anvend topic-baseret adgangskontrol');
define('_PGEXPORT', 'Eksporter');
define('_PGEXPORTFORM', 'Eksporter Pagesetter Struktur');
define('_PGEXPORTSCHEMA', 'Eksporter kun databaseskema');
define('_PGFIELDTYPE', 'Type');
define('_PGFIELDISTITLE', 'Overskrift');
define('_PGFOLDERNOTINSTALLED', 'Folder modulet er ikke installeret');
define('_PGFOLDERNONE', 'Anvend ikke mapper');
define('_PGFOLDERSETUP','Ops�tning til brug for Folder-modulet');
define('_PGFOLDERDEFAULT','Standard mappe');
define('_PGFOLDERDEFAULTTOPIC','Standard emne');
define('_PGFOLDERSUBDEFAULT','Standard undermappe');
define('_PGFOLDERSTRANSFERED', 'Alle publikationer er nu overf�rt til Folder modulet');
define('_PGFTCREATEDDATE', 'Oprettet');
define('_PGFTMANDATORY', 'P');
define('_PGFTMULTIPLEPAGES', 'FS');
define('_PGFTPUBLISHDATE', 'Publiseringsdato');
define('_PGFTSEARCHABLE', 'S');
define('_PGID', 'id');
define('_PGIMPORTPUBLICATIONS', 'Importer publikationer');
define('_PGIMPORTARTICLE', 'Opret artikel');
define('_PGIMPORTARTICLEDESC', 'Opretter en ny publikationstype der kan h�ndtere simple artikler med overskrift, tekst og billede.');
define('_PGIMPORTCE', 'Importer ContentExpress');
define('_PGIMPORTCEDESC', 'Opretter en ny publikationstype kaldet CE og importerer alle ContentExpress sider.');
define('_PGIMPORTFILEUPLOAD', 'Opret FileUpload');
define('_PGIMPORTFILEUPLOADDESC', 'Opretter en ny publikationstype der kan h�ndtere generiske fileuploads. Typen er designet til brug sammen med "Folder" modulet.');
define('_PGIMPORTIMAGE', 'Opret Image');
define('_PGIMPORTIMAGEDESC', 'Opretter en ny publikationstype der kan h�ndtere billeder. Typen er designet til brug sammen med "Folder" modulet.');
define('_PGIMPORTNEWS', 'Importer nyheder');
define('_PGIMPORTNEWSDESC', 'Opretter en ny publikationstype kaldet PN-News og importerer alle standard PostNuke nyheder.');
define('_PGIMPORTNEWSEXTRA', 'Tilf�j billedfelt');
define('_PGIMPORTNOTE', 'Opret Note');
define('_PGIMPORTNOTEDESC', 'Opretter en ny publikationstype der kan h�ndtere sm� noter. Typen er designet til brug sammen med "Folder" modulet.');
define('_PGIMPORTPC', 'Importer PostCalendar');
define('_PGIMPORTPCDESC', 'Opretter en ny publikationstype kaldet PostCalendar og importerer alle PostCalendar sider.');
define('_PGIMPORTXMLSCHEMA', 'Importer XML schema');
define('_PGIMPORTXMLSCHEMADESC', 'Opretter en ny publikationstype p� baggrund af den uploaded Pagesetter XML schema.');
define('_PGIMPORTXMLSCHEMAFILE', 'XML schema fil');
define('_PGINCLUDECAT', 'Medtag kategorier');
define('_PGLISTEDIT', 'Rediger kategori');
define('_PGLISTITEMS', 'Kategorielementer');
define('_PGLISTLIST', 'Liste');
define('_PGLISTSETUP', 'Listeops�tning');
define('_PGLISTSHOWCOUNT', 'Antal sider der skal vises i listen');
define('_PGLISTTITLE', 'Overskrift');
define('_PGMISSINGFIELDROW', 'Du skal oprette mindst �t felt');
define('_PGNAME', 'Navn');
define('_PGNEWLIST', 'Ny kategori');
define('_PGNEWPUBINSTANCE', 'Ny');
define('_PGNOAUTH', 'Du har ikke adgang til denne funktion');
define('_PGNODEFAULTSUBFOLDER', 'Ingen standard undermappe');
define('_PGNONE', 'Ingen');
define('_PGONLYONEPAGEABLE', 'Kun �t felt kan markeres som flersiders!');
define('_PGPAGESETTERBASEDIR', 'Pagesetter installationsmappe');
define('_PGPUBLICATIONFIELDS', 'Publikationsfelter');
define('_PGPUBLICATIONTYPES', 'Publikationstyper');
define('_PGPUBLICATIONTYPEEDIT', 'Publikationstype ops�tning');
define('_PGPUBLICATIONTYPEADD1', 'Opret ny publikationstype');
define('_PGPUBLICATIONTYPEADD2', 'Opret skabeloner og specificer sortering');
define('_PGPUBLIST', 'Liste');
define('_PGPUBTYPEEDITADDSTEP1', 'Trin 1: V�lg navn og eventuelt beskrivelse for din nye publikation.');
define('_PGPUBTYPEEDITADDSTEP2', 'Trin 2: Opret felter for publikationstypen.');
define('_PGPUBTYPEEDITADDSTEP2NOTE', 'Klik [+] for at tilf�je felter under tilsvarende r�kke. Gentag indtil at du har til f�jet alle de felter du �nsker. Listen af felter kan altid rettes senere.');
define('_PGPUBTYPETITLE', 'Overskrift');
define('_PGPUBTYPEFILENAME', 'Skabelonnavn');
define('_PGPUBTYPEFORMNAME', 'Formularnavn');
define('_PGPUBTYPETEMPLATES', 'Skabeloner');
define('_PGPUBTYPELISTGENERATE', 'Opret skabelon til forsideliste');
define('_PGPUBTYPELISTTEMPLATE', 'Skabelonnavn for forsideliste');
define('_PGPUBTYPEFULLGENERATE', 'Opret skabelon til helsides visning');
define('_PGPUBTYPEFULLTEMPLATE', 'Skabelonnavn for helside');
define('_PGPUBTYPEPRINTGENERATE', 'Opret skabelon til udskriftvenlig visning');
define('_PGPUBTYPEPRINTTEMPLATE', 'Skabelonnavn for udskrift');
define('_PGPUBTYPERSSGENERATE', 'Opret skabelon til RSS');
define('_PGPUBTYPERSSTEMPLATE', 'Skabelonnavn for RSS');
define('_PGPUBTYPEBLOCKGENERATE', 'Opret skabelon til PostNuke block');
define('_PGPUBTYPEBLOCKTEMPLATE', 'Skabelonnavn for block');
define('_PGPUBTYPEEDITCOLINFO', 'P = P�kr�vet, S = S�gbar, FS = Flere sider');
define('_PGPUBTYPESHELP', '<p>I dette vindue kan du tilf�je nye publikationstyper (f.eks.
nyheder, opskrifter eller artikler).</p>
<p>Klik p� "Ny publikationstype" for at definere hvilke database felter 
din publikationstype skal best� af (f.eks. en overskrift, introtekst og 
fuldtekst for en nyhedstype).</p>
<p>Du kan ogs� installere <em>pr�definerede publikationstyper</em>. Klik
p� menuen "Funktioner:Importer data" for at f� en oversigt over mulighederne.</p>');
define('_PGREL_PUBLICATION_SELECT', 'Publikationstype');
define('_PGREL_FIELD_SELECT', 'Felt');
define('_PGREL_STYLE_SELECT', 'Udv�lgertype');
define('_PGREL_STYLE_ASPOPUP', 'Popupvindue');
define('_PGREL_FILTER_INPUT', 'Standard filter');
define('_PGREL_STYLE_SELECTLIST', 'Liste');
define('_PGREL_STYLE_ADVANCEDSELECT', 'To-delt liste');
define('_PGREL_STYLE_CHECKBOX', 'Flueben');
define('_PGREL_STYLE_HIDDEN', 'Skjult (vises ikke)');
define('_PGSORTCREATED', 'Oprettet');
define('_PGSORTFIELD1', 'F�rste sorteringsn�gle');
define('_PGSORTFIELD2', 'Anden sorteringsn�gle');
define('_PGSORTFIELD3', 'Tredie sorteringsn�gle');
define('_PGDEFAULTFILTER', 'Standard filter');
define('_PGSETUPFOLDER', 'Overf�r Pagesetter publikationer til Folder modulet'); //
define('_PGSETUPFOLDERNONESEL', 'Der er ikke valgt nogen standardmappe. Disse publikationer kan ikke overf�res til Folder modulet.');
define('_PGSORTID', 'Publikations id');
define('_PGSORTDESC', 'Sorter faldende');
define('_PGSORTLASTUPDATED', 'Sidst opdateret');
define('_PGTITLE', 'Overskrift');
define('_PGTRANSFER', 'Overf�r');
define('_PGTS_EXTRATYPEINFO', 'Ekstra typeinformation');
define('_PGTS_EXTRATYPEINFOFOR', 'Ekstra typeinformation for');
define('_PGTS_OK', 'Gem');
define('_PGTS_PUBLICATION_SELECT', 'V�lg publikationstype');
define('_PGTS_CANCEL', 'Fortryd');
define('_PGTYPE', 'Type'); //
define('_PGTYPESTRING', 'string');
define('_PGTYPETEXT', 'text');
define('_PGTYPEHTML', 'html');
define('_PGTYPEBOOL', 'bool');
define('_PGTYPEINT', 'int');
define('_PGTYPEREAL', 'real');
define('_PGTYPETIME', 'klokkeslet');
define('_PGTYPEDATE', 'dato');
define('_PGTYPEIMAGE', 'billede (url)');
define('_PGTYPEIMAGEUPLOAD', 'billed-upload');
define('_PGTYPEUPLOAD', 'upload');
define('_PGTYPEEMAIL', 'e-mail');
define('_PGTYPEURL', 'hyperlink');
define('_PGTYPECURRENCY', 'bel�b');
define('_PGTYPEPUBID', 'publikations ID');
define('_PGUNKNOWNFOLDER', 'Ukendt mappe');
define('_PGUPDATE', 'Opdater');
define('_PGUPLOADDIR', 'Midlertidig uploadmappe');
define('_PGUPLOADDIRDOCS', 'Dokument uploadmappe');
define('_PGVALUE', 'V�rdi');
define('_PGWFCFGLIST', 'Workflowops�tning - v�lg publikationstype');
define('_PGWFCFG', 'Workflowops�tning');
define('_PGWORKFLOW', 'Workflow');
define('_PGWFWORKFLOW', 'Workflow');

?>
