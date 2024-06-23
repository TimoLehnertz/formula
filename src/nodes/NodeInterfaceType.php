<?php
declare(strict_types = 1);
namespace TimoLehnertz\formula\nodes;

use TimoLehnertz\formula\type\Type;

/**
 * @author Timo Lehnertz
 */
class NodeInterfaceType {

  private readonly Type $type;

  public function __construct(Type $type) {
    $this->type = $type;
  }

  public function toArray(): array {
    // $compatibleOperators = $this->type->get
    return ['identifier' => $this->type->getIdentifier(), 'compatibleOperands' => []];
  }
}
