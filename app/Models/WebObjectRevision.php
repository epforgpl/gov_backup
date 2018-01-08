<?php

namespace App\Models;

class WebObjectRevision
{

    private $id, $http_code, $portal_revision_id, $body_changed, $cts;

    public function __construct($data)
    {
        $this->id = (int) $data['id'];
        $this->portal_revision_id = (int) $data['portal_revision_id'];
        $this->http_code = (int) $data['http_code'];
        $this->body_changed = (boolean) $data['body_changed'];
        $this->cts = $data['cts'];
    }

}