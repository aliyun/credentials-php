<?php

namespace AlibabaCloud\Credentials\Providers;

use Exception;
use RuntimeException;
use GuzzleHttp\Exception\GuzzleException;
use AlibabaCloud\Credentials\StsCredential;
use AlibabaCloud\Credentials\Request\AssumeRole;

class RamRoleArnProvider extends Provider
{

    /**
     * Get credential.
     *
     * @return StsCredential
     * @throws Exception
     * @throws GuzzleException
     */
    public function get()
    {
        $credential = $this->getCredentialsInCache();

        if (null === $credential) {
            $result = (new AssumeRole($this->credential))->request();

            if ($result->getStatusCode() !== 200) {
                throw new RuntimeException(isset($result['Message']) ? $result['Message'] : (string)$result->getBody());
            }

            if (!isset($result['Credentials']['AccessKeyId'],
                $result['Credentials']['AccessKeySecret'],
                $result['Credentials']['SecurityToken'])) {
                throw new RuntimeException($this->error);
            }

            $credential = $result['Credentials'];
            $this->cache($credential);
        }

        return new StsCredential(
            $credential['AccessKeyId'],
            $credential['AccessKeySecret'],
            strtotime($credential['Expiration']),
            $credential['SecurityToken']
        );
    }
}
