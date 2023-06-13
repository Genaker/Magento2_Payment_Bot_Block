<?php
/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Genaker\BlockPaymentBot\Observer\Webapi\Core;

use Psr\Log\LoggerInterface;

class AbstractLoadBefore implements \Magento\Framework\Event\ObserverInterface
{
    // Execute only once per request
    protected $flag = false;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @param LoggerInterface $logger
     */
    public function __construct(
        LoggerInterface $logger
    ) {
        $this->logger = $logger;
    }

    /**
     * @param \Magento\Framework\Event\Observer $observer
     * @return int|void
     */
    public function execute(
        \Magento\Framework\Event\Observer $observer
    ) {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || $this->flag === true) {
            return 0;
        }

        // if you don't have native redis instaleed this extension will not work
        if (!class_exists('\Redis')) {
            return 0;
        }

        $this->flag = true;

        try {
            $re = '/\/rest\/default\/V1\/guest-carts\/(.*)\/payment-information/i';

            preg_match($re, $_SERVER['REQUEST_URI'], $matches, PREG_OFFSET_CAPTURE, 0);

            if (count($matches) > 0) {
                $config = require BP . '/app/etc/env.php';

                // Skip if Redis Cache is not set up in env.php
                if (!isset($config['cache']['frontend']['default']['backend_options']['server']) ||
                    !isset($config['cache']['frontend']['default']['backend_options']['port'])
                ) {
                    return 0;
                }

                // Get customer Cart Id
                $cartId = trim($matches[1][0]);

                // Get customer Ip address
                if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
                    $ips = $_SERVER['HTTP_CLIENT_IP'];
                } elseif (!empty($_SERVER['FASTLY-CLIENT-IP'])) {
                    $ips = $_SERVER['FASTLY-CLIENT-IP'];
                } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
                    $ips = $_SERVER['HTTP_X_FORWARDED_FOR'];
                } elseif (!empty($_SERVER['HTTP_X_REAL_IP'])) {
                    $ips = $_SERVER['HTTP_X_REAL_IP'];
                } else {
                    $ips = $_SERVER['REMOTE_ADDR'];
                }

                // We may have comma separated list
                $ip = trim(count(explode(',', (string)$ips)) > 0 ? explode(',', (string)$ips)[0] : $ips);

                if (empty($cartId) || empty($ip)) {
                    $this->logger->error("Genaker_BlockPaymentBot::AbstractLoadBefore observer logical error: ip: " . $ip . ",  or cartId: " . $cartId ." are empty");
                    return 0;
                }

                $redis = new \Redis();

                $redis->pconnect(
                    $config['cache']['frontend']['default']['backend_options']['server'],
                    (int)$config['cache']['frontend']['default']['backend_options']['port'],
                    0,
                    'cache'
                );

                // If the cheater changed IP address we are blocking that guy right away
                $previousIP = $redis->get('Cart_' . $cartId . 'IP');
                if ($previousIP !== $ip && $previousIP != null) {
                    $this->logger->error("Genaker_BlockPaymentBot::AbstractLoadBefore cheater detected, ip: " . $ip . ", previousIP: " . $previousIP . ", cartId: " . $cartId);
                    die("Cheater?");
                }

                $counter = $redis->get('Cart_' . $cartId);
                if (!$counter || $counter == '') {
                    $counter = 0;
                }
                if ($counter > 20) {
                    $this->logger->error("Genaker_BlockPaymentBot::AbstractLoadBefore sent bye, ip: " . $ip . ", cartId: " . $cartId);
                    die("Bye!");
                }

                $redis->set('Cart_' . $cartId, ++$counter, 60 * 2);
                $redis->set('Cart_' . $cartId . 'IP', $ip, 60 * 2);
            }
        } catch (\Throwable $e) {
            $this->logger->error("Genaker_BlockPaymentBot::AbstractLoadBefore observer error: " . $e->getMessage());
        }
    }
}
