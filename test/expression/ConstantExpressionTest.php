<?php
namespace TimoLehnertz\formula\expression;

use PHPUnit\Framework\TestCase;
use TimoLehnertz\formula\PrettyPrintOptions;
use TimoLehnertz\formula\procedure\Scope;
use TimoLehnertz\formula\type\FloatType;
use TimoLehnertz\formula\type\FloatValue;

class ConstantExpressionTest extends TestCase {

  public function testOK(): void {
    /**
     * Setup
     */
    $type = new FloatType();
    $value = new FloatValue(123.4);
    $expression = new ConstantExpression($type, $value, '123.4');

    /**
     * Validate
     */
    $type = $expression->validate(new Scope());
    $this->assertInstanceOf(FloatType::class, $type);
    $this->assertEquals(123.4, $type->getRestrictedValues()[0]->toPHPValue());

    /**
     * Run
     */
    $result = $expression->run(new Scope());
    $this->assertInstanceOf(FloatValue::class, $result);
    $this->assertEquals(123.4, $result->toPHPValue());

    /**
     * ToString
     */
    $this->assertEquals('123.4', $expression->toString(PrettyPrintOptions::buildDefault()));

    /**
     * Node
     */
    $node = $expression->buildNode(new Scope());
    $this->assertEquals('ConstantExpression', $node->nodeType);
    $this->assertCount(0, $node->connected);
    $this->assertEquals(['type' => $type->getInterfaceType(),'value' => $value->toString()], $node->info);
  }
}
