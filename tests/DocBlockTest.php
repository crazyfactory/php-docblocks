<?php

namespace Crazyfactory\DocBlocks\Tests;

use CrazyFactory\DocBlocks\DocBlock;
use CrazyFactory\DocBlocks\DocBlockParameter;

/**
 * MockClass Title
 *
 * Some textblock thing
 *
 * @foo
 * @apple this is an apple
 * @apple this is an apple too
 *        and it has a second line of text!
 *
 * another textish blockish thingish thing
 *
 *     badly intended text-block
 *        with multiple lines
 *
 * @peach ain't that a peach?
 *        oh yes it is!
 */
class MockClass
{

}

/**
 * @apple sad sad apple
 */
class NoTitleClass
{
}

class DocBlockTest extends \PHPUnit_Framework_TestCase
{
    /* @var \CrazyFactory\DocBlocks\DocBlock */
    private $docBlock;
    /* @var \ReflectionClass */
    private $rc;

    public function setUp()
    {
        $this->rc = new \ReflectionClass(MockClass::class);
        $this->docBlock = new DocBlock($this->rc);
    }

    public function testParse()
    {
        /* @var DocBlockParameter[] $results */
        $results = DocBlock::parse($this->rc->getDocComment());
        // Check total amount
        $this->assertEquals(8, count($results),
            "should have correct number of results");
        // All results should be arrays with only key and value
        $mistyped_results = array_filter($results, function($kv) {
            return !($kv instanceof DocBlockParameter);
        });
        $this->assertEmpty($mistyped_results,
            "should not have mistyped results");

        // Comparing apples
        /* @var DocBlockParameter[] $apples */
        $apples = array_values(array_filter($results, function($kv) {
            /* @var DocBlockParameter $kv */
            return $kv->getKey() === "apple";
        }));
        $this->assertEquals(2, count($apples),
            "should have exactly two apple-results");
        $this->assertEquals("this is an apple", ($apples[0])->getValue(),
            "should have extracted the correct value from first @apple-attribute");

        // Last one is a peach
        $last = $results[7];
        $this->assertEquals("peach", $last->getKey(),
            "should have extracted the last element correctly");

        // Badly intended text
        $text = $results[6]->getValue();
        $expected = "badly intended text-block\nwith multiple lines";
        $this->assertEquals($expected, $text,
            "should have extracted and cleaned the multiline text");
    }

    public function testTitle()
    {
        // Class with title
        $this->assertEquals("MockClass Title", $this->docBlock->title(),
            "should get the correct title");
        // Class without title
        $noClassDocBlock = new DocBlock(new \ReflectionClass(NoTitleClass::class));
        $this->assertNull($noClassDocBlock->title(), "should not get title");
    }

    public function testAll()
    {
        $this->assertCount(8, $this->docBlock->all());
    }

    public function testTexts()
    {
        $texts = $this->docBlock->texts();
        $this->assertContainsOnly('string', $texts,
            "should only contain strings");
        $this->assertCount(4, $texts);
    }

    public function testHeader()
    {
        // Class with multi block header
        $expected = "MockClass Title\n\nSome textblock thing";
        $actual = $this->docBlock->header();
        $this->assertEquals($expected, $actual, "should get the correct header");

        // Class without header
        $noClassDocBlock = new DocBlock(new \ReflectionClass(NoTitleClass::class));
        $this->assertNull($noClassDocBlock->header(), "should not get a header");
    }

    public function testAttributes()
    {
        $results = array_values($this->docBlock->attributes());
        $this->assertCount(4, $results, "should get the correct amount of results");
    }

    public function provideTestFind()
    {
        return [
            [null, 4],
            ['peach', 1],
            ['does-not-exist', 0],
            ['apple', 2],
        ];
    }

    /**
     * @dataProvider provideTestFind
     */
    public function testFind($key, $expectedCount)
    {
        // Check normal amount
        $results = $this->docBlock->find($key);
        $this->assertCount($expectedCount, $results, "should get correct amount");

        foreach ($results as $kv) {
            $this->assertEquals($key, $kv->getKey(), "should get only with specified key");
        }
    }


    /**
     * @dataProvider provideTestFind
     */
    public function testFindValues($key, $expectedCount)
    {
        // Check normal amount
        $results = $this->docBlock->findValues($key);
        $this->assertCount($expectedCount, $results, "should get correct amount");

        foreach ($results as $kv) {
            $this->assertNotInstanceOf(DocBlockParameter::class, $kv);
        }
    }

    public function testFindOrFail()
    {
        $results = $this->docBlock->findOrFail('apple');
        $this->assertCount(2, $results, "should get correct amount");
    }

    /**
     * @expectedException \Exception
     */
    public function testFindOrFail_throwsException()
    {
        $this->docBlock->findOrFail('does-not-exist');
    }

    public function provideTestFirst()
    {
        return [
            [['key' => null, 'value' => 'MockClass Title'], true],
            [['key' => 'foo', 'value' => null], true],
            [['key' => 'apple', 'value' => 'this is an apple'], true],
            [['key' => 'does-not-exist', 'value' => null], false],
        ];
    }

    /**
     * @dataProvider provideTestFirst
     */
    public function testFirst($expKv, $exists)
    {
        // Expanded
        $kv = $this->docBlock->first($expKv['key']);
        if ($exists) {
            $this->assertEquals($expKv['key'], $kv->getKey());
            $this->assertEquals($expKv['value'], $kv->getValue());
        }
        else {
            $this->assertNull($kv);
        }
    }

    /**
     * @dataProvider provideTestFirst
     */
    public function testFirstValue($expKv, $exists)
    {
        $value = $this->docBlock->firstValue($expKv['key']);

        if ($exists) {
            $this->assertEquals($expKv['value'], $value);
        }
        else {
            $this->assertNull($value);
        }
    }

    public function testFirstValueOrFail() {
        $value = $this->docBlock->firstValueOrFail('apple');
        $this->assertEquals('this is an apple', $value);
    }

    /**
     * @expectedException \Exception
     */
    public function testFirstValueOrFail_throwsException() {
        $this->docBlock->firstValueOrFail('does-not-exist');
    }

    public function testFirstOrFail()
    {
        // Flat
        $v = $this->docBlock->firstOrFail('apple');
        $this->assertEquals('this is an apple', $v->getValue());
    }

    /**
     * @expectedException \Exception
     */
    public function testFirstOrFail_throwsException()
    {
        $this->docBlock->firstOrFail('does-not-exist');
    }
}
