<?php
/**
 * Created by PhpStorm.
 * User: kmadejski
 * Date: 13.03.18
 * Time: 11:00
 */

namespace App\Repositories;


class UrlRevision
{
    public $timestamp;
    public $object_id;
    public $version_id;
    public $object_url;

    /**
     * UrlRevision constructor.
     *
     * @param $timestamp
     * @param $object_id
     * @param $version_id
     * @param $rewritten_url
     */
    public function __construct($timestamp, $object_id, $version_id, $object_url)
    {
        $this->timestamp = $timestamp;
        $this->object_id = $object_id;
        $this->version_id = $version_id;
        $this->object_url = $object_url;
    }

    /**
     * @return string
     */
    public function getRewrittenUrl() {
        $url =  route('view', [
            'url' => $this->object_url,
            'timestamp' => $this->timestamp->format('YmdHis')]);

        return $url;
    }
}