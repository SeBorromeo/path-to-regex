<?php namespace SeBorromeo\PathToRegex\AST;

class Parameter extends Key {
    public function type(): string {
        return 'parameter';
    }
}
