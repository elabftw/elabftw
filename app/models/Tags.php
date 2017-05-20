<?php
/**
 * \Elabftw\Elabftw\Tags
 *
 * @author Nicolas CARPi <nicolas.carpi@curie.fr>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */
namespace Elabftw\Elabftw;

use PDO;

/**
 * All about the tag
 */
class Tags
{
    /** an instance of Experiments or Database */
    public $Entity;

    /** pdo object */
    protected $pdo;

    /**
     * Constructor
     *
     * @param Entity $entity
     */
    public function __construct(Entity $entity)
    {
        $this->pdo = Db::getConnection();
        $this->Entity = $entity;
    }

    /**
     * Create a tag
     *
     * @param string $tag
     * @return bool
     */
    public function create($tag)
    {
        if ($this->Entity->type === 'experiments') {
            $userOrTeam = 'userid';
            $userOrTeamValue = $this->Entity->Users->userid;
        } else {
            $userOrTeam = 'team_id';
            $userOrTeamValue = $this->Entity->Users->userData['team'];
        }
        $sql = "INSERT INTO " . $this->Entity->type . "_tags (tag, item_id, " . $userOrTeam . ")
            VALUES(:tag, :item_id, :userOrTeam)";
        $req = $this->pdo->prepare($sql);
        $req->bindParam(':tag', $tag, PDO::PARAM_STR);
        $req->bindParam(':item_id', $this->Entity->id, PDO::PARAM_INT);
        $req->bindParam(':userOrTeam', $userOrTeamValue);

        return $req->execute();
    }

    /**
     * Read all the tags from team
     *
     * @param string|null $term The beginning of the input for tag autocomplete
     * @return array
     */
    public function readAll($term = null)
    {
        $tagFilter = "";
        if (!is_null($term)) {
            $tagFilter = " AND experiments_tags.tag LIKE '$term%'";
        }
        if ($this->Entity->type === 'experiments') {
            $sql = "SELECT DISTINCT tag, COUNT(*) AS nbtag
                FROM experiments_tags
                INNER JOIN users ON (experiments_tags.userid = users.userid)
                WHERE users.team = :team
                $tagFilter
                GROUP BY tag ORDER BY tag ASC";
        } else {
            $sql = "SELECT DISTINCT tag, COUNT(*) AS nbtag
                FROM items_tags
                WHERE team_id = :team
                $tagFilter
                GROUP BY tag ORDER BY tag ASC";
        }
        $req = $this->pdo->prepare($sql);
        $req->bindParam(':team', $this->Entity->Users->userData['team']);
        $req->execute();

        return $req->fetchAll();
    }

    /**
     * Copy the tags from one experiment/item to an other.
     *
     * @param int $newId The id of the new experiment/item that will receive the tags
     * @return null
     */
    public function copyTags($newId)
    {
        // TAGS
        $sql = "SELECT tag FROM " . $this->Entity->type . "_tags WHERE item_id = :id";
        $req = $this->pdo->prepare($sql);
        $req->bindParam(':id', $this->Entity->id);
        $req->execute();
        if ($req->rowCount() > 0) {
            while ($tags = $req->fetch()) {
                // Put them in the new one. here $newId is the new exp created
                if ($this->Entity->type === 'experiments') {
                    $sql = "INSERT INTO experiments_tags(tag, item_id, userid) VALUES(:tag, :item_id, :userid)";
                    $reqtag = $this->pdo->prepare($sql);
                    $reqtag->bindParam(':tag', $tags['tag']);
                    $reqtag->bindParam(':item_id', $newId);
                    $reqtag->bindParam(':userid', $this->Entity->Users->userid);
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
     * Get an array of tags starting with the query ($term)
     *
     * @param string $term the beginning of the tag
     * @return array the tag list filtered by the term
     */
    public function getList($term)
    {
        $tagListArr = array();
        $tagsArr = $this->readAll($term);

        foreach ($tagsArr as $tag) {
            $tagListArr[] = $tag['tag'];
        }
        return $tagListArr;
    }

    /**
     * Get the tag list as option html tag for the search page. Will disappear in search.html once it exists...
     *
     * @param string $selected the selected tag(s)
     * @return string html for include in a select input
     */
    public function generateTagList($selected)
    {
        $tagsArr = $this->readAll();

        $tagList = "";

        foreach ($tagsArr as $tag) {
            $tagList .= "<option value='" . $tag['tag'] . "'";
            if (in_array($tag['tag'], $selected)) {
                $tagList .= " selected='selected'";
            }
            $tagList .= ">" . $tag['tag'] . " (" . $tag['nbtag'] . ")</option>";
        }

        return $tagList;
    }

    /**
     * Destroy a tag
     *
     * @param int $id id of the tag
     * @return bool
     */
    public function destroy($id)
    {
        $sql = "DELETE FROM " . $this->Entity->type . "_tags WHERE id = :id";
        $req = $this->pdo->prepare($sql);
        $req->bindParam(':id', $id);

        return $req->execute();
    }

    /**
     * Destroy all the tags for an item ID
     *
     * @return bool
     */
    public function destroyAll()
    {
        if ($this->Entity->type === 'experiments') {
            $sql = "DELETE FROM experiments_tags WHERE item_id = :id";
        } else {
            $sql = "DELETE FROM items_tags WHERE item_id = :id";
        }

        $req = $this->pdo->prepare($sql);
        $req->bindParam(':id', $this->Entity->id);

        return $req->execute();
    }
}
