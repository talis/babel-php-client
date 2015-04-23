<?php
namespace babel;

/**
 * Babel client.
 *
 * This is a port of the babel-node-client, please try to keep the two libraries in sync.
 *
 * @package babel
 */
class BabelClient
{
    function __construct()
    {
        /*
         * The calling project needs to have already set these up.
         */
//        if (!defined('FOO_VAR'))
//        {
//            throw new \Exception('Missing define: FOO_VAR');
//        }
    }

    /**
     * Get a feed based off a target identifier. Return either a list of feed identifiers, or hydrate it and
     * pass back the data as well
     *
     * @param string $target Feed target identifier
     * @param string $token Persona token
     * @param bool $hydrate Gets a fully hydrated feed, i.e. actually contains the posts
     * @throws \Exception
     */
    function getTargetFeed($target, $token, $hydrate=false)
    {
        if (empty($target))
        {
            throw new \Exception('Missing target');
        }
        if (empty($token))
        {
            throw new \Exception('Missing token');
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
} 