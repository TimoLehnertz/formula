<?php

namespace TimoLehnertz\formula;

use TimoLehnertz\formula\nodes\NodeTree;
use TimoLehnertz\formula\parsing\CodeBlockOrExpressionParser;
use TimoLehnertz\formula\procedure\Scope;
use TimoLehnertz\formula\statement\CodeBlockOrExpression;
use TimoLehnertz\formula\tokens\TokenisationException;
use TimoLehnertz\formula\tokens\Tokenizer;
use TimoLehnertz\formula\type\Type;
use TimoLehnertz\formula\type\Value;
use TimoLehnertz\formula\type\VoidType;
use TimoLehnertz\formula\type\VoidValue;
use TimoLehnertz\formula\procedure\DefaultScope;

/**
 * This class represents a formula session that can interpret/run code
 *
 * @author Timo Lehnertz
 */
class Formula {

  private readonly CodeBlockOrExpression $content;

  private readonly Scope $parentScope;

  private readonly string $source;

  private readonly Type $returnType;

  public function __construct(string $source, ?Scope $parentScope = new DefaultScope(), ?Type $expectedReturnType = null) {
    $this->source = $source;
    $this->parentScope = $parentScope ?? new Scope();
    $firstToken = Tokenizer::tokenize($source);
    if ($firstToken !== null) {
      $firstToken = $firstToken->skipComment();
    }
    if ($firstToken === null) {
      throw new TokenisationException('Invalid formula', 0, 0);
    }
    $parsedContent = (new CodeBlockOrExpressionParser(true))->parse($firstToken, true, true);
    $this->content = $parsedContent->parsed;
    $this->returnType = $this->content->validate($this->buildScope(), $expectedReturnType)->returnType ?? new VoidType();
  }

  /**
   * @psalm-return array{rootNode: array{
   *   nodeType: string,
   *   connected: list<array>,
   *   properties: array<string, mixed>
   * }, scope: array<string, array{
   *   typeName: string,
   *   properties?: array<string, mixed>
   * }>}
   * @throws NodesNotSupportedException
   */
  public function getNodeTree(): array {
    $node = $this->content->buildNode($this->buildScope());
    return (new NodeTree($node->toArray(), $this->buildScope()->toNodeTreeScope()))->toArray();
  }

  public function getReturnType(): Type {
    return $this->returnType;
  }

  /**
   * Calculates and returnes the result of this formula
   */
  public function calculate(): Value {
    return $this->content->run($this->buildScope())->returnValue ?? new VoidValue();
  }

  private function buildScope(): Scope {
    $scope = new Scope();
    $scope->setParent($this->parentScope);
    return $scope;
  }

  public function prettyprintFormula(?PrettyPrintOptions $prettyprintOptions = null): string {
    if ($prettyprintOptions === null) {
      $prettyprintOptions = PrettyPrintOptions::buildDefault();
    }
    return $this->content->toString($prettyprintOptions);
  }

  public function getScope(): Scope {
    return $this->parentScope;
  }

  public function getSource(): string {
    return $this->source;
  }

  public function getContent(): CodeBlockOrExpression {
    return $this->content;
  }
}
