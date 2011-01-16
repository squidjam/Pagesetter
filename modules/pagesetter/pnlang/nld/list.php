<?php
/**
 * Dutch Translation for PostNuke Pagesetter module
 *
 * @package Pagesetter module
 * @subpackage Languages
 * @version $Id: list.php 327 2007-02-12 09:35:51Z teb $
 * @author Jorn Lind-Nielsen
 * @author Arjen Tebbenhof (Teb)
 * @link http://www.elfisk.dk The Pagesetter Home Page
 * @link http://postnuke.opencms.nl The Dutch PostNuke Community
 * @license http://www.gnu.org/copyleft/gpl.html GNU General Public License
 */

require_once dirname(__FILE__).'/common.php';

define('_PGBLOCKLISTPUBTYPE', 'Publicatie type');
define('_PGBLOCKLISTSHOWCOUNT', 'Aantal publicaties (laat leeg voor standaard aantal)');
define('_PGBLOCKLISTSHOWOFFSET', 'Eerste publicatie nummer die getoond moet worden (laat leeg voor de eerste in de lijst)');
define('_PGBLOCKLISTTEMPLATE', 'Sjabloon formaat om lijst items te tonen');
define('_PGBLOCKLISTFILTER', 'Lijst filter zoals in URL gebruikt, gescheiden door "&", maar zonder "filter=" (dus "land:eq:NL")');
define('_PGBLOCKLISTORDERBY', 'Sorteer expressie zoals gebruikt in URL. Dit hoort een komma-gescheiden lijst van veldnamen zonder "orderby=" te zijn (dus "core.laatstBijgewerkt:beschr,titel")');

?>