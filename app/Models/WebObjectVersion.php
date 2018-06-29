<?php

namespace App\Models;

class WebObjectVersion
{

    private $id, $body_hash, $portal_revision_id, $media_type, $body, $header, $body_processed, $has_body_text, $keywords,
        $image_object_id, $description, $title, $http_code, $locale, $content_length_bytes, $size_bytes, $timestamp, $object_id;

    /**
     * Map version of the WebObject
     *
     * @param array $data Data taken from ES
     * @param $object_id WebObject's id to which this version belongs
     */
    public function __construct(array $data, $object_id)
    {
        $this->title = $data['title'];
        $this->id = (int) $data['id'];
        $this->body_processed = (boolean) $data['body_transformed'];
        $this->has_body_text = isset($data['has_body_text']) ? $data['has_body_text'] : false;
        $this->object_id = $object_id;
        $this->media_type = $data['media_type'];
        $this->image_object_id = $data['image_object_id'];

        // other available fields

//        $this->portal_revision_id = (int) $data['portal_revision_id'];
//        $this->body_hash = $data['body_hash'];
//        $this->description = $data['description'];
//
//        $this->http_code = $data['http_code'];
//        $this->media_type = $data['media_type'];
//        $this->keywords = $data['keywords'];
//        $this->locale = $data['locale'];
//        $this->content_length_bytes = $data['content_length_bytes']; // with possible encoding
//        $this->size_bytes = $data['size_bytes']; // without encoding

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

    public function hasBodyText($value = null) {
        if ($value !== null) {
            $this->has_body_text = $value;
        }
        return $this->has_body_text;
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

    public function getImageObjectId()
    {
        return $this->image_object_id;
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