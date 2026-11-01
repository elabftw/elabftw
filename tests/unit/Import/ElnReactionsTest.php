<?php

declare(strict_types=1);

namespace Elabftw\Import;

use Elabftw\Enums\Action;
use Elabftw\Make\MakeEln;
use Elabftw\Models\Instance2Rors;
use Elabftw\Models\Reactions;
use Elabftw\Models\Teams2Rors;
use Elabftw\Models\Users2Rors;
use Elabftw\Models\Users\Users;
use Elabftw\Storage\Cache\ElabCache;
use Elabftw\Traits\TestsUtilsTrait;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use ReflectionProperty;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use ZipStream\ZipStream;

use function sys_get_temp_dir;
use function tempnam;
use function unlink;

final class ElnReactionsTest extends TestCase
{
    use TestsUtilsTrait;

    public function testLockedReactionRoundTripPreservesLocalSnapshots(): void
    {
        $source = $this->getFreshExperiment();
        $id = new Reactions($source)->postAction(Action::Create, array(
            'title' => 'Oxidation', 'temperature_c' => -78,
            'scheme' => array('root' => array('nodes' => array())),
            'components' => array(array('name' => 'Starting material', 'smiles' => 'CCO', 'molecular_weight' => '46.06844', 'actual_value' => 25, 'actual_unit' => 'mg')),
        ));
        $source->lock();
        $users = new Users(1, 1);
        $logger = $this->createStub(LoggerInterface::class);
        $maker = new MakeEln($logger, $this->createStub(ZipStream::class), $users, array($source), new Instance2Rors(), new Teams2Rors(1), new Users2Rors(1), includeLinkedEntities: false);
        $path = tempnam(sys_get_temp_dir(), 'reaction-eln-');
        $this->assertIsString($path);
        try {
            $maker->writeToFile($path);
            $import = new Eln($users, $users, new UploadedFile($path, 'reaction.eln', test: true), new ElabCache()->getFs(), $logger);
            $import->import();
            $inserted = new ReflectionProperty(Eln::class, 'insertedEntities')->getValue($import);
            $this->assertCount(1, $inserted);
            $target = $inserted[0]['entity_type']->toInstance($users, $inserted[0]['id']);
            $reaction = new Reactions($target)->readAll()[0];
            $this->assertNotSame($id, (int) $reaction['id']);
            $this->assertSame('Oxidation', $reaction['title']);
            $this->assertSame('-78.000000000000', $reaction['temperature_c']);
            $this->assertSame('46.068440000000', $reaction['components'][0]['molecular_weight']);
            $this->assertSame('mg', $reaction['components'][0]['actual_unit']);
            $this->assertSame('25.000000000000', $reaction['components'][0]['actual_value']);
            $this->assertSame(array('root' => array('nodes' => array())), $reaction['scheme']);
        } finally {
            unlink($path);
        }
    }
}
