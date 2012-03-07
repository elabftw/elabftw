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
if(filter_var($_GET['id'], FILTER_VALIDATE_INT)) {
    $id = $_GET['id'];
} else {
    die("The id parameter in the URL isn't a valid experiment ID");
}

if ($_GET['type'] === 'exp'){
    $table = 'experiments';
}elseif ($_GET['type'] === 'prot'){
    $table = 'protocols';
}else{
    die('bad type');
}

// SQL to get title, body and date
$sql = "SELECT title, body, date, userid FROM ".$table." WHERE id = $id";
$req = $bdd->prepare($sql);
$req->execute();
$data = $req->fetch();
// problem : fpdf is not utf-8 aware...
    $title = stripslashes(str_replace("&#39;", "'", utf8_decode($data['title'])));
    $date = $data['date'];
    $body = stripslashes(str_replace("&#39;", "'", utf8_decode($data['body'])));
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
$tags = NULL;
while($data = $req->fetch()){
    $tags .= stripslashes(str_replace("&#39;", "'", utf8_decode($data['tag']))).' ';
}
$req->closeCursor();


// PDF creation
require_once('lib/fpdf.php');
class PDF extends FPDF
{
// Page header
function Header()
{
    global $title;
// Logo
$this->Image('img/institut_curie.jpg',10,6,30);
// Arial bold 15
$this->SetFont('Arial','B',15);
// Width of title
$w = $this->GetStringWidth($title)+6;
$this->SetX((210-$w)/2);
// Colors of frame, background and text
$this->SetDrawColor(0,80,180);
$this->SetFillColor(230,230,0);
$this->SetTextColor(220,50,50);
// Title
$this->Cell(100,10,$title);
// Line break
$this->Ln(20);
}


// Page footer
function Footer()
{
// Position at 1.5 cm from bottom
$this->SetY(-15);
// Arial italic 8
$this->SetFont('Arial','I',8);
// Page number
$this->Cell(0,10,'Page '.$this->PageNo().'/{nb}',0,0,'C');
}
}

// Instanciation of inherited class
$pdf = new PDF();
$pdf->AliasNbPages();
$pdf->AddPage();
// user + date
$pdf->SetFont('Times','B',12);
$pdf->Cell(190,10,'Made by '.$firstname.' '.$lastname.' on '.$date);
$pdf->Ln();
// tags
$pdf->SetFont('Times','I',12);
$tags = "Keywords : ".$tags;
$pdf->Cell(190,10,$tags);
$pdf->Ln();
// body
$pdf->SetFont('Times','',14);
$pdf->MultiCell(190,5,$body,'J');
$pdf->Output();
