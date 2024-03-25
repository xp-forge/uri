URI handling
============

[![Build status on GitHub](https://github.com/xp-forge/uri/workflows/Tests/badge.svg)](https://github.com/xp-forge/uri/actions)
[![XP Framework Module](https://raw.githubusercontent.com/xp-framework/web/master/static/xp-framework-badge.png)](https://github.com/xp-framework/core)
[![BSD Licence](https://raw.githubusercontent.com/xp-framework/web/master/static/licence-bsd.png)](https://github.com/xp-framework/core/blob/master/LICENCE.md)
[![Requires PHP 7.4+](https://raw.githubusercontent.com/xp-framework/web/master/static/php-7_4plus.svg)](http://php.net/)
[![Supports PHP 8.0+](https://raw.githubusercontent.com/xp-framework/web/master/static/php-8_0plus.svg)](http://php.net/)
[![Latest Stable Version](https://poser.pugx.org/xp-forge/uri/version.png)](https://packagist.org/packages/xp-forge/uri)

A Uniform Resource Identifier (URI) is a compact sequence of characters that identifies an abstract or physical resource.

```
  foo://example.com:8042/over/there?name=ferret#nose
  \_/   \______________/\_________/ \_________/ \__/
   |           |            |            |        |
scheme     authority       path        query   fragment
   |   _____________________|__
  / \ /                        \
  urn:example:animal:ferret:nose
```

See https://tools.ietf.org/html/rfc3986

## Examples

### Parsing from a string

The most common case will include constructing URIs from a given input string.

```php
use util\URI;

$uri= new URI('https://user:password@localhost:8443/index?sort=name#top');
$uri->isOpaque();     // false - it's a hierarchical URI
$uri->scheme();       // "https"
$uri->authority();    // util.Authority("localhost", 8443, "user", util.Secret("password"))
$uri->host();         // "localhost"
$uri->port();         // 8443
$uri->user();         // "user"
$uri->password();     // util.Secret("password")
$uri->path();         // "index"
$uri->query();        // "sort=name"
$uri->params();       // util.URIParameters("sort=name")
$uri->param('sort');  // "name"
$uri->fragment();     // "top"
```

### Creating or modifying

URI instances are immutable. However, a fluent interface is offered via `with()` and `using()`. Both return fresh instances.

```php
use util\URI;

$uri= URI::with()->scheme('mailto')->path('timm@example.com')->param('Subject', 'Hello')->create();
$uri->isOpaque();   // true - it's an opaque URI
$uri->scheme();     // "mailto"
$uri->authority();  // null
(string)$uri;       // "mailto:timm@example.com?Subject=Hello"

$copy= $uri->using()->path('cc@example.com')->create();
(string)$copy;      // "mailto:cc@example.com?Subject=Hello"
```

### Resolving URIs

Given `http://localhost/home/` as the base URI, you can resolve links in its context using the `resolve()` method:

```php
use util\URI;

$uri= new URI('http://localhost/home/');
$uri->resolve('/index.html');       // util.URI<http://localhost/index.html>
$uri->resolve('index.html');        // util.URI<http://localhost/home/index.html>
$uri->resolve('?sort=name');        // util.URI<http://localhost/home/?sort=name>
$uri->resolve('#top');              // util.URI<http://localhost/home/#top>
$uri->resolve('//example.com');     // util.URI<http://example.com>
$uri->resolve('https://localhost'); // util.URI<https://localhost>
```

### Filesystem

URIs can point to filesystem paths. Converting between the two is not trivial - you need to handle Windows UNC paths correctly. The URI class' `file()` and `asPath()` methods take care of this.

```php
use util\URI;

$uri= URI::file('/etc/php.ini');
(string)$uri;       // "file:///etc/php.ini"

$uri= new URI('file://c:/Windows');
$uri->path();       // "C:/Windows"
$uri->asPath();     // io.Path("C:\Windows")

$uri= new URI('file://share/file.txt');
$uri->authority();  // util.Authority("share")
$uri->path();       // "/file.txt"
$uri->asPath();     // io.Path("\\share\file.txt")
```