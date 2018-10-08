[![Build Status](https://www.travis-ci.com/scriptotek/php-primo-search-client.svg?branch=master)](https://www.travis-ci.com/scriptotek/php-primo-search-client)
[![Scrutinizer code quality](https://scrutinizer-ci.com/g/scriptotek/php-primo-search-client/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/scriptotek/php-primo-search-client/?branch=master)
[![Packagist](https://img.shields.io/packagist/v/scriptotek/primo-search-client.svg)](https://packagist.org/packages/scriptotek/primo-search-client)

# Primo search client

Client package for searching Primo using the Primo REST API.

### Installation

```sh
$ composer install scriptotek/primo-search [to be published]
```

You also need *some* HTTP client with a HTTPlug adapter.
If you don't have a preference, just use curl:

```sh
$ composer require php-http/curl-client guzzlehttp/psr7
```

Or if you already use Guzzle, you need the Guzzle adapter:

```sh
$ composer require php-http/guzzle6-adapter
```

For more information, see [HTTPlug for library users](http://docs.php-http.org/en/latest/httplug/users.html).

### Configuration for hosted Primo

You need an API key from [Ex Libris Developer Network](https://developers.exlibrisgroup.com/).

```php
require('vendor/autoload.php');

$primo = new Primo([
    'apiKey' => 'SECRET',
    'region' => 'eu',       // 'eu', 'na' or 'ap'
    'vid'    => '',
    'scope'  => 'default_scope',
]);
```

### Configuration for on-premises Primo

The package can handle guest JWT tokens transparently for you.

```php
require('vendor/autoload.php');

$primo = new Scriptotek\PrimoSearch\Primo([
    'baseUrl' => '<base-local-url>/primo_library/libweb/webservices/rest/v1/'
    'inst'    => '',
    'vid'     => '',
    'scope'   => 'default_scope',
]);
```

If the URL for the search endpoint is not the standard one (`{baseUrl}/pnxs`),
you can specify it manually:

```php
$primo = new Scriptotek\PrimoSearch\Primo([
    'baseUrl' => '<base-local-url>/primo_library/libweb/webservices/rest/v1/',
    'searchUrl' => '<base-local-url>/primo_library/libweb/webservices/rest/primo-explore/v1/pnxs',
    ...
]);
```

#### Storing the guest JWT token (optional)

If you want to re-use the same JWT token rather than generating a new one each time,
you can get it from the primo instance:

```php
$token = $primo->getJwtToken();
```

And then, the next time you want to use it:

```php
$primo->setJwtToken($token);
```

## Searching

To do a simple search:

```php
$query = new Scriptotek\PrimoSearch\Query();
$query->where('<field>', '<operator>', '<value>');
$results = $primo->search($query);

foreach($results->docs as $doc) {
    $title = $doc->pnx->display->title ?? '-';
    echo "Title: $title\n";
}
```
## The query builder

Note: If you want to inspect the resulting query from the query builder,
the `$query->build()` method returns an array of query string parameters.

### Where clauses

The `where` method supports three operators, all of which have short-form aliases:

- `exact` (alias: `=`): exact phrase match
- `contains` (alias: `~`)
- `begins with` (alias: `^`)

Multiple where clauses are joined by `AND`

```php
$query->where('title', '=' 'General chemistry');
$query->where('author', '~', 'Chang');
```

This will poduce `title,exact,General+chemistry,AND;author,contains,Chang`.

#### Or statements

The `orWhere` method accepts the same arguments as the `where` method:


```php
$query->where('title', '=' 'General chemistry');
$query->orWhere('author', '~', 'Chang');
```

This will poduce `title,exact,General+chemistry,OR;author,contains,Chang`.

### Facets

The Primo API supports facets through the three parameters `qInclude`, `qExclude` and `multifacets`.
It seems like the reason for there being three parameters rather than two is historical,
and it's also a bit confusing that the syntax differs between the parameters, so I decided to
implement an interface with a syntax that abstracts away the differences and only includes two methods rather than three:
`havingFacetValues()` and `notHavingFacetValues()`.

#### Filtering by facet values

To return only results where the `facet_type` facet has the value `p-books`:

```php
$query->havingFacetValues('facet_type', 'p-books');
```

Multiple values can be passed as an array:

```php
$query->havingFacetValues('facet_type', ['p-books', 'e-books']);
```

By default these are joined using the OR logic, so this
will return only results where the `facet_type` facet has values `p-books` OR `e-books`.

To return only results where the `facet_type` facet has values `p-books` AND `e-books`:

```php
$query->havingFacetValues('facet_type', ['p-books', 'e-books'], 'AND');
```

#### Filtering by excluding facet values

To return only results where the `facet_type` facet does not have values `p-books` OR `e-books`:

```php
$query->notHavingFacetValues('facet_type', ['p-books', 'e-books']);
```

This produces `multifacets=facet_rtype,exclude,p-books|,|facet_rtype,exclude,e-books`

To return only results where the `facet_type` facet does not have both values `p-books` AND `e-books`:

```php
$query->notHavingFacetValues('facet_type', ['p-books', 'e-books'], 'AND');
```

This produces `qExclude=facet_rtype,exact,p-books|,|facet_rtype,exact,e-books`

#### Ordering and paging

The `orderBy` method allows you to sort the result of the query in a given way.
Supported values for the first field is `rank` (default), `title`, `author` and `date`
and possibly more. Each field has a predefined direction and there is no way to change that,
but different fields may exist to support both ascending and descending ordering.
In our case, we have the field `date` for descending date sort and `date2` for
ascending date sort.

```php
$query->orderBy('date')
```

To limit the number of results returned from the query, or to skip a given number of
results in the query, you may use the `offset` and `limit` methods:

```php
$query->offset(10)->limit(10);  // Returns results 11 to 20
```

