<?php
// $Id: pnversion.php,v 1.44 2006/03/27 21:53:17 jornlind Exp $
$modversion['name'] = 'Pagesetter';
$modversion['version'] = '6.3.0.1';
$modversion['description'] = 'Page creation and manager module';
$modversion['credits'] = 'docs/credits.txt';
$modversion['help'] = 'docs/manual/PagesetterManual.html';
$modversion['changelog'] = 'docs/changelog.txt';
$modversion['license'] = 'docs/copying.txt';
$modversion['official'] = 0;
$modversion['author'] = 'J&oslash;rn Lind-Nielsen';
$modversion['contact'] = 'jln@fjeldgruppen.dk';
$modversion['admin'] = 1;
$modversion['dependencies'] = array(
    array( 'modname'    => 'scribite',
           'minversion' => '3.0', 'maxversion' => '',
           'status'     => PNMODULE_DEPENDENCY_RECOMMENDED),
    array( 'modname'    => 'Topics',
           'minversion' => '1.0', 'maxversion' => '',
           'status'     => PNMODULE_DEPENDENCY_REQUIRED));
$modversion['securityschema'] = array('pagesetter::' => 'tid:pid:', 'pagesetter:workflow:' => '::');
?>
