<?php

namespace Cerpus\VersionClient;

use Cerpus\VersionClient\exception\LinearVersioningException;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Stream;
use GuzzleHttp\Exception\ClientException;
use Cerpus\VersionClient\interfaces\VersionDataInterface;
use Cerpus\VersionClient\interfaces\VersionClientInterface;

class VersionClient implements VersionClientInterface
{

    const CREATE_VERSION = "/v1/resources";
    const GET_VERSION_DATA = "/v1/resources/%s";
    const GET_VERSION_LATEST_DATA = "/v1/resources/%s/latest";
    const GET_VERSION_DATA_FROM_ORIGIN = "/v1/origin/%s/%s";

    protected $oauthToken;
    protected $oauthKey, $oauthSecret;

    /** @var  VersionDataInterface */
    protected $resourceData;

    protected $responseData;

    protected $errors = null;

    protected $errorCode = null;

    protected $message = null;

    public function __construct($key = null, $secret = null, $server = null)
    {
        $this->versionServer = is_null($server) ? $this->getConfig("versionClient.versionserver") : $server; //$oauthServer;
        $this->verifyConfig();
    }

    public function getConfig($key)
    {
        return config($key, "http://versioningapi:8080");
    }

    public function verifyConfig()
    {
        foreach (["versionServer"] as $key) {
            if (empty($this->$key)) {
                throw new \Exception("Setting '$key' is missing or empty. Aborting");
            }
        }
    }

    protected function getClient()
    {
        return new Client(['base_uri' => $this->versionServer]);
    }

    private function doRequest($endPoint, $json = [], $method = 'GET')
    {
        try {
            $responseClient = $this->getClient();

            return $responseClient->request($method, $endPoint, [ 'form_params' => $json ])->getBody()->getContents();
        } catch (ClientException $clientException) {
            if ($clientException->hasResponse()) {
                $error = $clientException->getResponse();
                $this->errorCode = $error->getStatusCode();
                $this->errors = json_decode($error->getBody()->getContents());
            }
            throw $clientException;
        }
    }

    /**
     * Create new version in API
     *
     * @param VersionDataInterface $resourceData
     * @return bool|VersionData
     * @throws LinearVersioningException
     */
	public function createVersion(VersionDataInterface $resourceData)
	{
		$this->resourceData = $resourceData;
		try {
			/** @var Stream $responseStream */
			$resourceArray = $resourceData->toArray();
			try {
                $responseStream = $this->doRequest(self::CREATE_VERSION, $resourceArray, "POST");
            } catch (ClientException $e) {
			    if ($this->errorCode == 409 && $this->errors && isset($this->errors->requestedParent) && isset($this->errors->leafs)) {
                    $parent = new VersionData();
                    $parent->populate($this->errors->requestedParent);
                    $leafs = array_map(function ($leafData) {
                        $leaf = new VersionData();
                        $leaf->populate($leafData);
                        return $leaf;
                    }, $this->errors->leafs);
                    throw new LinearVersioningException($parent, $leafs);
                }
			    throw $e;
            }

			if (!$this->verifyResponse($responseStream)) {
				return false;
			}

			$versionData = new VersionData(); // Can/Should we use the Laravel Service Container to resolve this?
			$versionData->populate($this->responseData->data);
		} catch (\Exception $exception) {
		    if ($exception instanceof LinearVersioningException) {
		        throw $exception;
            }
			$this->errorCode = $exception->getCode();
			$this->errors = $exception->getMessage();
			return false;
		}

		return $versionData;
	}

    /**
     * Create new version in API
     *
     * @param VersionDataInterface $resourceData
     * @return bool|VersionData
     */
    public function initialVersion(VersionDataInterface $resourceData)
    {
	    $resourceData->setVersionPurpose(VersionData::INITIAL);
        $this->resourceData = $resourceData;
        try {
            /** @var Stream $responseStream */
            $resourceArray = $resourceData->toArray();
            $responseStream = $this->doRequest(self::CREATE_VERSION, $resourceArray, "POST");

            if (!$this->verifyResponse($responseStream)) {
                return false;
            }

            $versionData = new VersionData(); // Can/Should we use the Laravel Service Container to resolve this?
            $versionData->populate($this->responseData->data);
        } catch (\Exception $exception) {
            $this->errorCode = $exception->getCode();
            $this->errors = $exception->getMessage();
            return false;
        }

        return $versionData;
    }

    /**
     * Get version info
     *
     * @param $versionId
     * @return bool|VersionData
     */
    public function getVersion($versionId)
    {
        try {
            $endPoint = sprintf(self::GET_VERSION_DATA, $versionId);
            $responseStream = $this->doRequest($endPoint, [], "GET");

            if (!$this->verifyResponse($responseStream)) {
                return false;
            }

            $versionData = new VersionData(); // Can/Should we use the Laravel Service Container to resolve this?
            $receivedData = $this->responseData->data;
            $versionData->populate($receivedData);
        } catch (\Exception $e) {
            $this->errorCode = $e->getCode();
            $this->errors = $e->getMessage();
            return false;
        }

        return $versionData;
    }

    public function latest($versionId)
    {
        $endPoint = sprintf(self::GET_VERSION_LATEST_DATA, $versionId);
        $responseStream = $this->doRequest($endPoint, [], "GET");

        if (!$this->verifyResponse($responseStream)) {
            return false;
        }

        $receivedData = $this->responseData->data;

        $versionData = app(VersionData::class);
        $versionData->populate($receivedData);

        return $versionData;
    }

    protected function verifyResponse($responseStream)
    {
        $this->responseData = json_decode($responseStream);
        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->errorCode = json_last_error();
            $this->errors[] = json_last_error_msg();
            return false;
        }

        if (!$this->verifyResponseJson()) {
            return false;
        }

        return true;
    }

    public function getVersionId()
    {
        return !empty($this->responseData->data->id) ? $this->responseData->data->id : null;
    }

    /**
     * @return mixed
     */
    public function getError()
    {
        return $this->errors;
    }

    public function getErrorCode()
    {
        return $this->errorCode;
    }

    public function getMessage()
    {
        return $this->message;
    }

    protected function verifyResponseJson()
    {
        $json = $this->responseData;
        $valid = property_exists($json, 'data')
            && property_exists($json, 'errors')
            && property_exists($json, 'type')
            && property_exists($json, 'message');
        if (!$valid) {
            $this->errorCode = 1;
            $this->errors = 'Invalid data format in response';
        }
        return $valid;
    }


}