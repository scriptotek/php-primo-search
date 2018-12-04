<?php

namespace Scriptotek\PrimoSearch;

use Http\Client\Common\Plugin\DecoderPlugin;
use Http\Client\Common\Plugin\ErrorPlugin;
use Http\Client\Common\Plugin\RetryPlugin;
use Http\Client\Common\PluginClient;
use Http\Client\HttpClient;
use Http\Discovery\HttpClientDiscovery;
use Http\Discovery\MessageFactoryDiscovery;
use Http\Message\MessageFactory;

class Primo
{
    // Services
    protected $http;
    protected $messageFactory;

    // For hosted setup
    protected $apiKey;
    protected $region;

    // For on-premises setup
    protected $baseUrl;
    protected $searchUrl;
    protected $inst;
    protected $jwtToken;

    // Common config
    protected $userAgent = 'scriptotek/primo-search';
    protected $vid;
    protected $scope;
    protected $lang = 'en_US';

    public function __construct(
        array $config,
        HttpClient $httpClient = null,
        array $plugins = [],
        MessageFactory $messageFactory = null
    ) {
        $this->vid = $config['vid'];
        $this->scope = $config['scope'];

        if (isset($config['apiKey'])) {
            // Hosted
            $this->apiKey = $config['apiKey'];
            $this->region = $config['region'] ?? 'eu';
            $this->baseUrl = "https://api-{$this->region}.hosted.exlibrisgroup.com/primo/v1";
            $this->searchUrl = "{$this->baseUrl}/search";
        } else {
            // On-premises
            $this->inst = $config['inst'];
            $this->baseUrl = rtrim($config['baseUrl'], '/');
            $this->searchUrl = $config['searchUrl'] ?? "{$this->baseUrl}/pnxs";
        }

        $httpClient = $httpClient ?: HttpClientDiscovery::find();
        $plugins[] = new RetryPlugin();
        $plugins[] = new ErrorPlugin();
        $plugins[] = new DecoderPlugin();
        $this->http = new PluginClient($httpClient, $plugins);
        $this->messageFactory = $messageFactory ?: MessageFactoryDiscovery::find();
    }

    /**
     * Set the view ID.
     *
     * @param $vid
     */
    public function setVid(string $vid)
    {
        $this->vid = $vid;

        return $this;
    }

    /**
     * Set the search scope.
     *
     * @param $scope
     */
    public function setScope(string $scope)
    {
        $this->scope = $scope;

        return $this;
    }

    protected function getGuestJwtToken()
    {
        $res = $this->request("{$this->baseUrl}/guestJwt/{$this->inst}?" . http_build_query([
            'isGuest' => 'true',
            'viewId'  => $this->vid,
            'lang'    => $this->lang,
        ]));

        $this->jwtToken = trim($res, '"');

        return $this->jwtToken;
    }

    /**
     * Build and return the full URL for a search request.
     *
     * @param Query|array $query
     * @return string
     */
    public function buildSearchUrl($query)
    {
        if (is_object($query)) {
            $query = $query->build();
        }

        $params = array_merge(
            [
                'vid'              => $this->vid,
                'scope'            => $this->scope,
                'inst'             => $this->inst,
                'lang'             => $this->lang,
                'pcAvailability'   => 'false',
                'mode'             => 'advanced',
                'newspapersActive' => 'false',
                'newspapersSearch' => 'false',
                'skipDelivery'     => 'Y',
                'tab'              => 'default_tab',
                'rtaLinks'         => 'true',
            ],
            $query
        );

        return $this->searchUrl . '?' . http_build_query($params);
    }

    /**
     * Make a search request and return the decoded JSON response.
     *
     * @param Query|array $query
     * @return object
     */
    public function search($query)
    {
        if (!isset($this->apiKey) && !isset($this->jwtToken)) {
            $this->getGuestJwtToken();
        }

        $result = $this->request($this->buildSearchUrl($query));

        return json_decode($result) ?? $result;
    }

    /**
     * Make an API request and return the text response.
     *
     * @param string $url
     * @return string
     */
    protected function request($url)
    {
        $headers = [
            'Accept-Encoding' => 'gzip',
            'Accept'          => 'application/json',
            'User-Agent'      => $this->userAgent,
            'Authorization'   => isset($this->apiKey)
                ? "apikey {$this->apiKey}"
                : "Bearer {$this->jwtToken}",
        ];

        $request = $this->messageFactory->createRequest('GET', $url, $headers);

        $response = $this->http->sendRequest($request);

        return strval($response->getBody());
    }

    public function setJwtToken(string $token)
    {
        $this->jwtToken = $token;
    }

    public function getJwtToken()
    {
        if (isset($this->jwtToken)) {
            return $this->jwtToken;
        }

        return $this->getGuestJwtToken();
    }

    /**
     * Request the configuration for the current view and return the decoded JSON response.
     *
     * @return object
     */
    public function configuration()
    {
        $result = $this->request("{$this->baseUrl}/configuration/{$this->vid}");

        return json_decode($result) ?? $result;
    }

    /**
     * Request the translations for the current view and return the decoded JSON response.
     *
     * @param string $lang
     * @return object
     */
    public function translations($lang)
    {
        $result = $this->request("{$this->baseUrl}/translations/{$this->vid}?lang={$lang}");

        return json_decode($result) ?? $result;
    }
}
