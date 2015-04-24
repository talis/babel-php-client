<?php
namespace babel;

use \Guzzle\Http\Client;
use \Monolog\Handler\StreamHandler;
use \Monolog\Logger;

/**
 * Babel client.
 *
 * This is a port of the babel-node-client, please try to keep the two libraries in sync.
 *
 * @package babel
 */
class BabelClient
{
    /**
     * @var string
     */
    private $babelHost;

    /**
     * @var string
     */
    private $babelPort;

    /**
     * @var \Guzzle\Http\Client
     */
    private $httpClient = null;

    /**
     * @var \MonoLog\Logger
     */
    private $logger = null;

    /**
     * Babel client must be created with a host/port to connect to Babel.
     *
     * @param $babelHost
     * @param $babelPort
     * @throws BabelClientException
     */
    function __construct($babelHost, $babelPort)
    {
        if (empty($babelHost) || empty($babelPort))
        {
            throw new BabelClientException('Both babelHost and babelPort must be specified');
        }

        $this->babelHost = $babelHost;
        $this->babelPort = $babelPort;
    }

    /**
     * Specify an instance of MonoLog Logger for the Babel client to use.
     * @param Logger $logger
     */
    function setLogger(Logger $logger)
    {
        $this->logger = $logger;
    }

    /**
     * Get a feed based off a target identifier. Return either a list of feed identifiers, or hydrate it and
     * pass back the data as well
     *
     * @param string $target Feed target identifier
     * @param string $token Persona token
     * @param bool $hydrate Gets a fully hydrated feed, i.e. actually contains the posts
     * @throws \babel\BabelClientException
     */
    function getTargetFeed($target, $token, $hydrate=false)
    {
        if (empty($target))
        {
            throw new BabelClientException('Missing target');
        }
        if (empty($token))
        {
            throw new BabelClientException('Missing token');
        }

        $url = '/feeds/targets/'.md5($target).'/activity/annotations'.($hydrate ? '/hydrate':'');
        $headers = array(
            'Accept'=>'application/json',
            'Authorization'=>'Bearer:'.$token
        );

        $this->getLogger()->debug("URL: ".$url);

        $httpClient = $this->getHttpClient();

        //TODO Figure out how to have the exceptions:false globally in the client and not per request...
        $request = $httpClient->get($url, $headers, array('exceptions'=>false));

        $response = $request->send();

        if ($response->isSuccessful())
        {
            $this->getLogger()->debug('Successful response');
        }
        else
        {
            $this->getLogger()->error('Failed to call getTargetFeed: '.$response->getStatusCode().' - '.$response->getMessage());
            throw new BabelClientException('Error getting target Babel feed', $response->getStatusCode());
        }

    }

    /***
     * Queries multiple feeds.
     * Given an array of feed ids it will return a merged hydrated feed.
     *
     * @param array $feeds An array of Feed Identifiers
     * @param string $token Persona token
     */
    function getFeeds($feeds, $token)
    {

    }

    /**
     * Get annotations feed based off options passed in
     *
     * TODO See if all these are supported in the node client...
     *
     * Valid values for the options array:-
     *   hasTarget    - restrict to a specific target
     *   annotatedBy  - restrict to annotations made by a specific user
     *   hasBody.uri  - restrict to a specific body URI
     *   hasBody.type - restrict to annotations by the type of the body
     *   q            - perform a text search on hasBody.char field. If used, annotatedBy and hasTarget will be ignored
     *   limit        - limit returned results
     *   offset       - offset start of results
     */
    function getAnnotations($token, $queryStringMap)
    {

    }

    /**
     * Create an annotation
     *
     * TODO See if all these are supported in the node client...
     *
     * Valid values for the data array:-
     *   data.hasBody.format
     *   data.hasBody.type
     *   data.hasBody.chars
     *   data.hasBody.details
     *   data.hasBody.uri
     *   data.hasBody.asReferencedBy
     *   data.hasTarget
     *   data.hasTarget.uri
     *   data.hasTarget.fragment
     *   data.hasTarget.asReferencedBy
     *   data.annotatedBy
     *   data.motiviatedBy
     *   data.annotatedAt
     *
     * Valid values for the options array:-
     *   options.headers['X-Ingest-Synchronously']
     */
    function createAnnotation($token, $data, $options)
    {

    }

    /**
     * Get an instance to the passed in logger or lazily create one for Babel logging.
     */
    protected function getLogger()
    {
        if ($this->logger == null)
        {
            $this->logger = new Logger('BabelClient');
            $this->logger->pushHandler(new StreamHandler('php://stderr', Logger::DEBUG));
        }

        return $this->logger;
    }

    /**
     * Get the Babel host - can be mocked in tests.
     * @return string
     */
    protected function getBabelHost()
    {
        return $this->babelHost;
    }

    /**
     * Get the Babel port - can be mocked in tests.
     * @return string
     */
    protected function getBabelPort()
    {
        return $this->babelPort;
    }

    /**
     * Get an instance of the Guzzle HTTP client.
     *
     * @return \Guzzle\Http\Client
     */
    protected function getHttpClient()
    {
        if ($this->httpClient == null)
        {
//            $options = array(
//                'exceptions'=>false
//            );
            $options = array();

            $baseUrl = 'http://'.$this->getBabelHost().':'.$this->getBabelPort();
            $this->getLogger()->info('Created HTTP client with base URL: '.$baseUrl);

            $this->httpClient = new Client($baseUrl, $options);
        }

        return $this->httpClient;
    }
}