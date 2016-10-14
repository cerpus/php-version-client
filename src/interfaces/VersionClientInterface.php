<?php

namespace Cerpus\VersionClient\interfaces;


interface VersionClientInterface
{
    public function createVersion(VersionDataInterface $versionData);

    public function getVersion($versionId);

    public function getVersionId();

}