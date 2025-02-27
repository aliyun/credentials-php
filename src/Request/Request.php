<?php

namespace AlibabaCloud\Credentials\Request;

use AlibabaCloud\Credentials\Credentials;
use AlibabaCloud\Credentials\Utils\Helper;
use GuzzleHttp\Client;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use AlibabaCloud\Tea\Response;
use Psr\Http\Message\ResponseInterface;

use Exception;
use InvalidArgumentException;

/**
 * RESTful RPC Request.
 */
class Request
{

    /**
     * Request Connect Timeout
     */
    const CONNECT_TIMEOUT = 5;

    /**
     * Request Read Timeout
     */
    const READ_TIMEOUT = 5;

    /**
     * @var array
     */
    private static $config = [];


    /**
     *
     * @return array
     */
    public static function commonOptions()
    {
        $options = [];
        $options['http_errors'] = false;
        $options['connect_timeout'] = self::CONNECT_TIMEOUT;
        $options['read_timeout'] = self::READ_TIMEOUT;
        $options['headers']['User-Agent'] = Helper::getUserAgent();

        // Turn on debug mode based on environment variable.
        if (strtolower(Helper::env('DEBUG')) === 'sdk') {
            $options['debug'] = true;
        }
        return $options;
    }

    /**
     * @param string $salt
     *
     * @return string
     */
    public static function uuid($salt)
    {
        return md5($salt . uniqid(md5(microtime(true)), true));
    }

    /**
     * @param string $method
     * @param array  $parameters
     *
     * @return string
     */
    public static function signString($method, array $parameters)
    {
        ksort($parameters);
        $canonicalized = '';
        foreach ($parameters as $key => $value) {
            $canonicalized .= '&' . self::percentEncode($key) . '=' . self::percentEncode($value);
        }

        return $method . '&%2F&' . self::percentEncode(substr($canonicalized, 1));
    }

    public function getAuthorization($pathname, $method, $query, $headers, $signatureAlgorithm, $payload, $ak, $signingkey, $product, $region, $date)
    {
        $signature = $this->getSignature($pathname, $method, $query, $headers, $signatureAlgorithm, $payload, $signingkey);
        $signedHeaders = $this->getSignedHeaders($headers);
        $signedHeadersStr = ArrayUtil::join($signedHeaders, ";");
        return "" . $signatureAlgorithm . " Credential=" . $ak . "/" . $date . "/" . $region . "/" . $product . "/" . $this->_signPrefix . "_request,SignedHeaders=" . $signedHeadersStr . ",Signature=" . $signature . "";
    }

    /**
     * @param string $pathname
     * @param string $method
     * @param string[] $query
     * @param string[] $headers
     * @param string $signatureAlgorithm
     * @param string $payload
     * @param int[] $signingkey
     * @return string
     */
    public function getSignature($pathname, $method, $query, $headers, $signatureAlgorithm, $payload, $signingkey)
    {
        $canonicalURI = "/";
        if (!Utils::empty_($pathname)) {
            $canonicalURI = $pathname;
        }
        $stringToSign = "";
        $canonicalizedResource = $this->buildCanonicalizedResource($query);
        $canonicalizedHeaders = $this->buildCanonicalizedHeaders($headers);
        $signedHeaders = $this->getSignedHeaders($headers);
        $signedHeadersStr = ArrayUtil::join($signedHeaders, ";");
        $stringToSign = "" . $method . "\n" . $canonicalURI . "\n" . $canonicalizedResource . "\n" . $canonicalizedHeaders . "\n" . $signedHeadersStr . "\n" . $payload . "";
        $hex = EncodeUtil::hexEncode(EncodeUtil::hash(Utils::toBytes($stringToSign), $signatureAlgorithm));
        $stringToSign = "" . $signatureAlgorithm . "\n" . $hex . "";
        $signature = Utils::toBytes("");
        if (Utils::equalString($signatureAlgorithm, $this->_sha256)) {
            $signature = SignatureUtil::HmacSHA256SignByBytes($stringToSign, $signingkey);
        } else if (Utils::equalString($signatureAlgorithm, $this->_sm3)) {
            $signature = SignatureUtil::HmacSM3SignByBytes($stringToSign, $signingkey);
        }
        return EncodeUtil::hexEncode($signature);
    }

    /**
     * @param string $signatureAlgorithm
     * @param string $secret
     * @param string $product
     * @param string $region
     * @param string $date
     * @return array
     */
    public function getSigningkey($signatureAlgorithm, $secret, $product, $region, $date)
    {
        $sc1 = "" . $this->_signPrefix . "" . $secret . "";
        $sc2 = Utils::toBytes("");
        if (Utils::equalString($signatureAlgorithm, $this->_sha256)) {
            $sc2 = SignatureUtil::HmacSHA256Sign($date, $sc1);
        } else if (Utils::equalString($signatureAlgorithm, $this->_sm3)) {
            $sc2 = SignatureUtil::HmacSM3Sign($date, $sc1);
        }
        $sc3 = Utils::toBytes("");
        if (Utils::equalString($signatureAlgorithm, $this->_sha256)) {
            $sc3 = SignatureUtil::HmacSHA256SignByBytes($region, $sc2);
        } else if (Utils::equalString($signatureAlgorithm, $this->_sm3)) {
            $sc3 = SignatureUtil::HmacSM3SignByBytes($region, $sc2);
        }
        $sc4 = Utils::toBytes("");
        if (Utils::equalString($signatureAlgorithm, $this->_sha256)) {
            $sc4 = SignatureUtil::HmacSHA256SignByBytes($product, $sc3);
        } else if (Utils::equalString($signatureAlgorithm, $this->_sm3)) {
            $sc4 = SignatureUtil::HmacSM3SignByBytes($product, $sc3);
        }
        $hmac = Utils::toBytes("");
        if (Utils::equalString($signatureAlgorithm, $this->_sha256)) {
            $hmac = SignatureUtil::HmacSHA256SignByBytes("" . $this->_signPrefix . "_request", $sc4);
        } else if (Utils::equalString($signatureAlgorithm, $this->_sm3)) {
            $hmac = SignatureUtil::HmacSM3SignByBytes("" . $this->_signPrefix . "_request", $sc4);
        }
        return $hmac;
    }

    /**
     * @param string $product
     * @param string $endpoint
     * @param string $regionId
     * @return string
     */
    public function getRegion($product, $endpoint, $regionId)
    {
        if (!Utils::empty_($regionId)) {
            return $regionId;
        }
        $region = "center";
        if (Utils::empty_($product) || Utils::empty_($endpoint)) {
            return $region;
        }
        $strs = StringUtil::split($endpoint, ":", null);
        $withoutPort = @$strs[0];
        $preRegion = StringUtil::replace($withoutPort, "." . $this->_endpointSuffix . "", "", null);
        $nodes = StringUtil::split($preRegion, ".", null);
        if (Utils::equalNumber(ArrayUtil::size($nodes), 2)) {
            $region = @$nodes[1];
        }
        return $region;
    }

    /**
     * @param string[] $query
     * @return string
     */
    public function buildCanonicalizedResource($query)
    {
        $canonicalizedResource = "";
        if (!Utils::isUnset($query)) {
            $queryArray = MapUtil::keySet($query);
            $sortedQueryArray = ArrayUtil::ascSort($queryArray);
            $separator = "";
            foreach ($sortedQueryArray as $key) {
                $canonicalizedResource = "" . $canonicalizedResource . "" . $separator . "" . EncodeUtil::percentEncode($key) . "";
                if (!Utils::empty_(@$query[$key])) {
                    $canonicalizedResource = "" . $canonicalizedResource . "=" . EncodeUtil::percentEncode(@$query[$key]) . "";
                }
                $separator = "&";
            }
        }
        return $canonicalizedResource;
    }

    /**
     * @param string[] $headers
     * @return string
     */
    public function buildCanonicalizedHeaders($headers)
    {
        $canonicalizedHeaders = "";
        $sortedHeaders = $this->getSignedHeaders($headers);
        foreach ($sortedHeaders as $header) {
            $canonicalizedHeaders = "" . $canonicalizedHeaders . "" . $header . ":" . StringUtil::trim(@$headers[$header]) . "\n";
        }
        return $canonicalizedHeaders;
    }

    /**
     * @param string[] $headers
     * @return array
     */
    public function getSignedHeaders($headers)
    {
        $headersArray = MapUtil::keySet($headers);
        $sortedHeadersArray = ArrayUtil::ascSort($headersArray);
        $tmp = "";
        $separator = "";
        foreach ($sortedHeadersArray as $key) {
            $lowerKey = StringUtil::toLower($key);
            if (StringUtil::hasPrefix($lowerKey, "x-acs-") || StringUtil::equals($lowerKey, "host") || StringUtil::equals($lowerKey, "content-type")) {
                if (!StringUtil::contains($tmp, $lowerKey)) {
                    $tmp = "" . $tmp . "" . $separator . "" . $lowerKey . "";
                    $separator = ";";
                }
            }
        }
        return StringUtil::split($tmp, ";", null);
    }

    /**
     * @param string $string
     * @param string $accessKeySecret
     *
     * @return string
     */
    public static function shaHmac1sign($string, $accessKeySecret)
    {
        return base64_encode(hash_hmac('sha1', $string, $accessKeySecret, true));
    }

    /**
     * @param string $string
     * @param string $accessKeySecret
     *
     * @return string
     */
    public static function shaHmac256sign($string, $accessKeySecret)
    {
        return base64_encode(hash_hmac('sha256', $string, $accessKeySecret, true));
    }

    /**
     * @param string $string
     * @param string $privateKey
     *
     * @return string
     */
    public static function shaHmac256WithRsasign($string, $privateKey)
    {
        $binarySignature = '';
        try {
            openssl_sign(
                $string,
                $binarySignature,
                $privateKey,
                \OPENSSL_ALGO_SHA256
            );
        } catch (Exception $exception) {
            throw new InvalidArgumentException(
                $exception->getMessage()
            );
        }

        return base64_encode($binarySignature);
    }

    /**
     * @param string $string
     *
     * @return null|string|string[]
     */
    private static function percentEncode($string)
    {
        $result = rawurlencode($string);
        $result = str_replace(['+', '*'], ['%20', '%2A'], $result);
        $result = preg_replace('/%7E/', '~', $result);

        return $result;
    }

    /**
     * @return Client
     * @throws Exception
     */
    public static function createClient()
    {
        if (Credentials::hasMock()) {
            $stack = HandlerStack::create(Credentials::getMock());
            $history = Credentials::getHandlerHistory();
            $stack->push($history);
        } else {
            $stack = HandlerStack::create();
        }

        $stack->push(Middleware::mapResponse(static function (ResponseInterface $response) {
            return new Response($response);
        }));

        self::$config['handler'] = $stack;

        return new Client(self::$config);
    }
}
