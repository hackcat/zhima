<?php
/**
 * Created by PhpStorm.
 * User: hackc
 * Date: 2017-09-01
 * Time: 14:19
 */

namespace Hackcat\Zmxy\Core;

use GuzzleHttp\Client as HttpClient;
use GuzzleHttp\HandlerStack;

class Http
{
    protected $client;

    protected $middlewares = [];

    protected static $defaults = [];

    public static function setDefaultOptions($defaults = [])
    {
        self::$defaults = $defaults;
    }

    public static function getDefaultOptions()
    {
        return self::$defaults;
    }

    public function get($url, array $options = [])
    {
        return $this->request($url, 'GET', ['query' => $options]);
    }

    public function post($url, $options = [])
    {
        $key = is_array($options) ? 'form_params' : 'body';

        return $this->request($url, 'POST', [$key => $options]);
    }

    public function json($url, $options = [], $encodeOption = JSON_UNESCAPED_UNICODE)
    {
        is_array($options) && $options = json_encode($options, $encodeOption);

        return $this->request($url, 'POST', ['body' => $options, 'headers' => ['content-type' => 'application/json']]);
    }

    public function upload($url, array $files = [], array $form = [], array $queries = [])
    {
        $multipart = [];

        foreach ($files as $name => $path) {
            $multipart[] = [
                'name' => $name,
                'contents' => fopen($path, 'r'),
            ];
        }

        foreach ($form as $name => $contents) {
            $multipart[] = compact('name', 'contents');
        }

        return $this->request($url, 'POST', ['query' => $queries, 'multipart' => $multipart]);
    }

    public function setClient(HttpClient $client)
    {
        $this->client = $client;

        return $this;
    }

    public function getClient()
    {
        if (!($this->client instanceof HttpClient)) {
            $this->client = new HttpClient();
        }

        return $this->client;
    }

    public function addMiddleware(callable $middleware)
    {
        array_push($this->middlewares, $middleware);

        return $this;
    }

    public function getMiddlewares()
    {
        return $this->middlewares;
    }

    public function request($url, $method = 'GET', $options = [])
    {
        $method = strtoupper($method);

        $options = array_merge(self::$defaults, $options);

        //Log::debug('Client Request:', compact('url', 'method', 'options'));

        $options['handler'] = $this->getHandler();

        $response = $this->getClient()->request($method, $url, $options);

//        Log::debug('API response:', [
//            'Status' => $response->getStatusCode(),
//            'Reason' => $response->getReasonPhrase(),
//            'Headers' => $response->getHeaders(),
//            'Body' => strval($response->getBody()),
//        ]);

        return $response->getBody();
    }

    protected function getHandler()
    {
        $stack = HandlerStack::create();

        foreach ($this->middlewares as $middleware) {
            $stack->push($middleware);
        }

        return $stack;
    }
}