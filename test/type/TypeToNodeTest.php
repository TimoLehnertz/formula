<?php

namespace test\type;

use PHPUnit\Framework\TestCase;
use TimoLehnertz\formula\type\classes\ClassType;
use TimoLehnertz\formula\type\classes\FieldType;
use TimoLehnertz\formula\type\IntegerType;
use TimoLehnertz\formula\type\Type;

class TypeToNodeTest extends TestCase {

  public function provider(): array {
    return [
      [
        new ClassType(null, 'TestClass', ['i' => new FieldType(false, new IntegerType())]),
        [
          'name' => 'ClassType',
          'additionalInfo' => [
            'fields' => [
              [
                'identifier' => 'i',
                'final' => false,
                'type' => [
                  'name' => 'IntegerType',
                ]
              ]
            ]
          ]
        ]
      ]
    ];
  }

  /**
   * @dataProvider provider
   */
  public function testNodes(Type $type, array $expectedNode): void {
    // var_dump(json_decode(json_encode($type->buildNodeInterfaceType()), true));
    $this->assertEquals($expectedNode, json_decode(json_encode($type->buildNodeInterfaceType()), true));
  }
}
