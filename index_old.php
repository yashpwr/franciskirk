<!--<style>
body {
background: #30287d;
}
h1 {
color: #fff;
max-width: 580;
margin: 0 auto;
text-align: center;
font-family: Poppins;
font-weight: 500;
align-self: normal;
max-height: 100vh;
padding-top: 0;
border: 2px solid #f8dc00;
display: block;
padding: 100px;
}
h1:before {
content: "";
background-image: url(https://franciskirk.shop/pub/media/logo/stores/1/Francis_Kirk_Logo_1.png);
background-size: 250px;
background-position: center center;
display: block;
height: 100px;
background-repeat: no-repeat;
background-color: #fff;
max-width: 110px;
margin: 0 auto;
padding: 10px 80px;
border-radius: 60px;
margin-bottom: 50px;
}
</style>
<h1>SITE UNDER MAINTENANCE & FINAL CHECKS PRIOR TO GO LIVE</h1>-->
<?php
/**
 * Application entry point
 *
 * Example - run a particular store or website:
 * --------------------------------------------
 * require __DIR__ . '/app/bootstrap.php';
 * $params = $_SERVER;
 * $params[\Magento\Store\Model\StoreManager::PARAM_RUN_CODE] = 'website2';
 * $params[\Magento\Store\Model\StoreManager::PARAM_RUN_TYPE] = 'website';
 * $bootstrap = \Magento\Framework\App\Bootstrap::create(BP, $params);
 * \/** @var \Magento\Framework\App\Http $app *\/
 * $app = $bootstrap->createApplication(\Magento\Framework\App\Http::class);
 * $bootstrap->run($app);
 * --------------------------------------------
 *
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

try {
    require __DIR__ . '/app/bootstrap.php';
} catch (\Exception $e) {
    echo <<<HTML
<div style="font:12px/1.35em arial, helvetica, sans-serif;">
    <div style="margin:0 0 25px 0; border-bottom:1px solid #ccc;">
        <h3 style="margin:0;font-size:1.7em;font-weight:normal;text-transform:none;text-align:left;color:#2f2f2f;">
        Autoload error</h3>
    </div>
    <p>{$e->getMessage()}</p>
</div>
HTML;
    exit(1);
}

$bootstrap = \Magento\Framework\App\Bootstrap::create(BP, $_SERVER);
/** @var \Magento\Framework\App\Http $app */
$app = $bootstrap->createApplication(\Magento\Framework\App\Http::class);
$bootstrap->run($app);
