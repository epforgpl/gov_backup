<?php
/**
 * Created by PhpStorm.
 * User: kmadejski
 * Date: 27.03.18
 * Time: 22:25
 */

namespace App\Storage;

use App\Exceptions\StorageException;
use Psr\Http\Message\StreamInterface;

class S3Storage implements iStorage
{

    /**
     * @var \S3
     */
    private $storage;

    public function __construct(array $options)
    {
        $this->storage = new \S3();
        $this->storage->setAuth($options['accessKey'], $options['secretKey']);
        $this->storage->setEndpoint($options['endpoint']);
        $this->storage->setExceptions(true);
    }

    /**
     * Put an object
     *
     * @param string|\Psr\Http\Message\StreamInterface $input Input data
     * @param string $bucket Bucket or container name
     * @param string $uri Object URI
     * @param array $params Any custom params dependent of the implementation
     * @return null
     * @throws StorageException
     */
    public function putObject($input, $bucket, $uri, $contentType = null, $params = [])
    {
        $acl = ( isset($params['ACL']) && ($params['ACL'] == 'public') ) ? \S3::ACL_PUBLIC_READ : \S3::ACL_PRIVATE;
        $meta = isset($params['Meta']) ? $params['Meta'] : [];

        $headers = isset($params['Headers']) ? $params['Headers'] : [];
        if ($contentType !== null) {
            $headers['Content-Type'] = $contentType; // TODO test it
        }

        try {
            $this->storage->putObject($input, $bucket, $uri, $acl, $meta, $headers);
        } catch (\S3Exception $ex) {
            throw new StorageException($ex);
        }
    }

    /**
     * Get an object
     *
     * @param string $bucket Bucket or container name
     * @param string $uri Object URI
     * @return mixed
     */
    public function getObject($bucket, $uri)
    {
        $response = $this->storage->getObject($bucket, $uri);

        return $response->body;
    }

    public function getObjectStream($bucket, $uri): StreamInterface
    {
        throw new \Exception("Implement getObjectStream() method."); // TODO
    }

    /**
     * Delete an object
     *
     * @param string $bucket Bucket or container name
     * @param string $uri Object URI
     * @return boolean
     */
    public function deleteObject($bucket, $uri)
    {
        throw new \Exception("Implement deleteObject() method."); // TODO
    }

    /**
     * Return URL for publicly available endpoint or null
     *
     * @param $bucket Bucket or container name
     * @param $uri Object URI
     * @return mixed URL or null if this storage or bucket is not publicly available
     */
    public function getPublicUrl($bucket, $uri)
    {
        // TODO getPublicUrl method not implemented
        return null;
    }
}