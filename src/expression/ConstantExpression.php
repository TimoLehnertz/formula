<?php
declare(strict_types = 1);
namespace TimoLehnertz\formula\expression;

use TimoLehnertz\formula\PrettyPrintOptions;
use TimoLehnertz\formula\nodes\Node;
use TimoLehnertz\formula\procedure\Scope;
use TimoLehnertz\formula\type\Type;
use TimoLehnertz\formula\type\Value;

/**
 * @author Timo Lehnertz
 */
class ConstantExpression implements Expression {

  private readonly Type $type;

  private readonly Value $value;

  private readonly string $stringRepresentation;

  public function __construct(Type $type, Value $value, string $stringRepresentation) {
    $this->type = $type->setAssignable(false)->setRestrictedValues([$value]);
    $this->value = $value;
    $this->stringRepresentation = $stringRepresentation;
  }

  public function validate(Scope $scope): Type {
    return $this->type;
  }

  public function run(Scope $scope): Value {
    return $this->value->copy();
  }

  public function toString(PrettyPrintOptions $prettyPrintOptions): string {
    return $this->stringRepresentation;
  }

  public function buildNode(Scope $scope): Node {
    return new Node('ConstantExpression', [], ['type' => $this->type->getInterfaceType(),'value' => $this->value->toString()]);
  }

  public function getType(): Type {
    return $this->type;
  }

  public function getValue(): Value {
    return $this->value;
  }

  public function getStringRepresentation(): string {
    return $this->stringRepresentation;
  }
}