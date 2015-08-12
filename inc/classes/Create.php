<?php
/**
 * \Elabftw\Elabftw\Create
 *
 * @author Nicolas CARPi <nicolas.carpi@curie.fr>
 * @copyright 2012 Nicolas CARPi
 * @see http://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */
namespace Elabftw\Elabftw;

/**
 * Create an item, experiment or duplicate it.
 */
class Create
{
    /** The PDO object */
    private $pdo;

    /**
     * Constructor
     *
     */
    public function __construct()
    {
        $this->pdo = Db::getConnection();
    }

    /**
     * Check if we have a template to load for experiments
     *
     * @param int $tpl The template ID
     * @return bool
     */
    private function checkTpl($tpl)
    {
        return is_pos_int($tpl);
    }

    /**
     * Select what will be the status for the experiment
     *
     * @return int The status ID
     */
    private function getStatus()
    {
        // what will be the status ?
        // go pick what is the default status upon creating experiment
        // there should be only one because upon making a status default,
        // all the others are made not default
        $sql = 'SELECT id FROM status WHERE is_default = true AND team = :team LIMIT 1';
        $req = $this->pdo->prepare($sql);
        $req->bindParam(':team', $_SESSION['team_id']);
        $req->execute();
        $status = $req->fetchColumn();

        // if there is no is_default status
        // we take the first status that come
        if (!$status) {
            $sql = 'SELECT id FROM status WHERE team = :team LIMIT 1';
            $req = $this->pdo->prepare($sql);
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
     * Copy the tags from one experiment/item to an other.
     *
     * @param int $id The id of the original experiment/item
     * @param int $newId The id of the new experiment/item that will receive the tags
     * @param string $type can be experiment or item
     * @return null
     */
    private function copyTags($id, $newId, $type)
    {
        // TAGS
        if ($type === 'experiment') {
            $sql = "SELECT tag FROM experiments_tags WHERE item_id = :id";
        } else {
            $sql = "SELECT tag FROM items_tags WHERE item_id = :id";
        }
        $req = $this->pdo->prepare($sql);
        $req->bindParam(':id', $id);
        $req->execute();
        $tag_number = $req->rowCount();
        if ($tag_number > 0) {
            while ($tags = $req->fetch()) {
                // Put them in the new one. here $newId is the new exp created
                if ($type === 'experiment') {
                    $sql = "INSERT INTO experiments_tags(tag, item_id, userid) VALUES(:tag, :item_id, :userid)";
                    $reqtag = $this->pdo->prepare($sql);
                    $reqtag->bindParam(':tag', $tags['tag']);
                    $reqtag->bindParam(':item_id', $newId);
                    $reqtag->bindParam(':userid', $_SESSION['userid']);
                } else {
                    $sql = "INSERT INTO items_tags(tag, item_id) VALUES(:tag, :item_id)";
                    $reqtag = $this->pdo->prepare($sql);
                    $reqtag->bindParam(':tag', $tags['tag']);
                    $reqtag->bindParam(':item_id', $newId);
                }
                $reqtag->execute();
            }
        }
    }

    /**
     * Copy the links from one experiment to an other.
     *
     * @param int $id The id of the original experiment
     * @param int $newId The id of the new experiment that will receive the links
     * @return null
     */
    private function copyLinks($id, $newId)
    {
        // LINKS
        $linksql = "SELECT link_id FROM experiments_links WHERE item_id = :id";
        $linkreq = $this->pdo->prepare($linksql);
        $linkreq->bindParam(':id', $id);
        $linkreq->execute();

        while ($links = $linkreq->fetch()) {
            $sql = "INSERT INTO experiments_links (link_id, item_id) VALUES(:link_id, :item_id)";
            $req = $this->pdo->prepare($sql);
            $req->execute(array(
                'link_id' => $links['link_id'],
                'item_id' => $newId
            ));
        }
    }

    /**
     * Create an experiment.
     *
     * @param int|null $tpl the template on which to base the experiment
     * @return int the new id of the experiment
     */
    public function createExperiment($tpl = null)
    {
        // do we want template ?
        if ($this->checkTpl($tpl)) {
            // SQL to get template
            $sql = "SELECT name, body FROM experiments_templates WHERE id = :id AND team = :team";
            $get_tpl = $this->pdo->prepare($sql);
            $get_tpl->bindParam(':id', $tpl);
            $get_tpl->bindParam(':team', $_SESSION['team_id']);
            $get_tpl->execute();
            $get_tpl_info = $get_tpl->fetch();

            // the title is the name of the template
            $title = $get_tpl_info['name'];
            $body = $get_tpl_info['body'];

        } else {
            // if there is no template, title is 'Untitled' and the body is the default exp_tpl
            // SQL to get body
            $sql = "SELECT body FROM experiments_templates WHERE userid = 0 AND team = :team";
            $get_body = $this->pdo->prepare($sql);
            $get_body->bindParam(':team', $_SESSION['team_id']);
            $get_body->execute();
            $body = $get_body->fetchColumn();
            $title = _('Untitled');
        }

        // SQL for create experiments
        $sql = "INSERT INTO experiments(team, title, date, body, status, elabid, visibility, userid) VALUES(:team, :title, :date, :body, :status, :elabid, :visibility, :userid)";
        $req = $this->pdo->prepare($sql);
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

        return $this->pdo->lastInsertId();
    }


    /**
     * Duplicate an experiment.
     *
     * @param int $id The id of the experiment to duplicate
     * @return int Will return the ID of the new item
     */
    public function duplicateExperiment($id)
    {
        // SQL to get data from the experiment we duplicate
        $sql = "SELECT title, body, visibility FROM experiments WHERE id = :id AND team = :team";
        $req = $this->pdo->prepare($sql);
        $req->bindParam(':id', $id);
        $req->bindParam(':team', $_SESSION['team_id']);
        $req->execute();
        $experiments = $req->fetch();

        // let's add something at the end of the title to show it's a duplicate
        // capital i looks good enough
        $title = $experiments['title'] . ' I';

        // SQL for duplicateXP
        $sql = "INSERT INTO experiments(team, title, date, body, status, elabid, visibility, userid) VALUES(:team, :title, :date, :body, :status, :elabid, :visibility, :userid)";
        $req = $this->pdo->prepare($sql);
        $req->execute(array(
            'team' => $_SESSION['team_id'],
            'title' => $title,
            'date' => kdate(),
            'body' => $experiments['body'],
            'status' => $this->getStatus(),
            'elabid' => $this->generateElabid(),
            'visibility' => $experiments['visibility'],
            'userid' => $_SESSION['userid']));
        $newId = $this->pdo->lastInsertId();

        self::copyTags($id, $newId, 'experiment');
        self::copyLinks($id, $newId);
        return $newId;
    }

    /**
     * Create an item.
     *
     * @param int $itemType What kind of item we want to create.
     * @return int the new id of the item
     */
    public function createItem($itemType)
    {
        // SQL to get template
        $sql = "SELECT template FROM items_types WHERE id = :id";
        $get_tpl = $this->pdo->prepare($sql);
        $get_tpl->bindParam(':id', $itemType);
        $get_tpl->execute();
        $get_tpl_body = $get_tpl->fetch();

        // SQL for create DB item
        $sql = "INSERT INTO items(team, title, date, body, userid, type) VALUES(:team, :title, :date, :body, :userid, :type)";
        $req = $this->pdo->prepare($sql);
        $req->execute(array(
            'team' => $_SESSION['team_id'],
            'title' => 'Untitled',
            'date' => kdate(),
            'body' => $get_tpl_body['template'],
            'userid' => $_SESSION['userid'],
            'type' => $itemType
        ));

        return $this->pdo->lastInsertId();
    }

    /**
     * Duplicate an item.
     *
     * @param int $id The id of the item to duplicate
     * @return int $newId The id of the newly created item
     */
    public function duplicateItem($id)
    {
        // SQL to get data from the item we duplicate
        $sql = "SELECT * FROM items WHERE id = :id AND team = :team";
        $req = $this->pdo->prepare($sql);
        $req->bindParam(':id', $id);
        $req->bindParam(':team', $_SESSION['team_id']);
        $req->execute();
        $items = $req->fetch();

        // SQL for duplicateItem
        $sql = "INSERT INTO items(team, title, date, body, userid, type) VALUES(:team, :title, :date, :body, :userid, :type)";
        $req = $this->pdo->prepare($sql);
        $req->execute(array(
            'team' => $items['team'],
            'title' => $items['title'],
            'date' => kdate(),
            'body' => $items['body'],
            'userid' => $_SESSION['userid'],
            'type' => $items['type']
        ));
        $newId = $this->pdo->lastInsertId();

        self::copyTags($id, $newId, 'item');
        return $newId;
    }
}
