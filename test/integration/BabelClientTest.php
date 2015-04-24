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
    private $personaToken = 'd7a7da93924d6b957dd51647495326a2a1ae0ca4';     // Needs to be a valid Persona token. Remember it expires frequently!

    /**
     * @var \babel\BabelClient
     */
    private $babelClient;

    protected function setUp()
    {
        $this->babelClient = new \babel\BabelClient($this->babelHost, $this->babelPort);
    }

    /**
     * All of this is lumped into one test case as there the querying is based on what has just been created.
     */
    function testCreationAndRetrieval()
    {
        $annotatedBy = uniqid('annotatedBy', true);
//        $targetUri1 = uniqid('http://foo/1/');
//        $targetUri2 = uniqid('http://foo/2/');
        $targetUri1 = 'http://foo/6/';
        $targetUri2 = 'http://foo/7/';

        error_log($targetUri1);
        error_log($targetUri2);

        /*
         * Create first annotation...
         */
        $data1 = array(
            'hasBody'=>array('format'=>'text/plain', 'type'=>'Text'),
            'hasTarget'=>array('uri'=>$targetUri1),
            'annotatedBy'=>$annotatedBy
        );
        $annotation1 = $this->babelClient->createAnnotation($this->personaToken, $data1);
        $this->assertEquals($targetUri1, $annotation1['hasTarget']['uri']);
        $this->assertEquals($annotatedBy, $annotation1['annotatedBy']);

        /*
         * Create first annotation...
         */
        $data2 = array(
            'hasBody'=>array('format'=>'text/plain', 'type'=>'Text'),
            'hasTarget'=>array('uri'=>$targetUri2),
            'annotatedBy'=>$annotatedBy
        );
        $annotation2 = $this->babelClient->createAnnotation($this->personaToken, $data2);
        $this->assertEquals($targetUri2, $annotation2['hasTarget']['uri']);
        $this->assertEquals($annotatedBy, $annotation2['annotatedBy']);

        /*
         * Query annotations by 'annotatedBy' and check we get the two we created...
         */
        $arrAnnotations = $this->babelClient->getAnnotations($this->personaToken, array('annotatedBy'=>$annotatedBy));

        $this->assertEquals(2, $arrAnnotations['count'], 'Should match our two newly created annotations');

        $expectedTargetUris = array($targetUri1, $targetUri2);
        $actualTargetUris = array();
        foreach ($arrAnnotations['annotations'] as $annotation)
        {
            array_push($actualTargetUris, $annotation['hasTarget']['uri']);
        }
        sort($actualTargetUris);
        $this->assertEquals($expectedTargetUris, $actualTargetUris, 'Targets URIs should match our two created annotations');

        /*
         * Get the feed for targetUri1
         */
        $targetFeed = $this->babelClient->getTargetFeed($targetUri1, $this->personaToken);
        print_r($targetFeed);
    }


//    function testGetTargetFeed()
//    {
//    }

//    function testGetFeeds()
//    {
//        $this->babelClient->getFeeds(array($this->feedId), $this->personaToken);
//    }

}