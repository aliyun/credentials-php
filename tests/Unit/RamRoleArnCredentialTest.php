<?php

namespace AlibabaCloud\Credentials\Tests\Unit;

use AlibabaCloud\Credentials\Credentials;
use AlibabaCloud\Credentials\RamRoleArnCredential;
use AlibabaCloud\Credentials\Signature\ShaHmac1Signature;
use Exception;
use PHPUnit\Framework\TestCase;

class RamRoleArnCredentialTest extends TestCase
{

    /**
     * @var RamRoleArnCredential
     */
    protected $credential;

    public function testConstruct()
    {
        putenv('DEBUG=sdk');

        // Setup
        $accessKeyId     = 'access_key_id';
        $accessKeySecret = 'access_key_secret';
        $arn             = 'role_arn';
        $sessionName     = 'role_session_name';
        $policy          = '';

        // Test
        $credential = new RamRoleArnCredential([
            'access_key_id'     => 'access_key_id',
            'access_key_secret' => 'access_key_secret',
            'role_arn'          => 'role_arn',
            'role_session_name' => 'role_session_name',
            'policy'            => '',
        ]);

        // Assert
        $this->assertEquals($arn, $credential->getRoleArn());
        $this->assertEquals($sessionName, $credential->getRoleSessionName());
        $this->assertEquals($policy, $credential->getPolicy());
        $this->assertInstanceOf(ShaHmac1Signature::class, $credential->getSignature());
        $this->assertEquals(
            "$accessKeyId#$accessKeySecret#$arn#$sessionName",
            (string)$credential
        );

        $this->assertEquals(
            [],
            $credential->getConfig()
        );
    }

    /**
     * @throws Exception
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function testSts()
    {
        $result = '{
    "RequestId": "88FEA385-EF5D-4A8A-8C00-A07DAE3BFD44",
    "AssumedRoleUser": {
        "AssumedRoleId": "********************",
        "Arn": "********************"
    },
    "Credentials": {
        "AccessKeySecret": "********************",
        "AccessKeyId": "STS.**************",
        "Expiration": "2020-02-25T03:56:19Z",
        "SecurityToken": "**************"
    }
}';
        Credentials::mockResponse(200, [], $result);
        Credentials::mockResponse(200, [], $result);
        Credentials::mockResponse(200, [], $result);
        Credentials::mockResponse(200, [], $result);
        $credential = new RamRoleArnCredential([
            'access_key_id'     => 'access_key_id',
            'access_key_secret' => 'access_key_secret',
            'role_arn'          => 'role_arn',
            'role_session_name' => 'role_session_name',
            'policy'            => [],
        ]);

        self::assertEquals('STS.**************', $credential->getAccessKeyId());
        self::assertEquals('********************', $credential->getAccessKeySecret());
        self::assertEquals('**************', $credential->getSecurityToken());
        self::assertEquals(strtotime('2020-02-25T03:56:19Z'), $credential->getExpiration());
    }

    /**
     * @throws Exception
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @expectedException \RuntimeException
     * @expectedExceptionMessage Result contains no credentials
     */
    public function testStsIncomplete()
    {
        // Setup
        Credentials::cancelMock();
        $result = '{
    "RequestId": "88FEA385-EF5D-4A8A-8C00-A07DAE3BFD44",
    "AssumedRoleUser": {
        "AssumedRoleId": "********************",
        "Arn": "********************"
    },
    "Credentials": {
        "AccessKeyId": "STS.**************",
        "Expiration": "2020-02-25T03:56:19Z",
        "SecurityToken": "**************"
    }
}';
        Credentials::mockResponse(200, [], $result);
        $credential = new RamRoleArnCredential([
            'access_key_id'     => 'access_key_id2',
            'access_key_secret' => 'access_key_secret2',
            'role_arn'          => 'role_arn2',
            'role_session_name' => 'role_session_name2',
            'policy'            => '',
        ]);

        // Test
        self::assertEquals('TMPSK.**************', $credential->getAccessKeyId());
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage access_key_id cannot be empty
     */
    public function testAccessKeyIdEmpty()
    {

        // Test
        new RamRoleArnCredential([
            'access_key_id'     => '',
            'access_key_secret' => 'access_key_secret',
            'role_arn'          => 'role_arn',
            'role_session_name' => 'role_session_name',
            'policy'            => '',
        ]);
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Missing required access_key_secret option in config for ram_role_arn
     */
    public function testAccessKeyIdFormat()
    {
        // Test
        new RamRoleArnCredential([
            'access_key_id'     => 'access_key_id',
            'access_key_secret' => null,
            'role_arn'          => 'role_arn',
            'role_session_name' => 'role_session_name',
            'policy'            => '',
        ]);
    }

    protected function setUp()
    {
        // Setup
        Credentials::cancelMock();
        $this->credential = new  RamRoleArnCredential([
            'access_key_id'     => 'access_key_id',
            'access_key_secret' => 'access_key_secret',
            'role_arn'          => 'role_arn',
            'role_session_name' => 'role_session_name',
            'policy'            => '',
        ]);
    }
}
