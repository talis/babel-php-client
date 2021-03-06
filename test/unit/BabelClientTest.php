<?php
if (!defined('APPROOT'))
{
    define('APPROOT', dirname(dirname(__DIR__)));
}

require_once APPROOT.'/vendor/autoload.php';

use Guzzle\Http\Client;
use Guzzle\Plugin\Mock\MockPlugin;
use Guzzle\Http\Message\Response;

/**
 * Travis-CI runs against the unit tests but can only test certain things.
 *
 * You should run the integration tests locally, with a running local Babel server setup, as the
 * integration tests actually prove that this client library can read/write to Babel correctly.
 */
class BabelClientTest extends PHPUnit_Framework_TestCase
{
    private $babelClient;
    private $baseCreateAnnotationData;

    protected function setUp()
    {
        $this->babelClient = new \babel\BabelClient('http://someHost', '3001');

        $this->baseCreateAnnotationData = array(
            'annotatedBy'=>'a',
            'hasTarget'=>array(
                'uri'=>'http://foo'
            ),
            'hasBody'=>array(
                'type'=>'t',
                'format'=>'f'
            )
        );
    }

    /**
     * @expectedException \babel\BabelClientException
     * @expectedExceptionMessage babelHost must be specified
     */
    function testConstructorFailure()
    {
        $client = new \babel\BabelClient(null, null);

        $target = null;
        $token = 'personaToken';

        $client->getTargetFeed($target, $token);
    }

    /**
     * @expectedException \babel\BabelClientException
     * @expectedExceptionMessage Missing target
     */
    function testGetTargetWithNoTarget()
    {
        $target = null;
        $token = 'personaToken';

        $this->babelClient->getTargetFeed($target, $token);
    }


    /**
     * @expectedException \babel\BabelClientException
     * @expectedExceptionMessage Missing token
     */
    function testGetTargetFeedWithNoToken()
    {
        $this->babelClient->getTargetFeed('target', null);
    }

    /**
     * @expectedException \babel\InvalidPersonaTokenException
     * @expectedExceptionMessage No persona token specified
     */
    function testCreateAnnotationMissingToken()
    {
        $this->babelClient->createAnnotation(null, $this->baseCreateAnnotationData);
    }

    /**
     * @expectedException \babel\BabelClientException
     * @expectedExceptionMessage Missing hasBody in data array
     */
    function testCreateAnnotationMissingHasBody()
    {
        unset($this->baseCreateAnnotationData['hasBody']);
        $this->babelClient->createAnnotation('someToken', $this->baseCreateAnnotationData);
    }

    /**
     * @expectedException \babel\BabelClientException
     * @expectedExceptionMessage hasBody must be an array containing format and type
     */
    function testCreateAnnotationHasBodyNotArray()
    {
        $this->baseCreateAnnotationData['hasBody'] = 'foo';
        $this->babelClient->createAnnotation('someToken', $this->baseCreateAnnotationData);
    }

    /**
     * @expectedException \babel\BabelClientException
     * @expectedExceptionMessage Missing hasBody.format in data array
     */
    function testCreateAnnotationMissingHasBodyFormat()
    {
        unset($this->baseCreateAnnotationData['hasBody']['format']);
        $this->babelClient->createAnnotation('someToken', $this->baseCreateAnnotationData);
    }

    /**
     * @expectedException \babel\BabelClientException
     * @expectedExceptionMessage Missing hasBody.type in data array
     */
    function testCreateAnnotationMissingHasBodyType()
    {
        unset($this->baseCreateAnnotationData['hasBody']['type']);
        $this->babelClient->createAnnotation('someToken', $this->baseCreateAnnotationData);
    }

    /**
     * @expectedException \babel\BabelClientException
     * @expectedExceptionMessage Missing annotatedBy in data array
     */
    function testCreateAnnotationMissingAnnotatedBy()
    {
        unset($this->baseCreateAnnotationData['annotatedBy']);
        $this->babelClient->createAnnotation('someToken', $this->baseCreateAnnotationData);
    }

    /**
     * @expectedException \babel\BabelClientException
     * @expectedExceptionMessage Missing hasTarget in data array
     */
    function testCreateAnnotationMissingHasTarget()
    {
        unset($this->baseCreateAnnotationData['hasTarget']);
        $this->babelClient->createAnnotation('someToken', $this->baseCreateAnnotationData);
    }

    /**
     * @expectedException \babel\BabelClientException
     * @expectedExceptionMessage hasTarget must be an array containing uri
     */
    function testCreateAnnotationHasTargetIsNotArray()
    {
        $this->baseCreateAnnotationData['hasTarget'] = 'foo';
        $this->babelClient->createAnnotation('someToken', $this->baseCreateAnnotationData);
    }

    function testCreateAnnotationErrorMessage()
    {
        $babelClient = $this->getMockBuilder('babel\BabelClient')
            ->setMethods(array('getHTTPClient'))
            ->setConstructorArgs(array('http://someHost', '3001'))
            ->getMock();

        $httpClient = new Client('http://someHost:3001/annotations');

        $mock = new MockPlugin();
        $responseBody = array('message' => 'Some kind of validation failure');
        $mock->addResponse(new Response(400, null, json_encode($responseBody)));
        $httpClient->addSubscriber($mock);
        $babelClient->expects($this->once())->method('getHTTPClient')->will($this->returnValue($httpClient));
        $this->setExpectedException('babel\BabelClientException', 'Error 400 for POST /annotations: ' . $responseBody['message']);
        $babelClient->createAnnotation('someToken', $this->baseCreateAnnotationData);
    }

    function testGetFeedErrorMessage()
    {
        $babelClient = $this->getMockBuilder('babel\BabelClient')
            ->setMethods(array('getHTTPClient'))
            ->setConstructorArgs(array('http://someHost', '3001'))
            ->getMock();

        $path = '/feeds/targets/'.md5('1234').'/activity/annotations';
        $httpClient = new Client('http://someHost:3001' . $path);

        $mock = new MockPlugin();
        $responseBody = array('message' => 'Something important was left out');
        $mock->addResponse(new Response(400, null, json_encode($responseBody)));
        $httpClient->addSubscriber($mock);
        $babelClient->expects($this->once())->method('getHTTPClient')->will($this->returnValue($httpClient));
        $this->setExpectedException('babel\BabelClientException', 'Error 400 for GET ' . $path . ': ' . $responseBody['message']);
        $babelClient->getTargetFeed('1234', 'someToken');
    }

    function testGetFeedCountErrorMessage()
    {
        $babelClient = $this->getMockBuilder('babel\BabelClient')
            ->setMethods(array('getHTTPClient'))
            ->setConstructorArgs(array('http://someHost', '3001'))
            ->getMock();

        $path = '/feeds/targets/'.md5('1234').'/activity/annotations?delta_token=0';
        $httpClient = new Client('http://someHost:3001' . $path);

        $mock = new MockPlugin();
        $responseBody = array('message' => 'Something went bang');
        $mock->addResponse(new Response(500, null, json_encode($responseBody)));
        $httpClient->addSubscriber($mock);
        $babelClient->expects($this->once())->method('getHTTPClient')->will($this->returnValue($httpClient));
        $this->setExpectedException('babel\BabelClientException', 'Error 500 for HEAD ' . $path);
        $babelClient->getTargetFeedCount('1234', 'someToken');
    }
}