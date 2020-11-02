<?php
/**
 * @copyright 2018 Matthew Nagi
 * @license http://opensource.org/licenses/BSD-2-Clause BSD 2-Clause License
 */

namespace Pleo\Merkle;

use LegacyPHPUnit\TestCase;
use RangeException;
use RuntimeException;
use UnexpectedValueException;

class FixedSizeTreeTest extends TestCase
{
    /**
     * @var callable
     */
    private $hasher;

    public function legacySetUp()
    {
        $this->hasher = function ($data) {
            $result = md5($data);
            return $result;
        };
    }

    /**
     * @covers \Pleo\Merkle\FixedSizeTree
     */
    public function testWidthOfTwoHashesCorrectly()
    {
        $expected = 'ae802c1f58f394d46485b7da18c56e9b';
        $builder = new FixedSizeTree(2, $this->hasher);
        $builder->set(0, 'hello');
        $builder->set(1, 'world');
        $actual = $builder->hash();

        $this->assertEquals($expected, $actual);
    }

    public function testWidthOfThreeConstructsCorrectly()
    {
        $expected = '080e5d16555fed82b0e0429f48f69697';
        $builder = new FixedSizeTree(3, $this->hasher);
        $builder->set(0, 'Children');
        $builder->set(1, 'of');
        $builder->set(2, 'Dune');
        $actual = $builder->hash();

        $this->assertEquals($expected, $actual);
    }

    public function testWidthOfFourConstructsCorrectly()
    {
        $expected = '61f7715d5dde1f2406fc1074b9279642';
        $builder = new FixedSizeTree(4, $this->hasher);
        $builder->set(0, 'the');
        $builder->set(1, 'quick');
        $builder->set(2, 'brown');
        $builder->set(3, 'fox');
        $actual = $builder->hash();

        $this->assertEquals($expected, $actual);
    }

    public function testWidthOfFiveConstructsCorrectly()
    {
        $expected = '1a3c2d140c8d974bf5a64e35431eae80';
        $builder = new FixedSizeTree(5, $this->hasher);
        $builder->set(0, 'short');
        $builder->set(1, 'end');
        $builder->set(2, 'of');
        $builder->set(3, 'the');
        $builder->set(4, 'stick');
        $actual = $builder->hash();

        $this->assertEquals($expected, $actual);
    }

    public function testWidthOfSixConstructsCorrectly()
    {
        $expected = '1c532ad0b8d7a2af86c321f14f722416';
        $builder = new FixedSizeTree(6, $this->hasher);
        $builder->set(0, 'at');
        $builder->set(1, 'the');
        $builder->set(2, 'end');
        $builder->set(3, 'of');
        $builder->set(4, 'the');
        $builder->set(5, 'day');
        $actual = $builder->hash();

        $this->assertEquals($expected, $actual);
    }

    public function testSettingDataWithIndexLessThanZero()
    {
        $this->expectException(RangeException::class);
        $builder = new FixedSizeTree(8, $this->hasher);
        $builder->set(-1, 'asdf');
    }

    public function testSettingDataWithIndexGreaterThanTreeWidth()
    {
        $this->expectException(RangeException::class);
        $builder = new FixedSizeTree(8, $this->hasher);
        $builder->set(8, 'asdf');
    }

    public function testHashCompleteCallbackTriggeredAsSoonAsEntireTreeResolved()
    {
        $numCalls = 0;
        $resultHash = null;
        $finished = function ($hash) use (&$numCalls, &$resultHash) {
            $numCalls++;
            $resultHash = $hash;
        };
        $tree = new FixedSizeTree(6, $this->hasher, $finished);

        $tree->set(5, 'asdf');
        $this->assertSame(0, $numCalls, "finished callback called prematurely");
        $tree->set(3, 'asdf');
        $this->assertSame(0, $numCalls, "finished callback called prematurely");
        $tree->set(2, 'asdf');
        $this->assertSame(0, $numCalls, "finished callback called prematurely");
        $tree->set(0, 'asdf');
        $this->assertSame(0, $numCalls, "finished callback called prematurely");
        $tree->set(1, 'asdf');
        $this->assertSame(0, $numCalls, "finished callback called prematurely");
        $tree->set(4, 'asdf');
        $this->assertSame(1, $numCalls, "finished callback not called as expected");

        $this->assertNotNull($resultHash);
    }

    public function testSettingAChunkTwiceThrowsException()
    {
        $this->expectException(RuntimeException::class);
        $tree = new FixedSizeTree(8, $this->hasher);
        $tree->set(0, 'asdf');
        $tree->set(0, 'asdf');
    }

    public function testSettingAChunkTwiceAfterResolvedThrowsException()
    {
        $this->expectException(RuntimeException::class);
        $tree = new FixedSizeTree(8, $this->hasher);
        $tree->set(0, 'asdf');
        $tree->set(1, 'asdf');
        $tree->set(0, 'asdf');
    }

    public function testErrorThrownIfWidthIsLessThanOne()
    {
        $this->expectException(UnexpectedValueException::class);
        new FixedSizeTree(0, function () {});
    }
}
