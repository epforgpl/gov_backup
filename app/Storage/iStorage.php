<?php

namespace App\Storage;

use Psr\Http\Message\StreamInterface;

interface iStorage
{
    /**
     * Put an object
     *
     * @param string|\Psr\Http\Message\StreamInterface $input Input data
     * @param string $bucket Bucket or container name
     * @param string $uri Object URI
     * @param array $params Any custom params dependent of the implementation
     * @return null
     */
    public function putObject($input, $bucket, $uri, $params = []);


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
}