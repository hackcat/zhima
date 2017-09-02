<?php
/**
 * Created by PhpStorm.
 * User: hackc
 * Date: 2017-09-01
 * Time: 15:38
 */

namespace Hackcat\Zhima\ServiceProviders;


use Hackcat\Zhima\Certification\Certification;
use Pimple\Container;
use Pimple\ServiceProviderInterface;

class CertificationServiceProvider implements ServiceProviderInterface
{
    public function register(Container $pimple)
    {
        $pimple['auth'] = function ($pimple) {
            return new Certification($pimple);
        };
    }
}