<?php

namespace App\Repositories;

use App\Exceptions\ContentViewNotFound;
use App\Exceptions\ResourceNotIndexedException;
use App\Helpers\Reply;
use App\Models\WebObjectRedirect;
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

    public function __construct(\Elasticsearch\Client $es, iStorage $storage)
    {
        $this->ES = $es;
        $this->storage = $storage;
        $this->bucket = config('services.storage.bucket');
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
     * @param $contentView Which type of content to load, defaults to null = don't load
     * @return WebObject
     * @throws ResourceNotIndexedException
     */
    public function get(string $url, \DateTime $requestedTimestamp, $contentView = null): WebObject
    {
        $url = trim($url);
        if( !$url ) {
            throw new \InvalidArgumentException('URL is not set');
        }

        // if there is no schema, guess it for now TODO fix it properly https://github.com/epforgpl/gov_backup/issues/79
        if (stripos($url, '//') === false) {
            $url = 'http://' . $url;
        }

        $urlp = Reply::createAbsoluteStandardizedUrl($url, $url, true);

        $object_conditions = [
            ['term' => ['dataset' => 'web_objects_revisions']],
            ['term' => ['data.web_objects.host' => $urlp['host']]],
            ['bool' => [
                'should' => [
                    ['term' => ['data.web_objects.path' => $urlp['path']]]
                ]
            ]],
            ['term' => ['data.web_objects.query' => $urlp['query']]]
        ];

        // search for the closest revision
        $search_request = [
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
                                "origin" => $requestedTimestamp->format('c'),
                                "scale" => "1d"
                            ],
                        ],
                        "boost_mode" => "max"
                    ]
                ],
                '_source' => ['data.*'],
            ]
        ];
        $res = $this->ES->search($search_request);

        if(!isset($res['hits']['hits'][0])) {
            throw new ResourceNotIndexedException("$url at " . $requestedTimestamp->format('c')
            . "\nQuery: " . json_encode($search_request['body']));
        }

        $hit = $res['hits']['hits'][0];
        $revision = $hit['_source']['data']['web_objects_revisions'];


        if ($revision['redirection_location']) {
            // this was a redirection
            $web_object = new WebObjectRedirect($hit['_source']['data']['web_objects']);
            $web_object->setTimestamp(WebRepository::parseESDate($revision['timestamp']));

            $web_object->setRedirectLocation($revision['redirection_location']);
            $web_object->setRedirectionArchived((bool) $revision['redirection_object_id']);

            return $web_object;
        }
        $web_object = new WebObject($hit['_source']['data']['web_objects']);
        $version = new WebObjectVersion($hit['_source']['data']['web_objects_versions'], $web_object->getId());
        $web_object->setVersion($version);
        $web_object->setTimestamp(WebRepository::parseESDate($revision['timestamp']));

        if ($contentView) {
            $this->loadVersionContent($version, $contentView);
        }

        return $web_object;
    }

    public function loadVersionContent(WebObjectVersion &$version, $contentView)
    {
        $uri = $version->getObjectId() . '/' . $version->getId() . '/body';

        if ($contentView === 'text') {
            if (!$version->hasBodyText()) {
                throw new ContentViewNotFound($contentView, $version->getId());
            } else {
                $uri .= '-text';
            }
        } else if ($contentView === 'basic') {
            // there is no prefix for it
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
        $url = trim($url);
        if( !$url ) {
            throw new \InvalidArgumentException('URL is not set');
        }

        $urlp = Reply::createAbsoluteStandardizedUrl($url, $url, true);

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
                    // version_id == null will be returned if this revision is a redirection
                    isset($data['version_id']) ? $data['version_id'] : null,
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
     * Search for given text
     *
     * @return \Illuminate\Http\Response
     */
    public function searchText(string $query, $search_deleted = false, $filters = []) {
        // escape $query;
        $query =  json_encode( $query );

        $minimum_should_match = $search_deleted ? 0 : 1;

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
      "minimum_should_match": $minimum_should_match,
      "should": [
        {
          "multi_match": {
            "type": "cross_fields",
            "query": $query,
            "fields": [
              "data.web_objects_versions.title",
              "data.web_objects_versions.url",
              "text"
            ]
          }
        }
      ]
    }
  },
  "aggs": {
    "top-urls": {
      "terms": {
        "field": "data.web_objects_versions.url.keyword",
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
                "data.web_objects_versions.url": {
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
JSON;
        if ($search_deleted) {
            $request .= <<<JSON
        "matching": {
          "filter": { 
            "match": {
              "text": $query
            }
          },
          "aggs": { "last_seen": { "max": { "field": "data.web_objects_versions.last_seen_date" }}}
        },
        "not_matching": {
          "filter" : { 
              "bool": {
                "must_not": {
                  "match": {
                    "text": $query
                  }
                }
              }
          },
          "aggs": { "first_seen": { "min": { "field": "data.web_objects_versions.first_seen_date" }}}
        },
        "deleted phrases: objects with last_not_matching after last matching": {
            "bucket_selector": {
                "buckets_path": {
                  "last_not_matching_date": "not_matching.first_seen",
                  "last_matching_date": "matching.last_seen"
                },
                "script": "params.last_not_matching_date > params.last_matching_date"
            }
        }
JSON;
        } else {
            // get best matching version first and last seen dates (TODO it should be max & min really)
            $request .= <<<JSON
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
JSON;
        }

        $request .= <<<JSON
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
            $result = [
                'url' => $b['key'],
                'versions_count' => $b['doc_count'],
                'score' => $b['top_hit']['value'],
                'data' => $b['top_hits']['hits']['hits'][0]['_source']['data'],
                'highlight' => $b['top_hits']['hits']['hits'][0]['highlight']
            ];

            if ($search_deleted) {
                $result = array_merge($result, [
                    'matching_last_seen' => new \DateTime('@'.($b['matching']['last_seen']['value']/1000)),
                    'not_matching_first_seen' => new \DateTime('@'.($b['not_matching']['first_seen']['value']/1000)),
                ]);
            } else {
                $result = array_merge($result, [
                    'first_seen' => new \DateTime('@'.($b['first_seen']['value']/1000)),
                    'last_seen' => new \DateTime('@'.($b['last_seen']['value']/1000)),
                ]);
            }

            $results[] = $result;
        }

        return $results;
    }
}
