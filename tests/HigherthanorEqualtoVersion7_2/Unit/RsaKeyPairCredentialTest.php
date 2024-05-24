<?php

namespace AlibabaCloud\Credentials\Tests\HigherthanorEqualtoVersion7_2\Unit;

use AlibabaCloud\Credentials\Credentials;
use AlibabaCloud\Credentials\Helper;
use AlibabaCloud\Credentials\RsaKeyPairCredential;
use AlibabaCloud\Credentials\Signature\ShaHmac1Signature;
use AlibabaCloud\Credentials\Tests\HigherthanorEqualtoVersion7_2\Unit\Ini\VirtualRsaKeyPairCredential;
use Exception;
use GuzzleHttp\Exception\GuzzleException;
use PHPUnit\Framework\TestCase;

class RsaKeyPairCredentialTest extends TestCase
{
    /**
     * @var RsaKeyPairCredential
     */
    protected $credential;

    public static function testNotFoundFile()
    {
        // Setup
        $publicKeyId = 'PUBLIC_KEY_ID';

        if (Helper::isWindows()) {
            $privateKeyFile = 'C:\\projects\\no.no';
        } else {
            $privateKeyFile = '/a/b/no.no';
        }

        // Test
        try {
            new RsaKeyPairCredential($publicKeyId, $privateKeyFile);
        } catch (Exception $e) {
            self::assertEquals(
                "file_get_contents($privateKeyFile): failed to open stream: No such file or directory",
                $e->getMessage()
            );
        }
    }

    public static function testOpenBasedirException()
    {
        // Setup
        $publicKeyId = 'PUBLIC_KEY_ID';
        if (Helper::isWindows()) {
            $dirs           = 'C:\\projects;C:\\Users';
            $privateKeyFile = 'C:\\AlibabaCloud\\no.no';
        } else {
            $dirs           = 'vfs://AlibabaCloud:/home:/Users:/private:/a/b';
            $privateKeyFile = '/dev/no.no';
        }

        // Test
        ini_set('open_basedir', $dirs);
        try {
            new RsaKeyPairCredential($publicKeyId, $privateKeyFile);
        } catch (Exception $e) {
            self::assertEquals(
                "file_get_contents(): open_basedir restriction in effect. File($privateKeyFile) is not within the allowed path(s): ($dirs)",
                $e->getMessage()
            );
        }
    }

    public function testConstruct()
    {
        // Setup
        $publicKeyId    = 'public_key_id';
        $privateKeyFile = VirtualRsaKeyPairCredential::privateKeyFileUrl();

        // Test
        $credential = new RsaKeyPairCredential($publicKeyId, $privateKeyFile);

        // Assert
        $this->assertEquals($publicKeyId, $credential->getPublicKeyId());
        $this->assertStringEqualsFile($privateKeyFile, $credential->getPrivateKey());
        $this->assertEquals(
            "publicKeyId#$publicKeyId",
            (string)$credential
        );
        $this->assertEquals([], $credential->getConfig());
        $this->assertInstanceOf(ShaHmac1Signature::class, $credential->getSignature());
        $this->assertEquals($publicKeyId, $credential->getOriginalAccessKeyId());
    }

    /**
     * @throws Exception
     * @throws GuzzleException
     */
    public function testSts()
    {
        $publicKeyId    = 'public_key_id';
        $privateKeyFile = VirtualRsaKeyPairCredential::privateKeyFileUrl();
        $result = '{
    "RequestId": "F702286E-F231-4F40-BB86-XXXXXX",
    "SessionAccessKey": {
        "SessionAccessKeyId": "TMPSK.**************",
        "Expiration": "2023-02-19T07:02:36.225Z",
        "SessionAccessKeySecret": "**************"
    }
}';
        Credentials::mockResponse(200, [], $result);
        Credentials::mockResponse(200, [], $result);
        Credentials::mockResponse(200, [], $result);
        Credentials::mockResponse(200, [], $result);

        // Test
        $credential = new RsaKeyPairCredential($publicKeyId, $privateKeyFile);

        self::assertEquals('TMPSK.**************', $credential->getAccessKeyId());
        self::assertEquals('**************', $credential->getAccessKeySecret());
        self::assertEquals('', $credential->getSecurityToken());
        self::assertEquals(strtotime('2023-02-19T07:02:36.225Z'), $credential->getExpiration());
    }

    /**
     * @throws Exception
     * @throws GuzzleException
     * @expectedException \RuntimeException
     * @expectedExceptionMessage Result contains no credentials
     */
    public function testStsIncomplete()
    {
        // Setup
        $publicKeyId    = 'public_key_id_new';
        $privateKeyFile = VirtualRsaKeyPairCredential::privateKeyFileUrl();
        Credentials::cancelMock();
        $result = '{
    "RequestId": "F702286E-F231-4F40-BB86-XXXXXX",
    "SessionAccessKey": {
        "SessionAccessKeyId": "TMPSK.**************",
        "Expiration": "2023-02-19T07:02:36.225Z"
    }
}';
        Credentials::mockResponse(200, [], $result);
        $credential = new RsaKeyPairCredential($publicKeyId, $privateKeyFile);

        // Test
        self::assertEquals('TMPSK.**************', $credential->getAccessKeyId());
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage public_key_id cannot be empty
     */
    public function testPublicKeyIdEmpty()
    {
        // Setup
        $publicKeyId    = '';
        $privateKeyFile = VirtualRsaKeyPairCredential::privateKeyFileUrl();

        // Test
        new RsaKeyPairCredential($publicKeyId, $privateKeyFile);
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage public_key_id must be a string
     */
    public function testPublicKeyIdFormat()
    {
        // Setup
        $publicKeyId    = null;
        $privateKeyFile = VirtualRsaKeyPairCredential::privateKeyFileUrl();

        // Test
        new RsaKeyPairCredential($publicKeyId, $privateKeyFile);
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage private_key_file cannot be empty
     */
    public function testPrivateKeyFileEmpty()
    {
        // Setup
        $publicKeyId    = 'publicKeyId';
        $privateKeyFile = '';

        // Test
        new RsaKeyPairCredential($publicKeyId, $privateKeyFile);
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage private_key_file must be a string
     */
    public function testPrivateKeyFileFormat()
    {
        // Setup
        $publicKeyId    = 'publicKeyId';
        $privateKeyFile = null;

        // Test
        new RsaKeyPairCredential($publicKeyId, $privateKeyFile);
    }

    protected function setUp(): void
    {
        // Setup
        Credentials::cancelMock();

        // Setup
        $publicKeyId    = 'public_key_id';
        $privateKeyFile = VirtualRsaKeyPairCredential::privateKeyFileUrl();

        // Test
        $this->credential = new RsaKeyPairCredential($publicKeyId, $privateKeyFile);
    }
}
