<?php

namespace spec\Scriptotek\PrimoSearch;

use Scriptotek\PrimoSearch\QueryPart;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class QueryPartSpec extends ObjectBehavior
{
    function it_is_initializable()
    {
        $this->beConstructedWith('field1', '=', 'value1', 'AND');
        $this->shouldHaveType(QueryPart::class);
    }

    function it_supports_exact_alias()
    {
        $this->beConstructedWith('field1', '=', 'value1');
        $this->build()
            ->shouldReturn('field1,exact,value1,AND');
    }

    function it_supports_contains_alias()
    {
        $this->beConstructedWith('field1', '~', 'value1');
        $this->build()
            ->shouldReturn('field1,contains,value1,AND');
    }

    function it_supports_begins_with_alias()
    {
        $this->beConstructedWith('field1', '^', 'value1');
        $this->build()
            ->shouldReturn('field1,begins with,value1,AND');
    }

}
