<?php namespace SeBorromeo\PathToRegex\AST;

abstract class Key extends FlatToken {
    public function __construct(
        public readonly string $name
    ) {}
}
