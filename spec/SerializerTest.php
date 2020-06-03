<?php
namespace GarryDzeng\Route {

  class SerializerTest extends \PHPUnit\Framework\TestCase {

    public function testSave() {

      $self = new Serializer();
      $state = [
        (object)[
          'token'=> '/',
          'callbacks'=> [1],
          'edges'=> [
            (object)[
              'token'=> 'users/',
              'edges'=> [
                (object)[
                  'token'=> ['name'=> 'id','type'=> 'digit'],
                  'callbacks'=> [2],
                  'edges'=> [
                    (object)[
                      'token'=> '/',
                      'callbacks'=> [4],
                      'edges'=> [
                        (object)[
                          'token'=> 'profile',
                          'callbacks'=> [3],
                          'edges'=> [],
                        ]
                      ]
                    ]
                  ]
                ]
              ]
            ],
            (object)[
              'token'=> ['name'=> 0,'type'=> 'hexadecimal'],
              'callbacks'=> [5],
              'edges'=> []
            ],
            (object)[
              'token'=> 's',
              'edges'=> [
                (object)[
                  'token'=> 'upports',
                  'callbacks'=> [6],
                  'edges'=> []
                ],
                (object)[
                  'token'=> 'hop',
                  'callbacks'=> [7],
                  'edges'=> []
                ]
              ]
            ]
          ]
        ]
      ];

      $self->persist($state, './route.php');

      $this->assertEquals($state, require('./route.php'));
    }
  }
}