<?php
/**
 * Created by PhpStorm.
 * User: hackc
 * Date: 2017-09-01
 * Time: 15:11
 */

namespace Hackcat\Zhima\Certification;


use Hackcat\Zhima\Core\AbstractBase;
use Pimple\Container;

class Certification extends AbstractBase
{
    public $biz_attributes = [
        'cert_type'      =>  '',
        'biz_code'       =>  '',
        'identity_type'  =>  '',
        'cert_name'      =>  '',
        'cert_no'        =>  '',
        'identity_param' => '',
        'return_url'     => '',
    ];

    public function __construct(Container $application)
    {
        parent::__construct($application);
    }

    public function getBizNo()
    {
        $request = new ZhimaCustomerCertificationInitializeRequest();
        $request->setChannel("apppc");
        $request->setPlatform("zmop");
        $date = date("YmdHis");
        $request->setTransactionId("ZGYD{$date}23000001234");// 必要参数
        $request->setProductCode("w1010100000000002978");// 必要参数
        $request->setBizCode("FACE");// 必要参数
        $request->setIdentityParam($this->identity_type);
        $request->setMerchantConfig("{\"need_user_authorization\":\"false\"}");//
        $request->setExtBizParam("{}");// 必要参数

        $this->method = $request->getApiMethodName();
        $this->biz_attributes = $request->getApiParas();

        $data = $this->get();
        return $data;
    }


    public function getCertUrl()
    {
        $bizNo = $this->getBizNo();
        $request = new ZhimaCustomerCertificationCertifyRequest();
        $request->setChannel("apppc");
        $request->setPlatform("zmop");
        $request->setBizNo($bizNo);
        $request->setReturnUrl($this->return_url);

        $this->method = $request->getApiMethodName();
        $this->channel = 'appsdk';
        $this->biz_attributes = $request->getApiParas();

        $data = $this->getUrl();
        return $data;
    }
}