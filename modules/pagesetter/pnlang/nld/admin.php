<?php
/**
 * Dutch Translation for PostNuke Pagesetter module
 *
 * @package Pagesetter module
 * @subpackage Languages
 * @version $Id: admin.php 327 2007-02-12 09:35:51Z teb $
 * @author Jorn Lind-Nielsen
 * @author Arjen Tebbenhof (Teb)
 * @link http://www.elfisk.dk The Pagesetter Home Page
 * @link http://postnuke.opencms.nl The Dutch PostNuke Community
 * @license http://www.gnu.org/copyleft/gpl.html GNU General Public License
 */

require_once 'modules/pagesetter/guppy/lang/nld/global.php';
require_once dirname(__FILE__).'/common.php';

define('_PGPUBTYPEEDITADDSTEP1', 'Stap 1: Wijs Naam en beschrijving toe');
define('_PGPUBTYPEEDITADDSTEP2', 'Stap 2: Definieer publicatie velden.');
define('_PGPUBTYPEEDITADDSTEP2NOTE', 'Klik [+] om een nieuwe rij onder de huidige in te voegen. Herhaal dit tot het klaar is. Dit kan later aangepast worden in het hoofdmenu: Publicaties -> Types');
define('_PGBACKTOADMIN', 'Terug naar Beheer');
define('_PGBTBACKTOPUBLIST', 'Terug naar publicaties');
define('_PGBTTYPEEXTRA', 'Klik om extra parameters voor dit invoertype te specificeren');
define('_PGCANCEL', 'Annuleren'); //
define('_PGCOMMIT', 'Vastleggen');
define('_PGCONFIGURATION', 'Pagesetter Configuratie');
define('_PGCONFIGAUTOFILLPUBDATE', 'Publicatie datum automatisch invullen');
define('_PGCONFIGEDITOR', 'Editor');
define('_PGCONFIGEDITORENABLED', 'Zet gebruik van WYSIWYG editor aan');
define('_PGCONFIGEDITORSTYLED', 'Gebruik theme stijlen in editor');
define('_PGCONFIGEDITORUNDO', 'Gebruik "Ongedaan maken" in editor (verwijdert statusbar)');
define('_PGCONFIGEDITORWORDKILL', 'Gebruik "Word code kill" bij plakken in editor');
define('_PGCONFIGGENERAL', 'Algemeen');
define('_PGCONFIGUPLOAD', 'Configuratie uploaden');
define('_PGCONFIRMLISTDELETE', 'Weet je ECHT zeker dat je deze categorie wilt verwijderen?');
define('_PGCONFIRMPUBTYPEDELETE', 'Weet je ECHT zeker dat je dit publicatie type - inclusief alle publicaties - wilt verwijderen?');
define('_PGCREATEDDATE', 'Aangemaakt');
define('_PGCREATETEMPLATES', 'Selecteer sjablonen om automatisch te genereren');
define('_PGDEFAULTFOLDER', 'Standaard directory'); //
define('_PGDEFAULTPUBTYPE', 'Standaard publicatie type (die getoond dient te worden op de module-startpagina)');
define('_PGDEFAULTSUBFOLDER', 'Standaard sub-directory');
define('_PGDESCRIPTION', 'Beschrijving');
define('_PGDOWNLOADINGSCHEMA', 'Het Pagesetter XML schema wordt nu gedownload. Als er niets gebeurt na een paar seconden, klik dan op deze link');
define('_PGDOWNLOAD', 'Downloaden');
define('_PGEDIT', 'Bewerken');
define('_PGNERROROTACCESSIBLEDIR', 'De opgegeven directory is niet beschrijfbaar!');
define('_PGERRORUPLOAD', 'Fout bij uploaden van bestand: ');
define('_PGERRORUPLOADDIREMPTY', 'Tijdelijke upload directory is niet ingesteld. Specificeer deze aub in Beheer : PageSetter : Configuratie : Algemeen.');
define('_PGERRORUPLOADMOVE', 'Niet in staat het bestand te verplaatsen van of naar: ');
define('_PGENABLEHOOKS', 'Schakel PN-Hooks in');
define('_PGENABLEREVISIONS', 'Schakel herziening controle in');
define('_PGENABLEEDITOWN', 'Schakel bewerken van eigen publicaties in.');
define('_PGENABLETOPICACCESS', 'Schakel onderwerp toegang in');
define('_PGEXPORT', 'Exporteren');
define('_PGEXPORTFORM', 'Exporteer Pagesetter structuur');
define('_PGEXPORTSCHEMA', 'Exporteer alleen database schema');
define('_PGFIELDTYPE', 'Type');
define('_PGFIELDISTITLE', 'Titel veld');
define('_PGFOLDERNOTINSTALLED', 'Folder module is niet geïnstalleerd');
define('_PGFOLDERNONE', 'Gebruik geen folders');
define('_PGFOLDERSETUP', 'Instellen om te gebruiken met Folder module');
define('_PGFOLDERDEFAULT', 'Standaard folder');
define('_PGFOLDERDEFAULTTOPIC', 'Standaard onderwerp');
define('_PGFOLDERSUBDEFAULT', 'Standaard sub-folder');
define('_PGFOLDERSTRANSFERED', 'Alle publicaties overgezet naar Folder module');
define('_PGFTMANDATORY', 'V');
define('_PGFTMULTIPLEPAGES', 'MP');
define('_PGFTPUBLISHDATE', 'Publicatie datum');
define('_PGFTSEARCHABLE', 'Z');
define('_PGID', 'id');
define('_PGIMPORTPUBLICATIONS', 'Publicaties importeren');
define('_PGIMPORTARTICLE', 'Artikel aanmaken');
define('_PGIMPORTARTICLEDESC', 'Maakt een nieuw artikelelen publicatie type aan voor algemeen gebruik, met titel, text en afbeelding.');
define('_PGIMPORTCE', 'ContentExpress importeren');
define('_PGIMPORTCEDESC', 'Maakt een nieuw publicatie type \'CE\' aan en zet alle CE gegevens over.');
define('_PGIMPORTFILEUPLOAD', 'FileUpload aanmaken');
define('_PGIMPORTFILEUPLOADDESC', 'Maakt een nieuw publicatie type aan welke standaard file uploads verwerkt. Dit type is ontworpen voor gebruik met de "Folder" module');
define('_PGIMPORTIMAGE', 'Afbeelding Aanmaken');
define('_PGIMPORTIMAGEDESC', 'Maakt een nieuw publicatie type aan dat afbeeldingen verwerkt. Dit type is ontworpen voor gebruik met de "Folder" module');
define('_PGIMPORTNEWS', 'Nieuws importeren');
define('_PGIMPORTNEWSDESC', 'Maakt een nieuw publication type \'PN-News\' aan en zet alle PostNuke Nieuws items over.');
define('_PGIMPORTNEWSEXTRA', 'Afbeelding veld toevoegen');
define('_PGIMPORTNOTE', 'Notitie aanmaken');
define('_PGIMPORTNOTEDESC', 'Maakt een nieuw publicatie type aan om kleine notities te behandelen. Ontworpen voor gebruik met de "Folder" module');
define('_PGIMPORTPC', 'PostCalendar importeren');
define('_PGIMPORTPCDESC', 'Maakt een nieuw publicatie type \'PostCalendar\' aan en zet alle Postcalendar items over.');
define('_PGIMPORTXMLSCHEMA', 'XML schema importeren');
define('_PGIMPORTXMLSCHEMADESC', 'Maakt een nieuw publicatie type aan, gebaseerd op het opgegeven Pagesetter XML schema bestand.');
define('_PGIMPORTXMLSCHEMAFILE', 'XML schema bestand');
define('_PGINCLUDECAT', 'Categorieën meenemen');
define('_PGLISTAUTHORHELP', 'Hier kunnen nieuwe publicaties aangemaakt en alle huidige bekeken worden. Klik op "Nieuwe publicatie" om een nieuwe publicatie op te geven, of gebruik de filter om reeds bestaande publicaties sneller te kunnen vinden.');
define('_PGLISTEDIT', 'Categorie bewerken');
define('_PGLISTITEMS', 'Categorie items');
define('_PGLISTLIST', 'Categorieën');
define('_PGLISTSETUP', 'Categorie Instellingen');
define('_PGLISTSHOWCOUNT', 'Aantal te tonen publicaties in de lijst');
define('_PGLISTTITLE', 'Titel');
define('_PGMISSINGFIELDROW', 'Er dient tenminste één publicatieveld ingevoerd te worden');
define('_PGNAME', 'Naam');
define('_PGNEWPUBINSTANCE', 'Nieuw');
define('_PGNEWLIST', 'Nieuwe categorie');
define('_PGNOAUTH', 'U heeft geen toestemming deze optie te gebruiken');
define('_PGNODEFAULTSUBFOLDER', 'Geen standaard subfolder'); //
define('_PGNONE', 'Geen');
define('_PGONLYONEPAGEABLE', 'Slechts 1 veld kan als \'pagineerbaar\' worden aangemerkt!');
define('_PGPAGESETTERBASEDIR', 'Pagesetter installatie directory');
define('_PGPUBLICATIONFIELDS', 'Publicatie velden');
define('_PGPUBLICATIONTYPES', 'Publicatie types');
define('_PGPUBLICATIONTYPEEDIT', 'Publicatie Type Configuratie');
define('_PGPUBLICATIONTYPEADD1', 'Nieuw publication type aanmaken');
define('_PGPUBLICATIONTYPEADD2', 'Templates aanmaken en sorteervolgorde instellen');
define('_PGPUBLIST', 'Lijst');
define('_PGPUBTYPETITLE', 'Titel');
define('_PGPUBTYPEFILENAME', 'Sjabloon');
define('_PGPUBTYPEFORMNAME', 'Formulier naam');
define('_PGPUBTYPETEMPLATES', 'Aanmaken van de weergave templates');
define('_PGPUBTYPELISTGENERATE', 'Maak een template aan voor lijstweergave op de startpagina van dit publicatie type');
define('_PGPUBTYPELISTTEMPLATE', 'Bestandsnaam voor lijstweergave template');
define('_PGPUBTYPEFULLGENERATE', 'Maak een template aan voor volledige weergave van een publicatie item');
define('_PGPUBTYPEFULLTEMPLATE', 'Bestandsnaam voor volledige weergave template');
define('_PGPUBTYPEPRINTGENERATE', 'Maak een template aan voor printvriendelijke pagina');
define('_PGPUBTYPEPRINTTEMPLATE', 'Bestandsnaam voor printvriendelijke template');
define('_PGPUBTYPERSSGENERATE', 'Maak een template aan voor RSS');
define('_PGPUBTYPERSSTEMPLATE', 'Bestandsnaam voor RSS template');
define('_PGPUBTYPEBLOCKGENERATE', 'Maak een template aan voor lijstweergave in een blok');
define('_PGPUBTYPEBLOCKTEMPLATE', 'Bestandsnaam voor Block template');
define('_PGPUBTYPEEDITCOLINFO', 'V = Verplicht, Z = Doorzoekbaar, MP = Meerdere pagina\'s');
define('_PGPUBTYPESHELP', '<p>In dit venster kun je nieuwe publicatie types toevoegen (bijvoorbeeld Nieuws,
        Recepten, of Artikelen &mdash; Uw keuze).</p>
        <p>Klik op "Nieuw Publicatie Type" om op te geven welke database velden moeten worden
        gebruikt bij de publicatie (bijvoorbeeld Titel, Introtekst,
        en een volledige (Full) tekst voor een Nieuws publicatie).</p>
        <p>Er kan ook gekozen worden voor <em>voorgedefinieerde publicatie types</em>.
        Klik in het menu op "Tools:Importeer data" om een overzicht te tonen.</p>');
define('_PGREL_PUBLICATION_SELECT', 'Publicatie type');
define('_PGREL_FIELD_SELECT', 'Veld');
define('_PGREL_STYLE_SELECT', 'Selector type');
define('_PGREL_STYLE_ASPOPUP', 'Popup venster');
define('_PGREL_FILTER_INPUT', 'Standaard filter');
define('_PGREL_STYLE_SELECTLIST', 'Lijst');
define('_PGREL_STYLE_ADVANCEDSELECT', 'Splits list');
define('_PGREL_STYLE_CHECKBOX', 'Checkbox');
define('_PGREL_STYLE_HIDDEN', 'Verborgen (niet tonen)');
define('_PGSORTCREATED', 'Aangemaakt datum');
define('_PGSORTFIELD1', 'Eerste sorteersleutel');
define('_PGSORTFIELD2', 'Tweede sorteersleutel');
define('_PGSORTFIELD3', 'Derde sorteersleutel');
define('_PGDEFAULTFILTER', 'Standaard filter');
define('_PGSETUPFOLDER', 'Zet Pagesetter publicaties over naar Folder module'); //
define('_PGSETUPFOLDERNONESEL', 'Geen standaard folder geselecteerd. Deze publicaties kunnen niet worden overgezet.'); //
define('_PGSORTID', 'Publicatie ID');
define('_PGSORTDESC', 'Aflopend sorteren');
define('_PGSORTLASTUPDATED', 'Laatst bijgewerkt datum');
define('_PGTITLE', 'Titel');
define('_PGTRANSFER', 'Overzetten'); //
define('_PGTS_EXTRATYPEINFO', 'Extra type informatie');
define('_PGTS_EXTRATYPEINFOFOR', 'Extra type informatie voor');
define('_PGTS_PUBLICATION_SELECT', 'Selecteer publicatie type');
define('_PGTS_OK', 'Ok');
define('_PGTS_CANCEL', 'Annuleren');
define('_PGTYPE', 'Type'); //
define('_PGTYPESTRING', 'string');
define('_PGTYPETEXT', 'text');
define('_PGTYPEHTML', 'html');
define('_PGTYPEBOOL', 'bool');
define('_PGTYPEINT', 'int');
define('_PGTYPEREAL', 'real');
define('_PGTYPETIME', 'tijd');
define('_PGTYPEDATE', 'datum');
define('_PGTYPEIMAGE', 'afbeelding (url)');
define('_PGTYPEIMAGEUPLOAD', 'afbeelding (upload)');
define('_PGTYPEUPLOAD', 'elke upload');
define('_PGTYPEEMAIL', 'e-mail');
define('_PGTYPEURL', 'hyperlink');
define('_PGTYPECURRENCY', 'valuta');
define('_PGTYPEPUBID', 'publicatie ID');
define('_PGUNKNOWNFOLDER', 'Onbekende directory'); //
define('_PGUPDATE', 'Bijwerken');
define('_PGUPLOADDIR', 'Tijdelijke upload directory');
define('_PGUPLOADDIRDOCS', 'Document upload directory');
define('_PGVALUE', 'Waarde');
define('_PGWFCFGLIST', 'Workflow configuratie - selecteer publicatie type');
define('_PGWFCFG', 'Workflow configuratie');
define('_PGWFWORKFLOW', 'Workflow');
define('_PGWORKFLOW', 'Workflow');

?>
