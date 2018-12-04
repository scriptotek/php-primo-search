<?php

namespace spec\Scriptotek\PrimoSearch;

use GuzzleHttp\Psr7\Response;
use function GuzzleHttp\Psr7\stream_for;
use Http\Mock\Client as MockClient;
use Http\Discovery\HttpClientDiscovery;
use Http\Discovery\Strategy\MockClientStrategy;
use Psr\Http\Message\ResponseInterface;
use Scriptotek\PrimoSearch\Primo;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use Scriptotek\PrimoSearch\Query;

class PrimoSpec extends ObjectBehavior
{
    function let()
    {
        HttpClientDiscovery::prependStrategy(MockClientStrategy::class);
    }

    function initWithResponses($responses)
    {
        $http = HttpClientDiscovery::find();
        foreach ($responses as $res) {
            $http->addResponse($res);
        }
        $this->beConstructedWith([
            'apiKey' => 'abc',
            'region' => 'eu',
            'vid' => 'ABC',
            'scope' => 'default_scope',
        ], $http);
    }

    function it_can_be_configured_for_hosted_environments()
    {
        $this->beConstructedWith([
            'apiKey' => 'magic key!',
            'region' => 'eu',
            'vid' => 'UIO',
            'scope' => 'default_scope',
        ]);
        $this->shouldHaveType(Primo::class);
    }

    function it_can_be_configured_for_on_premise_environments()
    {
        $this->beConstructedWith([
            'inst' => 'UBO',
            'baseUrl' => 'http://example.com/primo_library/libweb/webservices/rest/primo-explore/v1/pnxs',
            'vid' => 'UIO',
            'scope' => 'default_scope',
        ]);
        $this->shouldHaveType(Primo::class);
    }

    function it_accepts_the_vid_parameter()
    {
        $this->initWithResponses([]);
        $this->setVid('UIO')->shouldReturn($this);
    }

    function it_can_make_search_requests_using_query_builder(Query $query)
    {
        $this->initWithResponses([
            new Response(200, [], 'test123'),
        ]);

        $query->build()->willReturn(['q' => 'any,contains,abc']);
        $this->search($query)->shouldReturn('test123');
    }

    function it_can_make_search_requests_using_query_array()
    {
        $query = [
            'q' => 'any,contains,abc',
        ];
        $this->initWithResponses([
            new Response(200, [], 'test123'),
        ]);

        $this->search($query)->shouldReturn('test123');
    }

    function it_can_build_and_return_search_urls(Query $query)
    {
        $this->initWithResponses([
            new Response(200, [], 'test123'),
        ]);

        $query->build()->willReturn(['q' => 'any,contains,abc']);
        $this->buildSearchUrl($query)->shouldReturn('https://api-eu.hosted.exlibrisgroup.com/primo/v1/search?vid=ABC&scope=default_scope&lang=en_US&pcAvailability=false&mode=advanced&newspapersActive=false&newspapersSearch=false&skipDelivery=Y&tab=default_tab&rtaLinks=true&q=any%2Ccontains%2Cabc');
    }


    function it_can_get_jwt_tokens()
    {
        $this->initWithResponses([
            new Response(200, [], 'test123'),
        ]);

        $this->getJwtToken()->shouldBe('test123');
    }

    function it_accepts_jwt_tokens()
    {
        $this->initWithResponses([]);
        $this->setJwtToken('test456');
        $this->getJwtToken()->shouldBe('test456');
    }
}
