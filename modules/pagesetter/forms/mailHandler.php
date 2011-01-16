<?php
// $Id: mailHandler.php,v 1.4 2005/10/28 19:42:50 jornlind Exp $
// =======================================================================
// Pagesetter by Jorn Lind-Nielsen (C) 2003.
// ----------------------------------------------------------------------
// For POST-NUKE Content Management System
// Copyright (C) 2002 by the PostNuke Development Team.
// http://www.postnuke.com/
// ----------------------------------------------------------------------
// LICENSE
//
// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License (GPL)
// as published by the Free Software Foundation; either version 2
// of the License, or (at your option) any later version.
//
// This program is distributed in the hope that it will be useful,
// but WithOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// To read the license please visit http://www.gnu.org/copyleft/gpl.html
// =======================================================================

require_once 'modules/pagesetter/guppy/guppy.php';

class MailHandler extends GuppyDecodeHandler
{
  function button($event)
  {
    $tid = $event['extra']['tid'];
    $pid = $event['extra']['pid'];

      // Check access
    if (!pnSecAuthAction(0, 'pagesetter::', '$tid:$pid:', ACCESS_READ))
      return $this->commander->errorMessage( pagesetterErrorPage(__FILE__, __LINE__, _PGNOAUTH) );

    if ($event['action']['button'] == 'send')
    {
      $mailData = $event['data']['mail']['rows'][0];
  
      $recipient = "$mailData[mailTo]";
      $subject   = $mailData['subject'];
      $text      = $mailData['text'];

      if (pnModAvailable('Mailer')  &&  pnModAPILoad('Mailer', 'user'))
      {
        $ok = pnModAPIFunc('mailer', 'user', 'sendmessage',
                           array('toaddress'   => $recipient,
                                 'toname'      => $mailData['nameTo'],
                                 'fromname'    => $mailData['nameFrom'],
                                 'fromaddress' => $mailData['mailFrom'],
                                 'subject'     => $subject,
                                 'body'        => $text,
                                 'html'        => false));
      }
      else
      {
        $headers  = "MIME-Version: 1.0\r\n";
        $headers .= "Content-type: text/plain; charset=iso-8859-1\r\n";
        $headers .= "To: $mailData[nameTo]&lt;$mailData[mailTo]&gt;\r\n";
        $headers .= "From: $mailData[nameFrom]<$mailData[mailFrom]>\r\n";

        $ok = mail($recipient, $subject, $text, $headers);
      }

      if ($ok === true)
        $this->commander->close(pnModURL('pagesetter', 'user', 'viewpub',
                                         array('tid' => $tid, 'pid' => $pid)));
      else if ($ok === false)
        $this->commander->errorMessage(_PGMAILFAILED);
      else
        $this->commander->errorMessage(_PGMAILFAILED . " - $ok");
    }
    else if ($event['action']['button'] == 'cancel')
    {
      $this->commander->close(pnModURL('pagesetter', 'user', 'viewpub',
                                       array('tid' => $tid, 'pid' => $pid)));
    }
  }
}


?>
