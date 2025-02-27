<?php

namespace AlibabaCloud\Credentials\Tests\Unit\Providers;

use AlibabaCloud\Credentials\Providers\Credentials;
use AlibabaCloud\Credentials\Credential\RefreshResult;
use AlibabaCloud\Credentials\Providers\SessionCredentialsProvider;
use PHPUnit\Framework\TestCase;

class TestSessionCredentialsProvider extends SessionCredentialsProvider
{
    public function refreshCredentials()
    {

        return new RefreshResult(new Credentials([
            'accessKeyId' => 'testAccessKeyId',
            'accessKeySecret' => 'testAccessKeySecret',
            'securityToken' => 'testSecurityToken',
            'providerName' => $this->getProviderName(),
        ]), time() + 3600);
    }

    public function clearCache()
    {
        unset(self::$credentialsCache[$this->key()]);
    }

    public function getProviderName()
    {
        return 'test';
    }

    public function key()
    {
        return 'testKey';
    }

    public function handleFetchedFailure(\Exception $e)
    {
        return parent::handleFetchedFailure($e);
    }

    public function handleFetchedSuccess(RefreshResult $value)
    {
        return parent::handleFetchedSuccess($value);
    }

    public function refreshCache()
    {
        return parent::refreshCache();
    }

    /**
     * Cache credentials.
     *
     * @param RefreshResult $credential|null
     */
    public function cache(RefreshResult $value)
    {
        return parent::cache($value);
    }

    public function cacheIsStale()
    {
        return parent::cacheIsStale();
    }

    public function shouldInitiateCachePrefetch()
    {
        return parent::shouldInitiateCachePrefetch();
    }

    public function getCredentialsInCache()
    {
        return parent::getCredentialsInCache();
    }
}

class SessionCredentialsProviderTest extends TestCase
{

    protected $provider;

    /**
     * @before
     */
    protected function initialize()
    {
        parent::setUp();
        $this->provider = new TestSessionCredentialsProvider();
    }

    public function testGetCredentialsInCache()
    {
        $this->assertNull($this->provider->getCredentialsInCache());

        $refreshResult = new RefreshResult(new Credentials([
            'accessKeyId' => 'testAccessKeyId',
            'accessKeySecret' => 'testAccessKeySecret',
            'securityToken' => 'testSecurityToken',
        ]), time() + 3600);
        $this->provider->cache($refreshResult);

        $cachedResult = $this->provider->getCredentialsInCache();
        $this->assertInstanceOf(RefreshResult::class, $cachedResult);
        $this->assertEquals('testAccessKeyId', $cachedResult->credentials()->getAccessKeyId());
        $this->provider->clearCache();
    }

    public function testCache()
    {
        $refreshResult = new RefreshResult(new Credentials([
            'accessKeyId' => 'testAccessKeyId',
            'accessKeySecret' => 'testAccessKeySecret',
            'securityToken' => 'testSecurityToken',
        ]), time() + 3600);
        $this->provider->cache($refreshResult);

        $this->assertNotNull($this->provider->getCredentialsInCache());
        $this->provider->clearCache();
    }

    public function testGetCredentials()
    {
        $credentials = $this->provider->getCredentials();
        $this->assertEquals('testAccessKeyId', $credentials->getAccessKeyId());
    }

    public function testRefreshCache()
    {
        $refreshResult = $this->provider->refreshCache();
        $this->assertInstanceOf(RefreshResult::class, $refreshResult);
        $this->assertEquals('testAccessKeyId', $refreshResult->credentials()->getAccessKeyId());
    }

    public function testHandleFetchedFailure()
    {
        $this->provider->clearCache();
        $exception = new \Exception('Test exception');
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Test exception');

        $this->provider->handleFetchedFailure($exception);
    }

    public function testHandleFetchedSuccess()
    {
        $refreshResult = new RefreshResult(new Credentials([
            'accessKeyId' => 'testAccessKeyId',
            'accessKeySecret' => 'testAccessKeySecret',
            'securityToken' => 'testSecurityToken',
        ]), time() + 3600);
        $result = $this->provider->handleFetchedSuccess($refreshResult);
        $this->assertInstanceOf(RefreshResult::class, $result);
        $this->assertEquals('testAccessKeyId', $result->credentials()->getAccessKeyId());
        $this->provider->clearCache();
    }

    public function testCacheIsStale()
    {
        $this->assertTrue($this->provider->cacheIsStale());

        $refreshResult = new RefreshResult(new Credentials([
            'accessKeyId' => 'testAccessKeyId',
            'accessKeySecret' => 'testAccessKeySecret',
            'securityToken' => 'testSecurityToken',
        ]), time() + 3600);
        $this->provider->cache($refreshResult);

        $this->assertFalse($this->provider->cacheIsStale());
        $this->provider->clearCache();
    }

    public function testShouldInitiateCachePrefetch()
    {
        $this->assertTrue($this->provider->shouldInitiateCachePrefetch());

        $refreshResult = new RefreshResult(new Credentials([
            'accessKeyId' => 'testAccessKeyId',
            'accessKeySecret' => 'testAccessKeySecret',
            'securityToken' => 'testSecurityToken',
        ]), time() + 3600);
        $this->provider->cache($refreshResult);

        $this->assertFalse($this->provider->shouldInitiateCachePrefetch());
        $this->provider->clearCache();

        $refreshResult = new RefreshResult(new Credentials([
            'accessKeyId' => 'aaa',
            'accessKeySecret' => 'aaa',
            'securityToken' => 'aaa',
        ]), time() + 3600, time() - 3600);
        $this->provider->cache($refreshResult);

        $this->assertTrue($this->provider->shouldInitiateCachePrefetch());
        $this->provider->clearCache();
    }

    public function testGetStaleTime()
    {
        $this->assertEquals(2700, $this->provider->getStaleTime(3600));
        $this->assertEquals(time() + 3600, $this->provider->getStaleTime(0));
    }
}
