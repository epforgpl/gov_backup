<?php

namespace App\Models;

use App\Models\WebObjectRevision;
use App\Models\WebObjectVersion;

// TODO update it or throw it (why we need this class?)
class WebObject
{

    private $id, $portal_id, $revision_id, $scheme, $host, $path, $query, $web_url, $url, $current_revision, $current_version;

    public function __construct($data)
    {
        $web_object = $data['data']['web_objects'];
        // $revision = $data['data']['web_objects_revisions'];
        $version = $data['data']['web_objects_versions'];

        $this->id = (int) $web_object['id'];
        $this->portal_id = (int) $web_object['portal_id'];
        $this->revision_id = (int) $web_object['revision_id'];
        $this->scheme = $web_object['scheme'];
        $this->host = $web_object['host'];
        $this->path = $web_object['path'];
        if ($this->path == '') {
            $this->path = '/';
        }
        $this->query = $web_object['query'];

        $this->web_url = $this->scheme . '://' . $this->host . $this->path;
        $this->url = '/get/20001010232323/' . $this->host . $this->path; // TODO put real timestamps in #5
        if( $this->query ) {
            $this->web_url .= '?' . $this->query;
            $this->url .= '?' . $this->query;
        }

        // $this->current_revision = new WebObjectRevision($revision);
        $this->current_version = new WebObjectVersion($version);

    }

    public function getCurrentRevision()
    {
        return $this->current_revision;
    }

    public function hasCurrentRevision()
    {
        return !empty($this->current_revision);
    }

    public function getCurrentVersion()
    {
        return $this->current_version;
    }

    public function hasCurrentVersion()
    {
        return !empty($this->current_version);
    }

    public function getWebUrl()
    {
        return $this->web_url;
    }

    public function getUrl()
    {
        return $this->url;
    }

    public function getId()
    {
        return $this->id;
    }

}