<?php
if (!defined('APPROOT'))
{
    define('APPROOT', dirname(dirname(__DIR__)));
}

require_once APPROOT.'/vendor/autoload.php';

/**
 * Babel client integration tests
 *
 * NB: To be able to run the integration tests you need a valid Persona token and a running instance of Babel server.
 */
class BabelClientTest extends PHPUnit_Framework_TestCase
{
    private $babelHost = 'babel';
    private $babelPort = '3001';
    private $personaToken = '159a1c58d73786193227bae9d02023e546b5502f';     // Needs to be a valid Persona token. Remember it expires frequently!
    private $feedId = '';           // This needs to match a valid feed ID in your local MongoDB for Babel server

    /**
     * @var \babel\BabelClient
     */
    private $babelClient;

    protected function setUp()
    {
        $this->babelClient = new \babel\BabelClient($this->babelHost, $this->babelPort);
    }

    function testCreateAnnotation()
    {
        // NB Sample data nabbed from critic app to see what gets passed in.
        $data = array(
            'hasBody'=>array(
                'format'=>'text/plain',
                'type'=>'Text'
            ),
            'annotatedBy'=>'aa',
            'hasTarget'=>array('uri'=>'http://foo')
        );

        error_log('json '.json_encode($data));


        $this->babelClient->createAnnotation($this->personaToken, $data);
    }


//    function testGetTargetFeed()
//    {
//        $this->babelClient->getTargetFeed($this->feedId, $this->personaToken);
//    }

//    function testGetFeeds()
//    {
//        $this->babelClient->getFeeds(array($this->feedId), $this->personaToken);
//    }

}