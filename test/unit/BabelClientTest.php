<?php
if (!defined('APPROOT'))
{
    define('APPROOT', dirname(dirname(__DIR__)));
}

require_once APPROOT.'/vendor/autoload.php';

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

}