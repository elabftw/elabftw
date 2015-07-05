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

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ordering'])) {

    // remove the create new entry
    unset($_POST['ordering'][0]);
    // loop the array and update sql
    foreach($_POST['ordering'] as $ordering => $id) {
        $id = explode('_', $id);
        $id = $id[1];
        // check we own it
        $sql = "SELECT userid FROM experiments_templates WHERE id = :id";
        $req = $pdo->prepare($sql);
        $req->bindParam(':id', $id, PDO::PARAM_INT);
        $req->execute();
        $exp_tpl = $req->fetch();
        if ($exp_tpl['userid'] != $_SESSION['userid']) {
            exit;
        }
        // update the ordering
        $sql = "UPDATE experiments_templates SET ordering = :ordering WHERE id = :id";
        $req = $pdo->prepare($sql);
        $req->bindParam(':ordering', $ordering, PDO::PARAM_INT);
        $req->bindParam(':id', $id, PDO::PARAM_INT);
        $req->execute();
    }
}
