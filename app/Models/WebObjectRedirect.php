<?php

namespace App\Models;

class WebObjectRedirect extends \App\Models\WebObject
{
    /**
     * @var string
     */
    private $redirect_location;

    /**
     * @var bool
     */
    private $redirection_archived;

    /**
     * @return mixed
     */
    public function getRedirectLocation()
    {
        return $this->redirect_location;
    }

    /**
     * @param mixed $redirect_location
     */
    public function setRedirectLocation($redirect_location)
    {
        $this->redirect_location = $redirect_location;
    }

    /**
     * @return bool
     */
    public function isRedirectionArchived(): bool
    {
        return $this->redirection_archived;
    }

    /**
     * @param bool $redirection_archived
     */
    public function setRedirectionArchived(bool $redirection_archived)
    {
        $this->redirection_archived = $redirection_archived;
    }
}