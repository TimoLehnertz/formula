<?php
namespace TimoLehnertz\formula\tokens;

use TimoLehnertz\formula\FormulaException;

/**
 * @author Timo Lehnertz
 *
 */
class TokenisationException extends FormulaException {

  public function __construct(string $message, int $line, int $position) {
    parent::__construct("unexpected symbol at: $line:$position. $message");
  }
}

