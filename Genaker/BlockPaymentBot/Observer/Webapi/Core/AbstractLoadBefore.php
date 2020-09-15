<?php
/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Genaker\BlockPaymentBot\Observer\Webapi\Core;

class AbstractLoadBefore implements \Magento\Framework\Event\ObserverInterface
{

    /**
     * Execute observer
     *
     * @param \Magento\Framework\Event\Observer $observer
     * @return void
     */
    public function execute(
        \Magento\Framework\Event\Observer $observer
    ) {
	if ($_SERVER['REQUEST_METHOD'] !== 'POST')
		return 0;
	try {
	$re = '/\/rest\/default\/V1\/guest-carts\/(.*)\/payment-information/i';

	preg_match($re, $_SERVER['REQUEST_URI'], $matches, PREG_OFFSET_CAPTURE, 0);

	//var_dump($_SERVER); die();

	if (count($matches) > 0)
	{

	$ip = $_SERVER['REMOTE_ADDR'];
	$cartId = $matches[1][0];

	$config = require BP.'/app/etc/env.php';


	if (!class_exists('\Redis'))
		return 0;

	$redis = new \Redis();

	$redis->pconnect($config['cache']['frontend']['default']['backend_options']['server'],
	  (int)$config['cache']['frontend']['default']['backend_options']['port'], 0, 'cache');

	$counter = $redis->get('Cart_'.$cartId);
	$previousIP = $redis->get('Cart_'.$cartId.'IP');
//die($previousIP);
	if($previousIP !== $ip && $previousIP != null)
	{
	die("cheater");
	}

	if($counter === null){
	$counter = 0;
	}
	if ($counter > 6){
	die("By!");
	}

	$redis->set('Cart_'.$cartId, ++$counter, 60 * 5);
	$redis->set('Cart_'.$cartId.'IP', $ip, 60 * 5);
	}
	} catch (\Throwable $e){
	 die("Custom Function Error -> " . $e->getMessage());
	}

    }
}

