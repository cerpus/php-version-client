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
    public function getCreatedAt();
	public function getParent();
	public function setVersionPurpose($versionPurpose);
	public function setCreatedAt($createdAt);
	public function setOriginSystem($originSystem);
	public function setOriginReference($originReference);
	public function setExternalSystem($externalSystem);
	public function setExternalReference($externalReference);
	public function setExternalUrl($externalUrl);
	public function setUserId($userId);
	public function setParent($parent);
}