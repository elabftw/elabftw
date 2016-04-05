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

// track the sql request results
$success = array();

foreach ($_POST as $key => $value) {
    switch ($key) {
        case 'ordering_templates':
            // remove the create new entry
            unset($_POST['ordering_templates'][0]);
            // loop the array and update sql
            foreach ($_POST['ordering_templates'] as $ordering => $id) {
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
                try {
                    $success[] = $req->execute();
                } catch (Exception $e) {
                    $Logs = new Logs();
                    $Logs->create('Error', $_SESSION['userid'], $e->getMessage());
                    echo 0;
                }
            }
            break;

        case 'ordering_status':
            // loop the array and update sql
            foreach ($_POST['ordering_status'] as $ordering => $id) {
                // check we own it
                $sql = "SELECT team FROM status WHERE id = :id";
                $req = $pdo->prepare($sql);
                $req->bindParam(':id', $id, PDO::PARAM_INT);
                $req->execute();
                $team = $req->fetch();
                if ($team['team'] != $_SESSION['team_id']) {
                    exit;
                }
                // update the ordering
                $sql = "UPDATE status SET ordering = :ordering WHERE id = :id";
                $req = $pdo->prepare($sql);
                $req->bindParam(':ordering', $ordering, PDO::PARAM_INT);
                $req->bindParam(':id', $id, PDO::PARAM_INT);
                try {
                    $success[] = $req->execute();
                } catch (Exception $e) {
                    $Logs = new Logs();
                    $Logs->create('Error', $_SESSION['userid'], $e->getMessage());
                    echo 0;
                }

            }
            break;

        case 'ordering_itemstypes':
            // loop the array and update sql
            foreach ($_POST['ordering_itemstypes'] as $ordering => $id) {
                $id = explode('_', $id);
                $id = $id[1];
                // check we own it
                $sql = "SELECT team FROM items_types WHERE id = :id";
                $req = $pdo->prepare($sql);
                $req->bindParam(':id', $id, PDO::PARAM_INT);
                $req->execute();
                $team = $req->fetch();
                if ($team['team'] != $_SESSION['team_id']) {
                    exit;
                }
                // update the ordering
                $sql = "UPDATE items_types SET ordering = :ordering WHERE id = :id";
                $req = $pdo->prepare($sql);
                $req->bindParam(':ordering', $ordering, PDO::PARAM_INT);
                $req->bindParam(':id', $id, PDO::PARAM_INT);
                try {
                    $success[] = $req->execute();
                } catch (Exception $e) {
                    $Logs = new Logs();
                    $Logs->create('Error', $_SESSION['userid'], $e->getMessage());
                    echo 0;
                }

            }
            break;

        default:
            $success[] = false;
    }

    if (in_array(false, $success)) {
        echo 0;
    } else {
        echo 1;
    }
}
