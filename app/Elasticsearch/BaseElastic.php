<?php

namespace App\Elasticsearch;

use Elasticsearch\ClientBuilder;
use Exception;

class BaseElastic
{
    private $clientBuilder = null;

    protected string $indexName;

    public function __construct()
    {
        if (!$this->clientBuilder) {
            try {
	            $parseUrl = parse_url(env('ELASTIC_HOST'));
	            $hosts = [
		            [
			            'host' => $parseUrl['host'],
			            'port' => $parseUrl['port'],
			            'user' => $parseUrl['user'],
			            'pass' => $parseUrl['pass']
		            ]
	            ];
	            $this->clientBuilder = ClientBuilder::create()
	            ->setHosts($hosts)
	            ->build();
            } catch (Exception $e) {
                echo 'Can not connect ES';
            }
        }
    }

    public function getClientBuilder()
    {
        return $this->clientBuilder;
    }
}
