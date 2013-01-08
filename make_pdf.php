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
require_once('inc/common.php');
// Check id is valid and assign it to $id
if(isset($_GET['id']) && is_pos_int($_GET['id'])) {
    $id = $_GET['id'];
} else {
    die("The id parameter in the URL isn't a valid experiment ID");
}

if ($_GET['type'] === 'exp'){
    $table = 'experiments';
}elseif ($_GET['type'] === 'db'){
    $table = 'items';
}else{
    die('bad type');
}

// SQL to get title, body and date
$sql = "SELECT * FROM ".$table." WHERE id = $id";
$req = $bdd->prepare($sql);
$req->execute();
$data = $req->fetch();
    $title = stripslashes(str_replace("&#39;", "'", utf8_decode($data['title'])));
    $date = $data['date'];
    $body = $data['body'];
    if ($table == 'experiments') {
        $elabid = $data['elabid'];
    }
$req->closeCursor();

// SQL to get firstname + lastname
$sql = "SELECT firstname,lastname FROM users WHERE userid = ".$data['userid'];
$req = $bdd->prepare($sql);
$req->execute();
$data = $req->fetch();
$firstname = $data['firstname'];
$lastname = $data['lastname'];
$req->closeCursor();

// SQL to get tags
$sql = "SELECT tag FROM ".$table."_tags WHERE item_id = $id";
$req = $bdd->prepare($sql);
$req->execute();
$tags = null;
while($data = $req->fetch()){
    $tags .= $data['tag'].' ';
}
$req->closeCursor();

// build content of page
$content = "<h1>".$title."</h1><br />
    Date : ".$date."<br />
    <em>Keywords : ".$tags."</em><br />
    <hr>".$body."<br /><br />
    <hr>Made by : ".$firstname." ".$lastname;

// convert in PDF with html2pdf
require_once('lib/html2pdf/html2pdf.class.php');
try
{
    $html2pdf = new HTML2PDF('P', 'A4', 'fr');
    $html2pdf->pdf->SetAuthor($firstname.' '.$lastname);
    $html2pdf->pdf->SetTitle($title);
    $html2pdf->pdf->SetSubject('eLabFTW pdf');
    $html2pdf->pdf->SetKeywords($tags);
    $html2pdf->setDefaultFont('Arial');
    $html2pdf->writeHTML($content);
    // we give the elabid as filename for experiments
    if ($table == 'experiments') {
        $html2pdf->Output($elabid.'.pdf');
    } else {
        $html2pdf->Output('item.pdf');
    }
}

catch(HTML2PDF_exception $e) {
    echo $e;
    exit;
}
?>
