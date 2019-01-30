<?php

namespace Partnerly;

use GuzzleHttp\ClientInterface;

class SecurityClient
{
    private $client;
    private $partner;
    private $secret;

    /**
     * @param ClientInterface $client
     * @param $partner
     * @param $secret
     */
    public function __construct(ClientInterface $client, $partner, $secret)
    {
        $this->client = $client;
        $this->partner = $partner;
        $this->secret = $secret;
    }

    /**
     * @param $method
     * @param $uri
     * @param $data
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function sendRequest($method, $uri, $data = null)
    {
        $sign = md5(json_encode($data, true) . $this->secret);
        $signature = "partner=$this->partner, sign=$sign";
        $headers = [
            'Authorization' => sprintf('PRTN-SGN %s', $signature),
        ];

        return $this->client->request($method, $uri, [
            'headers' => $headers,
            'verify' => false,
            'json' => $data
        ]);
    }

}