<?php

namespace AlibabaCloud\Credentials\Tests\Unit;

use AlibabaCloud\Credentials\Credentials;
use AlibabaCloud\Credentials\RamRoleArnCredential;
use GuzzleHttp\Psr7\Request;
use PHPUnit\Framework\TestCase;

/**
 * Class MockTraitTest
 *
 * @package AlibabaCloud\Credentials\Tests\Unit
 */
class MockTraitTest extends TestCase
{
     /**
     * @before
     */
    protected function initialize()
    {
        parent::setUp();
        Credentials::cancelMock();
    }
    /**
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @expectedException \GuzzleHttp\Exception\RequestException
     * @expectedExceptionMessage Error
     */
    public function testRequestException()
    {
        Credentials::mockRequestException('Error', new Request('GET', 'test'));
        $credential = new RamRoleArnCredential([
                                                   'access_key_id'     => 'access_key_id',
                                                   'access_key_secret' => 'access_key_secret',
                                                   'role_arn'          => 'role_arn',
                                                   'role_session_name' => 'role_session_name',
                                                   'policy'            => [],
                                               ]);

        $this->expectException(\GuzzleHttp\Exception\RequestException::class);
        $this->expectExceptionMessage('Error');
        self::assertEquals('STS.**************', $credential->getAccessKeyId());
    }
}
