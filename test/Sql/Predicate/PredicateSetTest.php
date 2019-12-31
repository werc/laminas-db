<?php

/**
 * @see       https://github.com/laminas/laminas-db for the canonical source repository
 * @copyright https://github.com/laminas/laminas-db/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas/laminas-db/blob/master/LICENSE.md New BSD License
 */

namespace LaminasTest\Db\Sql\Predicate;

use Laminas\Db\Sql\Predicate\IsNull;
use Laminas\Db\Sql\Predicate\PredicateSet;
use PHPUnit_Framework_TestCase as TestCase;

class PredicateSetTest extends TestCase
{

    public function testEmptyConstructorYieldsCountOfZero()
    {
        $predicateSet = new PredicateSet();
        $this->assertEquals(0, count($predicateSet));
    }

    public function testCombinationIsAndByDefault()
    {
        $predicateSet = new PredicateSet();
        $predicateSet->addPredicate(new IsNull('foo'))
                  ->addPredicate(new IsNull('bar'));
        $parts = $predicateSet->getExpressionData();
        $this->assertEquals(3, count($parts));
        $this->assertContains('AND', $parts[1]);
        $this->assertNotContains('OR', $parts[1]);
    }

    public function testCanPassPredicatesAndDefaultCombinationViaConstructor()
    {
        $predicateSet = new PredicateSet();
        $set = new PredicateSet(array(
            new IsNull('foo'),
            new IsNull('bar'),
        ), 'OR');
        $parts = $set->getExpressionData();
        $this->assertEquals(3, count($parts));
        $this->assertContains('OR', $parts[1]);
        $this->assertNotContains('AND', $parts[1]);
    }

    public function testCanPassBothPredicateAndCombinationToAddPredicate()
    {
        $predicateSet = new PredicateSet();
        $predicateSet->addPredicate(new IsNull('foo'), 'OR')
                  ->addPredicate(new IsNull('bar'), 'AND')
                  ->addPredicate(new IsNull('baz'), 'OR')
                  ->addPredicate(new IsNull('bat'), 'AND');
        $parts = $predicateSet->getExpressionData();
        $this->assertEquals(7, count($parts));

        $this->assertNotContains('OR', $parts[1], var_export($parts, 1));
        $this->assertContains('AND', $parts[1]);

        $this->assertContains('OR', $parts[3]);
        $this->assertNotContains('AND', $parts[3]);

        $this->assertNotContains('OR', $parts[5]);
        $this->assertContains('AND', $parts[5]);
    }

    public function testCanUseOrPredicateAndAndPredicateMethods()
    {
        $predicateSet = new PredicateSet();
        $predicateSet->orPredicate(new IsNull('foo'))
                  ->andPredicate(new IsNull('bar'))
                  ->orPredicate(new IsNull('baz'))
                  ->andPredicate(new IsNull('bat'));
        $parts = $predicateSet->getExpressionData();
        $this->assertEquals(7, count($parts));

        $this->assertNotContains('OR', $parts[1], var_export($parts, 1));
        $this->assertContains('AND', $parts[1]);

        $this->assertContains('OR', $parts[3]);
        $this->assertNotContains('AND', $parts[3]);

        $this->assertNotContains('OR', $parts[5]);
        $this->assertContains('AND', $parts[5]);
    }

    /**
     * @covers Laminas\Db\Sql\Predicate\PredicateSet::addPredicates
     */
    public function testAddPredicates()
    {
        $predicateSet = new PredicateSet();

        $predicateSet->addPredicates('x = y');
        $predicateSet->addPredicates(array('foo > ?' => 5));
        $predicateSet->addPredicates(array('id' => 2));
        $predicateSet->addPredicates(array('a = b'), PredicateSet::OP_OR);
        $predicateSet->addPredicates(array('c1' => null));
        $predicateSet->addPredicates(array('c2' => array(1, 2, 3)));
        $predicateSet->addPredicates(array(new \Laminas\Db\Sql\Predicate\IsNotNull('c3')));

        $predicates = $this->readAttribute($predicateSet, 'predicates');
        $this->assertEquals('AND', $predicates[0][0]);
        $this->assertInstanceOf('Laminas\Db\Sql\Predicate\Literal', $predicates[0][1]);

        $this->assertEquals('AND', $predicates[1][0]);
        $this->assertInstanceOf('Laminas\Db\Sql\Predicate\Expression', $predicates[1][1]);

        $this->assertEquals('AND', $predicates[2][0]);
        $this->assertInstanceOf('Laminas\Db\Sql\Predicate\Operator', $predicates[2][1]);

        $this->assertEquals('OR', $predicates[3][0]);
        $this->assertInstanceOf('Laminas\Db\Sql\Predicate\Literal', $predicates[3][1]);

        $this->assertEquals('AND', $predicates[4][0]);
        $this->assertInstanceOf('Laminas\Db\Sql\Predicate\IsNull', $predicates[4][1]);

        $this->assertEquals('AND', $predicates[5][0]);
        $this->assertInstanceOf('Laminas\Db\Sql\Predicate\In', $predicates[5][1]);

        $this->assertEquals('AND', $predicates[6][0]);
        $this->assertInstanceOf('Laminas\Db\Sql\Predicate\IsNotNull', $predicates[6][1]);

        $test = $this;
        $predicateSet->addPredicates(function ($what) use ($test, $predicateSet) {
            $test->assertSame($predicateSet, $what);
        });

        $this->setExpectedException('Laminas\Db\Sql\Exception\InvalidArgumentException', 'Predicate cannot be null');
        $predicateSet->addPredicates(null);
    }
}
