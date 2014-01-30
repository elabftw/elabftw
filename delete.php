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
if (isset($_POST['id']) && is_pos_int($_POST['id'])) {
    $id = $_POST['id'];
}

function is_owned_by_user($id, $table, $userid) {
    global $bdd;
    // type can be experiments or experiments_templates
    $sql = "SELECT userid FROM $table WHERE id = $id";
    $req = $bdd->prepare($sql);
    $req->execute();
    $result = $req->fetchColumn();

    if ($result === $userid) {
        return true;
    } else {
        return false;
    }
}

// DELETE LINKS
function delete_links ($id) {
    global $bdd;
    // get all experiments with that item linked
    $sql = "SELECT id FROM experiments_links WHERE link_id = :link_id";
    $req = $bdd->prepare($sql);
    $req->execute(array(
        'link_id' => $id
    ));
    while ($links = $req->fetch()) {
        $delete_sql = "DELETE FROM experiments_links WHERE id=".$links['id'];
        $delete_req = $bdd->prepare($delete_sql);
        $result = $delete_req->execute();
    }
}

// Item switch
if (isset($_POST['type']) && !empty($_POST['type'])) {
    switch ($_POST['type']) {

    // EXPERIMENTS
    case 'exp':
    // check if we can delete experiments
    if ((!DELETABLE_XP && !$_SESSION['is_admin']) || !is_owned_by_user($id, 'experiments', $_SESSION['userid']) ) {
        die('No rights.');

    } else {

        // delete the experiment
        $sql = "DELETE FROM experiments WHERE id = :id";
        $req = $bdd->prepare($sql);
        $req->execute(array(
            'id' => $id
        ));

        // delete associated tags
        $sql = "DELETE FROM experiments_tags WHERE item_id = :id";
        $req = $bdd->prepare($sql);
        $req->execute(array(
            'id' => $id
        ));

        // delete associated files
        $sql = "DELETE FROM uploads WHERE item_id = :id AND type = :type";
        $req = $bdd->prepare($sql);
        $req->execute(array(
            'id' => $id,
            'type' => 'exp' 
        ));

        // delete links
        delete_links($id);
    }

        break;

    // DELETE EXPERIMENTS TEMPLATES
    case 'tpl':
    $delete_sql = "DELETE FROM experiments_templates WHERE id = :id";
    $delete_req = $bdd->prepare($delete_sql);
    $result = $delete_req->execute(array(
        'id' => $id
    ));
        break;

    // DELETE EXPERIMENT COMMENT
    case 'expcomment':
    $delete_sql = "DELETE FROM experiments_comments WHERE id = :id";
    $delete_req = $bdd->prepare($delete_sql);
    $result = $delete_req->execute(array(
        'id' => $id
    ));
        break;

    // DELETE ITEM
    case 'item':

    // delete the database item
    $sql = "DELETE FROM items WHERE id = :id";
    $req = $bdd->prepare($sql);
    $result1 = $req->execute(array(
        'id' => $id
    ));

    // delete associated tags
    $sql = "DELETE FROM items_tags WHERE item_id = :id";
    $req = $bdd->prepare($sql);
    $result2 = $req->execute(array(
        'id' => $id
    ));

    // delete associated files
    $sql = "DELETE FROM uploads WHERE item_id = :id AND type = :type";
    $req = $bdd->prepare($sql);
    $req->execute(array(
        'id' => $id,
        'type' => 'database' 
    ));

    // delete links
    delete_links($id);

    break;

    // DELETE ITEMS TYPES
    case 'item_type':

    $sql = "DELETE FROM items_types WHERE id = :id";
    $req = $bdd->prepare($sql);
    $req->execute(array(
        'id' => $id
    ));

    break;

    default:
        $err_flag = true;
    }
} else {
    $err_flag = true;
}

if (isset($err_flag)) {
    die('Nope.');
}

