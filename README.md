# Mage2 Module Genaker BlockPaymentBot

How to test 

send POSTrequest to {domain}/rest/default/V1/guest-carts/dgfjsdhfgsdhfgsdhfgsdhfgsdjfk/payment-information

With the same cart ID multiple times after, your request will be blocked for 5 minutes...

Now you can send GET request for the test with the parameter ?bot_test=1

```
https://domain.com/rest/default/V1/guest-carts/GKxNF6em8IzxaZlk78YR3soEYby/payment-information?bot_test=1
```
Also, you can set ENV variables to adjust the logic: 

 - **$_ENV['MAGE_BOT_BLOCK_TIME']** Block bot for N Minutes, Default 2.
 - **$_ENV['MAGE_BOT_RECORD_TIME']** Time  in minutes during which the counter will not be null. So if you have 60, then your counting limit for 1 hour. Default 2.
 - **$_ENV['MAGE_BOT_BLOCK_COUNT']** Request counter limit when user will be locked for **MAGE_BOT_BLOCK_TIME** minutes. Default 20

Adjust until bots gone.

# Why are we using ENV variables and not a Magento config? 

Magento 2 is a slow legacy system; however, the new approach is to store configurations in the env variables.
Enviremental config has gained significant popularity in PHP over recent years. It uses dotenv files, which are named after the de facto file name: .env. 
These plain text files define the environment variables required for an application to work as a list of key/value pairs.
When using the Magento configuration, you need magento to be up and running. This extension doesn't load the entire magento but blocks bots immediately. If you will load magento bots will consume entire resources from your servers. Don't load Magento when you don't need it. Use PHP microservices! 

I am lovers of the magento config. Config contains hundreds of thousands of records and even cached. It takes minutes to load every request from the cache (it still requires unzipping and unserializing). Magento config is an excellent example of how not to do it. With the bigger projects, merchants have more significant issues with magento. 


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

Using **ENV** varriables


## Specifications

 - Observer
 - core_abstract_load_before > Genaker\BlockPaymentBot\Observer\Webapi\Core\AbstractLoadBefore


## Requirements
This Module has a dependency on  phpRedis.  If your magento store is not running Redis, this module will have no effect on protecting your site.  It won't break your site, but the protection will not be enabled.

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


