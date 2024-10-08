# Mage2 Module Genaker BlockPaymentBot

How to test 

send POSTrequest to {domain}/rest/default/V1/guest-carts/dgfjsdhfgsdhfgsdhfgsdhfgsdjfk/payment-information

with the same cart ID multiple times after you request will be blocked for 5 minutes...

Now you can send GET request for test with the parameter ?bot_test=1

```
https://query.tilebar.com/rest/default/V1/guest-carts/GKxNF6em8IzxaZlk78YR3soEYby/payment-information?bot_test=1
```
Also, you can set ENV variables to adjust the logic:  <br\>

 - **$_ENV['MAGE_BOT_BLOCK_TIME']** Block bot for N Minutes, Default 2.
 - **$_ENV['MAGE_BOT_RECORD_TIME']** Time  in minutes during which the counter will not be null. So if you have 60, then your counting limit for 1 hour. Default 2.
 - **$_ENV['MAGE_BOT_BLOCK_COUNT']** Request counter limit when user will be locked for **MAGE_BOT_BLOCK_TIME** minutes. Default 20

You can adjust the rate and time.

    ``genaker/module-blockpaymentbot``

 - [Main Functionalities](#markdown-header-main-functionalities)
 - [Installation](#markdown-header-installation)
 - [Configuration](#markdown-header-configuration)
 - [Specifications](#markdown-header-specifications)
 - [Attributes](#markdown-header-attributes)


## Main Functionalities


## Installation
\* = in production please use the `--keep-generated` option

### Type 1: Zip file

 - Unzip the zip file in `app/code/Genaker`
 - Enable the module by running `php bin/magento module:enable Genaker_BlockPaymentBot`
 - Apply database updates by running `php bin/magento setup:upgrade`\*
 - Flush the cache by running `php bin/magento cache:flush`

### Type 2: Composer

 - Make the module available in a composer repository for example:
    - private repository `repo.magento.com`
    - public repository `packagist.org`
    - public github repository as vcs
 - Add the composer repository to the configuration by running `composer config repositories.repo.magento.com composer https://repo.magento.com/`
 - Install the module composer by running `composer require genaker/module-blockpaymentbot`
 - enable the module by running `php bin/magento module:enable Genaker_BlockPaymentBot`
 - apply database updates by running `php bin/magento setup:upgrade`\*
 - Flush the cache by running `php bin/magento cache:flush`


## Configuration




## Specifications

 - Observer
	- core_abstract_load_before > Genaker\BlockPaymentBot\Observer\Webapi\Core\AbstractLoadBefore


## Attributes

## Requirements
This Module has a dependency on redis.  If your magento store is not running redis this module will have not effect on protecting your site.  It won't break your site, but the protection will not be enabled.

## Testing

To verify the module is working as expected, you can use curl on cli to test.

```
curl -i -X POST https://www.MYDOMAIN.com/rest/default/V1/guest-carts/GKxNF6em8IzxaZlk78YR3soEYby/payment-information
```

The expected outcome of the above is for the first 20 request you should get something like this:

```
{"message":"One or more input exceptions have occurred.","errors":[{"...
```

After the first 20 requests you should get:

```
Bye!
```


