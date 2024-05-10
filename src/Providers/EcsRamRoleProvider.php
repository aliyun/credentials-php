<?php

namespace AlibabaCloud\Credentials\Providers;

use AlibabaCloud\Credentials\Helper;
use AlibabaCloud\Credentials\Request\Request;
use AlibabaCloud\Credentials\StsCredential;
use Exception;
use GuzzleHttp\Exception\GuzzleException;
use AlibabaCloud\Tea\Response;
use InvalidArgumentException;
use Psr\Http\Message\ResponseInterface;
use RuntimeException;

/**
 * Class EcsRamRoleProvider
 *
 * @package AlibabaCloud\Credentials\Providers
 */
class EcsRamRoleProvider extends Provider
{

    /**
     * Expiration time slot for temporary security credentials.
     *
     * @var int
     */
    protected $expirationSlot = 10;

    /**
     * refresh time for meta server token.
     *
     * @var int
     */
    private $staleTime = 0;

    /**
     * @var string
     */
    private $metadataHost = 'http://100.100.100.200';

    /**
     * @var string
     */
    private $metadataToken;

    /**
     * @var string
     */
    private $ecsUri = '/latest/meta-data/ram/security-credentials/';

    /**
     * @var string
     */
    private $metadataTokenUri = '/latest/api/token';

    /**
     * Get credential.
     *
     * @return StsCredential
     * @throws Exception
     * @throws GuzzleException
     */
    public function get()
    {
        $result = $this->getCredentialsInCache();

        if ($result === null) {
            $result = $this->request();

            if (!isset($result['AccessKeyId'], $result['AccessKeySecret'], $result['SecurityToken'])) {
                throw new RuntimeException($this->error);
            }

            $this->cache($result->toArray());
        }

        return new StsCredential(
            $result['AccessKeyId'],
            $result['AccessKeySecret'],
            strtotime($result['Expiration']),
            $result['SecurityToken']
        );
    }
    
    
    protected function getEnableECSIMDSv2()
    {
        if (Helper::envNotEmpty('ALIBABA_CLOUD_ECS_IMDSV2_ENABLE')) {
            return Helper::env('ALIBABA_CLOUD_ECS_IMDSV2_ENABLE') === true ? true : false;
        }
        if(isset($this->config['enableIMDSv2'])) {
            return $this->config['enableIMDSv2'];
        }
        return false;
    }

    /**
     * Get credentials by request.
     *
     * @return ResponseInterface
     * @throws Exception
     * @throws GuzzleException
     */
    public function request()
    {
        $credential = $this->credential;
        $url        = $this->metadataHost . $this->ecsUri . $credential->getRoleName();

        $options = [
            'http_errors'     => false,
            'timeout'         => 1,
            'connect_timeout' => 1,
        ];
        
        if ($this->getEnableECSIMDSv2()) {
            $this->refreshMetadataToken();
            $options['headers']['X-aliyun-ecs-metadata-token'] = $this->metadataToken; 
        }

        $result = Request::createClient()->request('GET', $url, $options);

        if ($result->getStatusCode() === 404) {
            $message = 'The role was not found in the instance';
            throw new InvalidArgumentException($message);
        }

        if ($result->getStatusCode() !== 200) {
            throw new RuntimeException('Error retrieving credentials from result: ' . $result->toJson());
        } 

        return $result;
    }

    /**
     * Get metadata token by request.
     *
     * @return ResponseInterface
     * @throws Exception
     * @throws GuzzleException
     */
    protected function refreshMetadataToken()
    {
        if(!$this->needToRefresh()) {
            return;
        }
        $credential = $this->credential;
        $url        = $this->metadataHost . $this->metadataTokenUri;
        $tmpTime = $this->staleTime;
        $this->staleTime = time() + $this->config['metadataTokenDuration'];
        $options = [
            'http_errors'     => false,
            'timeout'         => 1,
            'connect_timeout' => 1,
            'headers' => [
                'X-aliyun-ecs-metadata-token-ttl-seconds' => $this->config['metadataTokenDuration'],
            ],
        ];

        $result = Request::createClient()->request('PUT', $url, $options);

        if ($result->getStatusCode() != 200) {
            $this->staleTime = $tmpTime;
            throw new RuntimeException('Failed to get token from ECS Metadata Service. HttpCode= ' . $result->getStatusCode());
        }

        $this->metadataToken = $result->getBody();

        return;
    }


    /**
     * @return boolean
     */
    protected function needToRefresh()
    {
        return \time() >= $this->staleTime;
    }
}
