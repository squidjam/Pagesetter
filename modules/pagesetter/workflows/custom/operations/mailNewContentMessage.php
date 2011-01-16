<?php

function pagesetter_operation_mailNewContentMessage(&$publication, $core, $args)
{
  $editURL = pnModUrl('pagesetter', 'user', 'pubedit',
                      array('tid'    => $core['tid'],
                            'id'     => $core['id'],
                            'action' => 'edit'));

  if ($args['PUBREFID'] == 'pid')
    $viewURL = pnModUrl('pagesetter', 'user', 'viewpub',
                        array('tid'    => $core['tid'],
                              'pid'    => $publication['core']['pid']));
  else
    $viewURL = pnModUrl('pagesetter', 'user', 'viewpub',
                        array('tid'    => $core['tid'],
                              'id'     => $core['id']));

  $message = $args['MESSAGE'] . "\n\nEdit: $editURL\nView: $viewURL";
  $subject = $args['SUBJECT'];
  $mailTo  = $args['RECIPIENT'];

  if (!empty($mailTo))
  {
    $recipients = str_replace ("\r\n", ',', $mailTo);
    $recipients = str_replace("\n", ",", $recipients);
    $recipients = str_replace("\r", ",", $recipients);

    if (pnModAvailable('Mailer')  &&  pnModAPILoad('Mailer', 'user'))
    {
      $ok = pnModAPIFunc('Mailer', 'user', 'sendmessage',
                         array('toaddress' => $recipients,
                               'subject'   => $subject,
                               'body'      => $message,
                               'html'      => false));

      if ($ok !== true  &&  $ok !== false)
        return pagesetterWarningWorkflow($ok);
    }
    else
      $ok = mail($recipients, $subject, $message);

    if (!$ok)
      return pagesetterWarningWorkflow("Mailing new content to '$mailTo' failed.");
  }

  return pagesetterWFOperationOk;
}

?>