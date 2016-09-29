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

    protected $error;

    public function __construct()
    {
        $this->oauthKey = $this->getConfig("versionClient.oauthkey"); //$oauthKey;
        $this->oauthSecret = $this->getConfig("versionClient.oauthsecret"); //$oauthSecret;
        $this->versionServer = $this->getConfig("versionClient.versionserver"); //$oauthSecret;
        $this->verifyConfig();
    }

    public function getConfig($key){
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
                if( is_object($authJson) && property_exists($authJson, "url")){
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
                \Log::error(__METHOD__. ': Unable to get token: URL: '.$authUrl.'. Wrong key/secret?');
                return false;
            }
        }

        return $this->oauthToken;
    }

    protected function getClient(){
        return new Client(['base_uri' => $this->versionServer]);
    }

    private function doRequest($endPoint, $params = [], $method = 'GET')
    {
        $token = $this->getToken();
        try {
            $finalParams = [];
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
        } catch (ClientException $clientException){
            if( $clientException->hasResponse()){
                $error = $clientException->getResponse();
                $this->error = json_decode($error->getBody()->getContents());
            }
            throw $clientException;
        }
    }

    /**
     * @param VersionDataInterface $resourceData
     * @return bool
     */
    public function createVersion(VersionDataInterface $resourceData)
    {
        $this->resourceData = $resourceData;
        try{
            /** @var Stream $responseStream */
            $responseStream = $this->doRequest(self::CREATE_VERSION, $resourceData, "POST");
            $this->responseData = json_decode($responseStream);
        } catch (\Exception $exception){
            return false;
        }

        return true;
    }

    public function getVersion($versionId)
    {
        // TODO: Implement getVersion() method.
    }

    public function getVersionsFromOrigin($originSystem, $originReference)
    {
        // TODO: Implement getVersionsFromOrigin() method.
    }

    public function getVersionId()
    {
        return !empty($this->responseData->data->id) ? $this->responseData->data->id : null;
    }
}