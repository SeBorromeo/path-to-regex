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

$fn('/test/route');
/** [
 *    'path' => '/test/route',
 *    'params' => [ 'foo' => 'test', 'bar' => 'route' ]
 *  ]
 */
```

### Wildcard

Wildcard parameters match one or more characters across multiple segments. They are defined the same way as regular parameters, but are prefixed with an asterisk (`*foo`).

```php
$fn = PathToRegex::match('/*splat');

$fn('/bar/baz');
// ['path' => '/bar/baz', 'splat' => [ 'bar', 'baz' ]]
```

### Optional

Braces can be used to define parts of the path that are optional.

```php
$fn = PathToRegex::match('/users{/:id}/delete');

$fn('/users/delete');
// [ 'path' => '/users/delete', 'params' => []]

$fn("/users/123/delete");
// [ 'path' => '/users/123/delete', 'params' => ['id' => '123']]
```

[path-to-regexp-url]: https://github.com/pillarjs/path-to-regexp



