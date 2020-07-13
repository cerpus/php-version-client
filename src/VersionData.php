<?php

namespace Cerpus\VersionClient;

use GuzzleHttp\Psr7\Uri;
use Psr\Http\Message\UriInterface;
use Cerpus\VersionClient\interfaces\VersionDataInterface;

class VersionData implements VersionDataInterface
{
    const INITIAL = 'Initial';
    const CREATE = 'Create';
    const UPDATE = 'Update';
    const IMPORT = 'Import';
    const COPY = 'Copy';
    const UPGRADE = 'Upgrade';
    const TRANSLATION = 'Translation';


    protected $id;

    protected $externalReference;

    /** @var UriInterface */
    protected $externalUrl;

    protected $externalSystem;

    protected $originSystem;

    protected $originReference;

    protected $originId;

    protected $versionPurpose;

    protected $userId;

    protected $parent;

    protected $children = [];

    protected $coreId;

    protected $createdAt;

    protected $linearVersioning;

    public function __construct($id = null, $url = '', $userId = null, $versionPurpose = '', $parent = null)
    {
        $this->externalReference = $id;
        if ($url instanceof UriInterface) {
            $this->externalUrl = $url;
        } else {
            $this->externalUrl = $url; //new Uri($url);
        }

        $this->versionPurpose = $versionPurpose;
        $this->parent = $parent;
        $this->userId = $userId;

        //Defaults
        $this->externalSystem = config('app.site-name');
        $this->createdAt = time();
    }

    /**
     * @param $data
     *
     * @return VersionData
     */
    public function populate($data)
    {
        property_exists($data, 'externalReference') ? $this->setExternalReference($data->externalReference) : null;
        property_exists($data, 'externalUrl') ? $this->setExternalUrl($data->externalUrl) : null;
        property_exists($data, 'externalSystem') ? $this->setExternalSystem($data->externalSystem) : null;
        property_exists($data, 'id') ? $this->setId($data->id) : null;
        property_exists($data, 'parent') ? $this->setParent($data->parent) : null;
        property_exists($data, 'children') ? $this->setChildren($data->children) : [];
        property_exists($data, 'coreId') ? $this->setCoreId($data->coreId) : null;
        property_exists($data, 'versionPurpose') ? $this->setVersionPurpose($data->versionPurpose) : null;
        property_exists($data, 'originReference') ? $this->setOriginReference($data->originReference) : null;
        property_exists($data, 'originSystem') ? $this->setOriginSystem($data->originSystem) : null;
        property_exists($data, 'userId') ? $this->setUserId($data->userId) : null;
        property_exists($data, 'createdAt') ? $this->setCreatedAt($data->createdAt) : null;
        property_exists($data, 'linearVersioning') ? $this->setLinearVersioning($data->linearVersioning) : null;

        return $this;
    }

    public function toArray()
    {
        $properties = [
            'externalReference',
            'externalUrl',
            'externalSystem',
            'id',
            'parent',
            'children',
            'coreId',
            'versionPurpose',
            'originReference',
            'originSystem',
            'userId',
            'linearVersioning'
       //     'createdAt'
        ];

        $versionData = [];
        foreach ($properties as $property) {
            $versionData = $this->toArrayHelper($versionData, $property);
        }

        return $versionData;
    }

    private function toArrayHelper($theArray, $theProperty)
    {
        if (property_exists($this, $theProperty)) {
            if (!empty($this->$theProperty) || $this->$theProperty === false) {
                $theArray[$theProperty] = $this->$theProperty;
            }
        }

        return $theArray;
    }

    /**
     * @return null
     */
    public function getExternalReference()
    {
        return $this->externalReference;
    }

    /**
     * @param null $externalReference
     * @return VersionData
     */
    public function setExternalReference($externalReference)
    {
        $this->externalReference = $externalReference;
        return $this;
    }

    /**
     * @return UriInterface
     */
    public function getExternalUrl()
    {
        return $this->externalUrl;
    }

    /**
     * @param UriInterface $externalUrl
     * @return VersionData
     */
    public function setExternalUrl($externalUrl)
    {
        $this->externalUrl = $externalUrl;
        return $this;
    }

    /**
     * @return string
     */
    public function getExternalSystem()
    {
        return $this->externalSystem;
    }

    /**
     * @param string $externalSystem
     * @return VersionData
     */
    public function setExternalSystem($externalSystem)
    {
        $this->externalSystem = $externalSystem;
        return $this;
    }


    /**
     * @return string
     */
    public function getVersionPurpose()
    {
        return $this->versionPurpose;
    }

    /**
     * @param string $versionPurpose
     * @return VersionData
     */
    public function setVersionPurpose($versionPurpose)
    {
        $this->versionPurpose = $versionPurpose;
        return $this;
    }

    /**
     * @return null
     */
    public function getUserId()
    {
        return $this->userId;
    }

    /**
     * @param null $userId
     * @return VersionData
     */
    public function setUserId($userId)
    {
        $this->userId = $userId;
        return $this;
    }

    /**
     * @return null
     */
    public function getParent()
    {
        return $this->parent;
    }

    /**
     * @param null $parent
     * @return VersionData
     */
    public function setParent($parent)
    {
        if (!is_null($parent)) {
            $theParent = (new VersionData())->populate($parent);
            $this->parent = $theParent;
        }


        return $this;
    }

    public function setParentId($parentId)
    {
        $this->parent = $parentId;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param mixed $id
     * @return VersionData
     */
    public function setId($id)
    {
        $this->id = $id;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getChildren()
    {
        return $this->children;
    }

    /**
     * @param mixed $children
     * @return VersionData
     */
    public function setChildren($children)
    {
        $this->children = [];

        if (is_array($children)) {
            $this->addChildren($children);
        }

        return $this;
    }

    /**
     * @param mixed $children
     * @return VersionData
     */
    public function addChildren($children)
    {
        foreach ($children as $child) {
            $theChild = new VersionData();
            $theChild->populate($child);
            $this->children[] = $theChild;
        }

        return $this;
    }


    /**
     * @return mixed
     */
    public function getCoreId()
    {
        return $this->coreId;
    }

    /**
     * @param mixed $coreId
     */
    public function setCoreId($coreId)
    {
        $this->coreId = $coreId;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getOriginSystem()
    {
        return $this->originSystem;
    }

    /**
     * @param mixed $originSystem
     * @return VersionData
     */
    public function setOriginSystem($originSystem)
    {
        $this->originSystem = $originSystem;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getOriginReference()
    {
        return $this->originReference;
    }

    /**
     * @param mixed $originReference
     * @return VersionData
     */
    public function setOriginReference($originReference)
    {
        $this->originReference = $originReference;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getOriginId()
    {
        return $this->originId;
    }

    /**
     * @param mixed $originId
     * @return VersionData
     */
    public function setOriginId($originId)
    {
        $this->originId = $originId;
        return $this;
    }

    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    /**
     * @param int $createdAt timestamp
     */
    public function setCreatedAt($createdAt)
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    /**
     * @return mixed
     */
    public function isLinearVersioning()
    {
        return isset($this->linearVersioning) && $this->linearVersioning !== null && $this->linearVersioning;
    }

    /**
     * @param boolean $linearVersioning
     */
    public function setLinearVersioning($linearVersioning)
    {
        $this->linearVersioning = $linearVersioning;
    }

}
