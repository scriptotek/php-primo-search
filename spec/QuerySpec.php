<?php

namespace spec\Scriptotek\PrimoSearch;

use Scriptotek\PrimoSearch\InvalidQueryException;
use Scriptotek\PrimoSearch\Query;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class QuerySpec extends ObjectBehavior
{
    function it_is_initializable()
    {
        $this->shouldHaveType(Query::class);
    }

    function it_cannot_be_empty()
    {
        $this->shouldThrow(InvalidQueryException::class)->duringBuild();
    }

    function it_cannot_start_with_or()
    {
        $this->shouldThrow(InvalidQueryException::class)->duringOrWhere('field1', '=', 'value1');
    }

    function it_cannot_start_with_not()
    {
        $this->shouldThrow(InvalidQueryException::class)->duringNot('field1', '=', 'value1');
    }

    function it_supports_chaining()
    {
        $this->where('field1', '=', 'value1')
            ->orWhere('field2', '~', 'value2')
            ->not('field3', '=', 'value3')
            ->build()
            ->shouldReturn([
                'q' => 'field1,exact,value1,OR;field2,contains,value2,NOT;field3,exact,value3,AND',
                'sort' => 'rank',
                'offset' => '0',
                'limit' => '10',
            ]);
    }

    function it_supports_facets()
    {
        $this->where('any', 'contains', 'general chemistry')
            ->includeFacetValues('facet_local12', ['test', 'abc'])  // multifacets
            ->includeFacetValues('facet_local10', ['530.12', '539'], 'AND')  // qInclude
            ->excludeFacetValues('facet_rtype', 'books')  // qExclude
            ->build()
            ->shouldReturn([
                'q' => 'any,contains,general chemistry,AND',
                'qInclude' => 'facet_local10,exact,530.12|,|facet_local10,exact,539',
                'multifacets' => 'facet_local12,include,test|,|facet_local12,include,abc|,|facet_rtype,exclude,books',
                'sort' => 'rank',
                'offset' => '0',
                'limit' => '10',
            ]);
    }

    function it_supports_different_sorting()
    {
        $this->where('any', '~','book')
            ->sort('title')
            ->build()
            ->shouldReturn([
                'q' => 'any,contains,book,AND',
                'sort' => 'title',
                'offset' => '0',
                'limit' => '10',
            ]);
    }

    function it_supports_paging()
    {
        $this->where('any', '~','book')
            ->offset(50)
            ->limit(20)
            ->build()
            ->shouldReturn([
                'q' => 'any,contains,book,AND',
                'sort' => 'rank',
                'offset' => '50',
                'limit' => '20',
            ]);
    }

}
