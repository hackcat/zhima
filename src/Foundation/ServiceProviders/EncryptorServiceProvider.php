<?php
/**
 * Created by PhpStorm.
 * User: hackc
 * Date: 2017-09-01
 * Time: 14:24
 */

namespace Hackcat\Zmxy\ServiceProviders;


use Hackcat\Zmxy\Foundation\Encryption\Encryptor;
use Pimple\Container;
use Pimple\ServiceProviderInterface;

class EncryptorServiceProvider implements ServiceProviderInterface
{
    public function register(Container $pimple)
    {
        $pimple['encryptor'] = function ($pimple) {
            return new Encryptor($pimple);
        };
    }
}