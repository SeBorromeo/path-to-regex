<?php namespace SeBorromeo\PathToRegex\AST;

abstract class Token {
    abstract public function type(): string;
}