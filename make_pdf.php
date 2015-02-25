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
require_once ELAB_ROOT . 'inc/locale.php';
require_once ELAB_ROOT . 'inc/classes/makepdf.class.php';
require_once ELAB_ROOT . 'vendor/autoload.php';

// Check id is valid and assign it to $id
if (isset($_GET['id']) && is_pos_int($_GET['id'])) {
    $id = $_GET['id'];
} else {
    die(_("The id parameter is not valid!"));
}

// check the type
if (($_GET['type'] === 'experiments') || ($_GET['type'] === 'items')) {
    $type = $_GET['type'];
} else {
    die(_("The type parameter is not valid."));
}

// do the pdf
$pdf = new \elabftw\elabftw\MakePdf($id, $type);
$mpdf = new mPDF();

$mpdf->SetAuthor($pdf->author);
$mpdf->SetTitle($pdf->title);
$mpdf->SetSubject('eLabFTW pdf');
$mpdf->SetKeywords($pdf->tags);
$mpdf->SetCreator('www.elabftw.net');
$mpdf->WriteHTML($pdf->content);
$mpdf->Output($pdf->getFileName(), 'I');
