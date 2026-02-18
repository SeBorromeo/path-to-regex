<?php namespace SeBorromeo\PathToRegex\AST;

class Group extends Token {
    /** 
     * @var Token[] 
     */
    public readonly array $tokens;

    /**
     * @param Token[] $tokens
     */
    public function __construct(array $tokens) {
        $this->tokens = $tokens;
    }

    public function type(): string {
        return 'group';
    }
}
