<?php
/********************************************************************************
*                                                                               *
*   Copyright 2012 Nicolas CARPi (nicolas.carpi@gmail.com)                      *
*   http://www.elabftw.net/                                                     *
*                                                                               *
********************************************************************************/

/********************************************************************************
*  This file is part of eLabFTW.                                                *
*                                                                               *
*    eLabFTW is free software: you can redistribute it and/or modify            *
*    it under the terms of the GNU Affero General Public License as             *
*    published by the Free Software Foundation, either version 3 of             *
*    the License, or (at your option) any later version.                        *
*                                                                               *
*    eLabFTW is distributed in the hope that it will be useful,                 *
*    but WITHOUT ANY WARRANTY; without even the implied                         *
*    warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR                    *
*    PURPOSE.  See the GNU Affero General Public License for more details.      *
*                                                                               *
*    You should have received a copy of the GNU Affero General Public           *
*    License along with eLabFTW.  If not, see <http://www.gnu.org/licenses/>.   *
*                                                                               *
********************************************************************************/
require_once 'inc/common.php';
require_once 'vendor/autoload.php';
// formkey stuff
//require_once 'inc/classes/formkey.class.php';
//$formKey = new formKey();

//Array to store validation errors
$msg_arr = array();
//Validation error flag
$errflag = false;

// CHECKS
/*
// Check the form_key
if (!isset($_POST['form_key']) || !$formKey->validate()) {
    // form key is invalid
    $msg_arr[] = 'The form key is invalid !';
    $errflag = true;
}
 */
// ID
if (is_pos_int($_POST['task_id'])) {
    $id = $_POST['task_id'];
} else {
    $id='';
    $msg_arr[] = _("The id parameter is not valid!");
    $errflag = true;
}
$title = check_title($_POST['title']);
// the date gets updated to today's date
$date = kdate();
$description = check_body($_POST['body']);
$assignedUser = $_POST['assignedUser'];
If(isset($_POST['done']))
{
  $done = 1;
} else {
  $done = 0;
}

// Store stuff in Session to get it back if error input
$_SESSION['new_title'] = $title;
$_SESSION['new_date'] = $date;

// If input errors, redirect back to the edit form
if ($errflag) {
    $_SESSION['errors'] = $msg_arr;
    session_write_close();
    header("location: tasks.php?mode=edit&id=$id");
    exit;
}

// SQL for editDB
/*    $sql = "UPDATE tasks
        SET title = :title,
        datetime = :datetime,
        description = :description,
        assignedUser = :assignedUser
        WHERE id = :id";
$req = $pdo->prepare($sql);
$result = $req->execute(array(
    'title' => $title,
    'datetime' => $date,
    'description' => $description,
    'assignedUser' => $_SESSION['userid'],
    'id' => $id
));
*/
$sql = "UPDATE tasks
SET title = :title,
    description = :description,
    assignedUser = :assignedUser,
    status = :status
WHERE id = :id";
$req = $pdo->prepare($sql);
$result = $req->execute(array(
  'title' => $title,
  'description' => $description,
  'assignedUser' => $assignedUser,
  'status' => $done,
  'id' => $id
));

$sql = "SELECT * FROM tasks WHERE id = '$id'";
$res = $pdo->query($sql);
$task = $res->fetch();

$sql = "SELECT * FROM users WHERE userid = '$assignedUser'";
$res = $pdo->query($sql);
$user = $res->fetch();
//echo $user['email'];



$sql = "SELECT * FROM users WHERE userid = '$task[creator]'";
$res = $pdo->query($sql);
$creator = $res->fetch();


// Check if insertion is successful
if ($result) {

  if($creator['userid'] <> $user['userid'])
  {
  // If task is for another user send email
  $url = 'https://'.$_SERVER['SERVER_NAME'].':'.$_SERVER['SERVER_PORT'].$_SERVER['PHP_SELF'];
  $url = str_replace('editTask-exec.php', 'tasks.php', $url);
  // we send an email to each validated new user
  $footer = "\n\n~~~\nSent from eLabFTW http://www.elabftw.net\n";
  // Create the message
  $message = Swift_Message::newInstance()
  // Give the message a subject
  // no i18n here
  ->setSubject('[eLabFTW] New Task assigned')
  // Set the From address with an associative array
  ->setFrom(array(get_config('smtp_username') => get_config('smtp_username')))
  // Set the To addresses with an associative array
  ->setTo(array($user['email']=> 'eLabFTW'))
  // Give it a body
  ->setContentType('text/html')
  ->setBody('Hello, <br><br>'.$creator['firstname'].' '.$creator['lastname'].' assigned a new task for you.<br><br><b>'.$task['title'].'</b><br>'.$task['description'].'<br><br>More details <a href="'.$url.'">here</a>.'.$footer);
  $transport = Swift_SmtpTransport::newInstance(
  get_config('smtp_address'),
  get_config('smtp_port'),
  get_config('smtp_encryption')
  )
  ->setUsername(get_config('smtp_username'))
  ->setPassword(get_config('smtp_password'));
  $mailer = Swift_Mailer::newInstance($transport);
  // now we try to send the email
  echo $message;
  try {
    $mailer->send($message);
  } catch (Exception $e) {
    // log the error
    echo $e->getMessage();

    dblog('Error', $_SESSION['userid'], $e->getMessage());
    $errflag = true;
  }
  if ($errflag) {
    $msg_arr[] = _('There was a problem sending the email! Error was logged.');
    $_SESSION['errors'] = $msg_arr;

  }
}
    // unset session variables
    unset($_SESSION['new_title']);
    unset($_SESSION['new_date']);
    unset($_SESSION['errors']);
    header("location: tasks.php?mode=view&id=$id");



    exit;
} else {
    die(sprintf(_("There was an unexpected problem! Please %sopen an issue on GitHub%s if you think this is a bug."), "<a href='https://github.com/NicolasCARPi/elabftw/issues/'>", "</a>"));
}
