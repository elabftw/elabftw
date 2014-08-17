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
$msg_arr = array();

// ID
if (isset($_GET['id']) && !empty($_GET['id']) && is_pos_int($_GET['id'])) {
    $id = $_GET['id'];
} else {
    $message = "The id parameter in the URL isn't a valid experiment ID.";
    display_message('error', $message);
    require_once 'inc/footer.php';
    die();
}

// Get login/password info
$login = get_config('stamplogin');
$password = get_config('stamppass');

if (strlen($login) < 2) {
    $msg_arr[] = "The timestamping feature is not configured. Please read the <a href='https://github.com/NicolasCARPi/elabftw/wiki/finalizing#setting-up-timestamping'>wiki</a>.";
    $_SESSION['errors'] = $msg_arr;
    header("Location:experiments.php?mode=view&id=$id");
    exit;
}


// generate the pdf to timestamp
$pdf_path = make_pdf($id, 'experiments', 'uploads/tmp');

// generate the sha256 hash that we will send
$hashedDataToTimestamp = hash_file('sha256', "uploads/tmp/$pdf_path");
// delete the pdf, we don't use it anymore
unlink("uploads/tmp/$pdf_path");

$dataToSend = array ('hashAlgo' => 'SHA256', 'withCert' => 'true', 'hashValue' => $hashedDataToTimestamp);
$dataQuery = http_build_query($dataToSend);
$context_options = array (
    'http' => array (
        'method' => 'POST',
        'header'=> "Content-type: application/x-www-form-urlencoded\r\n"
        ."Content-Length: " . strlen($dataQuery) . "\r\n"
        ."Authorization: Basic ".base64_encode($login.':'.$password)."\r\n",
        'content' => $dataQuery
        )
);

$context = stream_context_create($context_options);
$fp = fopen("https://ws.universign.eu/tsa/post/", 'r', false, $context);
$token = stream_get_contents($fp);

$longname = hash("sha512", uniqid(rand(), true)).".asn1";
$file_path = 'uploads/'.$longname;

// save the timestamptoken
try {
    file_put_contents($file_path, $token);
} catch (Exception $e) {
    dblog('Error', $_SESSION['userid'], $e->getMessage());
    $msg_arr[] = "There was an error with the timestamping. Error has been logged.";
    $_SESSION['errors'] = $msg_arr;
    header("Location:experiments.php?mode=view&id=$id");
    exit;
}
$sql = "UPDATE `experiments` SET `timestamped` = 1, `timestampedby` = :userid, `timestampedwhen` = CURRENT_TIMESTAMP, `timestamptoken` = :longname WHERE `id` = :id;";
$req = $pdo->prepare($sql);
$req->bindParam(':longname', $longname);
$req->bindParam(':userid', $_SESSION['userid']);
$req->bindParam(':id', $id);
$res = $req->execute();
if ($res) {
    $msg_arr[] = "Experiment timestamped with success.";
    $_SESSION['infos'] = $msg_arr;
    header("Location:experiments.php?mode=view&id=$id");
    exit;
} else {
    die('SQL failed');
}
