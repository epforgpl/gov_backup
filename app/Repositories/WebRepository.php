<?php

namespace App\Repositories;

use App\Exceptions\MalformedUrlException;
use App\Exceptions\ResourceNotIndexedException;
use App\Helpers\EpfHelpers;
use App\Models\WebObjectVersion;
use App\Storage\iStorage;
use App\Models\WebObject;

class WebRepository
{
    /**
     * @var \Elasticsearch\Client
     */
    protected $ES;

    /**
     * @var iStorage
     */
    protected $storage;

    protected $bucket;

    private $schemes = ['https', 'http'];

    public function __construct(\Elasticsearch\Client $es, iStorage $storage)
    {
        $this->ES = $es;
        $this->storage = $storage;
        $this->bucket = config('services.storage.bucket');
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

        // contract ./ and ../ to get canonical url
        if( isset($path) ) {
            $path_parts = explode('/', $path);
            EpfHelpers::contract_path_parts($path_parts);
            $path = implode('/', $path_parts);
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

    // TODO at which timestamp this should be loaded?
    public function getById($id, $loadContent = false)
    {
        $res = $this->ES->search([
            'index' => 'mojepanstwo_v1',
            'type' => 'objects',
            'body' => [
                'query' => [
                    'bool' => [
                        'must' => [
                            ['term' => ['dataset' => 'web_objects']],
                            ['term' => ['id' => $id]],
                        ],
                    ],
                ],
                '_source' => ['data.*'],
            ]
        ]);

        if(!isset($res['hits']['hits'][0])) {
            throw new ResourceNotIndexedException("ID = $id");
        }
        $this->warnInCaseOfMultipleHits($res);

        $web_object = $this->convertWebObjectResponse($res['hits']['hits'][0], $loadContent);

        return $web_object;
    }

    public static function formatTimestampToISO($timestamp) {
        return date('c', $timestamp);
    }

    public static function dateStringToISO($dateString) {
        return str_replace(' ', 'T', $dateString);
    }

    /**
     * Search for and return the webresource metadata and optionally content
     *
     * @param string $url World-facing url that user is interested in
     * @param \DateTime $requestedTimestamp Timestamp at which resource should be returned. Actual returned revision may differ
     * @param bool $loadContent Return content as well, defaults to false
     * @return WebObject
     * @throws ResourceNotIndexedException
     */
    public function get(string $url, \DateTime $requestedTimestamp, bool $loadContent = false): WebObject
    {
        $urlp = trim($url);
        if( !$urlp ) {
            throw new \InvalidArgumentException('URL is not set');
        }

        $urlp = $this->parseUrl($urlp);

        $object_conditions = [
            ['term' => ['dataset' => 'web_objects_revisions']],
            ['term' => ['data.web_objects.host' => $urlp['host']]],
            ['bool' => [
                'should' => [
                    ['term' => ['data.web_objects.path' => $urlp['path']]],
                    ['term' => ['data.web_objects.path' => $urlp['path'] . '/']]
                    // TODO try to save paths in a consistent manner to simplify this query
                ]
            ]],
            ['term' => ['data.web_objects.query' => $urlp['query']]]
        ];

        // search for the closest revision
        $res = $this->ES->search([
            'index' => 'mojepanstwo_v1',
            'type' => 'objects',
            'body' => [
                'size' => 1,
                'query' => [
                    'function_score' => [
                        'query' => [
                            'bool' => [
                                'filter' => $object_conditions,
                            ],
                        ],
                        'exp' => [ // return revision with the closest date to requested
                            "data.web_objects_revisions.timestamp" => [
                                "origin"=> $requestedTimestamp->format('c'),
                                "scale" => "1d"
                            ],
                        ],
                        "boost_mode"=>"max"
                    ]
                ],
                '_source' => ['data.*'],
            ]
        ]);

        if(!isset($res['hits']['hits'][0])) {
            throw new ResourceNotIndexedException("$url at " . $requestedTimestamp->format('c'));
        }

        $hit = $res['hits']['hits'][0];
        $revision = $hit['_source']['data']['web_objects_revisions'];
        if (!$revision['version_id']) {
            throw new \Exception("Redirects are not handled. Implement https://github.com/epforgpl/OpenScrapers/issues/106");
        }

        $web_object = new WebObject($hit['_source']['data']['web_objects']);
        $version = new WebObjectVersion($hit['_source']['data']['web_objects_versions'], $web_object->getId());
        $web_object->setVersion($version);
        $web_object->setTimestamp(WebRepository::parseESDate($revision['timestamp']));

        if ($loadContent) {
            $this->loadVersionContent($version);
        }

        return $web_object;
    }

    /**
     * Process ES response into a nice object
     *
     * @param array $hit Response from Elastic Search containing the object
     * @param bool $loadCurrentVersionContent Whether we should load the content of resulting objects
     *
     * @return WebObject Return WebObject
     */
    private function convertWebObjectResponse(array $hit, bool $loadCurrentVersionContent)
    {
        $web_object = new WebObject($hit['_source']['data']['web_objects']);

        // Loading WebObject we have access to the last revision as well
        $web_object->setLastSeen(WebRepository::parseESDate($hit['_source']['data']['web_objects_revisions']['timestamp']));

        if ($loadCurrentVersionContent) {
            $this->loadVersionContent($web_object->getVersion());
        }

        return $web_object;
    }

    public function loadVersionContent(WebObjectVersion &$version)
    {
        $uri = $version->getObjectId() . '/' . $version->getId() . '/body';
        if ($version->isBodyProcessed()) {
            $uri .= '-t';
        }

        $response = $this->storage->getObject($this->bucket, $uri);
        $version->setBody($response);
    }

    private function warnInCaseOfMultipleHits($response) {
        // TODO handle multiple hits -> log to warnings
    }

    /**
     * @param $url
     * @return UrlRevision[]
     */
    public function getUrlRevisions($url) {
        $urlp = trim($url);
        if( !$urlp ) {
            throw new \InvalidArgumentException('URL is not set');
        }

        $urlp = $this->parseUrl($urlp);

        $must = [
            ['term' => ['dataset' => 'web_objects_revisions']],
            ['term' => ['data.web_objects.host' => $urlp['host']]],
            ['bool' => [
                'should' => [
                    ['term' => ['data.web_objects.path' => $urlp['path']]],
                    ['term' => ['data.web_objects.path' => $urlp['path'] . '/']]
                ]
            ]],
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
                '_source' => ['data.web_objects_revisions.*', 'data.web_objects.url'],
            ]
        ]);

        $results = [];
        if($res && isset($res['hits']['hits'])) {
            foreach ($res['hits']['hits'] as $hit) {
                $data = $hit['_source']['data']['web_objects_revisions'];
                $dt = self::parseESDate($data['timestamp']);

                array_push($results, new UrlRevision(
                    $dt,
                    $data['object_id'],
                    isset($data['version_id']) ? $data['version_id'] : null, // TODO is version_id needed? // TODO get rid of null after reindexing
                    $hit['_source']['data']['web_objects']['url']
                ));
            }
        }

        // TODO for development in case if ES is not available
        if (env('MOCK_ES', false)) {
            // echo json_encode($results);
            $results = json_decode('[{"timestamp":{"date":"2018-03-22 12:57:31.000000","timezone_type":3,"timezone":"UTC"},"object_id":"1","version_id":null,"object_url":"trybunal.gov.pl"},{"timestamp":{"date":"2018-03-13 13:05:02.000000","timezone_type":3,"timezone":"UTC"},"object_id":"1","version_id":null,"object_url":"trybunal.gov.pl"},{"timestamp":{"date":"2018-03-23 20:40:25.000000","timezone_type":3,"timezone":"UTC"},"object_id":"1","version_id":"1768","object_url":"trybunal.gov.pl"},{"timestamp":{"date":"2018-03-23 20:08:44.000000","timezone_type":3,"timezone":"UTC"},"object_id":"1","version_id":"1768","object_url":"trybunal.gov.pl"},{"timestamp":{"date":"2018-03-23 18:42:10.000000","timezone_type":3,"timezone":"UTC"},"object_id":"1","version_id":null,"object_url":"trybunal.gov.pl"},{"timestamp":{"date":"2018-03-23 20:43:58.000000","timezone_type":3,"timezone":"UTC"},"object_id":"1","version_id":"1768","object_url":"trybunal.gov.pl"},{"timestamp":{"date":"2018-03-23 20:49:29.000000","timezone_type":3,"timezone":"UTC"},"object_id":"1","version_id":"1768","object_url":"trybunal.gov.pl"},{"timestamp":{"date":"2018-03-23 18:49:25.000000","timezone_type":3,"timezone":"UTC"},"object_id":"1","version_id":null,"object_url":"trybunal.gov.pl"},{"timestamp":{"date":"2018-03-13 20:21:26.000000","timezone_type":3,"timezone":"UTC"},"object_id":"1","version_id":null,"object_url":"trybunal.gov.pl"},{"timestamp":{"date":"2018-03-23 20:40:58.000000","timezone_type":3,"timezone":"UTC"},"object_id":"1","version_id":"1768","object_url":"trybunal.gov.pl"}]');
        }

        return $results;
    }

    public static function parseESDate($date_string): \DateTime {
        return \DateTime::createFromFormat("Y-m-d\TH:i:s", $date_string);
    }

    /**
     * Search for given URL
     *
     * @return \Illuminate\Http\Response
     */
    public function searchUrl($query, $filters = []) {
        $request = <<<JSON
GET mojepanstwo_v1/objects/1/_explain
{
  "query": {
    "bool": {
      "filter": {
          "term": {"dataset": "web_objects"}
      },
      "should": [
        {"wildcard" : { 
          "data.web_objects.url" : "*trybunal*" 
        }},
        {"match": { // this doesn't work because it's keyword (we have to add extra field to match it)
          "data.web_objects.url": "trybunal"
        }}
      ]
    }
  }
}
JSON;
//
//        $response = $this->ES->search([
//            'index' => 'mojepanstwo_v1',
//            'type' => 'objects',
//            'body' => $request
//        ]);

        // TODO for now it is mocked
        $results = <<<JSON
[
{
"url": "http://trybunal.gov.pl/postepowanie-i-orzeczenia/postanowienia/nbrowse/5/",
"data": {
    "web_objects_versions": {
        "image_url": null,
        "description": null,
        "id": "833",
        "title": "Trybunał Konstytucyjny: Postanowienia",
        "object_id": "961"
    }
},
"highlight": {
    "data.web_objects_versions.title": [ "<em>Trybunał</em> Konstytucyjny: Postanowienia" ],
    "data.web_objects.url": [ "http://<em>trybunal</em>.gov.pl/postepowanie-i-orzeczenia/postanowienia/nbrowse/5/" ]
}
}
]
JSON;

        return json_decode($results, true);
    }

    /**
     * Search for given text
     *
     * @return \Illuminate\Http\Response
     */
    public function searchText($query, $filters = []) {
        // TODO escape $query;

        $request = <<<JSON
{
  "size": 0,
  "query": {
    "bool": {
      "filter": {
        "term": {
          "dataset": "web_objects_versions"
        }
      },
      "must": {
        "match": {
          "text": "$query"
        }
      },
      "should": [
        {
          "multi_match": {
            "query": "$query",
            "fields": [
              "data.web_objects_versions.title",
              "data.web_objects_versions.description"
            ]
          }
        }
      ]
    }
  },
  "aggs": {
    "top-urls": {
      "terms": {
        "field": "data.web_objects.url",
        "size": 10,
        "order": {
          "top_hit": "desc"
        }
      },
      "aggs": {
        "top_hits": {
          "top_hits": {
            "_source": {
              "includes": [
                "data.web_objects_versions.id",
                "data.web_objects_versions.object_id",
                "data.web_objects_versions.title",
                "data.web_objects_versions.description",
                "data.web_objects_versions.image_url"
              ]
            },
            "highlight": {
              "fields": {
                "data.web_objects_versions.title": {
                    "number_of_fragments" : 1
                },
                "text": {
                    "number_of_fragments" : 5
                }
              }
            },
            "size": 1
          }
        },
        "top_hit": {
          "max": {
            "script": {
              "lang": "painless",
              "inline": "_score"
            }
          }
        },
        "first_seen": {
          "min": {
            "script": {
              "lang": "painless",
              "inline": "doc['data.web_objects_versions.first_seen_date']"
            }
          }
        },
        "last_seen": {
          "min": {
            "script": {
              "lang": "painless",
              "inline": "doc['data.web_objects_versions.last_seen_date']"
            }
          }
        }
      }
    }
  }
}
JSON;

        $response = $this->ES->search([
            'index' => 'mojepanstwo_v1',
            'type' => 'objects',
            'body' => $request
        ]);

        $results = [];
        foreach ($response['aggregations']['top-urls']['buckets'] as $b) {
            $results[] = [
                'url' => $b['key'],
                'versions_count' => $b['doc_count'],
                'first_seen' => new \DateTime('@'.($b['first_seen']['value']/1000)),
                'last_seen' => new \DateTime('@'.($b['last_seen']['value']/1000)),
                'score' => $b['top_hit']['value'],
                'data' => $b['top_hits']['hits']['hits'][0]['_source']['data'],
                'highlight' => $b['top_hits']['hits']['hits'][0]['highlight']
            ];
        }

        // TODO for development in case if ES is not available
        if (env('MOCK_ES', false)) {
            // echo json_encode($results);
            $results = json_decode('[{"url":"trybunal.gov.pl\/postepowanie-i-orzeczenia\/postanowienia\/nbrowse\/5\/","versions_count":1,"first_seen":{"date":"2018-03-01 23:48:36.000000","timezone_type":1,"timezone":"+00:00"},"last_seen":{"date":"2018-03-01 23:48:36.000000","timezone_type":1,"timezone":"+00:00"},"score":1.0453555583953857,"data":{"web_objects_versions":{"image_url":null,"description":null,"id":"833","title":"Trybuna\u0142 Konstytucyjny: Postanowienia","object_id":"961"}},"highlight":{"data.web_objects_versions.title":["Trybuna\u0142<\/em> Konstytucyjny: Postanowienia"],"text":["Trybuna\u0142<\/em> Konstytucyjny: Postanowienia\ntrybunal.gov.pl\/postepowanie-i-orzeczenia\/postanowienia\/nbrowse\/5\/","e\/5\/\n Trybuna\u0142<\/em> Konstytucyjny: Postanowienia Przejd\u017a do g\u0142\u00f3wnej nawigacji Przejd\u017a do tre\u015bci Przejd\u017a do","do nawigacji w stopce Trybuna\u0142<\/em> Konstytucyjny Adres: 00-918 Warszawa, al. Szucha 12 a prasainfo@trybunal","gov.pl Biuletyn Informacji Publicznej PL EN FR Trybuna\u0142<\/em> Konstytucyjny Transmisja # Strona g\u0142\u00f3wna \u00bb Post\u0119powanie","Orzeczenia \u00bb Postanowienia Orzeczenia TK O Trybunale<\/em> Sprawy w Trybunale<\/em> Post\u0119powanie i Orzeczenia Wokanda Wyroki"]}},{"url":"trybunal.gov.pl\/postepowanie-i-orzeczenia\/postanowienia\/nbrowse\/7\/","versions_count":1,"first_seen":{"date":"2018-03-01 23:48:49.000000","timezone_type":1,"timezone":"+00:00"},"last_seen":{"date":"2018-03-01 23:48:49.000000","timezone_type":1,"timezone":"+00:00"},"score":1.0453555583953857,"data":{"web_objects_versions":{"image_url":null,"description":null,"id":"835","title":"Trybuna\u0142 Konstytucyjny: Postanowienia","object_id":"963"}},"highlight":{"data.web_objects_versions.title":["Trybuna\u0142<\/em> Konstytucyjny: Postanowienia"],"text":["Trybuna\u0142<\/em> Konstytucyjny: Postanowienia\ntrybunal.gov.pl\/postepowanie-i-orzeczenia\/postanowienia\/nbrowse\/7\/","e\/7\/\n Trybuna\u0142<\/em> Konstytucyjny: Postanowienia Przejd\u017a do g\u0142\u00f3wnej nawigacji Przejd\u017a do tre\u015bci Przejd\u017a do","do nawigacji w stopce Trybuna\u0142<\/em> Konstytucyjny Adres: 00-918 Warszawa, al. Szucha 12 a prasainfo@trybunal","gov.pl Biuletyn Informacji Publicznej PL EN FR Trybuna\u0142<\/em> Konstytucyjny Transmisja # Strona g\u0142\u00f3wna \u00bb Post\u0119powanie","Orzeczenia \u00bb Postanowienia Orzeczenia TK O Trybunale<\/em> Sprawy w Trybunale<\/em> Post\u0119powanie i Orzeczenia Wokanda Wyroki"]}},{"url":"trybunal.gov.pl\/postepowanie-i-orzeczenia\/podstawowe-informacje\/instrukcja-korzystania-ze-strony\/","versions_count":1,"first_seen":{"date":"2018-03-01 23:21:02.000000","timezone_type":1,"timezone":"+00:00"},"last_seen":{"date":"2018-03-01 23:21:02.000000","timezone_type":1,"timezone":"+00:00"},"score":0.998654842376709,"data":{"web_objects_versions":{"image_url":null,"description":null,"id":"562","title":"Trybuna\u0142 Konstytucyjny: Instrukcja korzystania ze strony","object_id":"660"}},"highlight":{"data.web_objects_versions.title":["Trybuna\u0142<\/em> Konstytucyjny: Instrukcja korzystania ze strony"],"text":["Trybuna\u0142<\/em> Konstytucyjny: Instrukcja korzystania ze strony\ntrybunal.gov.pl\/postepowanie-i-orzeczenia\/p","informacje\/instrukcja-korzystania-ze-strony\/\n Trybuna\u0142<\/em> Konstytucyjny: Instrukcja korzystania ze strony","Przejd\u017a do tre\u015bci Przejd\u017a do nawigacji w stopce Trybuna\u0142<\/em> Konstytucyjny Adres: 00-918 Warszawa, al. Szucha","gov.pl Biuletyn Informacji Publicznej PL EN FR Trybuna\u0142<\/em> Konstytucyjny Transmisja # Strona g\u0142\u00f3wna \u00bb Post\u0119powanie","korzystania ze strony Orzeczenia TK O Trybunale<\/em> Sprawy w Trybunale<\/em> Post\u0119powanie i Orzeczenia Wokanda Wyroki"]}},{"url":"trybunal.gov.pl\/sprawy-w-trybunale\/katalog\/s\/p-9415\/","versions_count":1,"first_seen":{"date":"2018-03-02 01:52:37.000000","timezone_type":1,"timezone":"+00:00"},"last_seen":{"date":"2018-03-02 01:52:37.000000","timezone_type":1,"timezone":"+00:00"},"score":0.9808427095413208,"data":{"web_objects_versions":{"image_url":null,"description":null,"id":"1260","title":"Trybuna\u0142 Konstytucyjny: Waloryzacja wynagrodze\u0144","object_id":"1427"}},"highlight":{"data.web_objects_versions.title":["Trybuna\u0142<\/em> Konstytucyjny: Waloryzacja wynagrodze\u0144"],"text":["Trybuna\u0142<\/em> Konstytucyjny: Waloryzacja wynagrodze\u0144\ntrybunal.gov.pl\/sprawy-w-trybunale<\/em>\/katalog\/s\/p-9415\/","\n Trybuna\u0142<\/em> Konstytucyjny: Waloryzacja wynagrodze\u0144 Przejd\u017a do g\u0142\u00f3wnej nawigacji Przejd\u017a do tre\u015bci Przejd\u017a","Przejd\u017a do nawigacji w stopce Trybuna\u0142<\/em> Konstytucyjny Adres: 00-918 Warszawa, al. Szucha 12 a prasainfo@trybunal","Publicznej PL EN FR Trybuna\u0142<\/em> Konstytucyjny Transmisja # Strona g\u0142\u00f3wna \u00bb Sprawy w Trybunale<\/em> \u00bb Katalog Orzeczenia","Orzeczenia TK O Trybunale<\/em> Sprawy w Trybunale<\/em> Om\u00f3wienia wybranych orzecze\u0144 od 2000 r. Statystyka Katalog 2018"]}},{"url":"trybunal.gov.pl","versions_count":15,"first_seen":{"date":"2018-03-12 18:22:48.000000","timezone_type":1,"timezone":"+00:00"},"last_seen":{"date":"2018-03-12 18:22:48.000000","timezone_type":1,"timezone":"+00:00"},"score":0.9644948244094849,"data":{"web_objects_versions":{"image_url":null,"description":null,"id":"1755","title":"Trybuna\u0142 Konstytucyjny: Trybuna\u0142 Konstytucyjny","object_id":"1"}},"highlight":{"data.web_objects_versions.title":["Trybuna\u0142<\/em> Konstytucyjny: Trybuna\u0142<\/em> Konstytucyjny"],"text":["Trybuna\u0142<\/em> Konstytucyjny: Trybuna\u0142<\/em> Konstytucyjny\ntrybunal.gov.pl\n Trybuna\u0142<\/em> Konstytucyjny: Trybuna\u0142<\/em> Konstytucyjny","Przejd\u017a do tre\u015bci Przejd\u017a do nawigacji w stopce Trybuna\u0142<\/em> Konstytucyjny Adres: 00-918 Warszawa, al. Szucha","FR Trybuna\u0142<\/em> Konstytucyjny Transmisja # Strona g\u0142\u00f3wna Orzeczenia TK O Trybunale<\/em> Sprawy w Trybunale<\/em> Post\u0119powanie","Narodowy Dzie\u0144 Pami\u0119ci \u201e\u017bo\u0142nierzy Wykl\u0119tych\u201d Prezes Trybuna\u0142u<\/em> Konstytucyjnego Julia Przy\u0142\u0119bska zosta\u0142a uhonorowana","Galeria zdj\u0119\u0107 siedziby Trybuna\u0142u<\/em> Konstytucyjnego Zeszyty OTK Komunikaty O Trybunale<\/em> e-Publikacje Wiadomo\u015bci"]}},{"url":"trybunal.gov.pl\/en\/news\/press-releases\/after-the-hearing\/art\/9904-ustawa-o-sadzie-najwyzszym-w-zakresie-dot-regulaminu-w-sprawie-wyboru-kandydatow-na-pierwszego-p\/s\/k-317\/","versions_count":1,"first_seen":{"date":"2018-03-01 23:39:58.000000","timezone_type":1,"timezone":"+00:00"},"last_seen":{"date":"2018-03-01 23:39:58.000000","timezone_type":1,"timezone":"+00:00"},"score":0.9617329835891724,"data":{"web_objects_versions":{"image_url":null,"description":null,"id":"664","title":"Trybuna\u0142 Konstytucyjny: after the hearing","object_id":"784"}},"highlight":{"data.web_objects_versions.title":["Trybuna\u0142<\/em> Konstytucyjny: after the hearing"],"text":["Trybuna\u0142<\/em> Konstytucyjny: after the hearing\ntrybunal.gov.pl\/en\/news\/press-releases\/after-the-hearing\/a","e-wyboru-kandydatow-na-pierwszego-p\/s\/k-317\/\n Trybuna\u0142<\/em> Konstytucyjny: after the hearing Jump to Main","Navigation Jump to Content Jump to Footer Navigations Trybuna\u0142<\/em> Konstytucyjny Adres: 00-918 Warszawa, al. Szucha","gov.pl Biuletyn Informacji Publicznej PL EN FR Trybuna\u0142<\/em> Konstytucyjny # Home \u00bb Hearings \u00bb Press releases","Tribunal Library Case list Links Address \u00a9 Biuro Trybuna\u0142u<\/em> Konstytucyjnego 2018 Report the problem RSS Address"]}},{"url":"trybunal.gov.pl\/sprawy-w-trybunale\/katalog\/s\/sk-3315\/","versions_count":1,"first_seen":{"date":"2018-03-02 02:38:42.000000","timezone_type":1,"timezone":"+00:00"},"last_seen":{"date":"2018-03-02 02:38:42.000000","timezone_type":1,"timezone":"+00:00"},"score":0.9602752923965454,"data":{"web_objects_versions":{"image_url":null,"description":null,"id":"1369","title":"Trybuna\u0142 Konstytucyjny: Wolno\u015b\u0107 dzia\u0142alno\u015bci gospodarczej","object_id":"1536"}},"highlight":{"data.web_objects_versions.title":["Trybuna\u0142<\/em> Konstytucyjny: Wolno\u015b\u0107 dzia\u0142alno\u015bci gospodarczej"],"text":["Trybuna\u0142<\/em> Konstytucyjny: Wolno\u015b\u0107 dzia\u0142alno\u015bci gospodarczej\ntrybunal.gov.pl\/sprawy-w-trybunale<\/em>\/katalog\/s\/sk-3315\/","\/s\/sk-3315\/\n Trybuna\u0142<\/em> Konstytucyjny: Wolno\u015b\u0107 dzia\u0142alno\u015bci gospodarczej Przejd\u017a do g\u0142\u00f3wnej nawigacji Przejd\u017a","Przejd\u017a do tre\u015bci Przejd\u017a do nawigacji w stopce Trybuna\u0142<\/em> Konstytucyjny Adres: 00-918 Warszawa, al. Szucha","Publicznej PL EN FR Trybuna\u0142<\/em> Konstytucyjny Transmisja # Strona g\u0142\u00f3wna \u00bb Sprawy w Trybunale<\/em> \u00bb Katalog Orzeczenia","Orzeczenia TK O Trybunale<\/em> Sprawy w Trybunale<\/em> Om\u00f3wienia wybranych orzecze\u0144 od 2000 r. Statystyka Katalog 2018"]}},{"url":"trybunal.gov.pl\/sprawy-w-trybunale\/katalog\/s\/sk-617\/","versions_count":1,"first_seen":{"date":"2018-03-02 00:26:21.000000","timezone_type":1,"timezone":"+00:00"},"last_seen":{"date":"2018-03-02 00:26:21.000000","timezone_type":1,"timezone":"+00:00"},"score":0.9602752923965454,"data":{"web_objects_versions":{"image_url":null,"description":null,"id":"1043","title":"Trybuna\u0142 Konstytucyjny: Ustawa o Policji","object_id":"1210"}},"highlight":{"data.web_objects_versions.title":["Trybuna\u0142<\/em> Konstytucyjny: Ustawa o Policji"],"text":["Trybuna\u0142<\/em> Konstytucyjny: Ustawa o Policji\ntrybunal.gov.pl\/sprawy-w-trybunale<\/em>\/katalog\/s\/sk-617\/\n Trybuna\u0142","Przejd\u017a do tre\u015bci Przejd\u017a do nawigacji w stopce Trybuna\u0142<\/em> Konstytucyjny Adres: 00-918 Warszawa, al. Szucha","Publicznej PL EN FR Trybuna\u0142<\/em> Konstytucyjny Transmisja # Strona g\u0142\u00f3wna \u00bb Sprawy w Trybunale<\/em> \u00bb Katalog Orzeczenia","Orzeczenia TK O Trybunale<\/em> Sprawy w Trybunale<\/em> Om\u00f3wienia wybranych orzecze\u0144 od 2000 r. Statystyka Katalog 2018","Publikacje Podstawowe informacje Kontakt Prezes Trybuna\u0142u<\/em> Konstytucyjnego Julia Przy\u0142\u0119bska zosta\u0142a uhonorowana"]}},{"url":"trybunal.gov.pl\/sprawy-w-trybunale\/katalog\/s\/k-3114\/","versions_count":1,"first_seen":{"date":"2018-03-02 03:04:11.000000","timezone_type":1,"timezone":"+00:00"},"last_seen":{"date":"2018-03-02 03:04:11.000000","timezone_type":1,"timezone":"+00:00"},"score":0.9448517560958862,"data":{"web_objects_versions":{"image_url":null,"description":null,"id":"1427","title":"Trybuna\u0142 Konstytucyjny: Bieg terminu przedawnienia zobowi\u0105zania podatkowego","object_id":"1594"}},"highlight":{"data.web_objects_versions.title":["Trybuna\u0142<\/em> Konstytucyjny: Bieg terminu przedawnienia zobowi\u0105zania podatkowego"],"text":["Trybuna\u0142<\/em> Konstytucyjny: Bieg terminu przedawnienia zobowi\u0105zania podatkowego\ntrybunal.gov.pl\/sprawy-w","pl\/sprawy-w-trybunale<\/em>\/katalog\/s\/k-3114\/\n Trybuna\u0142<\/em> Konstytucyjny: Bieg terminu przedawnienia zobowi\u0105zania podatkowego","Przejd\u017a do tre\u015bci Przejd\u017a do nawigacji w stopce Trybuna\u0142<\/em> Konstytucyjny Adres: 00-918 Warszawa, al. Szucha","Publicznej PL EN FR Trybuna\u0142<\/em> Konstytucyjny Transmisja # Strona g\u0142\u00f3wna \u00bb Sprawy w Trybunale<\/em> \u00bb Katalog Orzeczenia","Orzeczenia TK O Trybunale<\/em> Sprawy w Trybunale<\/em> Om\u00f3wienia wybranych orzecze\u0144 od 2000 r. Statystyka Katalog 2018"]}},{"url":"trybunal.gov.pl\/sprawy-w-trybunale\/katalog\/s\/k-4116\/","versions_count":1,"first_seen":{"date":"2018-03-02 00:34:45.000000","timezone_type":1,"timezone":"+00:00"},"last_seen":{"date":"2018-03-02 00:34:45.000000","timezone_type":1,"timezone":"+00:00"},"score":0.9448517560958862,"data":{"web_objects_versions":{"image_url":null,"description":null,"id":"1066","title":"Trybuna\u0142 Konstytucyjny: Ustawa o Trybunale Konstytucyjnym","object_id":"1233"}},"highlight":{"data.web_objects_versions.title":["Trybuna\u0142<\/em> Konstytucyjny: Ustawa o Trybunale Konstytucyjnym"],"text":["Trybuna\u0142<\/em> Konstytucyjny: Ustawa o Trybunale<\/em> Konstytucyjnym\ntrybunal.gov.pl\/sprawy-w-trybunale<\/em>\/katalog\/s\/k-4116\/","\/s\/k-4116\/\n Trybuna\u0142<\/em> Konstytucyjny: Ustawa o Trybunale<\/em> Konstytucyjnym Przejd\u017a do g\u0142\u00f3wnej nawigacji Przejd\u017a","Przejd\u017a do tre\u015bci Przejd\u017a do nawigacji w stopce Trybuna\u0142<\/em> Konstytucyjny Adres: 00-918 Warszawa, al. Szucha","Publicznej PL EN FR Trybuna\u0142<\/em> Konstytucyjny Transmisja # Strona g\u0142\u00f3wna \u00bb Sprawy w Trybunale<\/em> \u00bb Katalog Orzeczenia","Orzeczenia TK O Trybunale<\/em> Sprawy w Trybunale<\/em> Om\u00f3wienia wybranych orzecze\u0144 od 2000 r. Statystyka Katalog 2018"]}}]');
        }

        return $results;
    }
}
