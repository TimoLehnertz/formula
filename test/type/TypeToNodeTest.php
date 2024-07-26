<?php

namespace test\type;

use PHPUnit\Framework\TestCase;
use TimoLehnertz\formula\type\classes\ClassType;
use TimoLehnertz\formula\type\classes\FieldType;
use TimoLehnertz\formula\type\functions\FunctionType;
use TimoLehnertz\formula\type\functions\OuterFunctionArgument;
use TimoLehnertz\formula\type\functions\OuterFunctionArgumentListType;
use TimoLehnertz\formula\type\IntegerType;
use TimoLehnertz\formula\type\Type;
use TimoLehnertz\formula\type\VoidType;

class TypeToNodeTest extends TestCase {

  public function provider(): array {
    return [
      [
        new ClassType(null, 'TestClass', ['i' => new FieldType(false, new IntegerType())]),
        [
          'typeName' => 'ClassType',
          'properties' => [
            'fields' => [
              [
                'identifier' => 'i',
                'final' => false,
                'type' => 'int'
              ]
            ],
            'parentType' => null,
            'identifier' => 'TestClass',
          ]
        ]
      ], [
        new FunctionType(new OuterFunctionArgumentListType([new OuterFunctionArgument(new IntegerType())], false), new VoidType()),
        [
          'typeName' => 'FunctionType',
          'properties' => [
            'arguments' => [
              'typeName' => 'OuterFunctionArgumentListType',
              'properties' => [
                'arguments' => [
                  [
                    'name' => null,
                    'optional' => false,
                    'type' => [
                      'typeName'=> 'IntegerType'
                    ]
                  ],
                ],
                'varg' => false
              ],
            ],
            'generalReturnType' => [
              'typeName' => 'VoidType'
            ],
            'specificReturnType' => null,
          ]
        ]
      ]
    ];
  }

  /**
   * @dataProvider provider
   */
  public function testNodes(Type $type, array $expectedNode): void {
    $this->assertEquals($expectedNode, json_decode(json_encode($type->getInterfaceType()), true));
  }
}
