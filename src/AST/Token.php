<?php namespace Sebastian\PathToRegex\AST;

abstract class Token {
    abstract public function type(): string;
}