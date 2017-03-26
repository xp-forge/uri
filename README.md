URI handling
============

[![Build Status on TravisCI](https://secure.travis-ci.org/xp-forge/uri.svg)](http://travis-ci.org/xp-forge/uri)
[![XP Framework Module](https://raw.githubusercontent.com/xp-framework/web/master/static/xp-framework-badge.png)](https://github.com/xp-framework/core)
[![BSD Licence](https://raw.githubusercontent.com/xp-framework/web/master/static/licence-bsd.png)](https://github.com/xp-framework/core/blob/master/LICENCE.md)
[![Required PHP 5.6+](https://raw.githubusercontent.com/xp-framework/web/master/static/php-5_6plus.png)](http://php.net/)
[![Supports PHP 7.0+](https://raw.githubusercontent.com/xp-framework/web/master/static/php-7_0plus.png)](http://php.net/)
[![Supports HHVM 3.4+](https://raw.githubusercontent.com/xp-framework/web/master/static/hhvm-3_4plus.png)](http://hhvm.com/)
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

Given `http://localhost/home/` as the base URL, you can resolve links in its context using the `resolve()` method:

```php
use util\URI;

$uri= new URI('http://localhost/home/');
$uri->resolve('/index.html');       // util.URL<http://localhost/index.html>
$uri->resolve('index.html');        // util.URL<http://localhost/home/index.html>
$uri->resolve('?sort=name');        // util.URL<http://localhost/home/?sort=name>
$uri->resolve('#top');              // util.URL<http://localhost/home/#top>
$uri->resolve('//example.com');     // util.URL<http://example.com>
$uri->resolve('https://localhost'); // util.URL<https://localhost>
```