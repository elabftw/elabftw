<?php

namespace PhpCollection\Tests;

use PhpCollection\Map;

class MapTest extends \PHPUnit_Framework_TestCase
{
    /** @var Map */
    private $map;

    public function testExists()
    {
        $this->assertFalse($this->map->exists(function($k) { return $k === 0; }));

        $this->map->set('foo', 'bar');
        $this->assertTrue($this->map->exists(function($k, $v) { return $k === 'foo' && $v === 'bar'; }));
    }

    public function testSet()
    {
        $this->assertTrue($this->map->get('asdf')->isEmpty());
        $this->map->set('asdf', 'foo');
        $this->assertEquals('foo', $this->map->get('asdf')->get());

        $this->assertEquals('bar', $this->map->get('foo')->get());
        $this->map->set('foo', 'asdf');
        $this->assertEquals('asdf', $this->map->get('foo')->get());
    }

    public function testSetSetAll()
    {
        $this->map->setAll(array('foo' => 'asdf', 'bar' => array('foo')));
        $this->assertEquals(array('foo' => 'asdf', 'bar' => array('foo'), 'baz' => 'boo'), iterator_to_array($this->map));
    }
    
    public function testAll()
    {
        $this->map->setAll(array('foo' => 'asdf', 'bar' => array('foo')));
        $this->assertEquals(array('foo' => 'asdf', 'bar' => array('foo'), 'baz' => 'boo'), $this->map->all());
    }

    public function testAddMap()
    {
        $map = new Map();
        $map->set('foo', array('bar'));
        $this->map->addMap($map);

        $this->assertEquals(array('foo' => array('bar'), 'bar' => 'baz', 'baz' => 'boo'), iterator_to_array($this->map));
    }

    public function testRemove()
    {
        $this->assertTrue($this->map->get('foo')->isDefined());
        $this->assertEquals('bar', $this->map->remove('foo'));
        $this->assertFalse($this->map->get('foo')->isDefined());
    }

    public function testClear()
    {
        $this->assertCount(3, $this->map);
        $this->map->clear();
        $this->assertCount(0, $this->map);
    }

    /**
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessage The map has no key named "asdfasdf".
     */
    public function testRemoveWithUnknownIndex()
    {
        $this->map->remove('asdfasdf');
    }

    public function testFirst()
    {
        $this->assertEquals(array('foo', 'bar'), $this->map->first()->get());
        $this->map->clear();
        $this->assertTrue($this->map->first()->isEmpty());
    }

    public function testLast()
    {
        $this->assertEquals(array('baz', 'boo'), $this->map->last()->get());
        $this->map->clear();
        $this->assertTrue($this->map->last()->isEmpty());
    }

    public function testContains()
    {
        $this->assertTrue($this->map->contains('boo'));
        $this->assertFalse($this->map->contains('asdf'));
    }

    public function testContainsKey()
    {
        $this->assertTrue($this->map->containsKey('foo'));
        $this->assertFalse($this->map->containsKey('boo'));
    }

    public function testIsEmpty()
    {
        $this->assertFalse($this->map->isEmpty());
        $this->map->clear();
        $this->assertTrue($this->map->isEmpty());
    }

    public function testFilter()
    {
        $map = new Map(array('a' => 'b', 'c' => 'd', 'e' => 'f'));
        $newMap = $map->filter(function($v) { return $v === 'd'; });

        $this->assertNotSame($newMap, $map);
        $this->assertCount(3, $map);
        $this->assertCount(1, $newMap);
        $this->assertEquals(array('c' => 'd'), iterator_to_array($newMap));
    }

    public function testFilterNot()
    {
        $map = new Map(array('a' => 'b', 'c' => 'd', 'e' => 'f'));
        $newMap = $map->filterNot(function($v) { return $v === 'd'; });

        $this->assertNotSame($newMap, $map);
        $this->assertCount(3, $map);
        $this->assertCount(2, $newMap);
        $this->assertEquals(array('a' => 'b', 'e' => 'f'), iterator_to_array($newMap));
    }

    public function testFoldLeftRight()
    {
        $map = new Map(array('a' => 'b', 'c' => 'd', 'e' => 'f'));
        $rsLeft = $map->foldLeft('', function($a, $b) { return $a.$b; });
        $rsRight = $map->foldRight('', function($a, $b) { return $a.$b; });

        $this->assertEquals('bdf', $rsLeft);
        $this->assertEquals('bdf', $rsRight);
    }

    public function testDropWhile()
    {
        $newMap = $this->map->dropWhile(function($k, $v) { return 'foo' === $k || 'baz' === $v; });
        $this->assertEquals(array('baz' => 'boo'), iterator_to_array($newMap));
        $this->assertCount(3, $this->map);
    }

    public function testDrop()
    {
        $newMap = $this->map->drop(2);
        $this->assertEquals(array('baz' => 'boo'), iterator_to_array($newMap));
        $this->assertCount(3, $this->map);
    }

    /**
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessage The number must be greater than 0, but got -4.
     */
    public function testDropWithNegativeNumber()
    {
        $this->map->drop(-4);
    }

    public function testDropRight()
    {
        $newMap = $this->map->dropRight(2);
        $this->assertEquals(array('foo' => 'bar'), iterator_to_array($newMap));
        $this->assertCount(3, $this->map);
    }

    /**
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessage The number must be greater than 0, but got -5.
     */
    public function testDropRightWithNegativeNumber()
    {
        $this->map->dropRight(-5);
    }

    public function testTake()
    {
        $newMap = $this->map->take(1);
        $this->assertEquals(array('foo' => 'bar'), iterator_to_array($newMap));
        $this->assertCount(3, $this->map);
    }

    /**
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessage The number must be greater than 0, but got -5.
     */
    public function testTakeWithNegativeNumber()
    {
        $this->map->take(-5);
    }

    public function testTakeWhile()
    {
        $newMap = $this->map->takeWhile(function($k, $v) { return 'foo' === $k || 'baz' === $v; });
        $this->assertEquals(array('foo' => 'bar', 'bar' => 'baz'), iterator_to_array($newMap));
        $this->assertCount(3, $this->map);
    }

    public function testFind()
    {
        $foundElem = $this->map->find(function($k, $v) { return 'foo' === $k && 'bar' === $v; });
        $this->assertEquals(array('foo', 'bar'), $foundElem->get());

        $this->assertTrue($this->map->find(function() { return false; })->isEmpty());
    }

    public function testKeys()
    {
        $this->assertEquals(array('foo', 'bar', 'baz'), $this->map->keys());
    }

    public function testValues()
    {
        $this->assertEquals(array('bar', 'baz', 'boo'), $this->map->values());
    }

    protected function setUp()
    {
        $this->map = new Map();
        $this->map->setAll(array(
            'foo' => 'bar',
            'bar' => 'baz',
            'baz' => 'boo',
        ));
    }
}
