<?php

namespace App\Storage;

use Psr\Http\Message\StreamInterface;

// TODO this package should be split into a dedicated lib
interface iStorage
{
    /**
     * Put an object
     *
     * @param string|\Psr\Http\Message\StreamInterface $input Input data
     * @param string $bucket Bucket or container name
     * @param string $uri Object URI
     * @param string $contentType Content-Type header to be set on the object
     * @param array $params Any custom params dependent of the implementation
     * @return null
     */
    public function putObject($input, $bucket, $uri,  $contentType = null, $params = []);


    /**
     * Get an object
     *
     * @param string $bucket Bucket or container name
     * @param string $uri Object URI
     * @return mixed
     */
    public function getObject($bucket, $uri);

    public function getObjectStream($bucket, $uri): StreamInterface;

    /**
     * Delete an object
     *
     * @param string $bucket Bucket or container name
     * @param string $uri Object URI
     * @return boolean
     */
    public function deleteObject($bucket, $uri);

    /**
     * Return URL of publicly available endpoint or null
     *
     * @param $bucket Bucket or container name
     * @param $uri Object URI
     * @return mixed URL or null if this storage or bucket is not publicly available
     */
    public function getPublicUrl($bucket, $uri);
}