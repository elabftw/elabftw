<?php
/**
 * \Elabftw\Elabftw\Tags
 *
 * @author Nicolas CARPi <nicolas.carpi@curie.fr>
 * @copyright 2012 Nicolas CARPi
 * @see http://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */
namespace Elabftw\Elabftw;

use \PDO;
use \Exception;

/**
 * All about the tag
 */
class Tags
{
    /** pdo object */
    protected $pdo;

    /**
     * Constructor
     *
     */
    public function __construct()
    {
        $this->pdo = Db::getConnection();
    }

    /**
     * Create a tag
     *
     */
    public function create()
    {
    }

    /**
     * Generate a JS list for tags autocomplete
     *
     */
    public function generateTagList($type)
    {
        if ($type === 'experiments') {
        $sql = "SELECT DISTINCT tag, id FROM experiments_tags
            INNER JOIN users ON (experiments_tags.userid = users.userid)
            WHERE users.team = :team ORDER BY id DESC LIMIT 500";
        } else {
            $sql = "SELECT DISTINCT tag, id FROM items_tags WHERE team_id = :team ORDER BY id DESC LIMIT 500";
        }
        $req = $this->pdo->prepare($sql);
        $req->bindParam(':team', $_SESSION['team_id']);
        $req->execute();

        $tagList = "";
        while ($tag = $req->fetch()) {
            $tagList .= "'" . $tag[0] . "',";
        }

        return $tagList;
    }
}
