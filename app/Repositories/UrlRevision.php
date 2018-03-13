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
    public $rewritten_url;

    /**
     * UrlRevision constructor.
     *
     * @param $timestamp
     * @param $object_id
     * @param $version_id
     * @param $rewritten_url
     */
    public function __construct($timestamp, $object_id, $version_id, $rewritten_url)
    {
        $this->timestamp = $timestamp;
        $this->object_id = $object_id;
        $this->version_id = $version_id;
        $this->rewritten_url = $rewritten_url;
    }
}