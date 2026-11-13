<?php

/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2026 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

declare(strict_types=1);

namespace Elabftw\Services;

use Elabftw\Exceptions\ImproperActionException;
use PHPUnit\Framework\TestCase;

final class OpenBabelSmartsMatcherTest extends TestCase
{
    public function testMatches(): void
    {
        $Matcher = new OpenBabelSmartsMatcher();
        $structures = array('phenol' => 'Oc1ccccc1', 'anisole' => 'COc1ccccc1', 'ethanol' => 'CCO', 'acetic acid' => 'CC(=O)O');

        // an aromatic ring bearing an oxygen
        $this->assertSame(array('phenol', 'anisole'), $Matcher->matches('c[OX2]', $structures));
        // a hydroxyl group, aromatic or not, but not an ether or an acid
        $this->assertSame(array('phenol', 'ethanol'), $Matcher->matches('[OX2H][#6;!$(C=O)]', $structures));
        $this->assertSame(array(), $Matcher->matches('[#7]', $structures));
        $this->assertSame(array(), $Matcher->matches('c', array()));
    }

    public function testExact(): void
    {
        $Matcher = new OpenBabelSmartsMatcher();
        $structures = array('water' => 'O', 'ethanol' => 'CCO', 'ammonia' => 'N');
        $this->assertSame(array('water', 'ethanol'), $Matcher->matches('O', $structures));
        // as many heavy atoms as the pattern: the molecule itself
        $this->assertSame(array('water'), $Matcher->matches('O', $structures, exact: true));
    }

    public function testInvalidPatterns(): void
    {
        $Matcher = new OpenBabelSmartsMatcher();
        // not SMARTS, a path, an inverted match, two patterns
        foreach (array('[C', '/etc/hosts.sdf', '../structures.sdf', '~c', 'c O', '') as $smarts) {
            try {
                $Matcher->matches($smarts, array('CCO'));
                $this->fail('Accepted an invalid SMARTS pattern: ' . $smarts);
            } catch (ImproperActionException) {
                $this->addToAssertionCount(1);
            }
        }
        // checked even without structures to test
        $this->expectException(ImproperActionException::class);
        $Matcher->matches('[C', array());
    }
}
