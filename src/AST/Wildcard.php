<?php namespace Sebastian\PathToRegex\AST;

class Wildcard extends Key {
    public function type(): string {
        return 'wildcard';
    }
}
