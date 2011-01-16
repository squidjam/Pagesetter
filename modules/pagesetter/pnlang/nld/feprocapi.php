<?php
/**
 * Dutch Translation for PostNuke Pagesetter module
 *
 * @package Pagesetter module
 * @subpackage Languages
 * @version $Id: feprocapi.php 327 2007-02-12 09:35:51Z teb $
 * @author Jorn Lind-Nielsen
 * @author Arjen Tebbenhof (Teb)
 * @link http://www.elfisk.dk The Pagesetter Home Page
 * @link http://postnuke.opencms.nl The Dutch PostNuke Community
 * @license http://www.gnu.org/copyleft/gpl.html GNU General Public License
 */

define('_PGFEP_SUBMIT', 'Pagesetter Verzendformulier');
define('_PGFEP_SUBMITDESCR', 'Slaat formulier gegevens op in een Pagesetter publicatie.');
define('_PGFEP_SUBMITTID', 'Pagesetter type ID');
define('_PGFEP_SUBMITTOPIC', 'Onderwerp ID');
define('_PGFEP_SUBMITAUTHOR', 'Auteur naam');
define('_PGFEP_SUBMITSTATE', 'Workflow status');
define('_PGFEP_SUBMITHELP', '<p>Maakt een nieuwe Pagesetter publicatie aan van het aangegeven type ID. Onderwerp en Auteur moeten ook opgegeven worden als onderdeel van de handler configuratie, omdat de overgebleven ingebouwde waarden al in de code zitten.</p><p>Alle veldnamen die door de gebruiker zijn opgegeven worden eerst uit het ingezonden formulier gehaald, alvorens de pagesetter handler uit te voeren. Veldwaarden worden aangepast op veldnaam &mdash; FormExpress veldnamen moeten overeenkomen met de PageSetter veld namen.</p>');

?>
