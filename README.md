# Path-to-Regex (PHP)

A PHP port of the JavaScript library [path-to-regexp][path-to-regexp-url].

> Turn a path string such as /user/:name into a regular expression.

## Installation

```
composer require seborromeo/path-to-regex
```
## Usage

```php
use SeBorromeo\PathToRegex\PathToRegex;
```

### Parameters

Parameters match arbitrary strings in a path by matching up to the end of the segment, or up to any proceeding tokens. They are defined by prefixing a colon to the parameter name (`:foo`). Parameter names can use any valid JavaScript identifier, or be double quoted to use other characters (`:"param-name"`).

```php
$fn = PathToRegex::match('/:foo/:bar');

$result = $fn('/test/route');
// [ 'path' => '/test/route', 'params' => [ 'foo' => 'test', 'bar' => 'route' ]]
```

### Wildcard

Wildcard parameters match one or more characters across multiple segments. They are defined the same way as regular parameters, but are prefixed with an asterisk (`*foo`).

```php
$fn = PathToRegex::match('/*splat');

$result = $fn('/bar/baz');
// ['path' => '/bar/baz', 'splat' => [ 'bar', 'baz' ]]
```

### Optional

Braces can be used to define parts of the path that are optional.

```php
$fn = PathToRegex::match('/users{/:id}/delete');

$result = $fn('/users/delete');
// [ 'path' => '/users/delete', 'params' => []]

$result = $fn("/users/123/delete");
// [ 'path' => '/users/123/delete', 'params' => ['id' => '123']]
```

## Match

The `match` function returns a function for matching strings against a path:

- **path** String, `TokenData` object, or array of strings and `TokenData` objects.
- **options** _(optional)_ (Extends [pathToRegex](#pathToRegex) options)
  - **decode** Function for decoding strings to params, or `false` to disable all processing. (default: `PathToRegex::decodeURIComponent`)

```php
$fn = PathToRegex::match('/:foo/:bar');
```

**Please note:** `path-to-regex` is intended for ordered data (e.g. paths, hosts). It can not handle arbitrarily ordered data (e.g. query strings, URL fragments, JSON, etc).

## PathToRegex

The `pathToRegex` function returns the `Regex` for matching strings against paths, and an array of `keys` for understanding the `Regex` matches.

- **path** String, `TokenData` object, or array of strings and `TokenData` objects.
- **options** _(optional)_ (See [parse](#parse) for more options)
  - **sensitive** Regex will be case sensitive. (default: `false`)
  - **end** Validate the match reaches the end of the string. (default: `true`)
  - **delimiter** The default delimiter for segments, e.g. `[^/]` for `:named` parameters. (default: `'/'`)
  - **trailing** Allows optional trailing delimiter to match. (default: `true`)

```php
['regex' => $regex, 'keys' => $keys] = PathToRegex::pathToRegex('/:foo/:bar');

preg_match($regex, '/foo/123', $matches);
// $matches = ['/foo/123', '123']
```

## Compile ("Reverse" Path-To-Regex)

The `compile` function will return a function for transforming parameters into a valid path:

- **path** A string or `TokenData` object.
- **options** (See [parse](#parse) for more options)
  - **delimiter** The default delimiter for segments, e.g. `[^/]` for `:named` parameters. (default: `'/'`)
  - **encode** Function for encoding input strings for output into the path, or `false` to disable entirely. (default: `rawurlencode`)

```php
$toPath = PathToRegex::compile('/user/:id');

$toPath(['id' => 'name']); //=> '/user/name'
$toPath(['id' => 'café']); //=> '/user/caf%C3%A9'

$toPathRepeated = PathToRegex::compile('/*segment');

$toPathRepeated(['segment' => ['foo']]); //=> '/foo'
$toPathRepeated(['segment' => ['a', 'b', 'c']]); //=> '/a/b/c'

// When disabling `encode`, you need to make sure inputs are encoded correctly. No arrays are accepted.
$toPathRaw = PathToRegex::compile('/user/:id', ['encode' => false]);

$toPathRaw(['id' => '%3A%2F']); //=> '/user/%3A%2F'
```

## Stringify

Transform a `TokenData` object to a path string.

- **data** A `TokenData` object.

```php
$data = new TokenData([
  new Text('/'),
  new Parameter('foo')
]);

$path = PathToRegex::stringify($data); //=> "/:foo"
```

## Developers

- If you are rewriting paths with match and compile, consider using `'encode' => false` and `'decode' => false` to keep raw paths passed around.

### Parse

The `parse` function accepts a string and returns `TokenData`, which can be used with `match` and `compile`.

- **path** A string.
- **options** _(optional)_
  - **encodePath** A function for encoding input strings. (default: `x => x`)

### Tokens

`TokenData` has two properties:

- **tokens** A sequence of tokens, currently of types `text`, `param`, `wildcard`, or `group`.
- **originalPath** The original path used with `parse`, shown in error messages to assist debugging.

### Custom path

In some applications you may not be able to use the `path-to-regex` syntax, but you still want to use this library for `match` and `compile`. For example:

```php
$path = new TokenData(
    tokens: [
        new Text('/'),
        new Parameter('foo'),
    ],
    originalPath: '/[foo]' // To help debug error messages.
);

$fn = PathToRegex::match($path);

$result = $fn('/test'); //=> ['path' => '/test', 'params' => ['foo' => 'test']]
```

## Errors

Common errors that might arise.

### Missing parameter name

Parameter names must be provided after `:` or `*`, for example `/*path`. They can be valid JavaScript identifiers (e.g. `:myName`) or JSON strings (`:"my-name"`).

### Unexpected `?` or `+`

In past releases of the original JS library pillarjs/path-to-regexp, `?`, `*`, and `+` were used to denote optional or repeating parameters. As an alternative, try these:

- For optional (`?`), use braces: `/file{.:ext}`.
- For one or more (`+`), use a wildcard: `/*path`.
- For zero or more (`*`), use both: `/files{/*path}`.

### Unexpected `(`, `)`, `[`, `]`, etc.

To avoid confusion, these characters have been reserved to avoid ambiguity. To match these characters literally, escape them with a backslash, e.g. `"\\("`.

### Unterminated quote

Parameter names can be wrapped in double quote characters, and this error means you forgot to close the quote character. For example, `:"foo`.

## Compatibility

This library aims to closely follow the behavior of pillarjs/path-to-regexp for parsing, matching, compiling, and tokenization.

## License

MIT

[path-to-regexp-url]: https://github.com/pillarjs/path-to-regexp
