<?php

namespace Cerpus\VersionClient;

use Cerpus\VersionClient\interfaces\VersionDataInterface;
use GuzzleHttp\Psr7\Uri;
use Psr\Http\Message\UriInterface;

class VersionData implements VersionDataInterface
{

    public $externalReference;

    /** @var UriInterface */
    public $externalUrl;

    public $externalSystem;

    public $originSystem;

    public $originId;

    public $versionPurpose;

    public $userId;

    public $parent;

    public function __construct($id, $url, $userId, $versionPurpose, $parent)
    {
        $this->externalReference = $id;
        if( $url instanceof UriInterface){
            $this->externalUrl = $url;
        } else {
            $this->externalUrl = $url; //new Uri($url);
        }

        $this->versionPurpose = $versionPurpose;
        $this->parent = $parent;
        $this->userId = $userId;

        $this->externalSystem = "CONTENTAUTHOER"; //getenv("EXTERNAL_SYSTEM_NAME");
    }

    public function setExternalSystem($system)
    {
        $this->system = $system;
    }

    public function getExternalSystem()
    {
        // TODO: Implement getExternalSystem() method.
    }

    public function getExternalReference()
    {
        // TODO: Implement getExternalReference() method.
    }

    public function getExternalUrl()
    {
        // TODO: Implement getExternalUrl() method.
    }

    public function getVersionPurpose()
    {
        // TODO: Implement getVersionPurpose() method.
    }

    public function getUserId()
    {
        // TODO: Implement getUserId() method.
    }

    public function getOriginSystem()
    {
        // TODO: Implement getOriginSystem() method.
    }

    public function getOriginReference()
    {
        // TODO: Implement getOriginReference() method.
    }

}