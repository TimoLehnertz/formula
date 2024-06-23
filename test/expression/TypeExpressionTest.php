<?php
namespace TimoLehnertz\formula\expression;

use PHPUnit\Framework\TestCase;
use TimoLehnertz\formula\PrettyPrintOptions;
use TimoLehnertz\formula\procedure\Scope;
use TimoLehnertz\formula\type\FloatType;
use TimoLehnertz\formula\type\FloatValue;
use TimoLehnertz\formula\type\TypeType;
use TimoLehnertz\formula\type\TypeValue;

class TypeExpressionTest extends TestCase {

  public function testOK(): void {
    /**
     * Setup
     */
    $type = new FloatType();
    $expression = new TypeExpression($type);

    /**
     * Validate
     */
    $type = $expression->validate(new Scope());
    $this->assertInstanceOf(TypeType::class, $type);

    /**
     * Run
     */
    /** @var TypeValue $result */
    $result = $expression->run(new Scope());
    $this->assertInstanceOf(TypeValue::class, $result);
    $this->assertInstanceOf(FloatType::class, $result->getValue());

    /**
     * ToString
     */
    $this->assertEquals((new FloatType())->getIdentifier(), $expression->toString(PrettyPrintOptions::buildDefault()));

    /**
     * Node
     */
    $node = $expression->buildNode(new Scope());
    $this->assertEquals('TypeExpression', $node->nodeType);
    $this->assertCount(0, $node->connected);
    $this->assertEquals(['type' => (new FloatType())->getInterfaceType()], $node->info);
  }
}
