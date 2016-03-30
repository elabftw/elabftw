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
// delete.php
// This page is called with POST requests containing an id and a type.
namespace Elabftw\Elabftw;

use Exception;

require_once '../inc/common.php';

try {
    // Check id is valid and assign it to $id
    if (isset($_POST['id']) && Tools::checkId($_POST['id'])) {
        $id = $_POST['id'];
    } else {
        throw new Exception('pwet');
    }

    // Item switch
    if (isset($_POST['type']) && !empty($_POST['type'])) {
        switch ($_POST['type']) {

            // EXPERIMENTS
            case 'exp':
                // check if we can delete experiments
                if (((get_team_config('deletable_xp') == '0') &&
                    !$_SESSION['is_admin']) ||
                    !is_owned_by_user($id, 'experiments', $_SESSION['userid'])) {
                    throw new Exception("You don't have the rights to delete this experiment.");

                } else {

                    // delete the experiment
                    $sql = "DELETE FROM experiments WHERE id = :id";
                    $req = $pdo->prepare($sql);
                    $req->execute(array(
                        'id' => $id
                    ));

                    // delete associated tags
                    $sql = "DELETE FROM experiments_tags WHERE item_id = :id";
                    $req = $pdo->prepare($sql);
                    $req->execute(array(
                        'id' => $id
                    ));

                    // delete associated files
                    $sql = "SELECT id, real_name, long_name FROM uploads WHERE item_id = :id AND type = :type";
                    $req = $pdo->prepare($sql);
                    $req->execute(array(
                        'id' => $id,
                        'type' => 'experiments'
                    ));
                    while ($uploads = $req->fetch()) {
                        // delete file from disk
                        $filepath = ELAB_ROOT . 'uploads/' . $uploads['long_name'];
                        unlink($filepath);
                        // remove thumbnail
                        $ext = Tools::getExt($uploads['real_name']);
                        if (file_exists(ELAB_ROOT . 'uploads/' . $uploads['long_name'] . '_th.' . $ext)) {
                            unlink(ELAB_ROOT . 'uploads/' . $uploads['long_name'] . '_th.' . $ext);
                        }
                        // now delete row in uploads table
                        $delete_sql = "DELETE FROM uploads WHERE id = :id";
                        $delete_req = $pdo->prepare($delete_sql);
                        $delete_req->bindParam('id', $uploads['id']);
                        $delete_req->execute();
                    }

                    // delete associated links
                    $delete_sql = "DELETE FROM experiments_links WHERE item_id = :item_id";
                    $delete_req = $pdo->prepare($delete_sql);
                    $result = $delete_req->execute(array(
                        'item_id' => $id
                    ));

                    // delete associated experiments comments
                    $sql = "DELETE FROM experiments_comments WHERE exp_id = :id";
                    $req = $pdo->prepare($sql);
                    $req->execute(array(
                        'id' => $id
                    ));

                    $msg_arr[] = _('Experiment was deleted successfully.');
                    $_SESSION['ok'] = $msg_arr;

                }

                break;

            // DELETE EXPERIMENTS TEMPLATES
            case 'tpl':
                $delete_sql = "DELETE FROM experiments_templates WHERE id = :id";
                $delete_req = $pdo->prepare($delete_sql);
                $result = $delete_req->execute(array(
                    'id' => $id
                ));
                if ($result) {
                    $msg_arr[] = _('Template was deleted successfully.');
                    $_SESSION['ok'] = $msg_arr;
                }
                break;

            // DELETE ITEM
            case 'item':
                // to store the outcome of sql
                $result = array();

                // delete the database item
                $sql = "DELETE FROM items WHERE id = :id";
                $req = $pdo->prepare($sql);
                $result[] = $req->execute(array(
                    'id' => $id
                ));

                // delete associated tags
                $sql = "DELETE FROM items_tags WHERE item_id = :id";
                $req = $pdo->prepare($sql);
                $result[] = $req->execute(array(
                    'id' => $id
                ));

                // delete associated files
                $sql = "SELECT real_name, long_name FROM uploads WHERE item_id = :id AND type = :type";
                $req = $pdo->prepare($sql);
                $req->execute(array(
                    'id' => $id,
                    'type' => 'items'
                ));
                while ($uploads = $req->fetch()) {
                    $filepath = ELAB_ROOT . 'uploads/' . $uploads['long_name'];
                    unlink($filepath);
                    // remove thumbnail
                    $ext = Tools::getExt($uploads['real_name']);
                    if (file_exists(ELAB_ROOT . 'uploads/' . $uploads['long_name'] . '_th.' . $ext)) {
                        unlink(ELAB_ROOT . 'uploads/' . $uploads['long_name'] . '_th.' . $ext);
                    }
                }
                // now remove them from the database
                $sql = "DELETE FROM uploads WHERE item_id = :id AND type = :type";
                $req = $pdo->prepare($sql);
                $result[] = $req->execute(array(
                    'id' => $id,
                    'type' => 'items'
                ));

                // delete links of this item in experiments with this item linked
                // get all experiments with that item linked
                $sql = "SELECT id FROM experiments_links WHERE link_id = :link_id";
                $req = $pdo->prepare($sql);
                $result[] = $req->execute(array(
                    'link_id' => $id
                ));
                while ($links = $req->fetch()) {
                    $delete_sql = "DELETE FROM experiments_links WHERE id = :links_id";
                    $delete_req = $pdo->prepare($delete_sql);
                    $delete_req->bindParam(':links_id', $links['id']);
                    $result[] = $delete_req->execute();
                }

                // test if there was an error somewhere
                if (in_array(false, $result)) {
                    $msg_arr[] = sprintf(_("There was an unexpected problem! Please %sopen an issue on GitHub%s if you think this is a bug."), "<a href='https://github.com/elabftw/elabftw/issues/'>", "</a>");
                    $_SESSION['ko'] = $msg_arr;
                } else {
                    $msg_arr[] = _('Item was deleted successfully.');
                    $_SESSION['ok'] = $msg_arr;
                }


                break;

            // DELETE TAGS
            case 'exptag':
                if (Tools::checkId($_POST['item_id'])) {
                    $item_id = $_POST['item_id'];
                } else {
                    // pwet
                }

                if (is_owned_by_user($item_id, 'experiments', $_SESSION['userid'])) {

                    $delete_sql = "DELETE FROM experiments_tags WHERE id = :id";
                    $delete_req = $pdo->prepare($delete_sql);
                    $delete_req->execute(array(
                        'id' => $id
                    ));
                }
                break;

            case 'itemtag':
                $delete_sql = "DELETE FROM items_tags WHERE id = :id";
                $delete_req = $pdo->prepare($delete_sql);
                $delete_req->execute(array(
                    'id' => $id
                ));
                break;

            case 'status':
                // normally there is no experiments left with this status
                $delete_sql = "DELETE FROM status WHERE id = :id";
                $delete_req = $pdo->prepare($delete_sql);
                $delete_req->execute(array(
                    'id' => $id
                ));
                $msg_arr[] = _('Status was deleted successfully.');
                $_SESSION['ok'] = $msg_arr;
                break;

            // END
            default:
                throw new Exception('who cares, this code will disappear');
        }
    }

} catch (Exception $e) {
    dblog('Error', $_SESSION['userid'], $e->getMessage());
    $_SESSION['ko'][] = $e->getMessage();
}
