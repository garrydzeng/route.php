<?php
namespace GarryDzeng\Route\Contract {
  interface Table {
    public function register($state, $pattern, ...$callbacks);
    public function match($state, $pathname);
  }
}