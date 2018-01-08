<?php

namespace App\Repositories;

use Elasticsearch\ClientBuilder;
use App\Models\WebObject;
use Illuminate\Support\Facades\Config;

class WebRepository
{
    protected $ES;
    protected $S3;

    private $schemes = ['https', 'http'];

    public function __construct()
    {
        $clientBuilder = ClientBuilder::create()->setHosts(['localhost:9200']);
        $this->ES = $clientBuilder->build();

        $this->S3 = new \S3();

        $this->S3->setAuth(Config::get('constants.S3.key'), Config::get('constants.S3.secret'));
        $this->S3->setEndpoint(Config::get('constants.S3.endpoint'));
        $this->S3->setExceptions(true);
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
        $host = $data['host'] ?? '';
        $path = $data['path'] ?? '';
        $query = $data['query'] ?? '';

        if( !$host ) {
            return false;
        }

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

    public function get($url, $options = [])
    {
        $url = trim($url);
        if( !$url ) {
            return false;
        }

        $url = $this->parseUrl($url);
        if( !$url ) {
            return false;
        }

        $options = array_merge([
            'loadCurrentVersion' => false,
        ], $options);

        $must = [
            ['term' => ['dataset' => 'web_objects']],
            ['term' => ['data.web_objects.host' => $url['host']]],
            ['term' => ['data.web_objects.path' => $url['path']]],
            ['term' => ['data.web_objects.query' => $url['query']]]
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

        if(
            $res &&
            !empty($res['hits']) &&
            !empty($res['hits']['hits']) &&
            ( $hit = $res['hits']['hits'][0] )
        ) {

            // dd($hit);
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
                    $response = false;
                }

                if( $response && $response->body ) {
                    $current_version->setBody($response->body);
                }
            }

            return $web_object;

        }

        return false;
    }

}
