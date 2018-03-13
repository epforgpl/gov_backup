<?php

namespace App\Repositories;

use App\Exceptions\MalformedUrlException;
use App\Exceptions\ResourceNotIndexedException;
use App\Helpers\EpfHelpers;
use Elasticsearch\ClientBuilder;
use S3;
use App\Models\WebObject;

class WebRepository
{
    /**
     * @var \Elasticsearch\Client
     */
    protected $ES;
    /**
     * @var S3
     */
    protected $S3;

    private $schemes = ['https', 'http'];

    public function __construct(S3 $S3)
    {
        $clientBuilder = ClientBuilder::create()->setHosts(['localhost:9200']); // TODO move it o S3ServiceProvider
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

            if ($options['loadCurrentVersion']
                && ($current_version = $web_object->getCurrentVersion()))
            {
                $key = 'web/objects/' . $web_object->getId() . '/' . $current_version->getId() . '/body';
                if ($current_version->isBodyProcessed()) {
                    $key .= '-t';
                }
                $response = $this->S3->getObject('resources', $key);

                if( $response && $response->body ) {
                    $current_version->setBody($response->body);
                } // TODO what if not?
            }

            return $web_object;

        }

        return null;
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
                '_source' => ['data.*'],
            ]
        ]);

        $results = [new UrlRevision('timestamp', '1', '758', '/20181010121314')];
        if($res && isset($res['hits']['hits'])) {
            foreach ($res['hits']['hits'] as $hit) {
                // TODO
            }
        }

        return $results;
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
                'first_seen_ms' => $b['first_seen']['value'],
                'last_seen_ms' => $b['last_seen']['value'],
                'data' => $b['top_hits']['hits']['hits'][0]['_source']['data'],
                'highlight' => $b['top_hits']['hits']['hits'][0]['highlight']
            ];
        }

        // TODO for development in case if ES is not available
        $mocked_results = json_decode('[{"doc_count":"2", "url":"trybunal.gov.pl\/o-trybunale\/trybunal-konstytucyjny-w-polsce\/historia-siedziby-trybunalu-konstytucyjnego\/","first_seen_ms":1519946346000,"last_seen_ms":1519946346000,"data":{"web_objects_versions":{"image_url":null,"description":null,"id":"535","title":"Trybuna\u0142 Konstytucyjny: Historia siedziby Trybuna\u0142u Konstytucyjnego","object_id":"630"}},"highlight":{"text":["Trybuna\u0142 Konstytucyjny Adres: 00-918 Warszawa, al. <em>Szucha<\/em> 12 a prasainfo@trybunal.gov.pl tel: +22 657-45-15","znajduje si\u0119 w Warszawie w alei Jana Chrystiana <em>Szucha<\/em> 12a. Miejsce to zosta\u0142o ukszta\u0142towane przestrzennie","tego uk\u0142adu przestrzennego jest m.in. obecna aleja <em>Szucha<\/em>. Promienisty uk\u0142ad osi stanis\u0142awowskiej mia\u0142 si\u0119","dzia\u0142ce o numerze hipotecznym 1720 (mi\u0119dzy alej\u0105 <em>Szucha<\/em> i ul. Bagatela) nale\u017c\u0105cej do Skarbu Imperium Rosyjskiego","pomi\u0119dzy Alejami Ujazdowskimi a alej\u0105 <em>Szucha<\/em> (teraz al. Jana Chrystiana <em>Szucha<\/em> 12a). Kasyno wchodzi\u0142o w sk\u0142ad"]}},{"url":"trybunal.gov.pl\/en\/about-the-tribunal\/constitutional-tribunal\/history-of-the-building\/","first_seen_ms":1519947702000,"last_seen_ms":1519947702000,"data":{"web_objects_versions":{"image_url":null,"description":null,"id":"693","title":"Trybuna\u0142 Konstytucyjny: History of the building","object_id":"819"}},"highlight":{"text":["Trybuna\u0142 Konstytucyjny Adres: 00-918 Warszawa, al. <em>Szucha<\/em> 12 a prasainfo@trybunal.gov.pl tel: +22 657-45-15","12A <em>Szucha<\/em> Avenue The building of the Constitutional Tribunal is located in Warsaw, at 12Aa <em>Szucha<\/em> Avenue","district with \u017boliborz and Saska axis. Today only <em>Szucha<\/em> Avenue is a reminder of the past. The King Stanislaus","registered under the land plot number 1720 (between <em>Szucha<\/em> Avenue and Bagatela Street). Today, these buildings","barracks, between Ujazdowskie and <em>Szucha<\/em> avenues. Its address was 12A <em>Szucha<\/em> Avenue. Today it is the official"]}},{"url":"trybunal.gov.pl\/o-trybunale\/trybunal-konstytucyjny-w-polsce\/galeria-zdjec-siedziby-trybunalu-konstytucyjnego\/art\/3308-widok-ogolny-siedziby-trybunalu-konstytucyjnego\/","first_seen_ms":1519946354000,"last_seen_ms":1519946354000,"data":{"web_objects_versions":{"image_url":null,"description":null,"id":"536","title":"Trybuna\u0142 Konstytucyjny: Widok og\u00f3lny siedziby Trybuna\u0142u Konstytucyjnego","object_id":"631"}},"highlight":{"text":["Trybuna\u0142 Konstytucyjny Adres: 00-918 Warszawa, al. <em>Szucha<\/em> 12 a prasainfo@trybunal.gov.pl tel: +22 657-45-15","Konstytucyjnego Siedziba Trybuna\u0142u Konstytucyjnego\u00a0Al. J. Ch. <em>Szucha<\/em> 12 a Budynek z pocz\u0105tku XX wieku. Pocz\u0105tkowo pe\u0142ni\u0142","wewn\u0119trzny fot. Adam Jankiewicz Panorama od Al. <em>Szucha<\/em> fot. Adam Jankiewicz Trybuna\u0142 Konstytucyjny fot"]}},{"url":"trybunal.gov.pl\/postepowanie-i-orzeczenia\/podstawowe-informacje\/instrukcja-korzystania-ze-strony\/","first_seen_ms":1519946462000,"last_seen_ms":1519946462000,"data":{"web_objects_versions":{"image_url":null,"description":null,"id":"562","title":"Trybuna\u0142 Konstytucyjny: Instrukcja korzystania ze strony","object_id":"660"}},"highlight":{"text":["Trybuna\u0142 Konstytucyjny Adres: 00-918 Warszawa, al. <em>Szucha<\/em> 12 a prasainfo@trybunal.gov.pl tel: +22 657-45-15"]}},{"url":"trybunal.gov.pl\/postepowanie-i-orzeczenia\/postanowienia\/nbrowse\/5\/","first_seen_ms":1519948116000,"last_seen_ms":1519948116000,"data":{"web_objects_versions":{"image_url":null,"description":null,"id":"833","title":"Trybuna\u0142 Konstytucyjny: Postanowienia","object_id":"961"}},"highlight":{"text":["Trybuna\u0142 Konstytucyjny Adres: 00-918 Warszawa, al. <em>Szucha<\/em> 12 a prasainfo@trybunal.gov.pl tel: +22 657-45-15"]}},{"url":"trybunal.gov.pl\/postepowanie-i-orzeczenia\/postanowienia\/nbrowse\/7\/","first_seen_ms":1519948129000,"last_seen_ms":1519948129000,"data":{"web_objects_versions":{"image_url":null,"description":null,"id":"835","title":"Trybuna\u0142 Konstytucyjny: Postanowienia","object_id":"963"}},"highlight":{"text":["Trybuna\u0142 Konstytucyjny Adres: 00-918 Warszawa, al. <em>Szucha<\/em> 12 a prasainfo@trybunal.gov.pl tel: +22 657-45-15"]}},{"url":"trybunal.gov.pl\/postepowanie-i-orzeczenia\/postanowienia\/art\/9924-nieuwzglednienie-przez-ministra-sprawiedliwosci-wniosku-sedziego-o-przeniesienie-na-inne-miejsce\/","first_seen_ms":1519948071000,"last_seen_ms":1519948071000,"data":{"web_objects_versions":{"image_url":null,"description":null,"id":"826","title":"Trybuna\u0142 Konstytucyjny: Nieuwzgl\u0119dnienie przez Ministra Sprawiedliwo\u015bci wniosku s\u0119dziego o przeniesienie na inne miejsce s\u0142u\u017cbowe","object_id":"954"}},"highlight":{"text":["Trybuna\u0142 Konstytucyjny Adres: 00-918 Warszawa, al. <em>Szucha<\/em> 12 a prasainfo@trybunal.gov.pl tel: +22 657-45-15"]}},{"url":"trybunal.gov.pl\/wyszukiwarka\/","first_seen_ms":1519946559000,"last_seen_ms":1519946559000,"data":{"web_objects_versions":{"image_url":null,"description":null,"id":"580","title":"Trybuna\u0142 Konstytucyjny: Wyszukiwarka","object_id":"682"}},"highlight":{"text":["Trybuna\u0142 Konstytucyjny Adres: 00-918 Warszawa, al. <em>Szucha<\/em> 12 a prasainfo@trybunal.gov.pl tel: +22 657-45-15"]}},{"url":"trybunal.gov.pl\/en\/case-list\/judicial-decisions\/nbrowse\/5\/","first_seen_ms":1519947375000,"last_seen_ms":1519947375000,"data":{"web_objects_versions":{"image_url":null,"description":null,"id":"599","title":"Trybuna\u0142 Konstytucyjny: Judicial decisions","object_id":"710"}},"highlight":{"text":["Trybuna\u0142 Konstytucyjny Adres: 00-918 Warszawa, al. <em>Szucha<\/em> 12 a prasainfo@trybunal.gov.pl tel: +22 657-45-15"]}},{"url":"trybunal.gov.pl\/en\/hearings\/judgments\/nbrowse\/3\/","first_seen_ms":1519947429000,"last_seen_ms":1519947429000,"data":{"web_objects_versions":{"image_url":null,"description":null,"id":"611","title":"Trybuna\u0142 Konstytucyjny: Judgments","object_id":"723"}},"highlight":{"text":["Trybuna\u0142 Konstytucyjny Adres: 00-918 Warszawa, al. <em>Szucha<\/em> 12 a prasainfo@trybunal.gov.pl tel: +22 657-45-15"]}}]');

        return $results;
    }
}
