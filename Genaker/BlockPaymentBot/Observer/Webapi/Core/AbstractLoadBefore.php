<?php
/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Genaker\BlockPaymentBot\Observer\Webapi\Core;

class AbstractLoadBefore implements \Magento\Framework\Event\ObserverInterface
{
    
    // Execute only once per request ...
    protected $flag = false;

    /**
     * Execute observer
     *
     * @param \Magento\Framework\Event\Observer $observer
     * @return void
     */
    public function execute(
        \Magento\Framework\Event\Observer $observer
    ) {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || $this->flag === true) {
            return 0;
        }


        // We are usin native Redis we are not using Magento Broken Framework
        // if you don't have native redis instaleed this extension will not work
        if (!class_exists('\Redis')) {
            return 0;
        }

        $this->flag = true;
        
        try {
            $re = '/\/rest\/default\/V1\/guest-carts\/(.*)\/payment-information/i';

            preg_match($re, $_SERVER['REQUEST_URI'], $matches, PREG_OFFSET_CAPTURE, 0);

            //var_dump($_SERVER); die();

            if (count($matches) > 0) {
                $ip = $_SERVER['REMOTE_ADDR'];
                $cartId = $matches[1][0];
        
                if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
                    $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
                }
        
                if (isset($_SERVER['FASTLY-CLIENT-IP'])) {
                    $ip = $_SERVER['FASTLY-CLIENT-IP'];
                }

                $config = require BP.'/app/etc/env.php';

                $redis = new \Redis();

                $redis->pconnect(
                    $config['cache']['frontend']['default']['backend_options']['server'],
                    (int)$config['cache']['frontend']['default']['backend_options']['port'],
                    0,
                    'cache'
                );

                $counter = $redis->get('Cart_'.$cartId);
                $previousIP = $redis->get('Cart_'.$cartId.'IP');
                //die($previousIP);
                // If the cheater changed IP address we are blocking that guy right away
                if ($previousIP !== $ip && $previousIP != null) {
                    die("cheater");
                }

                if ($counter === null) {
                    $counter = 0;
                }
                if ($counter > 20) {
                    die("By!");
                }

                $redis->set('Cart_'.$cartId, ++$counter, 60 * 2);
                $redis->set('Cart_'.$cartId.'IP', $ip, 60 * 2);
            }
        } catch (\Throwable $e) {
            die("Custom Function Error -> " . $e->getMessage());
        }
    }
}
