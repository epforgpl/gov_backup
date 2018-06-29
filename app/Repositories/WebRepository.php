<?php

namespace App\Repositories;

use App\Exceptions\ContentViewNotFound;
use App\Exceptions\ResourceNotIndexedException;
use App\Helpers\EpfHelpers;
use App\Helpers\Reply;
use App\Models\WebObjectRedirect;
use App\Models\WebObjectVersion;
use App\Storage\iStorage;
use App\Models\WebObject;
use Illuminate\Support\Facades\Cache;

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
     * @return WebObject
     * @throws ResourceNotIndexedException
     */
    public function get(string $url, \DateTime $requestedTimestamp): WebObject
    {
        $url = trim($url);
        if( !$url ) {
            throw new \InvalidArgumentException('URL is not set');
        }

        $urlp = Reply::createAbsoluteStandardizedUrl($url, $url, true);

        $filterRevisions = self::filterRevisionsRequestPart($urlp);
        $requestedTimestampString = json_encode($requestedTimestamp->format('c'));

        // search for the closest revision
        $request = <<<JSON
{
    // return revision with the closest date to requested
    "size": 1,
    "query": {
        "function_score": {
            "query": {
                "bool": {
                    $filterRevisions
                }
            },
            // sort revisions to get one closest to requested timestamp 
            "exp": {
                "data.web_objects_revisions.timestamp": {
                    "origin": $requestedTimestampString,
                    "scale": "1d"
                }
            },
            "boost_mode": "max"
        }
    },
    "_source": [
        "data.*"
    ]
}
JSON;

        $res = $this->ES->search([
            'index' => 'mojepanstwo_v1',
            'type' => 'objects',
            'body' => EpfHelpers::strip_json_comments($request)
        ]);

        if(!isset($res['hits']['hits'][0])) {
            throw new ResourceNotIndexedException("$url at " . $requestedTimestamp->format('c')
            . "\nQuery: " . $request);
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

        return $web_object;
    }

    private static function cloudKey(WebObjectVersion $version, $contentView) {
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

        return $uri;
    }

    public function loadVersionContent(WebObjectVersion $version, $contentView)
    {
        return $this->storage->getObject($this->bucket, self::cloudKey($version, $contentView));
    }

    /**
     * Return URL of publicly available resource endpoint or null
     *
     * @return mixed URL or null if this storage or bucket is not publicly available
     */
    public function getPublicUrl(WebObjectVersion $version, $contentView) {
        return $this->storage->getPublicUrl($this->bucket, self::cloudKey($version, $contentView));
    }

    private function warnInCaseOfMultipleHits($response) {
        // TODO handle multiple hits -> log to warnings
    }

    public function getArchivedDomains($cache = true) {
        $domains = Cache::get('archivedDomains');
        if ($domains == null) {
            $request = <<<JSON
{
  // don't return any hits, we get all the data from aggregations
  "size": 0,
  "query": {
    "bool": { "filter": { "term": { "dataset": "web_objects" } } }
  },
  "aggs": {
    "hosts": {
      "terms": { "field": "data.web_objects.host" }
    }
  }
}
JSON;

            $request = EpfHelpers::strip_json_comments($request);
            $response = $this->ES->search([
                'index' => 'mojepanstwo_v1',
                'type' => 'objects',
                'body' => $request
            ]);

            $domains = [];
            foreach ($response['aggregations']['hosts']['buckets'] as $b) {
                array_push($domains, $b['key']);
            }

            Cache::set('archivedDomains', $domains, \DateInterval::createFromDateString("4 hours"));
        }

        return $domains;
    }

    /**
     * Return all revisions (moments visited) for a given object/URL
     *
     * @param $url
     * @return UrlRevision[]
     */
    public function getUrlRevisions($url) {
        $url = trim($url);
        if( !$url ) {
            throw new \InvalidArgumentException('URL is not set');
        }

        $urlp = Reply::createAbsoluteStandardizedUrl($url, $url, true);
        $filterRevisions = self::filterRevisionsRequestPart($urlp);

        // that's quite a self-explanatory query
        $request = <<<JSON
{
    "query": {
        "bool": {
            $filterRevisions
        }
    },
    "_source": [
        "data.web_objects_revisions.*",
        "data.web_objects.url"
    ]
}
JSON;

        $res = $this->ES->search([
            'index' => 'mojepanstwo_v1',
            'type' => 'objects',
            'body' => EpfHelpers::strip_json_comments($request)
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
  // don't return any hits, we get all the data from aggregations
  "size": 0,
  "query": {
    "bool": {
      "filter": {
        "term": {
          // content is stored in versions, that what we search
          "dataset": "web_objects_versions"
        }
      },
      // if we search for deleted phases, then we want to get also the versions that don't match, so minimum_should_match = 0
      // anyhow we need should in this search because it prioritizes verisons/objects that have contained the version
      // otherwise they might not be returned in the aggregation buckets
      "minimum_should_match": $minimum_should_match,
      "should": [
        {
          "multi_match": {
            // cross_fields looks for each word in any field, https://www.elastic.co/guide/en/elasticsearch/reference/current/query-dsl-multi-match-query.html
            "type": "cross_fields",
            "query": $query,
            "fields": [
              // we search separately in title, url, and text to be able to highlight it separately
              // and possibly also boost results from one of the fields
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
      // we are dividing all versions into buckets, one objects/resource in each bucket 
      "terms": {
        "field": "data.web_objects_versions.url.keyword",
        "size": 10,
        "order": {
          // order is affected by `should` query above
          "top_hit": "desc"
        }
      },
      // comment
      "aggs": {
        "top_hits": {
          "top_hits": {
            "_source": {
              // get just the data we need
              "includes": [
                "data.web_objects_versions.id",
                "data.web_objects_versions.object_id",
                "data.web_objects_versions.title",
                "data.web_objects_versions.description",
                "data.web_objects_versions.image_url"
              ]
            },
            // we want to highlight results in this field separately because in the frontend they are separated
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
            // in each bucket(object/resource) best-matching versions will be enough
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
        // we get last matching version (the word was present)
        "matching": {
          "filter": { 
            "match": {
              "text": $query
            }
          },
          "aggs": { "last_seen": { "max": { "field": "data.web_objects_versions.last_seen_date" }}}
        },
        // we get the first non-matching version (the word disappeared)
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
        // show only deleted phrases 
        // we are filtering (bucket_selector) only those versions that had a non-matching version after matching
        "deleted phrases": {
            "bucket_selector": {
                "buckets_path": {
                  "first_not_matching_date": "not_matching.first_seen",
                  "last_matching_date": "matching.last_seen"
                },
                "script": "params.first_not_matching_date > params.last_matching_date"
            }
        }
JSON;
        } else {
            // get best matching version first and last seen dates (TODO it should be max & min really across all versions)
            $request .= <<<JSON
        // some informative data for the frontend
        // if the url was scraped multiple times: when we've seen this version the first time and when the last time
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

        $request = EpfHelpers::strip_json_comments($request);

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

    private static function filterRevisionsRequestPart($urlp) {
        $scheme = json_encode($urlp['scheme']);
        $host = json_encode($urlp['host']);
        $port = json_encode($urlp['port']);
        $path = json_encode($urlp['path']);
        $query = json_encode($urlp['query']);

        return <<<JSON
            "filter": [
                { "term": { "dataset": "web_objects_revisions" }},
                { "term": { "data.web_objects.scheme": $scheme }},
                { "term": { "data.web_objects.host": $host }},
                { "term": { "data.web_objects.port": $port }},
                { "term": { "data.web_objects.path": $path }},
                { "term": { "data.web_objects.query": $query }}
            ]
JSON;
    }
}
