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
    private $babelHost = 'http://babel';
    private $babelPort = '3001';
    private $personaToken = 'b3306c15c47a317768ffa965329b6c0ced18bb47';     // Needs to be a valid Persona token. Remember it expires frequently!

    /**
     * @var \babel\BabelClient
     */
    private $babelClient;

    protected function setUp()
    {
        $this->babelClient = new \babel\BabelClient($this->babelHost, $this->babelPort);
    }

    /**
     * All of this is lumped into one test case as the querying is based on what has just been created.
     */
    function testCreationAndRetrieval()
    {
        $annotatedBy = uniqid('annotatedBy');
        $targetUri1 = uniqid('http://foo/1/');
        $targetUri2 = uniqid('http://foo/2/');

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
         * Create second annotation...
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
         * Create third annotation (for the same targetUri as the second annotation, so we can check we get two back)...
         */
        $data3 = array(
            'hasBody'=>array('format'=>'text/plain', 'type'=>'Text'),
            'hasTarget'=>array('uri'=>$targetUri2),
            'annotatedBy'=>$annotatedBy
        );
        $annotation3 = $this->babelClient->createAnnotation($this->personaToken, $data3);
        $this->assertEquals($targetUri2, $annotation3['hasTarget']['uri']);
        $this->assertEquals($annotatedBy, $annotation3['annotatedBy']);

        /*
         * Query annotations by 'annotatedBy' and check we get the two we created...
         */
        $arrAnnotations = $this->babelClient->getAnnotations($this->personaToken, array('annotatedBy'=>$annotatedBy));

        $this->assertEquals(3, $arrAnnotations['count'], 'Should match our two newly created annotations');

        $expectedTargetUris = array($targetUri1, $targetUri2, $targetUri2);
        $actualTargetUris = array();
        foreach ($arrAnnotations['annotations'] as $annotation)
        {
            array_push($actualTargetUris, $annotation['hasTarget']['uri']);
        }
        sort($actualTargetUris);
        $this->assertEquals($expectedTargetUris, $actualTargetUris, 'Targets URIs should match our two created annotations');

        /*
         * Get the feed for targetUri1
         * NB: It takes a while for the feed to be query-able so we have to loop a while to check (when the synchronous flag isn't set on annotation creation)
         */
        $iAttempts = 10;
        $targetFeedUnhydrated = array();
        $targetFeedHydrated = array();
        while (--$iAttempts > 0)
        {
            try
            {
                $targetFeedUnhydrated = $this->babelClient->getTargetFeed($targetUri2, $this->personaToken);
                $targetFeedHydrated = $this->babelClient->getTargetFeed($targetUri2, $this->personaToken, true);
                break;
            }
            catch (\babel\NotFoundException $e)
            {
                // Feed not created yet, wait a while and try again...
                sleep(1);
            }
            catch (\Exception $e)
            {
                $this->fail('Error getting feed: '.$e->getMessage());
                break;
            }
        }

        if ($iAttempts == 0)
        {
            $this->fail('Failed to get data after 10 attempts');
        }

        // Check the basics of the unhydrated version...
        $this->assertEquals(2, $targetFeedUnhydrated['feed_length']);
        $this->assertEquals(2, count($targetFeedUnhydrated['annotations']));
        $this->assertFalse(is_array($targetFeedUnhydrated['annotations'][0]));

        // Check the basics of the hydrated version...
        $this->assertEquals(2, $targetFeedHydrated['feed_length']);
        $this->assertEquals(2, count($targetFeedHydrated['annotations']));
        $this->assertArrayHasKey('hasTarget', $targetFeedHydrated['annotations'][0]);

        /*
         * Try to getFeeds() via the cryptic redis keys it seems to use...
         */
        $targetUri1Hash = 'targets:'.md5($targetUri1).':activity';
        $targetUri2Hash = 'targets:'.md5($targetUri2).':activity';

        $feedIds = array($targetUri1Hash, $targetUri2Hash);

        $arrFeeds = $this->babelClient->getFeeds($feedIds, $this->personaToken);

        $this->assertEquals(2, count($arrFeeds['feeds']), 'Should be 2 feeds');
        $this->assertEquals(3, $arrFeeds['feed_length'], 'Should be 3 annotations for the 2 feeds');
    }
}