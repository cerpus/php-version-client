<?php

namespace Cerpus\VersionClient\interfaces;


interface VersionDataInterface
{
    public function getExternalSystem();
    public function getExternalReference();
    public function getExternalUrl();
    public function getVersionPurpose();
    public function getUserId();
    public function getOriginSystem();
    public function getOriginReference();
}