<?php
namespace GarryDzeng\Route\Contract {
  interface Serializer {
    public function persist($state, $pathname);
  }
}