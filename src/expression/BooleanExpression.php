<?php
namespace TimoLehnertz\formula\expression;

/**
 *
 * @author Timo Lehnertz
 *        
 */
class BooleanExpression extends Number {
  
  /**
   * @param bool $true
   */
  public function __construct(bool $true) {
    parent::__construct($true ? 1 : 0);
  }
  
  public function getNode() {
    return [
      'type' => 'boolean',
      'value' => $this->isTruthy()
    ];
  }
}

