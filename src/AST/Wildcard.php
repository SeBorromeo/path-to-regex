<?php namespace SeBorromeo\PathToRegex\AST;

class Wildcard extends Key {
    public function type(): string {
        return 'wildcard';
    }
}
