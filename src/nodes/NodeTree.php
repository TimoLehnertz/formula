<?php

declare(strict_types=1);

namespace TimoLehnertz\formula\nodes;

/**
 * @author Timo Lehnertz
 */
class NodeTree {

  public readonly array $rootNode;

  /**
   * @var array<array> Defined types
   */
  public readonly array $scope;

  /**
   * @param array $rootNode
   * @param array $scope
   */
  public function __construct(array $rootNode, array $scope) {
    $this->rootNode = $rootNode;
    $this->scope = $scope;
  }

  /**
   * @psalm-return array{rootNode: array{
   *   nodeType: string,
   *   connected: list<array>,
   *   properties: array<string, mixed>
   * }, scope: array<string, @psalm-return array{
   *   typeName: string,
   *   properties?: array<string, mixed>
   * }>}
   */
  public function toArray(): array {
    return ['rootNode' => $this->rootNode, 'scope' => $this->scope];
  }
}
