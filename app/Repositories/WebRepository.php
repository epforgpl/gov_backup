<?php

namespace App\Repositories;

use App\Exceptions\MalformedUrlException;
use App\Exceptions\ResourceNotIndexedException;
use Elasticsearch\ClientBuilder;
use S3;
use App\Models\WebObject;
use Illuminate\Support\Facades\Config;

class WebRepository
{
    protected $ES;
    protected $S3;

    private $schemes = ['https', 'http'];

    public function __construct(S3 $S3)
    {
        $clientBuilder = ClientBuilder::create()->setHosts(['localhost:9201']);
        $this->ES = $clientBuilder->build();

        $this->S3 = $S3;
    }

    private function getScheme($url)
    {
        if( strpos($url, '//')===0 ) {
            return '//';
        }
        foreach( $this->schemes as $scheme ) {
            if( stripos($url, $scheme) === 0 ) {
                return $scheme;
            }
        }
        return false;
    }

    private function parseUrl($url)
    {
        $scheme = $this->getScheme($url);
        if( !$scheme ) {
            $url = '//' . $url;
        }
        $data = parse_url($url);
        if ($data === false || !($data['host'])) {
            throw new MalformedUrlException($url);
        }

        $host = $data['host'] ?? '';
        $path = $data['path'] ?? '';
        $query = $data['query'] ?? '';

        if( $path === '/' ) {
            $path = '';
        }

        if( $query ) {
            parse_str($query, $data);
            ksort($data);
            $query = http_build_query($data);
        }

        return [
            'host' => $host,
            'path' => $path,
            'query' => $query,
        ];
    }

    public function getById($id, $options = [])
    {
        $options = array_merge([
            'loadCurrentVersion' => false,
        ], $options);

        $must = [
            ['term' => ['dataset' => 'web_objects']],
            ['term' => ['id' => $id]],
        ];

        $res = $this->ES->search([
            'index' => 'mojepanstwo_v1',
            'type' => 'objects',
            'body' => [
                'query' => [
                    'bool' => [
                        'must' => $must,
                    ],
                ],
                '_source' => ['data.*'],
            ]
        ]);

        $web_object = $this->processGetResponse($res, $options);

        if ($web_object === null) {
            throw new ResourceNotIndexedException("ID = $id");
        }
        return $web_object;
    }

    public function get($url, $options = [])
    {
        $urlp = trim($url);
        if( !$urlp ) {
            throw new \InvalidArgumentException('URL is not set');
        }

        $urlp = $this->parseUrl($urlp);

        $options = array_merge([
            'loadCurrentVersion' => false,
        ], $options);

        $must = [
            ['term' => ['dataset' => 'web_objects']],
            ['term' => ['data.web_objects.host' => $urlp['host']]],
            ['term' => ['data.web_objects.path' => $urlp['path']]],
            ['term' => ['data.web_objects.query' => $urlp['query']]]
        ];

        $res = $this->ES->search([
            'index' => 'mojepanstwo_v1',
            'type' => 'objects',
            'body' => [
                'query' => [
                    'bool' => [
                        'must' => $must,
                    ],
                ],
                '_source' => ['data.*'],
            ]
        ]);

        $web_object = $this->processGetResponse($res, $options);

        if ($web_object === null) {
            throw new ResourceNotIndexedException($url);
        }
        return $web_object;
    }

    private function processGetResponse($res, $options)
    {
        if(
            $res &&
            !empty($res['hits']) &&
            !empty($res['hits']['hits']) &&
            ( $hit = $res['hits']['hits'][0] )
        ) {

            $web_object = new WebObject($hit['_source']);

            if(
                $options['loadCurrentVersion'] &&
                ( $current_version = $web_object->getCurrentVersion() )
            ) {
                try {
                    $key = 'web/objects/' . $web_object->getId() . '/' . $current_version->getBodyHash() . '/body';
                    if( $current_version->isBodyProcessed() ) {
                        $key .= '-p';
                    }
                    $response = $this->S3->getObject('resources', $key);
                } catch(\S3Exception $e) {
                    // TODO don't silence it!
                    $response = false;
                }

                if( $response && $response->body ) {
                    $current_version->setBody($response->body);
                }
            }

            return $web_object;

        }

        return null;
    }
}
