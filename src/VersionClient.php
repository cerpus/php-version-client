<?php

namespace Cerpus\VersionClient;

use Cerpus\VersionClient\interfaces\VersionClientInterface;
use Cerpus\VersionClient\interfaces\VersionDataInterface;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Psr7\Stream;

class VersionClient implements VersionClientInterface
{

    const CREATE_VERSION = "/v1/resources";
    const GET_VERSION_DATA = "/v1/%s";
    const GET_VERSION_DATA_FROM_ORIGIN = "/v1/origin/%s/%s";

    const AUTH_SERVICE = "/v1/oauth2/service";
    const AUTH_TOKEN = "/oauth/token";

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
        $this->oauthKey = is_null($key) ? $this->getConfig("versionClient.oauthkey") : $key; //$oauthKey;
        $this->oauthSecret = is_null($secret) ? $this->getConfig("versionClient.oauthsecret") : $secret; //$oauthSecret;
        $this->versionServer = is_null($server) ? $this->getConfig("versionClient.versionserver") : $server; //$oauthServer;
        $this->verifyConfig();
    }

    public function getConfig($key)
    {
        return config($key);
    }

    public function verifyConfig()
    {
        foreach (["oauthKey", "oauthSecret", "versionServer"] as $key) {
            if (empty($this->$key)) {
                throw new \Exception("Setting '$key' is missing or empty. Aborting");
            }
        }
    }

    /**
     * Get token to talk to license server
     * @return bool|string false on failure, token otherwise
     */
    private function getToken()
    {
        $tokenName = __METHOD__ . '-VersionToken';
        $this->oauthToken = \Cache::get($tokenName);
        if (is_null($this->oauthToken)) {
            try {
                $licenseClient = new Client(['base_uri' => $this->versionServer]);
                $authResponse = $licenseClient->get(self::AUTH_SERVICE);
                $authJson = json_decode($authResponse->getBody());
                if (is_object($authJson) && property_exists($authJson, "url")) {
                    $authUrl = $authJson->url;
                } else {
                    $authUrl = current($authJson);
                }


                $authClient = new Client(['base_uri' => $authUrl]);
                $authResponse = $authClient->request('POST', self::AUTH_TOKEN, [
                    'auth' => [
                        $this->oauthKey,
                        $this->oauthSecret
                    ],
                    'form_params' => [
                        'grant_type' => 'client_credentials'
                    ],
                ]);
                $oauthJson = json_decode($authResponse->getBody());
                $this->oauthToken = $oauthJson->access_token;
                \Cache::put($tokenName, $this->oauthToken, 3);
            } catch (\Exception $e) {
                \Log::error(__METHOD__ . ': Unable to get token: URL: ' . $authUrl . '. Wrong key/secret?');
                return false;
            }
        }

        return $this->oauthToken;
    }

    protected function getClient()
    {
        return new Client(['base_uri' => $this->versionServer]);
    }

    private function doRequest($endPoint, $params = [], $method = 'GET')
    {
        $token = $this->getToken();
        try {
            $responseClient = $this->getClient();

            if ($token) {
                $finalParams = [
                    'form_params' => $params,
                    'headers' => [
                        'Authorization' => 'Bearer ' . $token
                    ],
                ];

                $response = $responseClient->request($method, $endPoint, $finalParams);

                return $response->getBody()->getContents();
            } else {
                \Log::error(__METHOD__ . ' Missing token.');

                return false;
            }
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
     */
    public function createVersion(VersionDataInterface $resourceData)
    {
        $this->resourceData = $resourceData;
        try {
            /** @var Stream $responseStream */
            $responseStream = $this->doRequest(self::CREATE_VERSION, $resourceData, "POST");

            if(!$this->verifyResponse($responseStream)){
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

            if(!$this->verifyResponse($responseStream)){
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