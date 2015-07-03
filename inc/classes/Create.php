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
namespace Elabftw\Elabftw;

class Create
{

    /**
     * Check if we have a template to load for experiments
     *
     * @param int $tpl The template ID
     * @return bool
     */
    private function checkTpl($tpl)
    {
        if (is_pos_int($tpl)) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Select what will be the status for the experiment
     *
     * @return int The status ID
     */
    public function getStatus()
    {
        global $pdo;

        // what will be the status ?
        // go pick what is the default status upon creating experiment
        // there should be only one because upon making a status default,
        // all the others are made not default
        $sql = 'SELECT id FROM status WHERE is_default = true AND team = :team LIMIT 1';
        $req = $pdo->prepare($sql);
        $req->bindParam(':team', $_SESSION['team_id']);
        $req->execute();
        $status = $req->fetchColumn();

        // if there is no is_default status
        // we take the first status that come
        if (!$status) {
            $sql = 'SELECT id FROM status WHERE team = :team LIMIT 1';
            $req = $pdo->prepare($sql);
            $req->bindParam(':team', $_SESSION['team_id']);
            $req->execute();
            $status = $req->fetchColumn();
        }
        return $status;
    }

    /**
     * Generate unique elabID.
     * This function is called during the creation of an experiment.
     *
     * @return string unique elabid with date in front of it
     */
    private function generateElabid()
    {
        $date = kdate();
        return $date . "-" . sha1(uniqid($date, true));
    }

    /**
     * Copy the tags from one experiment to an other.
     *
     * @param int $id The id of the original experiment
     * @param int $new_id The id of the new experiment that will receive the tags
     * @return null
     */
    private function copyExperimentTags($id, $new_id)
    {
        global $pdo;

        // TAGS
        $sql = "SELECT tag FROM experiments_tags WHERE item_id = :id";
        $req = $pdo->prepare($sql);
        $req->bindParam('id', $id);
        $req->execute();
        $tag_number = $req->rowCount();
        if ($tag_number > 0) {
            while ($tags = $req->fetch()) {
                // Put them in the new one. here $new_id is the new exp created
                $sql = "INSERT INTO experiments_tags(tag, item_id, userid) VALUES(:tag, :item_id, :userid)";
                $reqtag = $pdo->prepare($sql);
                $result_tags = $reqtag->execute(array(
                    'tag' => $tags['tag'],
                    'item_id' => $new_id,
                    'userid' => $_SESSION['userid']
                ));
            }
        }
    }

    /**
     * Copy the tags from one item to an other.
     *
     * @param int $id The id of the original item
     * @param int $new_id The id of the new item that will receive the tags
     * @return null
     */
    private function copyItemTags($id, $new_id)
    {
        global $pdo;

        // TAGS
        $sql = "SELECT tag FROM items_tags WHERE item_id = :id";
        $req = $pdo->prepare($sql);
        $req->bindParam('id', $id);
        $req->execute();
        $tag_number = $req->rowCount();
        if ($tag_number > 0) {
            while ($tags = $req->fetch()) {
                // Put them in the new one. here $id is the new exp created
                // there is no userId for items (hence the need for two functions)
                $sql = "INSERT INTO items_tags(tag, item_id) VALUES(:tag, :item_id)";
                $reqtag = $pdo->prepare($sql);
                $reqtag->execute(array(
                    'tag' => $tags['tag'],
                    'item_id' => $new_id
                ));
            }
        }
    }

    /**
     * Copy the links from one experiment to an other.
     *
     * @param int $id The id of the original experiment
     * @param int $new_id The id of the new experiment that will receive the links
     * @return null
     */
    private function copyLinks($id, $new_id)
    {
        global $pdo;

        // LINKS
        $linksql = "SELECT link_id FROM experiments_links WHERE item_id = :id";
        $linkreq = $pdo->prepare($linksql);
        $linkreq->bindParam('id', $id);
        $linkreq->execute();

        while ($links = $linkreq->fetch()) {
            $sql = "INSERT INTO experiments_links (link_id, item_id) VALUES(:link_id, :item_id)";
            $req = $pdo->prepare($sql);
            $req->execute(array(
                'link_id' => $links['link_id'],
                'item_id' => $new_id
            ));
        }
    }


    /**
     * Create an experiment.
     *
     * @param int $tpl the template on which to base the experiment
     * @return int the new id of the experiment
     */
    public function createExperiment($tpl = null)
    {

        global $pdo;

        // do we want template ?
        if ($this->checkTpl($tpl)) {
            // SQL to get template
            $sql = "SELECT name, body FROM experiments_templates WHERE id = :id";
            $get_tpl = $pdo->prepare($sql);
            $get_tpl->bindParam('id', $tpl);
            $get_tpl->execute();
            $get_tpl_info = $get_tpl->fetch();

            // the title is the name of the template
            $title = $get_tpl_info['name'];
            $body = $get_tpl_info['body'];
        } else {
            // if there is no template, title is 'Untitled' and the body is the default exp_tpl
            // SQL to get body
            $sql = "SELECT body FROM experiments_templates WHERE userid = 0 AND team = :team";
            $get_body = $pdo->prepare($sql);
            $get_body->bindParam('team', $_SESSION['team_id']);
            $get_body->execute();
            $experiments_templates = $get_body->fetch();

            $title = _('Untitled');
            $body = $experiments_templates['body'];
        }

        // SQL for create experiments
        $sql = "INSERT INTO experiments(team, title, date, body, status, elabid, visibility, userid) VALUES(:team, :title, :date, :body, :status, :elabid, :visibility, :userid)";
        $req = $pdo->prepare($sql);
        $req->execute(array(
            'team' => $_SESSION['team_id'],
            'title' => $title,
            'date' => kdate(),
            'body' => $body,
            'status' => self::getStatus(),
            'elabid' => self::generateElabid(),
            'visibility' => 'team',
            'userid' => $_SESSION['userid']
        ));

        return $pdo->lastInsertId();
    }


    /**
     * Duplicate an experiment.
     *
     * @param int $id The id of the experiment to duplicate
     * @return int Will return the ID of the new item
     */
    public function duplicateExperiment($id)
    {
        global $pdo;

        // SQL to get data from the experiment we duplicate
        $sql = "SELECT title, body, visibility FROM experiments WHERE id = :id";
        $req = $pdo->prepare($sql);
        $req->bindParam('id', $id);
        $req->execute();
        $experiments = $req->fetch();

        // let's add something at the end of the title to show it's a duplicate
        // capital i looks good enough
        $title = $experiments['title'] . ' I';

        // SQL for duplicateXP
        $sql = "INSERT INTO experiments(team, title, date, body, status, elabid, visibility, userid) VALUES(:team, :title, :date, :body, :status, :elabid, :visibility, :userid)";
        $req = $pdo->prepare($sql);
        $req->execute(array(
            'team' => $_SESSION['team_id'],
            'title' => $title,
            'date' => kdate(),
            'body' => $experiments['body'],
            'status' => self::getStatus(),
            'elabid' => self::generateElabid(),
            'visibility' => $experiments['visibility'],
            'userid' => $_SESSION['userid']));
        $new_id = $pdo->lastInsertId();

        self::copyExperimentTags($id, $new_id);
        self::copyLinks($id, $new_id);
        return $new_id;
    }

    /**
     * Create an item.
     *
     * @param int $item_type What kind of item we want to create.
     * @return int the new id of the item
     */
    public function createItem($item_type)
    {

        global $pdo;

        // SQL to get template
        $sql = "SELECT template FROM items_types WHERE id = :id";
        $get_tpl = $pdo->prepare($sql);
        $get_tpl->bindParam('id', $item_type);
        $get_tpl->execute();
        $get_tpl_body = $get_tpl->fetch();

        // SQL for create DB item
        $sql = "INSERT INTO items(team, title, date, body, userid, type) VALUES(:team, :title, :date, :body, :userid, :type)";
        $req = $pdo->prepare($sql);
        $req->execute(array(
            'team' => $_SESSION['team_id'],
            'title' => 'Untitled',
            'date' => kdate(),
            'body' => $get_tpl_body['template'],
            'userid' => $_SESSION['userid'],
            'type' => $item_type
        ));

        return $pdo->lastInsertId();
    }

    /**
     * Duplicate an item.
     *
     * @param int $id The id of the item to duplicate
     * @return int $new_id The id of the newly created item
     */
    public function duplicateItem($id)
    {
        global $pdo;

        // SQL to get data from the item we duplicate
        $sql = "SELECT * FROM items WHERE id = :id";
        $req = $pdo->prepare($sql);
        $req->bindParam('id', $id);
        $req->execute();
        $items = $req->fetch();

        // SQL for duplicateItem
        $sql = "INSERT INTO items(team, title, date, body, userid, type) VALUES(:team, :title, :date, :body, :userid, :type)";
        $req = $pdo->prepare($sql);
        $req->execute(array(
            'team' => $items['team'],
            'title' => $items['title'],
            'date' => kdate(),
            'body' => $items['body'],
            'userid' => $_SESSION['userid'],
            'type' => $items['type']
        ));
        $new_id = $pdo->lastInsertId();

        self::copyItemTags($id, $new_id);
        return $new_id;
    }
}
