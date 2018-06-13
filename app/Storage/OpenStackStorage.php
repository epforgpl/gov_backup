<?php

namespace App\Storage;

use App\Exceptions\StorageException;
use OpenStack\OpenStack;
use Psr\Http\Message\StreamInterface;

/**
 * Implementation of Storage in OpenStack's Object Storage
 *
 * API Reference: https://developer.openstack.org/api-ref/object-store/
 * PHP docs: https://php-openstack-sdk.readthedocs.io/en/latest/services/object-store/v1/objects.html
 *
 * @package AppBundle\Utils\Storage
 */
class OpenStackStorage implements iStorage
{
    /**
     * @var \OpenStack\ObjectStore\v1\Service
     */
    private $store;


    /**
     * OpenStackSwiftStorage constructor.
     *
     * @param array $config
     * @throws StorageException
     */
    public function __construct(array $config)
    {
        if (isset($config['keystoneAuth']) && $config['keystoneAuth'] === 'v2') {
            // change implementation from default v3 to v2
            $httpClient = new \GuzzleHttp\Client([
                'base_uri' => \OpenStack\Common\Transport\Utils::normalizeUrl($config['authUrl']),
                'handler'  => \GuzzleHttp\HandlerStack::create(),
            ]);

            $config['identityService'] = \OpenStack\Identity\v2\Service::factory($httpClient);
        }

        try {
            $this->store = (new OpenStack($config))->objectStoreV1();
        } catch (\Exception $ex) {
            throw new StorageException($ex);
        }
    }

    public function putObject($input, $bucket, $uri, $contentType = null, $params = [])
    {
        $options = [
            'name' => $uri,
        ];

        // all options taken from https://github.com/php-opencloud/openstack/blob/master/src/ObjectStore/v1/Api.php#L156
        /*
        'containerName'      => $this->params->containerName(),
        'name'               => $this->params->objectName(),
        'content'            => $this->params->content(),
        'stream'             => $this->params->stream(),
        'contentType'        => $this->params->contentType(),
        'detectContentType'  => $this->params->detectContentType(),
        'copyFrom'           => $this->params->copyFrom(),
        'ETag'               => $this->params->etag(),
        'contentDisposition' => $this->params->contentDisposition(),
        'contentEncoding'    => $this->params->contentEncoding(),
        'deleteAt'           => $this->params->deleteAt(),
        'deleteAfter'        => $this->params->deleteAfter(),
        'metadata'           => $this->params->metadata('object'),
        'ifNoneMatch'        => $this->params->ifNoneMatch(),
        'objectManifest'     => $this->params->objectManifest(),
        */

        if ($input instanceof StreamInterface) {
            $options['stream'] = $input;

        } else {
            $options['content'] = $input;
        }

        if ($contentType !== null) {
            $options['contentType'] = $contentType;
        }

        try {
            $this->store->getContainer($bucket)
                ->createObject($options);

            // if we one wants to standardize the response, here is what we can get out of the return value
//            containerName = "govbackup-public"
//            name = "test"
//            hash = "098f6bcd4621d373cade4e832627b4f6"
//            contentType = "text/html; charset=UTF-8"
//            contentLength = "0"
//            lastModified = "Tue, 27 Mar 2018 23:01:14 GMT"
//            metadata
        } catch (\Exception $ex) {
            // rethrow exception
            throw new StorageException($ex);
        }
    }

    public function getObject($bucket, $uri)
    {
        return $this->getObjectStream($bucket, $uri);
    }

    public function getObjectDefinition($bucket, $uri)
    {
        try {
            // TODO standardize output
            return $this->store
                ->getContainer($bucket)
                ->getObject($uri);
        } catch (\Exception $ex) {
            // rethrow exception
            throw new StorageException($ex);
        }
    }

    public function getObjectStream($bucket, $uri): StreamInterface
    {
        try {
            // TODO content type would be handy
            return $this->store
                ->getContainer($bucket)
                ->getObject($uri)
                ->download();
        } catch (\Exception $ex) {
            // rethrow exception
            throw new StorageException($ex);
        }
    }

    public function deleteObject($bucket, $uri)
    {
        try {
            $response = $this->store
                ->getContainer($bucket)
                ->getObject($uri)
                ->delete();

            // TODO handle response
            return $response;
        } catch (\Exception $ex) {
            // rethrow exception
            throw new StorageException($ex);
        }
    }
}