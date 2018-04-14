<?php

namespace Partnerly;

use GuzzleHttp\Client;
use Partnerly\Exceptions\InvalidCodeException;
use Partnerly\Exceptions\InvalidConnection;
use Partnerly\Exceptions\NotApplicableException;
use Partnerly\Exceptions\NotFoundException;

class Partnerly
{
    private $client;
    private $partnerId;
    private $applier;
    private $validator;

    const METHOD_GET = 'GET';
    const METHOD_POST = 'POST';

    /**
     * @param ApplierInterface $applier
     * @param ValidatorInterface $validator
     * @param string $host
     * @param string $partnerId
     * @param string $secret
     */
    public function __construct(ApplierInterface $applier, ValidatorInterface $validator, $host, $partnerId, $secret)
    {
        $this->partnerId = $partnerId;
        $httpClient = new Client([
            'base_uri' => $host,
            'http_errors' => false
        ]);
        $this->client = new SecurityClient($httpClient, $partnerId, $secret);
        $this->applier = $applier;
        $this->validator = $validator;
    }

    /**
     * @param SecurityClient $securityClient
     * @return $this
     */
    public function setSecurityClient(SecurityClient $securityClient)
    {
        $this->client = $securityClient;
        return $this;
    }

    /**
     * @param string $eventId
     * @param string $customer
     * @param string $commission
     * @param string $referral
     * @return mixed
     */
    public function addCommission($eventId, $customer, $commission, $referral)
    {
        $response = $this->client->sendRequest('POST', 'commission', [
            'event_id' => $eventId,
            'referral' => $referral,
            'customer' => $customer,
            'commission' => $commission,
        ]);
        $statusCode = $response->getStatusCode();
        return json_decode($content = $response->getBody()->getContents(), true);
    }

    /**
     * @param string $codeString
     * @param Context $context
     * @param bool $skipValidation
     * @return PromoCode
     */
    public function useCode($codeString, Context $context, $skipValidation = false) {
        $promoCode = $skipValidation ? $this->getCode($codeString) : $this->validate($codeString, $context);
        $this->useCodeRequest($codeString, $context->id);
        $this->applier->apply($promoCode, $context);
        return $promoCode;
    }

    /**
     * @param string $ip
     * @return string
     */
    public function getCodeByIp($ip) {
        $ip = urlencode($ip);
        $response = $this->client->sendRequest('GET', "code/view/$ip");
        $result = json_decode($content = $response->getBody()->getContents(), true);
        return $result['code'] ?? null;
    }

    /**
     * @param $code
     * @param $contextId
     * @return mixed
     */
    public function useCodeRequest($code, $contextId)
    {
        return $this->sendPostRequest('code-usage', [
            'context' => $contextId,
            'code' => $code,
        ]);
    }

    /**
     * @param string $codeString
     * @param Context $context
     * @return null|PromoCode
     * @throws InvalidCodeException
     * @throws InvalidConnection
     * @throws NotApplicableException
     * @throws NotFoundException
     */
    public function validate($codeString, Context $context) {
        $usage = $this->getCodeUsage($codeString, $context);
        if (!empty($usage)) {
            throw new NotApplicableException("The code has been used already.");
        }

        try {
            $code = $this->getCode($codeString);
        } catch (NotFoundException $ex) {
            throw new NotFoundException(sprintf("Code [%s] not found.", $codeString));
        }
        $this->validator->validate($code, $context);
        return $code;
    }

    /**
     * @param string $codeString
     * @param Context $context
     * @return mixed
     * @throws InvalidCodeException
     * @throws InvalidConnection
     * @throws NotFoundException
     */
    public function getCodeUsage($codeString, Context $context) {
        $request = sprintf("code-usage/%s/%s/%s", $this->partnerId, $codeString, $context->id);
        return $response = $this->sendGETRequest($request);
    }

    /**
     * @param string $codeString
     * @return PromoCode
     * @throws InvalidCodeException
     * @throws InvalidConnection
     * @throws NotFoundException
     */
    public function getCode($codeString) {
        $request = sprintf("code/%s/%s", $this->partnerId, $codeString);
        $result = $this->sendGETRequest($request);
        return new PromoCode($result);
    }

    /**
     * @param $method
     * @param $request
     * @return mixed
     * @throws InvalidCodeException
     * @throws InvalidConnection
     * @throws NotFoundException
     */
    private function sendGETRequest($request) {
        $result = $this->client->sendRequest(self::METHOD_GET, $request);
        return $this->processResponse($result);
    }

    /**
     * @param string $uri
     * @param array $args
     * @return mixed
     * @throws InvalidCodeException
     * @throws InvalidConnection
     * @throws NotFoundException
     */
    private function sendPostRequest($uri, $args) {
        $result = $this->client->sendRequest(self::METHOD_POST, $uri, $args);
        return $this->processResponse($result);
    }

    /**
     * @param \Psr\Http\Message\ResponseInterface $result
     * @return mixed
     * @throws InvalidCodeException
     * @throws InvalidConnection
     * @throws NotFoundException
     */
    private function processResponse($result) {
        $statusCode = $result->getStatusCode();
        $response = $result->getBody()->getContents();
        if ($statusCode >= 200 && $statusCode < 300) {
            return json_decode($response, true);
        }

        if ($statusCode == 404) {
            throw new NotFoundException("Not found");
        }

        if ($statusCode == 400) {
            $result = json_decode($response, true);
            $error = $result['error'] ?? $response;
            throw new InvalidCodeException("Invalid usage: {$error}");
        }

        throw new InvalidConnection("Server error: $statusCode, $response");
    }
}
