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

            // DELETE EXPERIMENTS TEMPLATES
            case 'tpl':
                $Templates = new Templates($_SESSION['team_id']);
                if ($Templates->destroy($_POST['id'])) {
                    $_SESSION['ok'][] = _('Template was deleted successfully.');
                }
                break;

            // DELETE TAGS
            case 'exptag':
                if (Tools::checkId($_POST['item_id']) === false) {
                    throw new Exception(_('The id parameter is invalid.'));
                }

                if (is_owned_by_user($_POST['item_id'], 'experiments', $_SESSION['userid'])) {

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
                $_SESSION['ok'][] = _('Status was deleted successfully.');
                break;

            // END
            default:
                throw new Exception('who cares, this code will disappear');
        }
    }

} catch (Exception $e) {
    $Logs = new Logs();
    $Logs->create('Error', $_SESSION['userid'], $e->getMessage());
    $_SESSION['ko'][] = $e->getMessage();
}
