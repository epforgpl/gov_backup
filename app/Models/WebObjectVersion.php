<?php

namespace App\Models;

class WebObjectVersion
{

    private $id, $body_hash, $filetime, $portal_revision_id, $content_type, $url, $body, $header, $body_processed;

    public function __construct($data)
    {
        $this->id = (int) $data['id'];
        $this->portal_revision_id = (int) $data['portal_revision_id'];
        $this->filetime = (int) $data['filetime'];
        $this->body_hash = $data['body_hash'];
        $this->content_type = $data['content_type'];
        $this->url = $data['url'];
        $this->body_processed = (boolean) $data['body_processed'];
    }

    public function getBodyHash()
    {
        return $this->body_hash;
    }

    public function getBody()
    {
        return $this->body;
    }

    public function getHeader()
    {
        return $this->header;
    }

    public function setBody($content)
    {
        $this->body = $content;
    }

    public function isBodyProcessed()
    {
        return $this->body_processed;
    }

}