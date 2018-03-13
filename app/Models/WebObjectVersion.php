<?php

namespace App\Models;

// TODO update it or throw it (why we need this class?)
class WebObjectVersion
{

    private $id, $body_hash, $filetime, $portal_revision_id, $media_type, $url, $body, $header, $body_processed, $keywords, $image_url, $description, $title;

    public function __construct($data)
    {
        $this->id = (int) $data['id'];
        $this->portal_revision_id = (int) $data['portal_revision_id'];
        // $this->filetime = (int) $data['filetime'];
        $this->body_hash = $data['body_hash'];
        $this->media_type = $data['media_type'];
        // $this->url = $data['url'];
        $this->title = $data['title'];
        $this->description = $data['description'];
        $this->keywords = $data['keywords'];
        $this->image_url = $data['image_url'];
        $this->body_processed = (boolean) $data['body_transformed'];
    }

    public function getId() {
        return $this->id;
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

    public function getTitle()
    {
        return $this->title;
    }

    public function getDescription()
    {
        return $this->description;
    }

    public function getKeywords()
    {
        return $this->keywords;
    }

    public function getThumbUrl()
    {
        return $this->image_url;
    }
}