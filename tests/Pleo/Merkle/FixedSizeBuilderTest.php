<?php
namespace Pleo\Merkle;

use PHPUnit_Framework_TestCase;

class FixedSizeBuilderTest extends PHPUnit_Framework_TestCase
{
    public function setUp()
    {
    }

    /**
     * @covers Pleo\Merkle\FixedSizeBuilder
     */
    public function testWidthOfTwoConstructsCorrectly()
    {
        $expected = 'ae802c1f58f394d46485b7da18c56e9b';
        $hasher = function ($data) {
            $result = md5($data);
            return $result;
        };

        $builder = new FixedSizeBuilder(2, $hasher);
        $builder->set(0, 'hello');
        $builder->set(1, 'world');
        $actual = $builder->hash();

        $this->assertEquals($expected, $actual);
    }

    /**
     * @covers Pleo\Merkle\FixedSizeBuilder
     */
    public function testWidthOfThreeConstructsCorrectly()
    {
        $expected = '080e5d16555fed82b0e0429f48f69697';
        $hasher = function ($data) {
            $result = md5($data);
            return $result;
        };

        $builder = new FixedSizeBuilder(3, $hasher);
        $builder->set(0, 'Children');
        $builder->set(1, 'of');
        $builder->set(2, 'Dune');
        $actual = $builder->hash();

        $this->assertEquals($expected, $actual);
    }

    /**
     * @covers Pleo\Merkle\FixedSizeBuilder
     */
    public function testWidthOfFourConstructsCorrectly()
    {
        $expected = '61f7715d5dde1f2406fc1074b9279642';
        $hasher = function ($data) {
            $result = md5($data);
            return $result;
        };

        $builder = new FixedSizeBuilder(4, $hasher);
        $builder->set(0, 'the');
        $builder->set(1, 'quick');
        $builder->set(2, 'brown');
        $builder->set(3, 'fox');
        $actual = $builder->hash();

        $this->assertEquals($expected, $actual);
    }

    /**
     * @covers Pleo\Merkle\FixedSizeBuilder
     */
    public function testWidthOfFiveConstructsCorrectly()
    {
        $expected = '1a3c2d140c8d974bf5a64e35431eae80';
        $hasher = function ($data) {
            $result = md5($data);
            return $result;
        };

        $builder = new FixedSizeBuilder(5, $hasher);
        $builder->set(0, 'short');
        $builder->set(1, 'end');
        $builder->set(2, 'of');
        $builder->set(3, 'the');
        $builder->set(4, 'stick');
        $actual = $builder->hash();

        $this->assertEquals($expected, $actual);
    }

    /**
     * @covers Pleo\Merkle\FixedSizeBuilder
     */
    public function testWidthOfSixConstructsCorrectly()
    {
        $expected = '1c532ad0b8d7a2af86c321f14f722416';
        $hasher = function ($data) {
            $result = md5($data);
            return $result;
        };

        $builder = new FixedSizeBuilder(6, $hasher);
        $builder->set(0, 'at');
        $builder->set(1, 'the');
        $builder->set(2, 'end');
        $builder->set(3, 'of');
        $builder->set(4, 'the');
        $builder->set(5, 'day');
        $actual = $builder->hash();

        $this->assertEquals($expected, $actual);
    }
}
