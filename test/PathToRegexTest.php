<?php

use PHPUnit\Framework\TestCase;
use SeBorromeo\PathToRegex\PathToRegex;
use SeBorromeo\PathToRegex\AST\Text;
use SeBorromeo\PathToRegex\AST\Group;
use SeBorromeo\PathToRegex\AST\Parameter;
use SeBorromeo\PathToRegex\AST\TokenData;
use SeBorromeo\PathToRegex\AST\Wildcard;
use SeBorromeo\PathToRegex\Exception\PathException;
use SeBorromeo\PathToRegex\Regex;

class PathToRegexTest extends TestCase {
    public function testEscapeText(): void {
        $method = new ReflectionMethod(PathToRegex::class, 'escapeText');

        $this->assertEquals('/users/\:id', $method->invoke(null, '/users/:id'));
        $this->assertEquals('/users/\:id\+', $method->invoke(null, '/users/:id+'));
        $this->assertEquals('/users/\*', $method->invoke(null, '/users/*'));
        $this->assertEquals('/users/\{id\}', $method->invoke(null, '/users/{id}'));
        $this->assertEquals('/posts\(/\:year\(/\:month\)\)', $method->invoke(null, '/posts(/:year(/:month))'));
        $this->assertEquals('/users/\:id\?', $method->invoke(null, '/users/:id?'));
        $this->assertEquals('/search/query\\\\\\?', $method->invoke(null, '/search/query\\?'));
    }

    /* ---------- Parse ---------- */

    public function testParseEmpty(): void {
        $result = PathToRegex::parse('');

        $this->assertCount(0, $result->tokens);
    }

    public function testParseText(): void {
        $result = PathToRegex::parse('/users/list');

        $this->assertCount(1, $result->tokens);

        /** @var Text */
        $text = $result->tokens[0];
        $this->assertInstanceOf(Text::class, $text);
        $this->assertSame('/users/list', $text->value);
    }

    public function testParseEscapedText(): void {
        $result = PathToRegex::parse('/users/\\:id');

        $this->assertCount(1, $result->tokens);

        /** @var Text */
        $text = $result->tokens[0];
        $this->assertInstanceOf(Text::class, $text);
        $this->assertSame('/users/:id', $text->value);
    }

    public function testParseParameter(): void {
        $result = PathToRegex::parse('/users/:id');

        /** @var Token[] */
        $tokens = $result->tokens;

        $this->assertCount(2, $tokens);
        $this->assertInstanceOf(Text::class, $tokens[0]);
        $this->assertSame('/users/', $tokens[0]->value);
        $this->assertInstanceOf(Parameter::class, $tokens[1]);
        $this->assertSame('id', $tokens[1]->name);
    }

    public function testParseWildcard(): void {
        $result = PathToRegex::parse('/files/*filepath');

        /** @var Token[] */
        $tokens = $result->tokens;

        $this->assertCount(2, $tokens);
        $this->assertInstanceOf(Text::class, $tokens[0]);
        $this->assertSame('/files/', $tokens[0]->value);
        $this->assertInstanceOf(Wildcard::class, $tokens[1]);
        $this->assertSame('filepath', $tokens[1]->name);
    }

    public function testParseGroup(): void {
        $result = PathToRegex::parse('/posts{/:year{/:month}}');

        /** @var Token[] */
        $tokens = $result->tokens;
        $this->assertCount(2, $tokens);
        $this->assertInstanceOf(Text::class, $tokens[0]);
        $this->assertSame('/posts', $tokens[0]->value);
        $this->assertInstanceOf(Group::class, $tokens[1]);

        /** @var Group */
        $group1 = $tokens[1];
        $this->assertCount(3, $group1->tokens);
        $this->assertInstanceOf(Parameter::class, $group1->tokens[1]);

        /** @var Parameter */
        $yearParam = $group1->tokens[1];
        $this->assertSame('year', $yearParam->name);
        $this->assertInstanceOf(Group::class, $group1->tokens[2]);

        /** @var Group */
        $group2 = $group1->tokens[2];
        $this->assertCount(2, $group2->tokens);

        /** @var Parameter */
        $monthParam = $group2->tokens[1];
        $this->assertInstanceOf(Parameter::class, $monthParam);
        $this->assertSame('month', $monthParam->name);
    }

    public function testNoParamName(): void {
        $this->expectException(PathException::class);
        PathToRegex::parse('/files/*');
    }

    public function testUnterminatedGroup(): void {
        $this->expectException(PathException::class);
        PathToRegex::parse('/posts{/:year');
    }

    public function testUnmatchedClosingGroup(): void {
        $this->expectException(PathException::class);
        PathToRegex::parse('/posts/:year}');
    }

    public function testInvalidParamName(): void {
        $this->expectException(PathException::class);
        PathToRegex::parse('/users/:123');
    }

    public function testQuoteParamName(): void {
        $result = PathToRegex::parse('/users/:"\{id\}"');

        /** @var Token[] */
        $tokens = $result->tokens;

        $this->assertCount(2, $tokens);
        $this->assertInstanceOf(Text::class, $tokens[0]);
        $this->assertSame('/users/', $tokens[0]->value);
        $this->assertInstanceOf(Parameter::class, $tokens[1]);
        $this->assertSame('{id}', $tokens[1]->name);
    }

    public function testUnterminatedQuote(): void {
        $this->expectException(PathException::class);
        PathToRegex::parse('/users/:"id');
    }

    /* ---------- Match ---------- */

    public function testMatchText(): void {
        $result = PathToRegex::match('/users/list');

        $this->assertNotFalse($result('/users/list'));
        $this->assertEquals(['path' => '/users/list', 'params' => []], $result('/users/list'));
        $this->assertFalse($result('/users/'));
        $this->assertFalse($result('/users/list/extra'));
    }

    public function testMatchParam(): void {
        $result = PathToRegex::match('/users/:id');

        $this->assertNotFalse($result('/users/123'));
        $this->assertEquals(['path' => '/users/123', 'params' => ['id' => ['123']]], $result('/users/123'));
        $this->assertFalse($result('/users/'));
        $this->assertFalse($result('/users/123/profile'));
    }

    public function testMatchWildcard(): void {
        $result = PathToRegex::match('/files/*filepath');

        $this->assertNotFalse($result('/files/images/photo.jpg'));
        $this->assertEquals(['path' => '/files/images/photo.jpg', 'params' => ['filepath' => ['images', 'photo.jpg']]], $result('/files/images/photo.jpg'));
        $this->assertEquals(['path' => '/files/docs/report.pdf', 'params' => ['filepath' => ['docs', 'report.pdf']]], $result('/files/docs/report.pdf'));
        $this->assertFalse($result('/files/'));
        $this->assertFalse($result('/files'));
    }

    public function testMatchOptionalGroup(): void {
        $result = PathToRegex::match('/posts{/:year{/:month}}');

        $this->assertEquals(['path' => '/posts', 'params' => []], $result('/posts'));
        $this->assertEquals(['path' => '/posts/2023', 'params' => ['year' => ['2023']]], $result('/posts/2023'));
        $this->assertEquals(['path' => '/posts/2023/06', 'params' => ['year' => ['2023'], 'month' => ['06']]], $result('/posts/2023/06'));
        $this->assertFalse($result('/posts/2023/06/extra'));
    }

    /* ---------- ToRegexSource ---------- */

    public function testPathToRegex(): void {
        ['regex' => $regex, 'keys' => $keys] = PathToRegex::pathToRegex('/users/:id');

        $this->assertInstanceOf(Regex::class, $regex);
        $this->assertSame('#^(?:/users/([^/]+))(?:/$)?$#i', (string) $regex);

        /** @var Parameter */
        $param = $keys[0];
        $this->assertInstanceOf(Parameter::class, $param);
        $this->assertEquals('id', $param->name);
    }

    /* ---------- Stringify ---------- */

    public function testStringifySimpleText(): void {
        $tokens = [new Text('/users')];
        $data = new TokenData($tokens, 'originalpath');

        $this->assertSame('/users', PathToRegex::stringify($data));
    }

    public function testEscapeSpecialCharacters(): void {
        $tokens = [new Text('/foo?bar+')];
        $data = new TokenData($tokens, 'originalpath');

        $this->assertSame('/foo\\?bar\\+', PathToRegex::stringify($data));
    }

    public function testSimpleParameter(): void {
        $tokens = [
            new Text('/users/'),
            new Parameter('id')
        ];
        $data = new TokenData($tokens, 'originalpath');

        $this->assertSame('/users/:id', PathToRegex::stringify($data));
    }

    public function testParameterWithUnsafeNameGetsQuoted(): void {
        $tokens = [
            new Parameter('not-valid-name!')
        ];
        $data = new TokenData($tokens, 'originalpath');

        $this->assertSame(':"not-valid-name!"', PathToRegex::stringify($data));
    }

    public function testParameterFollowedByUnsafeTextRequiresQuoting(): void {
        $tokens = [
            new Parameter('id'),
            new Text('abc')
        ];
        $data = new TokenData($tokens, 'originalpath');

        $this->assertSame(':"id"abc', PathToRegex::stringify($data));
    }

    public function testParameterFollowedBySafeTextDoesNotQuote(): void {
        $tokens = [
            new Parameter('id'),
            new Text('-abc')
        ];
        $data = new TokenData($tokens, 'originalpath');

        $this->assertSame(':id-abc', PathToRegex::stringify($data));
    }

    public function testSimpleWildcard(): void {
        $tokens = [
            new Text('/files/'),
            new Wildcard('path')
        ];
        $data = new TokenData($tokens, 'originalpath');

        $this->assertSame('/files/*path', PathToRegex::stringify($data));
    }

    public function testWildcardUnsafeNameGetsQuoted(): void {
        $tokens = [
            new Wildcard('bad-name!')
        ];
        $data = new TokenData($tokens, 'originalpath');

        $this->assertSame('*"bad-name!"', PathToRegex::stringify($data));
    }

    public function testWildcardFollowedByUnsafeTextRequiresQuoting(): void {
        $tokens = [
            new Wildcard('path'),
            new Text('abc')
        ];
        $data = new TokenData($tokens, 'originalpath');

        $this->assertSame('*"path"abc', PathToRegex::stringify($data));
    }

    public function testSimpleGroup(): void {
        $group = new Group([
            new Text('/inner')
        ]);

        $tokens = [$group];
        $data = new TokenData($tokens, 'originalpath');

        $this->assertSame('{/inner}', PathToRegex::stringify($data));
    }

    public function testNestedGroup(): void {
        $group = new Group([
            new Text('/a'),
            new Group([
                new Text('/b')
            ])
        ]);

        $data = new TokenData([$group], 'originalpath');

        $this->assertSame('{/a{/b}}', PathToRegex::stringify($data));
    }

    public function testGroupWithParameter(): void {
        $group = new Group([
            new Text('/user/'),
            new Parameter('id')
        ]);

        $data = new TokenData([$group], 'originalpath');

        $this->assertSame('{/user/:id}', PathToRegex::stringify($data));
    }

    public function testMixedTokens(): void {
        $tokens = [
            new Text('/users/'),
            new Parameter('id'),
            new Text('/files/'),
            new Wildcard('path')
        ];

        $data = new TokenData($tokens, 'originalpath');

        $this->assertSame('/users/:id/files/*path', PathToRegex::stringify($data));
    }

    public function testEmptyTokens(): void {
        $data = new TokenData([], 'originalpath');

        $this->assertSame('', PathToRegex::stringify($data));
    }

    public function testParameterNameWithUnicode(): void {
        $tokens = [
            new Parameter('ñame')
        ];
        $data = new TokenData($tokens, 'originalpath');

        $this->assertSame(':ñame', PathToRegex::stringify($data));
    }

    public function testUnsupportedTokenThrows(): void {
        $this->expectException(InvalidArgumentException::class);

        $badToken = new class {
            public function type() {
                return 'unknown';
            }
        };

        PathToRegex::stringify(new TokenData([$badToken], 'originalpath'));
    }
}   