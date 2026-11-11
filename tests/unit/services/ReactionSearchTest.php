<?php

declare(strict_types=1);

namespace Elabftw\Tests\Services;

use Elabftw\Elabftw\Db;
use Elabftw\Enums\Action;
use Elabftw\Enums\EntityType;
use Elabftw\Exceptions\ImproperActionException;
use Elabftw\Models\Experiments;
use Elabftw\Models\Items;
use Elabftw\Models\Reactions;
use Elabftw\Params\DisplayParams;
use Elabftw\Services\NullFingerprinter;
use Elabftw\Services\ReactionSearch;
use Elabftw\Traits\TestsUtilsTrait;
use PDO;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\InputBag;

use function array_column;
use function array_map;
use function intval;
use function str_repeat;
use function uniqid;

final class ReactionSearchTest extends TestCase
{
    use TestsUtilsTrait;

    private const string PHENOL = 'ISWSIDIOOBJBQZ-UHFFFAOYSA-N';

    private const string ANISOLE = 'RDOXTESZEPMUJZ-UHFFFAOYSA-N';

    private Experiments $Entity;

    private int $reactionId;

    private string $suffix;

    protected function setUp(): void
    {
        $this->suffix = uniqid();
        $this->Entity = $this->getFreshExperiment();
        $this->reactionId = new Reactions($this->Entity)->postAction(Action::Create, array(
            'title' => 'Phenol methylation ' . $this->suffix,
            'conditions_notes' => 'Stirred under reflux, 100% conversion',
            'components' => array(
                array('role' => 'reactant', 'name' => 'Phenol', 'smiles' => 'Oc1ccccc1', 'inchi_key' => self::PHENOL),
                array('role' => 'reagent', 'name' => 'Dimethyl sulfate ' . $this->suffix, 'smiles' => 'COS(=O)(=O)OC', 'inchi_key' => 'VAYGXNSJCAHWJZ-UHFFFAOYSA-N', 'molecular_formula' => 'C2H6O4S'),
                array('role' => 'product', 'name' => 'Anisole', 'smiles' => 'COc1ccccc1', 'inchi_key' => self::ANISOLE),
            ),
        ));
    }

    public function testSaveStoresSearchableIdentity(): void
    {
        $reaction = new Reactions($this->Entity, $this->reactionId)->readOne();
        $this->assertSame('Oc1ccccc1>COS(=O)(=O)OC>COc1ccccc1', $reaction['reaction_smiles']);
        $this->assertMatchesRegularExpression('/^[0-9a-f]{64}$/', $reaction['reaction_key']);
        $this->assertSame(3, $this->countFingerprints());
        // a full replacement of the components replaces their fingerprints
        new Reactions($this->Entity, $this->reactionId)->patch(Action::Update, array(
            'version' => $reaction['version'],
            'components' => array(array('role' => 'reactant', 'name' => 'Phenol', 'smiles' => 'Oc1ccccc1', 'inchi_key' => self::PHENOL)),
        ));
        $this->assertSame(1, $this->countFingerprints());
        $this->assertNull(new Reactions($this->Entity, $this->reactionId)->readOne()['reaction_key']);
    }

    public function testStructureIsSavedEvenIfItCannotBeFingerprinted(): void
    {
        $id = new Reactions($this->Entity, null, new NullFingerprinter())->postAction(Action::Create, array(
            'title' => 'Unindexed',
            'components' => array(array('role' => 'reactant', 'smiles' => 'CCO', 'inchi_key' => 'LFQSCWFLJHTTHZ-UHFFFAOYSA-N')),
        ));
        $this->assertSame('CCO>>', new Reactions($this->Entity, $id)->readOne()['reaction_smiles']);
    }

    public function testTextMatchesReactionAndComponents(): void
    {
        $this->assertFound(array('reaction_q' => 'phenol methylation ' . $this->suffix));
        $this->assertFound(array('reaction_q' => 'dimethyl sulfate ' . $this->suffix));
        $this->assertFound(array('reaction_q' => 'reflux'));
        $this->assertFound(array('reaction_q' => 'C2H6O4S'));
        $this->assertNotFound(array('reaction_q' => 'Ester hydrolysis ' . $this->suffix));
        // LIKE wildcards are literal characters
        $this->assertFound(array('reaction_q' => '100%'));
        $this->assertNotFound(array('reaction_q' => 'Phenol_methylation ' . $this->suffix));
    }

    public function testInchiKeyAndRole(): void
    {
        $this->assertFound(array('reaction_structure' => self::PHENOL, 'reaction_exact' => '1'));
        $this->assertFound(array('reaction_structure' => self::PHENOL, 'reaction_role' => 'reactant'));
        $this->assertNotFound(array('reaction_structure' => self::PHENOL, 'reaction_role' => 'product'));
        // same connectivity, other stereo layer: found unless exact
        $this->assertFound(array('reaction_structure' => 'ISWSIDIOOBJBQZ-ABCDEFGHIJ-N'));
        $this->assertNotFound(array('reaction_structure' => 'ISWSIDIOOBJBQZ-ABCDEFGHIJ-N', 'reaction_exact' => '1'));
    }

    public function testSubstructure(): void
    {
        $this->assertFound(array('reaction_structure' => 'c1ccccc1', 'reaction_role' => 'product'));
        $this->assertFound(array('reaction_structure' => 'COc1ccccc1', 'reaction_exact' => '1'));
        $this->assertNotFound(array('reaction_structure' => 'CCCCCCCCCC'));
    }

    public function testSameTransformationAndDeletedReactions(): void
    {
        $key = new Reactions($this->Entity, $this->reactionId)->readOne()['reaction_key'];
        $other = $this->getFreshExperiment();
        new Reactions($other)->postAction(Action::Create, array(
            'title' => 'Same transformation, other conditions',
            'components' => array(
                array('role' => 'reactant', 'smiles' => 'Oc1ccccc1', 'inchi_key' => self::PHENOL),
                array('role' => 'reagent', 'smiles' => 'CI', 'inchi_key' => 'INQOMBQAUSQDDS-UHFFFAOYSA-N'),
                array('role' => 'product', 'smiles' => 'COc1ccccc1', 'inchi_key' => self::ANISOLE),
            ),
        ));
        $ids = $this->search(array('reaction_key' => $key));
        $this->assertContains($this->Entity->id, $ids);
        $this->assertContains($other->id, $ids);

        new Reactions($this->Entity, $this->reactionId)->destroy();
        $this->assertNotFound(array('reaction_key' => $key));
    }

    public function testCopiesAreFoundLikeTheirSource(): void
    {
        // the copy has no fingerprints of its own to compute: they are copied
        $copy = EntityType::Experiments->toInstance($this->Entity->Users, $this->Entity->duplicate());
        $this->assertContains($copy->id, $this->search(array('reaction_structure' => 'c1ccccc1', 'reaction_role' => 'product')));
    }

    public function testResourceLinkAndRole(): void
    {
        $Item = new Items($this->Entity->Users);
        $Item->setId($Item->create());
        new Reactions($this->Entity)->postAction(Action::Create, array(
            'title' => 'Linked',
            'components' => array(array('role' => 'solvent', 'name' => 'Bottle', 'item_id' => $Item->id)),
        ));
        $this->assertFound(array('reaction_item' => (string) $Item->id));
        $this->assertFound(array('reaction_item' => (string) $Item->id, 'reaction_role' => 'solvent'));
        $this->assertNotFound(array('reaction_item' => (string) $Item->id, 'reaction_role' => 'reactant'));
    }

    public function testListingUsesTheFilterAndKeepsOtherFilters(): void
    {
        $query = new InputBag(array('reaction_q' => 'dimethyl sulfate ' . $this->suffix, 'scope' => '3'));
        $all = $this->Entity->readAll(new DisplayParams($this->Entity->Users, EntityType::Experiments, $query));
        $this->assertSame(array($this->Entity->id), array_column($all, 'id'));
        // the reaction filter is combined with the others
        $query->set('owner', '999999');
        $this->assertSame(array(), $this->Entity->readAll(new DisplayParams($this->Entity->Users, EntityType::Experiments, $query)));
    }

    public function testInvalidQueriesAreRejectedOnlyWhenSearching(): void
    {
        $params = new DisplayParams($this->Entity->Users, EntityType::Experiments, new InputBag(array('reaction_role' => 'enzyme')));
        $this->expectException(ImproperActionException::class);
        $params->getFilterSql();
    }

    public function testValidation(): void
    {
        $inactive = new ReactionSearch(EntityType::Experiments, new InputBag(array('reaction_role' => 'product', 'reaction_exact' => '1')));
        $this->assertFalse($inactive->isActive());
        $this->assertSame('', $inactive->getFilterSql());
        $tooLong = array('reaction_structure' => str_repeat('C', 2049));
        foreach (array(array('reaction_key' => 'abc'), array('reaction_compound' => 'x'), array('reaction_structure' => 'not a structure'), $tooLong) as $query) {
            try {
                $this->search($query);
                $this->fail('Accepted an invalid reaction search');
            } catch (ImproperActionException) {
                $this->addToAssertionCount(1);
            }
        }
    }

    private function assertFound(array $query): void
    {
        $this->assertContains($this->Entity->id, $this->search($query));
    }

    private function assertNotFound(array $query): void
    {
        $this->assertNotContains($this->Entity->id, $this->search($query));
    }

    /** Ids of all the experiments the filter keeps, without the permissions of a listing */
    private function search(array $query): array
    {
        $ReactionSearch = new ReactionSearch(EntityType::Experiments, new InputBag($query));
        $Db = Db::getConnection();
        $req = $Db->prepare('SELECT entity.id FROM experiments AS entity WHERE 1=1' . $ReactionSearch->getFilterSql());
        $ReactionSearch->bindValues($req);
        $Db->execute($req);
        return array_map(intval(...), $req->fetchAll(PDO::FETCH_COLUMN));
    }

    private function countFingerprints(): int
    {
        $req = Db::getConnection()->prepare('SELECT COUNT(*) FROM reaction_component_fingerprints AS f
            INNER JOIN reaction_components AS c ON (c.id = f.component_id) WHERE c.reaction_id = :id');
        $req->bindValue(':id', $this->reactionId);
        $req->execute();
        return (int) $req->fetchColumn();
    }
}
