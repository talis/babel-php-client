<?php
if (!defined('APPROOT'))
{
    define('APPROOT', dirname(dirname(__DIR__)));
}

require_once APPROOT.'/vendor/autoload.php';

class BabelClientTest extends PHPUnit_Framework_TestCase
{
    function testToDo()
    {
        $client = new \babel\BabelClient('babel', '3001');

        $feed = $client->getTargetFeed('53a95d7852f81c081f000001', '8ac657a72ae255b4ecde923e676cc9bbe1bd14a8');
    }

}