<?php

namespace App\Models;

class WebObjectVersion
{

    private $id, $body_hash, $portal_revision_id, $media_type, $body, $header, $body_processed, $keywords,
        $image_url, $description, $title, $http_code, $locale, $content_length_bytes, $size_bytes, $timestamp, $object_id;

    /**
     * Map version of the WebObject
     *
     * @param array $data Data taken from ES
     */
    public function __construct(array $data)
    {
        $this->id = (int) $data['id'];
        $this->object_id = (int) $data['object_id'];
        $this->portal_revision_id = (int) $data['portal_revision_id'];
        $this->body_hash = $data['body_hash'];
        $this->body_processed = (boolean) $data['body_transformed'];

        $this->http_code = $data['http_code'];
        $this->media_type = $data['media_type'];
        $this->title = $data['title'];
        $this->description = $data['description'];
        $this->keywords = $data['keywords'];
        $this->image_url = $data['image_url'];
        $this->locale = $data['locale'];
        $this->content_length_bytes = $data['content_length_bytes']; // with possible encoding
        $this->size_bytes = $data['size_bytes']; // without encoding

        // other available fields
//            'redirection_object_id',
//            'type',
//            'image_object_id',
//            'site_name',
//            'has_body_text'
    }

    public function getId() {
        return $this->id;
    }

    /**
     * @return int
     */
    public function getObjectId(): int
    {
        return $this->object_id;
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

    /**
     * @return int
     */
    public function getPortalRevisionId(): int
    {
        return $this->portal_revision_id;
    }

    /**
     * @return mixed
     */
    public function getMediaType()
    {
        return $this->media_type;
    }

    /**
     * @return mixed
     */
    public function getImageUrl()
    {
        return $this->image_url;
    }

    /**
     * @return mixed
     */
    public function getHttpCode()
    {
        return $this->http_code;
    }

    /**
     * @return mixed
     */
    public function getLocale()
    {
        return $this->locale;
    }

    /**
     * @return mixed
     */
    public function getContentLengthBytes()
    {
        return $this->content_length_bytes;
    }

    /**
     * @return mixed
     */
    public function getSizeBytes()
    {
        return $this->size_bytes;
    }

    /**
     * @return \DateTime
     */
    public function getTimestamp(): \DateTime
    {
        return $this->timestamp;
    }

    /**
     * @param \DateTime $timestamp
     */
    public function setTimestamp(\DateTime $timestamp)
    {
        $this->timestamp = $timestamp;
    }
}