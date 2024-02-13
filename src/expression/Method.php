<?php
namespace TimoLehnertz\formula\expression;

use TimoLehnertz\formula\ExpressionNotFoundException;
use TimoLehnertz\formula\Formula;
use TimoLehnertz\formula\Nestable;
use TimoLehnertz\formula\Parseable;
use TimoLehnertz\formula\ParsingException;
use TimoLehnertz\formula\SubFormula;
use TimoLehnertz\formula\operator\Calculateable;
use TimoLehnertz\formula\FormulaEarlyReturnException;

/**
 *
 * @author Timo Lehnertz
 *
 */
class Method implements Expression, Parseable, Nestable, SubFormula {

  /**
   * Identifier of this method. To be set by parse()
   * @var string|null
   */
  protected ?string $identifier = null;

  /**
   * Parameters of this method. To be set by parse()
   * @var ?MathExpression[]
   */
  private ?array $parameters = null;

  /**
   * @var callable
   */
  private $method = null;

  private Formula|null $formula = null;

  /**
   * @inheritdoc
   */
  public function calculate(): Calculateable {
    if($this->method === null) throw new ExpressionNotFoundException("No method provided for $this->identifier!");
    $parameters = $this->getParameterValues();
    $value = call_user_func_array($this->method, $parameters);
    if($this->formula !== null && $this->formula->isEarlyReturn()) {
      throw new FormulaEarlyReturnException();
    }
    return Method::calculateableFromValue($value);
  }

  public static function calculateableFromValue($value) : Calculateable {
    if($value === null) return new NullExpression();
    if(is_numeric($value)) return new Number($value);
    if($value instanceof \DateTimeImmutable) {
      return new TimeLiteral($value);
    }
    if($value instanceof \DateTime) {
      return new TimeLiteral(new \DateTimeImmutable($value));
    }
    if($value instanceof \DateInterval) {
      return new TimeIntervalLiteral($value);
    }
    if(is_array($value)) {
    	return Vector::fromArray($value);
    }
    if(is_bool($value)) {
      return new BooleanExpression($value);
    }
    return StringLiteral::fromString($value);
  }

  public function parse(array &$tokens, int &$index): bool {
    // identifier
    if($tokens[$index]->name != "I") return false;
    if(sizeof($tokens) <= $index + 2) return false; // must be variable as there are no parameters following
    if($tokens[$index + 1]->name != "(") return false; // must be variable    $this->identifier = $tokens[$index]['value'];
    $this->identifier = $tokens[$index]->value;
    // parse parameters
    $this->parameters = [];
    $index += 2; // skipping identifier and opening bracket
    $first = true;
    for ($index; $index < sizeof($tokens); $index++) {
      $token = $tokens[$index];
      if($token->name == ')') {
        $index++;
        return true; // parsing succsessfull
      }
      if($first && $token->name == ',') throw new ParsingException("", $token);
      if(!$first && $token->name != ',') throw new ParsingException("", $token);
      if(!$first) $index++;
      $param = new MathExpression();

      $param->parse($tokens, $index); // will throw on error
      if($param->size() == 0) throw new ExpressionNotFoundException("Invalid Method argument", $tokens, $index);

      $index--;
      $this->parameters []= $param;
      $first = false;
    }
    throw new ExpressionNotFoundException("Unexpected end of input", $tokens, $index);
  }

  /**
   * @psalm-mutation-free
   * @return string
   */
  public function getIdentifier(): string {
    return $this->identifier;
  }

  /**
   * @param string $identifier
   * @return string
   */
  public function setIdentifier(string $identifier): void {
    $this->identifier = $identifier;
  }

  public function isSet(): bool {
    return $this->method !== null;
  }

  /**
   * Will return the calculated values of all parameters
   * @return array<mixed>
   */
  private function getParameterValues(): array {
    $values = [];
    foreach($this->parameters as $parameter) {
      $values[] = $parameter->calculate()->getValue();
    }
    return $values;
  }

  /**
   * {@inheritDoc}
   * @see \TimoLehnertz\formula\Nestable::getContent()
   */
  public function getContent(): array {
    $content = [];
    foreach ($this->parameters as $parameter) {
      $content[] = $parameter;
      if($parameter instanceof Nestable) {
        $content = array_merge($content, $parameter->getContent());
      }
    }
    return $content;
  }

  /**
   * @param callable $method
   */
  public function setMethod(callable $method): void {
    $this->method = $method;
  }

  /**
   * Unsets this method value and will throw an exception if used in calculation
   */
  public function reset(): void {
    $this->method = null;
  }

  public function validate(bool $throwOnError): bool {
    foreach ($this->parameters as $parameter) {
      if($parameter instanceof Nestable) {
        if(!$parameter->validate($throwOnError)) return false;
      }
    }
    return true;
  }

  public function getNode() {
    $parameterNodes = [];
    foreach ($this->parameters as $parameter) {
      $parameterNodes []= $parameter->getNode();
    }
    return [
      'type' => 'method',
      'identifier' => $this->identifier,
      'parameters' => $parameterNodes
    ];
  }

  public function setFormula(Formula $formula): void {
    $this->formula = $formula;
  }

  public function toString(): string {
    $parameters = '';
    $delimiter = '';
    foreach ($this->parameters as $parameter) {
      $parameters .= $delimiter.$parameter->toString();
      $delimiter = ',';
    }
    return "$this->identifier($parameters)";
  }
}