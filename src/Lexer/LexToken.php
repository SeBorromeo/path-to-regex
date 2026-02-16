<?php namespace Sebastian\PathToRegex\Lexer;

/**
 * @internal
 */
class LexToken {
    public function __construct(
        public readonly TokenType $type,
        public readonly int $index,
        public readonly string $value,
    ) {}
}