<?php
declare(strict_types = 1);
namespace TimoLehnertz\formula\nodes;

use TimoLehnertz\formula\operator\ImplementableOperator;

/**
 * @author Timo Lehnertz
 */
class NodeTree {

  public readonly array $rootNode;

  /**
   * @var array<array> Defined types
   */
  public readonly array $scope;

  public function __construct(array $rootNode, array $scope) {
    $this->rootNode = $rootNode;
    $this->scope = $scope;
  }

  public function toArray(): array {
    return ['rootNode' => $this->rootNode, 'scope' => $this->scope];
  }
}
