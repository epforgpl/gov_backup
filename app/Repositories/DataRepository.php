<?php

namespace App\Repositories;

use App\Helpers\Reply;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class DataRepository
{
    private $conn;

    public function __construct()
    {
        $this->conn = DB::connection('data');
    }

    public function deletedPages() {
        $query = <<<SQL
SELECT  
  missing.cts AS missing_cts,
  missing.code AS missing_code,
  ok.cts AS ok_cts,
  ok.code AS ok_code,
  object.*

FROM
  (SELECT
     object_id,
     MAX(id) AS max_id
   FROM web_objects_revisions
   WHERE code >= 400
   GROUP BY object_id) AS last_missing
  INNER JOIN
  (SELECT
     object_id,
     MAX(id) AS max_id
   FROM web_objects_revisions
   WHERE code < 300
   GROUP BY object_id) AS last_ok ON last_missing.object_id = last_ok.object_id
INNER JOIN web_objects_revisions AS missing ON missing.id = last_missing.max_id
INNER JOIN web_objects_revisions AS ok ON ok.id = last_ok.max_id
INNER JOIN web_objects AS object ON object.id = last_missing.object_id
WHERE last_missing.max_id > last_ok.max_id
ORDER BY missing.cts DESC
LIMIT :limit;
SQL;

        $results = $this->conn->select(DB::raw($query), [
            'limit' => 50
        ]);

        // fill in full url
        $results = array_map(function($r) {
            $r = (array) $r;
            $r['url'] = Reply::unparse_url($r);
            $r['missing_cts'] = new Carbon($r['missing_cts']);
            $r['ok_cts'] = new Carbon($r['ok_cts']);

            return $r;
        }, $results);

        return $results;
    }
}