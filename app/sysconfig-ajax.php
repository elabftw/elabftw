<?php
require_once '../inc/common.php';

// only admin can use this
if ($_SESSION['is_sysadmin'] != 1 || $_SERVER['REQUEST_METHOD'] != 'POST') {
    die(_('This section is out of your reach.'));
}

// TEST EMAIL
if (isset($_POST['testemail']) && !empty($_POST['testemail']) && filter_var($_POST['testemail'], FILTER_VALIDATE_EMAIL)) {
        $footer = "\n\n~~~\nSent from eLabFTW http://www.elabftw.net\n";
        $message = Swift_Message::newInstance()
        // Give the message a subject
        ->setSubject(_('[eLabFTW] Test email'))
        // Set the From address with an associative array
        ->setFrom(array(get_config('mail_from') => 'eLabFTW'))
        // Set the To addresses with an associative array
        ->setTo(array($_POST['testemail'] => 'Admin eLabFTW'))
        // Give it a body
        ->setBody(_('Congratulations, you correctly configured eLabFTW to send emails :)') . $footer);
        // generate Swift_Mailer instance
        $mailer = getMailer();
        // SEND EMAIL
    try {
        $mailer->send($message);
    } catch (Exception $e) {
        dblog('Error', 'smtp', $e->getMessage());
        $msg_arr[] = _('Could not send test email. Check the logs to see why.');
        $_SESSION['errors'] = $msg_arr;
        header('Location: ../sysconfig.php');
        exit;
    }
    echo 1;
}
