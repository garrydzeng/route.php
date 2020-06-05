<?php
namespace GarryDzeng\Route {

  class TableSpy extends Table {

    public function _regex($source, $pattern) { return $this->regex($source, $pattern); }
    public function _string($source) { return $this->string($source); }
    public function _hexadecimal($source) { return $this->hexadecimal($source); }
    public function _digit($source) { return $this->digit($source); }
  }

  class TableTest extends \PHPUnit\Framework\TestCase {

    public function testRegister() {

      $table = new Table(new Tokenizer());
      $state = null;

      $state = $table->register($state, '/', 1);
      $state = $table->register($state, '/users/{id=digit}', 2);
      $state = $table->register($state, '/users/{id=digit}/profile', 3);
      $state = $table->register($state, '/users/{id=digit}/', 4);
      $state = $table->register($state, '/{=hexadecimal}', 5);
      $state = $table->register($state, '/supports', 6);
      $state = $table->register($state, '/shop', 7);

      $expected = [
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

      $this->assertEquals($expected, $state);
    }

    public function testTokenMatch() {

      $self = new TableSpy(new Tokenizer());

      $this->assertEquals(['value'=> '0','index'=> 1], $self->_digit('0/'));
      $this->assertEquals(['value'=> '2','index'=> 1], $self->_digit('2-/'));
      $this->assertEquals(['value'=> 'afd804ca926547f3927aac27332b50a0','index'=> 32], $self->_hexadecimal('afd804ca926547f3927aac27332b50a0-/'));
      $this->assertEquals(['value'=> 'this is string','index'=> 14], $self->_string('this is string/'));

      $this->assertEquals(['value'=> 'afd804ca926547f3927aac27332b50a0','index'=> 32], $self->_regex('afd804ca926547f3927aac27332b50a0/', '[A-Fa-f0-9]{32}'));

    }

    public function testMatch() {

      $table = new Table(new Tokenizer());
      $state = null;

      $state = $table->register($state, '/', 1);
      $state = $table->register($state, '/users/{id=digit}', 2);
      $state = $table->register($state, '/users/{id=digit}/profile', 3);
      $state = $table->register($state, '/users/{id=digit}/', 4);
      $state = $table->register($state, '/{=hexadecimal}', 5);
      $state = $table->register($state, '/supports', 6);
      $state = $table->register($state, '/shop', 7);

      $this->assertEquals(
        ['callbacks'=> [3],'arguments'=> [['key'=> 'id','value'=> '1']]],
        $table->match(
          $state,
          '/users/1/profile'
        )
      );

      $this->assertEquals(
        ['callbacks'=> [2],'arguments'=> [['key'=> 'id','value'=> '1']]],
        $table->match(
          $state,
          '/users/1'
        )
      );
    }
  }
}