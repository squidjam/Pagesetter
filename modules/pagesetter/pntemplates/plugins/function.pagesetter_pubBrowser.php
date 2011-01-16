<?php
/** 
 * Function:
 *
 * reads all publications from the current pubtype list and creates an array with
 * previous and next links for browsing through the publications.
 * The first and the last link go the the list of the pubtype.
 * 
 * Usage:
 * Customize the settings below. There's a customization area below. Don't edit anything outside of this area.
 * Put <!--[pagesetter_pubBrowser]--> at the top of the pubtype-full.html template.
 * This function returns an array with 2 values:        
 * Place <!--[$nav.prev]-->, where you want the link to the previous pub be located
 * Place <!--[$nav.next]-->, where you want the link to the previous pub be located
 *
 * Author:  Thomas Smiatek
 * Email : thomas@smiatek.com
 * Revision: 1.1
 * Date:  13.07.2004
 * 
 *
 */
function smarty_function_pagesetter_pubBrowser($args, &$smarty)
{

	//*****************************************
	// Customization area
	//*****************************************
	// Define the text attributes for the links (size, color...)
	$style = "color: black; text-decoration: none;";
	// Define a symbol. It is displayed left of the previous link. You can leave it blank, if you don't need it.
	$prevSymbol = "&lt;&lt; ";
	// Define a symbol. It is displayed right of the next link. You can leave it blank, if you don't need it.
	$nextSymbol = " &gt;&gt;";
	// The previous link of the first publication links to the list of the current pub type. Define the text here.
	$back2overview_left = "back to overview";
	// The next link of the last publication links to the list of the current pub type. Define the text here.
	$back2overview_right = "back to overview";
	//*****************************************
	// End of customization area
	//*****************************************
	
	$core = $smarty->get_template_vars('core');
	
	if (!isset($language))
	    $language = pnUserGetLang();
	
	$pubList = pnModAPIFunc( 'pagesetter', 'user', 'getPubList', array( 'tid' => $core['tid'], 'language' => $language ) );
	
	$html = '';
	
	$counter = 0;
	$navhtml['prev'] = '<a href="index.php?module=pagesetter&amp;tid='.$core['tid'].'" style="'.$style.'">'.$prevSymbol.$back2overview_left.'</a>';
	$navhtml['next'] = '<a href="index.php?module=pagesetter&amp;tid='.$core['tid'].'" style="'.$style.'">'.$nextSymbol.$back2overview_right.'</a>';
	$foundprev = false;
	$foundnext = false;
	foreach ($pubList['publications'] as $pub)
  	{
		if (($pub['title'] != $core['title']) && ($foundprev == false))
		{
			$link = pnModURL('pagesetter','user','viewpub',array('tid' => $core['tid'], 'pid' => $pub['pid'] ));
			$navhtml['prev'] = '<a href="'.$link.'" style="'.$style.'">'.$prevSymbol.$pub['title'].'</a>';
		}
		if ($pub['title'] == $core['title'])
		{
			$foundprev = true;
		}
		if (($pub['title'] != $core['title']) && ($foundprev == true) && ($foundnext == false))
		{
			$foundnext = true;
			$link = pnModURL( 'pagesetter', 'user', 'viewpub', array( 'tid' => $core['tid'], 'pid' => $pub['pid'] ));
			$navhtml['next'] = '<a href="'.$link.'" style="'.$style.'">'.$pub['title'].$nextSymbol.'</a>';
		}
	}
	
	$smarty->assign('nav', $navhtml );
	
}
?>
