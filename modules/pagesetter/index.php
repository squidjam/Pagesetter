<?php
// $Id: index.php,v 1.1.1.2 2003/10/14 13:36:44 jornlind Exp $

if (!defined("LOADED_AS_MODULE"))
{
    die ("You can't access this file directly...");
}

pnRedirect(pnModURL('pagesetter',
                    'user',
                    'view'));
?>
