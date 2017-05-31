<?php


namespace Crazyfactory\DocBlocksTests;

use CrazyFactory\DocBlocks\DocBlockParameter;

class DocBlockParameterTest extends \PHPUnit_Framework_TestCase
{
    public function provideData()
    {
        return [
            [null],
            ['peach'],
            ['  apple'],
            ['pie  '],
        ];
    }

    /**
     * @dataProvider provideData
     *
     * @param string|null $string
     */
    public function testGetKey($string)
    {
        $dbp = new DocBlockParameter($string);

        if ($string !== null && trim($string) === "") {
            $this->assertNull($dbp->getKey());
        }
        else {
            $this->assertEquals(trim($string), $dbp->getKey());
        }
    }

    /**
     * @dataProvider provideData
     *
     * @param string|null $string
     */
    public function testGetValue($string)
    {
        $dbp = new DocBlockParameter(null, $string);

        if ($string !== null && trim($string) === "") {
            $this->assertNull($dbp->getValue());
        }
        else {
            $this->assertEquals(trim($string), $dbp->getValue());
        }
    }
}
