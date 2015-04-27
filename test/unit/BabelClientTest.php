<?php
if (!defined('APPROOT'))
{
    define('APPROOT', dirname(dirname(__DIR__)));
}

require_once APPROOT.'/vendor/autoload.php';

/**
 * Travis-CI runs against the unit tests but can only test certain things.
 *
 * You should run the integration tests locally, with a running Babel and Persona server setup, as the
 * integration tests actually prove that this client library can read/write to Babel correctly.
 */
class BabelClientTest extends PHPUnit_Framework_TestCase
{
    /**
     * @expectedException \babel\BabelClientException
     * @expectedExceptionMessage Both babelHost and babelPort must be specified
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
        $client = new \babel\BabelClient('someHost', '3001');

        $target = null;
        $token = 'personaToken';

        $client->getTargetFeed($target, $token);
    }


    /**
     * @expectedException \babel\BabelClientException
     * @expectedExceptionMessage Missing token
     */
    function testGetTargetFeedWithNoToken()
    {
        $client = new \babel\BabelClient('someHost', '3001');

        $target = 'target';
        $token = null;

        $client->getTargetFeed($target, $token);
    }

    /**
     * @expectedException \babel\InvalidPersonaTokenException
     * @expectedExceptionMessage No persona token specified
     */
    function testCreateAnnotationMissingToken()
    {
        $client = new \babel\BabelClient('someHost', '3001');
        $client->createAnnotation(null, array('foo'=>'bar'));
    }

    /**
     * @expectedException \babel\BabelClientException
     * @expectedExceptionMessage Missing hasBody in data array
     */
    function testCreateAnnotationMissingHasBody()
    {
        $client = new \babel\BabelClient('someHost', '3001');
        $client->createAnnotation('someToken', array('foo'=>'bar'));
    }

    /**
     * @expectedException \babel\BabelClientException
     * @expectedExceptionMessage hasBody must be an array containing format and type
     */
    function testCreateAnnotationHasBodyNotArray()
    {
        $client = new \babel\BabelClient('someHost', '3001');
        $client->createAnnotation('someToken', array('hasBody'=>'foo'));
    }

    /**
     * @expectedException \babel\BabelClientException
     * @expectedExceptionMessage Missing hasBody.format in data array
     */
    function testCreateAnnotationMissingHasBodyFormat()
    {
        $client = new \babel\BabelClient('someHost', '3001');
        $client->createAnnotation('someToken', array('hasBody'=>array('type'=>'t')));
    }

    /**
     * @expectedException \babel\BabelClientException
     * @expectedExceptionMessage Missing hasBody.type in data array
     */
    function testCreateAnnotationMissingHasBodyType()
    {
        $client = new \babel\BabelClient('someHost', '3001');
        $client->createAnnotation('someToken', array('hasBody'=>array('format'=>'f')));
    }
}