<?php namespace SeBorromeo\PathToRegex\AST;

class Text extends FlatToken {
    public function __construct(
        public readonly string $value
    ) {}

    public function type(): string {
        return 'text';
    }
}