<?php
/**
 * German Translation for PostNuke Pagesetter module
 * 
 * @package Pagesetter module
 * @subpackage Languages
 * @version $Id: list.php,v 1.7 2006/07/12 21:06:57 jornlind Exp $
 * @author Jorn Lind-Nielsen 
 * @author klausd
 * @author Jörg Napp 
 * @link http://www.elfisk.de The Pagesetter Home Page
 * @license http://www.gnu.org/copyleft/gpl.html GNU General Public License
 */
require_once 'modules/pagesetter/pnlang/deu/common.php';

define('_PGBLOCKLISTPUBTYPE', 'Publikationstyp');
define('_PGBLOCKLISTSHOWCOUNT', 'Anzahl der Publikationen (oder Vorgabe, falls leer)');
define('_PGBLOCKLISTSHOWOFFSET', 'Erste anzuzeigende Publikation (frei lassen, um mit Listenbeginn zu starten)');
define('_PGBLOCKLISTTEMPLATE', 'Name des templates "format" für die Liste'); 
define('_PGBLOCKLISTFILTER', 'Filter für die Liste; wie auch in der URL verwendet -- getrennt durch "&amp;", aber ohne "filter=" (z.B. "land:eq:DE")');
define('_PGBLOCKLISTORDERBY', 'ORDERBY-Anweisung, wie in der URL verwendet. Es handelt sich um eine durch Kommata getrennte Liste von Feldnamen ohne "orderby=" (z.B. "core.lastUpdated:desc,title")');

?>