<?php
/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */
declare (strict_types = 1);

namespace Genaker\BlockPaymentBot\Observer\Webapi\Core;

use Psr\Log\LoggerInterface;

class AbstractLoadBefore implements \Magento\Framework\Event\ObserverInterface

{

    // Execute only once per request ...
    protected $flag = false;

    public function __construct(
        protected LoggerInterface $logger
    ) {
    }

    public function getEnabled()
    {
        return true;
    }

    /**
     * Execute observer
     *
     * @param \Magento\Framework\Event\Observer $observer
     *
     * @return void
     */
    public function execute(
        \Magento\Framework\Event\Observer $observer
    ) {
        // For the test, we can check the limit using an empty GET request with the parameter "?bot_test=1" from the browser or console 
        $isTest = $_SERVER['REQUEST_METHOD'] !== 'POST' && !isset($_GET['bot_test']);
        if ($isTest || $this->flag === true) {
            return 0;
        }

        // We are using native Redis.
        //If you don't have native Redis installed, this extension will not work
        if (!class_exists('\Redis')) {
            return 0;
        }

        if (!$this->getEnabled()) {
            return 0;
        }

        $this->flag = true;
        try {
            $matches = [];
            $cartMatch = false;

            if (strpos($_SERVER['REQUEST_URI'], '/totals-information') !== false) {
                $cartMatch = true;
            }
            if (!$cartMatch) {
                $re = '/\/V1\/guest-carts\/(.*)\/payment-information/i';
                preg_match($re, $_SERVER['REQUEST_URI'], $matches, PREG_OFFSET_CAPTURE, 0);
            }
            // Get customer's IP address
            if (count($matches) > 0 || $cartMatch) {
                $ip = $_SERVER['REMOTE_ADDR'];

                if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
                    $ips = $_SERVER['HTTP_X_FORWARDED_FOR'];
                } else if (isset($_SERVER['FASTLY-CLIENT-IP'])) {
                    $ips = $_SERVER['FASTLY-CLIENT-IP'];
                } else if (isset($_SERVER["HTTP_CF_CONNECTING_IP"])) {
                    $ips = $_SERVER["HTTP_CF_CONNECTING_IP"];
                } else {
                    $ips = $_SERVER['REMOTE_ADDR'];
                }

                // We may have comma separated list
                $ip = trim(count(explode(',', (string) $ips)) > 0 ? explode(',', (string) $ips)[0] : $ips);

                $config = require BP . '/app/etc/env.php';

                $redis = new \Redis();

                if (!isset($_ENV['MAGE_BOT_BLOCK_TIME'])) {
                    $_ENV['MAGE_BOT_BLOCK_TIME'] = 2;
                }
                if (!isset($_ENV['MAGE_BOT_RECORD_TIME'])) {
                    $_ENV['MAGE_BOT_RECORD_TIME'] = 2;
                }
                if (!isset($_ENV['MAGE_BOT_BLOCK_COUNT'])) {
                    $_ENV['MAGE_BOT_BLOCK_COUNT'] = 20;
                }

                if (!isset($config['cache']['frontend']['default']['backend_options']['server']) ||
                    !isset($config['cache']['frontend']['default']['backend_options']['port'])) {
                    return 0;
                }

                $persistentIdentifier = isset($config['cache']['frontend']['default']['backend_options']['persistent_identifier']) ? $config['cache']['frontend']['default']['backend_options']['persistent_identifier'] : 'cache';

                $redis->pconnect(
                    $config['cache']['frontend']['default']['backend_options']['server'],
                    (int) $config['cache']['frontend']['default']['backend_options']['port'],
                    (int) $config['cache']['frontend']['default']['backend_options']['database'],
                    $persistentIdentifier
                );

                if ($cartMatch) {
                    $redis->set('Cart_IP_Check_' . $ip, "true", 3600);
                    return 1;
                }

                if (!$cartMatch) {

                    $cartCheck = $redis->get('Cart_IP_Check_' . $ip);

                    if ($cartCheck === false) {
                        $this->logger->error("Genaker_BlockPaymentBot::AbstractLoadBefore cheater detected $ip - checkout without cart");
                        http_response_code(401);
                        die("Credit Card Error");
                    }

                    // Get customer Cart Id
                    $cartId = trim($matches[1][0]);

                    if (empty($cartId) || empty($ip)) {
                        $this->logger->error("Genaker_BlockPaymentBot::AbstractLoadBefore observer logical error: ip: " . $ip . ",  or cartId: " . $cartId . " are empty");
                        return 0;
                    }

                    $counter = $redis->get('Cart_' . $cartId);

                    $counterIP = $redis->get('Cart_' . $ip . '_IP');
                    $previousIP = $redis->get('Cart_' . $cartId . '_IP');

                    // If the cheater changed IP address, we are blocking that guy right away
                    if ($previousIP !== $ip && $previousIP != false) {
                        $this->logger->error("Genaker_BlockPaymentBot::AbstractLoadBefore cheater detected, ip: " . $ip . ", previousIP: " . $previousIP . ", cartId: " . $cartId);
                        http_response_code(511);
                        die("Cheater?");
                    }

                    if ($counter === false) {
                        $counter = 0;
                    }
                    if ($counterIP === false) {
                        $counterIP = 0;
                    }

                    $blockCounter = (int) $_ENV['MAGE_BOT_BLOCK_COUNT'];

                    if ($counter == $blockCounter) {
                        $redis->set('Cart_' . $cartId, ++$counter, 60 * (int) $_ENV['MAGE_BOT_BLOCK_TIME']);
                        $redis->set('Cart_' . $cartId . '_IP', $ip, 60 * (int) $_ENV['MAGE_BOT_BLOCK_TIME']);
                        http_response_code(511);
                        die(" Bye!");
                    } else if ($counter > $blockCounter) {
                        http_response_code(511);
                        die(" Bye Cheater!");
                    }
                    if ($counterIP == $blockCounter) {
                        $this->logger->error("Genaker_BlockPaymentBot::AbstractLoadBefore sent bye, ip: " . $ip . ", cartId: " . $cartId);
                        redis->set('Cart_' . $ip . '_IP', ++$counterIP, 60 * (int) $_ENV['MAGE_BOT_BLOCK_TIME']);
                        http_response_code(511);
                        die(" Bye!");
                    } else if ($counterIP > $blockCounter) {
                        http_response_code(511);
                        die(" Bye Cheater!");
                    }

                    $redis->set('Cart_' . $cartId, ++$counter, 60 * (int) $_ENV['MAGE_BOT_RECORD_TIME']);
                    $redis->set('Cart_' . $cartId . '_IP', $ip, 60 * (int) $_ENV['MAGE_BOT_RECORD_TIME']);
                    $redis->set('Cart_' . $ip . '_IP', ++$counterIP, 60 * (int) $_ENV['MAGE_BOT_RECORD_TIME']);
                }
            }
        } catch (\Throwable $e) {
            $this->logger->error("Genaker_BlockPaymentBot::AbstractLoadBefore observer error: " . $e->getMessage());
        }
    }
}
