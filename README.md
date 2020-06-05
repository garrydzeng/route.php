# Usage Guideline

This route component designed to provides fast matching (but it should generate file before). in my own performance test (from an real project), it costs 0.03 ms to match from 222 route patterns;

## Example

First, we should define a script that used to generate route file (in composer.json), such as:
```
{
  "scripts": {
    "genroute": "@php scripts/genroute.php"
  }
}
```
`scripts/genroute.php` looks like:

```
<?php
require '../vendor/autoload.php';

$table = new GarryDzeng\Route\Table(new GarryDzeng\Route\Tokenizer());
$state = null;

$state = $table->register($state, '/', 1);
$state = $table->register($state, '/users/{id=digit}', 2);
$state = $table->register($state, '/users/{id=digit}/profile', 3);
$state = $table->register($state, '/users/{id=digit}/', 4);
$state = $table->register($state, '/{=hexadecimal}', 5);
$state = $table->register($state, '/supports', 6);
$state = $table->register($state, '/shop', 7);

if ($state) {
  $serializer = new GarryDzeng\Route\Serializer();
  $serializer->persist(
    $state,
    "..."
  );
}
```
Run `composer run-script genroute` and use it in your entry script, such as:

```
<?php
require '../vendor/autoload.php';

$table = new GarryDzeng\Route\Table(new GarryDzeng\Route\Tokenizer());
$state = require "...";

$match = $table->match($state, "/users/42");

/*
 * Match {
 *   callbacks: [any],
 *   arguments: [
 *     { key: string, value: string }
 *   ]
 * }
 */
if ($match) {
  ...
}
```
(run genroute again if `scripts/genroute.php` changed)
