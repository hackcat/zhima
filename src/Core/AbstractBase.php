<?php
/**
 * Created by PhpStorm.
 * User: hackc
 * Date: 2017-09-01
 * Time: 15:11
 */

namespace Hackcat\Zhima\Core;


use Hackcat\Zhima\Support\Collection;
use Hackcat\Zhima\Support\WebUtil;
use Hackcat\Zhima\Foundation\Encryption\Encryptor;
use Pimple\Container;

abstract class AbstractBase
{
    protected $application = null;

    protected $gateway = 'https://zmopenapi.zmxy.com.cn/openapi.do';

    protected $http = null;

    public $sys_attributes = [
        'app_id'    => '',
        'scene'     => '',
        'charset'   => 'UTF-8',
        'method'    => '',
        'version'   => '1.0',
        'channel'   => '',
        'platform'  => 'zmop',
        'params'    => '',
        'sign'      => '',
        'ext_params'=> '',
    ];

    public $biz_attributes = [];

    public function __construct(Container $application)
    {
        $this->application = $application;
        $config = $application['config'];
        $this->app_id = $config['app_id'];
    }

    public function __get($name)
    {
        $getter = 'get' . $name;
        if (method_exists($this,$getter)) {
            return $this->$getter;
        } elseif (
            isset($this->sys_attributes[$name]) ||
            isset($this->biz_attributes[$name])
        ) {
            return $this->getAttribute($name);
        }
    }

    public function __set($name, $value)
    {
        $setter = 'set' . $name;
        if (method_exists($this,$setter)) {
            return $this->$setter($value);
        } elseif (
            isset($this->sys_attributes[$name]) ||
            isset($this->biz_attributes[$name])
        ){
            return $this->setAttribute($name, $value);
        }

        throw new Exception('Property ' . get_class($this) . '.' . $name . 'is not defined');
    }

    public function __call($name, $params) {
        if (strncasecmp($name, 'get', 3) === 0) {
            $attribute = strtolower(substr($name, 3));
            if (
                isset($this->sys_attributes[$attribute]) ||
                isset($this->biz_attributes[$attribute])
            ) {
                return $this->getAttribute($attribute);
            }
        } else if (strncasecmp($name, 'set', 3) === 0) {
            $attribute = strtolower(substr($name, 3));
            if (
                isset($this->sys_attributes[$attribute]) ||
                isset($this->biz_attributes[$attribute])
            ) {
                return $this->setAttribute($attribute, $params[0]);
            }
        }
        throw new Exception('Method ' . get_class($this) . '.' . $name . 'is not defined');
    }

    public function getAttribute($name)
    {
        if (isset($this->sys_attributes[$name])) {
            return $this->sys_attributes[$name];
        } elseif (isset($this->biz_attributes[$name])) {
            return $this->biz_attributes[$name];
        }
        return null;
    }

    public function setAttribute($name,$value)
    {
        if (isset($this->sys_attributes[$name])) {
            $this->sys_attributes[$name] = $value;
            return $this;
        } elseif (isset($this->biz_attributes[$name])) {
            $this->biz_attributes[$name] = $value;
            return $this;
        }

        return false;
    }

    public function getBizAttributesStr() {
        return WebUtil::buildQueryWithEncode($this->biz_attributes);
    }

    public function getHttp()
    {
        if (is_null($this->http)) {
            $this->http = new Http();
        }

        return $this->http;
    }

    public function setHttp(Http $http)
    {
        $this->http = $http;

        return $this;
    }

    protected function getUrl() {

        $url = $this->gateway . '?' . $this->getRequestString();
        return $url;
    }

    public function getRequestString() {
        $biz_query = $this->getBizAttributesStr();

        /** @var Encryptor $encryptor */
        $encryptor = $this->application->encryptor;
        $this->sign = $encryptor->sign($biz_query);
        $this->params = $encryptor->rsaEncrypt($biz_query);

        return WebUtil::buildQueryWithEncode($this->sys_attributes);
    }

    /**
     * 获取请求参数
     */
    public function getRequestParams() {
        $biz_query = $this->getBizAttributesStr();

        /** @var Encryptor $encryptor */
        $encryptor = $this->application->encryptor;
        $this->sign = $encryptor->sign($biz_query);
        $this->params = $encryptor->rsaEncrypt($biz_query);

        return WebUtil::trim($this->sys_attributes);
    }

    public function get() {
        $result = $this->getHttp()->get($this->gateway, $this->getRequestParams());
        return $this->parse($result);
    }

    public function post() {
        $result = $this->getHttp()->post($this->gateway, $this->getRequestParams());
        return $this->parse($result);
    }

    public function json() {
        $result = $this->getHttp()->json($this->gateway, $this->getRequestParams());
        return $this->parse($result);
    }

    /**
     * 解析返回结果，比如是不是需要解密什么的
     */
    public function parse($result) {
        $result = json_decode($result, true);
        if ($result['encrypted'] == false) {
            $result = $result['biz_response'];
            return new Collection(json_decode($result, true));
        } else {
            /** @var Encryptor $encryptor */
            $encryptor = $this->application->encryptor;
            $response = $result['biz_response'];
            $sign = $result['biz_response_sign'];
            $result = $encryptor->rsaDecrypt($response);
            if ($encryptor->verify($result, $sign)) {
                return new Collection(json_decode($result, true));
            } else {
                throw Exception('签名错误');
            }
        }

    }
}