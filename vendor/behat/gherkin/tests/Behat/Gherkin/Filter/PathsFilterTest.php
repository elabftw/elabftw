<?php

namespace Tests\Behat\Gherkin\Filter;

use Behat\Gherkin\Filter\PathsFilter;
use Behat\Gherkin\Node\FeatureNode;

class PathsFilterTest extends FilterTest
{
    public function testIsFeatureMatchFilter()
    {
        $feature = new FeatureNode(null, null, array(), null, array(), null, null, __FILE__, 1);

        $filter = new PathsFilter(array(__DIR__));
        $this->assertTrue($filter->isFeatureMatch($feature));

        $filter = new PathsFilter(array('/abc', '/def', dirname(__DIR__)));
        $this->assertTrue($filter->isFeatureMatch($feature));

        $filter = new PathsFilter(array('/abc', '/def', __DIR__));
        $this->assertTrue($filter->isFeatureMatch($feature));

        $filter = new PathsFilter(array('/abc', __DIR__, '/def'));
        $this->assertTrue($filter->isFeatureMatch($feature));

        $filter = new PathsFilter(array('/abc', '/def', '/wrong/path'));
        $this->assertFalse($filter->isFeatureMatch($feature));
    }
}
