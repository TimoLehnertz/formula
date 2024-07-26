<?php

declare(strict_types=1);

namespace TimoLehnertz\formula\nodes;

/**
 * @author Timo Lehnertz
 */
class Node {

  public readonly string $nodeType;

  /**
   * @var array<int, Node>
   */
  public readonly array $connected;

  public readonly array $info;

  /**
   * @param array<int, Node> $connectedInputs
   */
  public function __construct(string $nodeType, array $connected, array $info = []) {
    foreach ($connected as $node) {
      if(!($node instanceof Node)) {
        throw new \Exception('Invalid node');
      }
    }
    $this->nodeType = $nodeType;
    $this->connected = $connected;
    $this->info = $info;
  }

  public function toArray(): array {
    $connected = [];
    foreach ($this->connected as $node) {
      $connected[] = $node->toArray();
    }
    return ['nodeType' => $this->nodeType, 'connected' => $connected, 'properties' => $this->info];
  }
}
