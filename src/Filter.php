<?php

namespace AlibabaCloud\Credentials;

use InvalidArgumentException;

class Filter
{

    /**
     * @param $name
     *
     * @codeCoverageIgnore
     * @return string
     */
    public static function credentialName($name)
    {
        if (!is_string($name)) {
            throw new InvalidArgumentException('Name must be a string');
        }

        if ($name === '') {
            throw new InvalidArgumentException('Name cannot be empty');
        }

        return $name;
    }

    /**
     * @param $bearerToken
     *
     * @return mixed
     * @throws InvalidArgumentException
     */
    public static function bearerToken($bearerToken)
    {
        if (!is_string($bearerToken)) {
            throw new InvalidArgumentException('bearer_token must be a string');
        }

        if ($bearerToken === '') {
            throw new InvalidArgumentException('bearer_token cannot be empty');
        }

        return $bearerToken;
    }

    /**
     * @param $publicKeyId
     *
     * @return mixed
     */
    public static function publicKeyId($publicKeyId)
    {
        if (!is_string($publicKeyId)) {
            throw new InvalidArgumentException('public_key_id must be a string');
        }

        if ($publicKeyId === '') {
            throw new InvalidArgumentException('public_key_id cannot be empty');
        }

        return $publicKeyId;
    }

    /**
     * @param $privateKeyFile
     *
     * @return mixed
     */
    public static function privateKeyFile($privateKeyFile)
    {
        if (!is_string($privateKeyFile)) {
            throw new InvalidArgumentException('private_key_file must be a string');
        }

        if ($privateKeyFile === '') {
            throw new InvalidArgumentException('private_key_file cannot be empty');
        }

        return $privateKeyFile;
    }

    /**
     * @param string|null $role_name
     */
    public static function roleName($role_name)
    {
        if ($role_name === null) {
            return;
        }

        if (!is_string($role_name)) {
            throw new InvalidArgumentException('role_name must be a string');
        }

        if ($role_name === '') {
            throw new InvalidArgumentException('role_name cannot be empty');
        }
    }

    /**
     * @param boolean|null $disable_IMDS_v1
     */
    public static function disableIMDSv1($disable_imdsv1)
    {
        if (!is_bool($disable_imdsv1)) {
            throw new InvalidArgumentException('disable_IMDS_v1 must be a boolean');
        }
    }


    public static function metadataTokenDuration($metadata_token_duration) {
        if (!is_int($metadata_token_duration)) {
            throw new InvalidArgumentException('metadata_token_duration must be a int');
        }
    }

    /**
     * @param string $accessKeyId
     * @param string $accessKeySecret
     */
    public static function accessKey($accessKeyId, $accessKeySecret)
    {
        if (!is_string($accessKeyId)) {
            throw new InvalidArgumentException('access_key_id must be a string');
        }

        if ($accessKeyId === '') {
            throw new InvalidArgumentException('access_key_id cannot be empty');
        }

        if (!is_string($accessKeySecret)) {
            throw new InvalidArgumentException('access_key_secret must be a string');
        }

        if ($accessKeySecret === '') {
            throw new InvalidArgumentException('access_key_secret cannot be empty');
        }
    }

    /**
     * @param int $expiration
     */
    public static function expiration($expiration)
    {
        if (!is_int($expiration)) {
            throw new InvalidArgumentException('expiration must be a int');
        }
    }
}
