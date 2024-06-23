<?php
namespace TimoLehnertz\formula\expression;

use PHPUnit\Framework\TestCase;
use TimoLehnertz\formula\PrettyPrintOptions;
use TimoLehnertz\formula\procedure\Scope;
use TimoLehnertz\formula\type\FloatValue;
use TimoLehnertz\formula\type\MemberAccsessType;
use TimoLehnertz\formula\type\MemberAccsessValue;

class MemberAccsessExpressionTest extends TestCase {

  public function testOK(): void {
    /**
     * Setup
     */
    $scope = new Scope();
    $expression = new MemberAccsessExpression('abc');
    $this->assertEquals('abc', $expression->getIdentifier());

    /**
     * Validate
     */
    $type = $expression->validate($scope);
    $this->assertInstanceOf(MemberAccsessType::class, $type);

    /**
     * Run
     */
    $result = $expression->run($scope);
    $this->assertInstanceOf(MemberAccsessValue::class, $result);
    $this->assertEquals('abc', $result->toPHPValue());

    /**
     * ToString
     */
    $this->assertEquals('abc', $expression->toString(PrettyPrintOptions::buildDefault()));

    /**
     * Node
     */
    $node = $expression->buildNode($scope);
    $this->assertEquals('MemberAccsessExpression', $node->nodeType);
    $this->assertCount(0, $node->connected);
    $this->assertEquals(['identifier' => 'abc'], $node->info);
  }
}
