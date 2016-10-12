<?php

namespace Cerpus\VersionClient\tests;

use Cerpus\VersionClient\VersionData;
use Cerpus\VersionClient\VersionClient;
use Cerpus\VersionClient\interfaces\VersionDataInterface;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use Mockery as m;


class VersionClientTest extends \PHPUnit_Framework_TestCase
{

    protected $mockConfig = [
        'base-url' => 'http://version.local',
    ];

    protected $responseBodies = [
        'get-version-successful' => '{"data":{"id":"id1","externalSystem":"CONTENTAUTHOR","externalReference":"exref1","externalUrl":"http://ca.local/h5p/1","parent":null,"children":[],"createdAt":1476299644126,"coreId":"1","versionPurpose":"Testing","originReference":"reference","originSystem":"CONTENTAUTHOR","userId":"user1"},"errors":[],"type":"success","message":null}',
    ];

    public function tearDown()
    {
        m::close();
        parent::tearDown();
    }

    private function mockAuthentication()
    {
        m::mock("alias:Cache")
            ->shouldReceive('get')->once()->with(VersionClient::class . '::getToken-VersionToken')->andReturn("authenticated");
    }

    private function mockLog()
    {
        m::mock("alias:Log")
            ->shouldReceive('error')->andReturn("logged");
    }

    /**
     * @test
     */
    public function createVersion()
    {
        $this->mockAuthentication();

        /** @var VersionClient $versionClient */
        $versionClient = $this->getMockBuilder(VersionClient::class)
            ->setMethods(["getConfig", "getClient", "verifyConfig"])
            ->getMock();

        $versionClient->method("getConfig")->willReturnArgument(0);
        $versionClient->method("verifyConfig")->willReturn(true);
        $versionClient->method("getClient")->willReturnCallback(function () {

            $createdData = new \stdClass();
            $createdData->id = '123-456-789';
            $createdData->externalSystem = "UniTest";
            $createdData->externalReference = 12345;
            $createdData->externalUrl = "http://test.test";
            $createdData->parent = null;
            $createdData->children = null;
            $createdData->versionPurpose = "created";
            $createdData->userId = 123421;

            $respnseData = new \stdClass();
            $respnseData->error = [];
            $respnseData->data = $createdData;
            $respnseData->type = "success";
            $respnseData->message = null;


            $clientRequest = new MockHandler([
                new Response(201, ["Content-Type" => "application/json"], json_encode($respnseData))
            ]);
            $handler = HandlerStack::create($clientRequest);
            return new Client(['handler' => $handler]);
        });

        $data = new VersionData(1, "http://test.test", 1234321, "create", null);
        $this->assertTrue($versionClient->createVersion($data));
        $this->assertEquals($versionClient->getVersionId(), "123-456-789");

    }

    /** @test */
    public function createVersionWithErrors()
    {
        $this->mockAuthentication();
        $this->mockLog();

        /** @var VersionClient $versionClient */
        $versionClient = $this->getMockBuilder(VersionClient::class)
            ->setMethods(["getConfig", "getClient", "verifyConfig"])
            ->getMock();

        $versionClient->method("getConfig")->willReturnArgument(0);
        $versionClient->method("verifyConfig")->willReturn(true);
        $versionClient->method("getClient")->willReturnCallback(function () {

            $responseError = new \stdClass();
            $responseError->code = "URL";
            $responseError->message = "must be a valid URL, current value is 'qatesting.test/hello'";
            $responseError->field = "externalUrl";

            $respnseData = new \stdClass();
            $respnseData->error = [$responseError];
            $respnseData->data = [];
            $respnseData->type = "failure";
            $respnseData->message = "The request had invalid properties.";


            $clientRequest = new MockHandler([
                new Response(400, ["Content-Type" => "application/json"], json_encode($respnseData))
            ]);
            $handler = HandlerStack::create($clientRequest);
            return new Client(['handler' => $handler]);
        });

        $data = new VersionData(1, "invalidUrl", 1234321, "create", null);
        $this->assertFalse($versionClient->createVersion($data));
        $this->assertNull($versionClient->getVersionId());
    }

    public function testGetVersionSuccess()
    {
        $this->mockAuthentication();
        $this->mockLog();

        /** @var VersionClient $versionClient */
        $versionClient = $this->getMockBuilder(VersionClient::class)
            ->setMethods(["getConfig", "getClient", "verifyConfig"])
            ->getMock();

        $versionClient->method("getConfig")->willReturnArgument(0);
        $versionClient->method("verifyConfig")->willReturn(true);

        $guzzleHandler = new MockHandler([
            new Response(200, [], $this->responseBodies['get-version-successful']),
        ]);
        $stackHandler = HandlerStack::create($guzzleHandler);
        $mockClient = new Client(['handler' => $stackHandler]);

        $versionClient->method('getClient')->willReturn($mockClient);

        $versionResult = $versionClient->getVersion('1234');
//        'get-version-successful' => '{"data":{"id":"id1","externalSystem":"CONTENTAUTHOR","externalReference":"exref1","externalUrl":"http://ca.local/h5p/1","parent":null,"children":[],
//          "createdAt":1476299644126,"coreId":"1","versionPurpose":"Testing","originReference":"reference","originSystem":"CONTENTAUTHOR","userId":"user1"},"errors":[],"type":"success","message":null}',

        $this->assertNotFalse($versionResult);
        $this->assertInstanceOf(VersionDataInterface::class, $versionResult);
        $this->assertEquals('id1', $versionResult->getId());
        $this->assertEquals('CONTENTAUTHOR', $versionResult->getExternalSystem());
        $this->assertEquals('exref1', $versionResult->getExternalReference());
        $this->assertEquals('http://ca.local/h5p/1', $versionResult->getExternalUrl());
        $this->assertNull($versionResult->getParent());
        $this->assertEquals('http://ca.local/h5p/1', $versionResult->getExternalUrl());
        $this->assertTrue(is_array($versionResult->getChildren()));
        $this->assertCount(0, $versionResult->getChildren());
        $this->assertEquals('1', $versionResult->getCoreId());
        $this->assertEquals('Testing', $versionResult->getVersionPurpose());
        $this->assertEquals('reference', $versionResult->getOriginReference());
        $this->assertEquals('CONTENTAUTHOR', $versionResult->getOriginSystem());
        $this->assertEquals('user1', $versionResult->getUserId());

    }
}
