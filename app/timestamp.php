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
require_once '../inc/common.php';
require_once ELAB_ROOT . 'inc/locale.php';
require_once ELAB_ROOT . 'vendor/autoload.php';
$msg_arr = array();

// ID
if (isset($_GET['id']) && !empty($_GET['id']) && is_pos_int($_GET['id'])) {
    $id = $_GET['id'];
} else {
    display_message('error', _("The id parameter is not valid!"));
    require_once '../inc/footer.php';
    exit;
}

// check if a timestamp provider is set. If not, throw an error message
if (get_config('ts_provider_url')) {
    $ts_url = get_config('ts_provider_url');
} else {
    $msg_arr[] = _('There was an error in the timestamping. No timestamping service provider has been configured.');
    $_SESSION['errors'] = $msg_arr;
    header("Location: ../experiments.php?mode=view&id=$id");
    exit;
}

// this is somewhat reduntant to the php function hash_algos(), but will ensure only strong sha2 algorithms can be used
$hash_algorithms = array('sha256', 'sha384', 'sha512');

// check if a valid hash algorithm has been selected. If not, fall back to sane defaults (sha256)
if (get_config('ts_hash_algorithm') and in_array(get_config('ts_hash_algorithm'), $hash_algorithms)) {
    $ts_hash_algorithm = get_config('ts_hash_algorithm');
} else {
    $ts_hash_algorithm = 'sha256';
}

// Get login/password info
// if the team config is set, we use this one, else, we use the general one, unless we can't (not allowed in config)
if (strlen(get_team_config('stamplogin')) > 2) {
    $login = get_team_config('stamplogin');
    $password = get_team_config('stamppass');
} elseif (get_config('stampshare')) {
    $login = get_config('stamplogin');
    $password = get_config('stamppass');
// otherwise assume no login or password is needed
} else {
    $login = NULL;
    $password = NULL;
}

// generate the pdf to timestamp
$pdf = new \Elabftw\Elabftw\MakePdf($id, 'experiments');
$mpdf = new mPDF();

$pdf_filename = hash("sha512", uniqid(rand(), true)) . ".pdf";
$pdf_path = ELAB_ROOT . 'uploads/' . $pdf_filename;

$mpdf->SetAuthor($pdf->author);
$mpdf->SetTitle($pdf->title);
$mpdf->SetSubject('eLabFTW pdf');
$mpdf->SetKeywords($pdf->tags);
$mpdf->SetCreator('www.elabftw.net');
$mpdf->WriteHTML($pdf->content);
$mpdf->Output($pdf_path, 'F');

require_once '../inc/classes/timestamp.class.php';
$requestfile_path = TrustedTimestamps::createRequestfile($pdf_path);

// REQUEST TOKEN
if (is_string($login) and is_string($password)) {
    $token = TrustedTimestamps::signRequestfile($requestfile_path, $ts_url, $login, $password);
} else {
    $token = TrustedTimestamps::signRequestfile($requestfile_path, $ts_url);
}

try {
    $token = TrustedTimestamps::signRequestfile($requestfile_path, $ts_url, $login, $password);

    // throw an exception if token is not an array
    if (!is_array($token)) {
           throw new Exception(_('There was an error in the timestamping. Login credentials probably wrong or no more credits.'));
       }
} catch (Exception $e) {
        dblog("Error", $_SESSION['userid'], "File: " . $e->getFile() . ", line " . $e->getLine() . ": " . $e->getMessage());
        $msg_arr[] = _('There was an error with the timestamping. Experiment is NOT timestamped. Error has been logged.');
        $_SESSION['errors'] = $msg_arr;
        header("Location: ../experiments.php?mode=view&id=" . $id);
        exit;
}

$longname = hash("sha512", uniqid(rand(), true)) . ".asn1";
$file_path = ELAB_ROOT . 'uploads/' . $longname;

// save the timestamptoken
try {
    file_put_contents($file_path, $token);
} catch (Exception $e) {
    dblog('Error', $_SESSION['userid'], $e->getMessage());
    $msg_arr[] = _('There was an error with the timestamping. Experiment is NOT timestamped. Error has been logged.');
    $_SESSION['errors'] = $msg_arr;
    header("Location: ../experiments.php?mode=view&id=" . $id);
    exit;
}

// SQL
$sql = "UPDATE `experiments` SET `timestamped` = 1, `timestampedby` = :userid, `timestampedwhen` = :timestampedwhen, `timestamptoken` = :timestamptoken WHERE `id` = :id;";
$req = $pdo->prepare($sql);
$req->bindParam(':timestampedwhen', $token['response_time']);
// the date recorded in the db has to match the creation time of the timestamp token
$req->bindParam(':timestamptoken', $token['response_string']);
$req->bindParam(':userid', $_SESSION['userid']);
$req->bindParam(':id', $id);
$res1 = $req->execute();

// add also our pdf to the attached files of the experiment, this way it is kept safely :)
// I had this idea when realizing that if you comment an experiment, the hash won't be good anymore. Because the pdf will contain the new comments.
// Keeping the pdf here is the best way to go, as this leaves room to leave comments.

// this sql is to get the elabid which will be the real_name of the PDF
$sql = "SELECT elabid FROM experiments WHERE id = :id";
$req = $pdo->prepare($sql);
$req->bindParam(':id', $id);
$res2 = $req->execute();
$real_name = $req->fetch(PDO::FETCH_COLUMN) . "-timestamped.pdf";

$md5 = hash_file('md5', $pdf_path);

// DA REAL SQL
$sql = "INSERT INTO uploads(real_name, long_name, comment, item_id, userid, type, md5) VALUES(:real_name, :long_name, :comment, :item_id, :userid, :type, :md5)";
$req = $pdo->prepare($sql);
$req->bindParam(':real_name', $real_name);
$req->bindParam(':long_name', $pdf_filename);
$req->bindValue(':comment', "Timestamped PDF");
$req->bindParam(':item_id', $id);
$req->bindParam(':userid', $_SESSION['userid']);
$req->bindValue(':type', 'exp-pdf-timestamp');
$req->bindParam(':md5', $md5);
$res3 = $req->execute();

if ($res1 && $res2 && $res3) {
    $msg_arr[] =
    $_SESSION['infos'] = $msg_arr;
    header("Location: ../experiments.php?mode=view&id=" . $id);
    exit;
} else {
    die(sprintf(_("There was an unexpected problem! Please %sopen an issue on GitHub%s if you think this is a bug."), "<a href='https://github.com/elabftw/elabftw/issues/'>", "</a>"));
}
