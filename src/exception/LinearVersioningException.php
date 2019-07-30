<?php


namespace Cerpus\VersionClient\exception;


use Throwable;

class LinearVersioningException extends \Exception
{
    private $requestedParent;
    private $leafNodes = [];

    public function __construct($requestedParent, $leafNodes)
    {
        parent::__construct('Linear versioning exception');
        $this->requestedParent = $requestedParent;
        $this->leafNodes = $leafNodes;
    }

    /**
     * @return mixed
     */
    public function getRequestedParent()
    {
        return $this->requestedParent;
    }

    /**
     * @return array
     */
    public function getLeafNodes()
    {
        return $this->leafNodes;
    }
}
