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
declare(strict_types=1);

namespace Elabftw\Elabftw;


/**
 * All about the tag
 */
class Tags implements CrudInterface
{
    /** @var Db $Db SQL Database */
    protected $Db;

    /** @var AbstractEntity $Entity an instance of AbstractEntity */
    public $Entity;

    /**
     * Constructor
     *
     * @param AbstractEntity $entity
     */
    public function __construct(AbstractEntity $entity)
    {
        $this->Db = Db::getConnection();
        $this->Entity = $entity;
    }

    /**
     * Create a tag
     *
     * @param string $tag
     * @return int id of the tag
     */
    public function create(string $tag): int
    {
        if ($this->Entity->type === 'experiments' || $this->Entity->type === 'experiments_tpl') {
            $userOrTeam = 'userid';
            $userOrTeamValue = $this->Entity->Users->userid;
        } else {
            $userOrTeam = 'team_id';
            $userOrTeamValue = $this->Entity->Users->userData['team'];
        }
        $sql = "INSERT INTO " . $this->Entity->type . "_tags (tag, item_id, " . $userOrTeam . ")
            VALUES(:tag, :item_id, :userOrTeam)";
        $req = $this->Db->prepare($sql);
        $req->bindParam(':tag', $tag);
        $req->bindParam(':item_id', $this->Entity->id);
        $req->bindParam(':userOrTeam', $userOrTeamValue);

        $req->execute();

        return $this->Db->lastInsertId();
    }

    /**
     * Read all the tags from team
     *
     * @param string|null $term The beginning of the input for tag autocomplete
     * @return array
     */
    public function readAll(?string $term = null): array
    {
        $tagFilter = "";
        if ($term !== null) {
            $tagFilter = " AND tags.tag LIKE '%$term%'";
        }
        $sql = "SELECT DISTINCT tag, COUNT(*) AS nbtag
            FROM tags
            WHERE team = :team
            $tagFilter
            GROUP BY tag ORDER BY tag ASC";
        $req = $this->Db->prepare($sql);
        $req->bindParam(':team', $this->Entity->Users->userData['team']);
        $req->execute();

        return $req->fetchAll();
    }

    /**
     * Copy the tags from one experiment/item to an other.
     *
     * @param int $newId The id of the new experiment/item that will receive the tags
     * @return void
     */
    public function copyTags(int $newId): void
    {
        $sql = "SELECT tag FROM " . $this->Entity->type . "_tags WHERE item_id = :id";
        $req = $this->Db->prepare($sql);
        $req->bindParam(':id', $this->Entity->id);
        $req->execute();
        if ($req->rowCount() > 0) {
            while ($tags = $req->fetch()) {
                // Put them in the new one. here $newId is the new exp created
                if ($this->Entity->type === 'experiments' || $this->Entity->type === 'experiments_tpl') {
                    $sql = "INSERT INTO experiments_tags (tag, item_id, userid) VALUES(:tag, :item_id, :userid)";
                    $reqtag = $this->Db->prepare($sql);
                    $reqtag->bindParam(':tag', $tags['tag']);
                    $reqtag->bindParam(':item_id', $newId);
                    $reqtag->bindParam(':userid', $this->Entity->Users->userid);
                } else {
                    $sql = "INSERT INTO items_tags (tag, item_id, team_id) VALUES(:tag, :item_id, :team_id)";
                    $reqtag = $this->Db->prepare($sql);
                    $reqtag->bindParam(':tag', $tags['tag']);
                    $reqtag->bindParam(':item_id', $newId);
                    $reqtag->bindParam(':team_id', $this->Entity->Users->userData['team']);
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
    public function getList(string $term): array
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
     * @param array $selected the selected tag(s)
     * @return string html for include in a select input
     */
    public function generateTagList(array $selected): string
    {
        $tagsArr = $this->readAll();

        $tagList = "";

        foreach ($tagsArr as $tag) {
            $tagList .= "<option value='" . $tag['tag'] . "'";
            if (in_array($tag['tag'], $selected, true)) {
                $tagList .= " selected='selected'";
            }
            $tagList .= ">" . $tag['tag'] . " (" . $tag['nbtag'] . ")</option>";
        }

        return $tagList;
    }

    /**
     * Update a tag
     *
     * @param string $tag tag value
     * @param string $newtag new tag value
     * @return bool
     */
    public function update(string $tag, string $newtag): bool
    {
        $sql = "UPDATE tags SET tag = :newtag WHERE tag = :tag AND team = :team";
        $req = $this->Db->prepare($sql);
        $req->bindParam(':tag', $tag);
        $req->bindParam(':newtag', $newtag);
        $req->bindParam(':team', $this->Entity->Users->userData['team']);

        return $req->execute();
    }

    /**
     * Destroy a tag
     *
     * @param int $id id of the tag
     * @return bool
     */
    public function destroy(int $id): bool
    {
        $sql = "DELETE FROM " . $this->Entity->type . "_tags WHERE id = :id";
        $req = $this->Db->prepare($sql);
        $req->bindParam(':id', $id);

        return $req->execute();
    }

    /**
     * Destroy all the tags for an item ID
     *
     * @return bool
     */
    public function destroyAll(): bool
    {
        $sql = "DELETE FROM " . $this->Entity->type . "_tags WHERE item_id = :id";

        $req = $this->Db->prepare($sql);
        $req->bindParam(':id', $this->Entity->id);

        return $req->execute();
    }
}
