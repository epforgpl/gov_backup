<?php

namespace App\Models;

use App\Models\WebObjectRevision;
use App\Models\WebObjectVersion;
use App\Repositories\WebRepository;

class WebObject
{

    private $id, $portal_id, $scheme, $host, $path, $query, $last_seen, $version;
    private $timestamp;

    public function __construct($web_object)
    {
        $this->id = (int) $web_object['id'];
        $this->portal_id = (int) $web_object['portal_id'];
        $this->scheme = $web_object['scheme'];
        $this->host = $web_object['host'];
        $this->path = $web_object['path'];
        if ($this->path == '') {
            $this->path = '/';
        }
        $this->query = $web_object['query'];
    }

    public function getLastSeen()
    {
        return $this->last_seen;
    }

    public function getVersion(): WebObjectVersion
    {
        return $this->version;
    }

    public function getWebUrl(): string
    {
        $url = $this->scheme . '://' . $this->host . $this->path;
        if( $this->query ) {
            $url .= '?' . $this->query;
        }
        return $url;
    }

    public function getId()
    {
        return $this->id;
    }

    /**
     * @param mixed $last_seen
     */
    public function setLastSeen($last_seen)
    {
        $this->last_seen = $last_seen;
    }

    /**
     * @param mixed $version
     */
    public function setVersion($version)
    {
        $this->version = $version;
    }

    public function setTimestamp(\DateTime $timestamp)
    {
        $this->timestamp = $timestamp;
    }

    /**
     * Returns timestamp at which we will return public content URL
     *
     * @return \DateTime
     */
    public function getTimestamp(): \DateTime
    {
        return $this->timestamp;
    }
}