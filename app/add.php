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
// add.php -- called with POST containing data, type and id.
require_once '../inc/common.php';

// Check expid is valid and assign it to $id
if (isset($_POST['item_id']) && is_pos_int($_POST['item_id'])) {
    $id = $_POST['item_id'];
} else {
    die();
}

// what do we add ?
switch ($_POST['type']) {
    // TAGS FOR EXPERIMENTS
    case 'exptag':
        // Sanitize tag, we remove '\' because it fucks up the javascript if you have this in the tags
        $tag = strtr(filter_var($_POST['tag'], FILTER_SANITIZE_STRING), '\\', '');

        // check for string length and if user owns the experiment
        if (strlen($tag) > 0 && is_owned_by_user($id, 'experiments', $_SESSION['userid'])) {
                // SQL for addtag
                $sql = "INSERT INTO experiments_tags (tag, item_id, userid) VALUES(:tag, :item_id, :userid)";
                $req = $pdo->prepare($sql);
                $req->bindParam(':tag', $tag, PDO::PARAM_STR);
                $req->bindParam(':item_id', $_POST['item_id'], PDO::PARAM_INT);
                $req->bindParam(':userid', $_SESSION['userid'], PDO::PARAM_INT);
                $req->execute();
        }

        break;

    // TAG FOR ITEMS
    case 'itemtag':
        // Sanitize tag, we remove '\' because it fucks up the javascript if you have this in the tags
        $tag = strtr(filter_var($_POST['tag'], FILTER_SANITIZE_STRING), '\\', '');

        // check for string length only as there is no owning of database item
        if (strlen($tag) > 0) {
            // SQL for add tag to database item
            $sql = "INSERT INTO items_tags (tag, item_id) VALUES(:tag, :item_id)";
            $req = $pdo->prepare($sql);
            $req->bindParam(':tag', $tag, PDO::PARAM_STR);
            $req->bindParam(':item_id', $_POST['item_id'], PDO::PARAM_INT);
            $req->execute();
        }

        break;

    // ADD A LINK TO AN EXPERIMENT
    case 'link':
        // check link is int and experiment is owned by user
        if (filter_var($_POST['link_id'], FILTER_VALIDATE_INT) &&
            is_owned_by_user($id, 'experiments', $_SESSION['userid'])) {

                // SQL for addlink
                $sql = "INSERT INTO experiments_links (item_id, link_id) VALUES(:item_id, :link_id)";
                $req = $pdo->prepare($sql);
                $req->bindParam(':item_id', $_POST['item_id'], PDO::PARAM_INT);
                $req->bindParam(':link_id', $_POST['link_id'], PDO::PARAM_INT);
                $result = $req->execute();
        }
        break;

    default:
        die();
}
