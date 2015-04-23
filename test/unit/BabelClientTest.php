<?php
require_once '../../vendor/autoload.php';

class BabelClientTest extends PHPUnit_Framework_TestCase
{
    /**
     * @expectedException \Exception
     * @expectedExceptionMessage Missing target
     */
    function testGetTargetFeedWithNoTarget()
    {
        $client = new \babel\BabelClient();

        $target = null;
        $token = 'personaToken';

        $client->getTargetFeed($target, $token);
    }

    /**
     * @expectedException \Exception
     * @expectedExceptionMessage Missing token
     */
    function testGetTargetFeedWithNoToken()
    {
        $client = new \babel\BabelClient();

        $target = 'target';
        $token = null;

        $client->getTargetFeed($target, $token);
    }

}