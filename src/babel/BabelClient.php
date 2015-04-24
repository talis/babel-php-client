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
     * @return mixed
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

        //TODO Is this actually supported in Babel? It's in node client but not found the route in Babel yet...
        $url = '/feeds/targets/'.md5($target).'/activity/annotations'.($hydrate ? '/hydrate':'');

        return $this->performBabelGet($url, $token);
    }

    /***
     * Queries multiple feeds.
     * Given an array of feed ids it will return a merged hydrated feed.
     *
     * @param array $feedIds An array of Feed Identifiers
     * @param string $token Persona token
     * @throws BabelClientException
     * @return mixed
     */
    function getFeeds(array $feedIds, $token)
    {
        $strFeedIds = implode(',', $feedIds);
        $url = '/feeds/annotations/hydrate?feed_ids='.urlencode($strFeedIds);
        return $this->performBabelGet($url, $token);
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

    function getAnnotations($token, array $options)
    {
        $queryString = http_build_query($options);
        $url = '/annotations?'.$queryString;

        return $this->performBabelGet($url, $token);
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
    function createAnnotation($token, array $arrData, $options=null)
    {
        //TODO See the required fields checked by the node client...
        //TODO $options processing for async ingest

        $url = '/annotations';

        return $this->performBabelPost($url, $token, $arrData);
    }


    /**
     * Perform a GET request against Babel and return the response or handle error.
     *
     * @param $url
     * @param $token
     * @return mixed
     * @throws InvalidPersonaTokenException
     * @throws NotFoundException
     * @throws BabelClientException
     */
    protected function performBabelGet($url, $token)
    {
        $headers = array(
            'Accept'=>'application/json',
            'Authorization'=>'Bearer '.$token
        );

        $this->getLogger()->debug('Babel GET: '.$url, $headers);

        $httpClient = $this->getHttpClient();

        $request = $httpClient->get($url, $headers, array('exceptions'=>false));

        $response = $request->send();

        if ($response->isSuccessful())
        {
            $responseBody = $response->getBody(true);

            $arrResponse = json_decode($responseBody, true);
            if ($arrResponse == null)
            {
                $this->getLogger()->error('Failed to decode JSON response: '.$responseBody);
                throw new BabelClientException('Failed to decode JSON response: '.$responseBody);
            }

            return $arrResponse;
        }
        else
        {
            /*
             * Is is a Persona token problem?
             */
            $statusCode = $response->getStatusCode();
            switch ($statusCode)
            {
                case 401:
                    $this->getLogger()->error('Persona token invalid/expired for request: GET '.$url);
                    throw new InvalidPersonaTokenException('Persona token is either invalid or has expired');
                case 404:
                    $this->getLogger()->error('Nothing found for request: GET '.$url);
                    throw new NotFoundException('Nothing found for request:'.$url);
                default:
                    $this->getLogger()->error('Babel GET failed for request: '.$url, array('statusCode'=>$response->getStatusCode(), 'message'=>$response->getMessage(), 'body'=>$response->getBody(true)));
                    throw new BabelClientException('Error performing Babel request: GET '.$url , $response->getStatusCode());
            }
        }

    }

    /**
     * Perform a GET request against Babel and return the response or handle error.
     *
     * @param $url
     * @param $token
     * @param array $arrData
     * @return mixed
     * @throws InvalidPersonaTokenException
     * @throws BabelClientException
     */
    protected function performBabelPost($url, $token, array $arrData)
    {
        $headers = array(
            'Accept'=>'application/json',
            'Authorization'=>'Bearer '.$token
        );

        $this->getLogger()->debug('Babel POST: '.$url, $headers);

        $httpClient = $this->getHttpClient();

        $request = $httpClient->post($url, $headers, $arrData, array('exceptions'=>false));

        $response = $request->send();

        if ($response->isSuccessful())
        {
            $responseBody = $response->getBody(true);

            $arrResponse = json_decode($responseBody, true);
            if ($arrResponse == null)
            {
                $this->getLogger()->error('Failed to decode JSON response: '.$responseBody);
                throw new BabelClientException('Failed to decode JSON response: '.$responseBody);
            }

            return $arrResponse;
        }
        else
        {
            /*
             * Is is a Persona token problem?
             */
            $statusCode = $response->getStatusCode();
            if ($statusCode == 401)
            {
                $this->getLogger()->error('Persona token invalid/expired for request: POST '.$url);
                throw new InvalidPersonaTokenException('Persona token is either invalid or has expired');
            }
            else
            {
                $this->getLogger()->error('Babel GET failed for request: '.$url, array('statusCode'=>$response->getStatusCode(), 'message'=>$response->getMessage(), 'body'=>$response->getBody(true)));
                throw new BabelClientException('Error performing Babel request: POST '.$url , $response->getStatusCode());
            }
        }
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
            $baseUrl = 'http://'.$this->getBabelHost().':'.$this->getBabelPort();
            $this->httpClient = new Client($baseUrl);
        }

        return $this->httpClient;
    }
}