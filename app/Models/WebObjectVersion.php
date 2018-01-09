<?php

namespace App\Models;

class WebObjectVersion
{

    private $id, $body_hash, $filetime, $portal_revision_id, $content_type, $url, $body, $header, $body_processed, $keywords, $og_image_object_id, $description, $title;

    public function __construct($data)
    {
        $this->id = (int) $data['id'];
        $this->portal_revision_id = (int) $data['portal_revision_id'];
        $this->filetime = (int) $data['filetime'];
        $this->body_hash = $data['body_hash'];
        $this->content_type = $data['content_type'];
        $this->url = $data['url'];
        $this->title = $data['title'];
        $this->description = $data['description'];
        $this->keywords = $data['keywords'];
        $this->og_image_object_id = $data['og_image_object_id'];
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

    public function getOgImageObjectId() {
        return $this->og_image_object_id;
    }

    public function getThumbUrl()
    {
        return '/thumb//' . $this->getOgImageObjectId();
    }
}