<?php
/*
|--------------------------------------------------------------------------
| S3 Bucket 設定檔
|--------------------------------------------------------------------------
*/
return [
	/*
	|--------------------------------------------------------------------------
	| holkee 常用素材庫
	|--------------------------------------------------------------------------
	*/
	'common-assets' => [
		'bucket' => 'holkee-common-assets',
		'root_path' => 'common-assets/',
		'allowed_file' => []
	],
	/*
	|--------------------------------------------------------------------------
	| holkee 行銷素材庫
	|--------------------------------------------------------------------------
	*/
	'marketing-assets' => [
		'bucket' => 'holkee-marketing-assets',
		'root_path' => 'marketing-assets/',
		'allowed_file' => ['jpg', 'png']
	],
	/*
	|--------------------------------------------------------------------------
	| holkee 官方主題素材庫
	|--------------------------------------------------------------------------
	*/
	'official-theme' => [
		'bucket' => 'holkee-official-theme-assets',
		'root_path' => 'official-theme/',
		'allowed_file' => ['jpg', 'png', 'svg', 'css', 'scss', 'map']
	],
	/*
	|--------------------------------------------------------------------------
	| holkee 用戶自訂主題素材庫
	|--------------------------------------------------------------------------
	*/
	'user-custom-theme' => [
		'bucket' => 'holkee-user-custom-theme-assets',
		'root_path' => 'user-custom-theme-assets/',
		'allowed_file' => ['jpg', 'png', 'svg', 'css', 'scss', 'map']
	],
	/*
	|--------------------------------------------------------------------------
	| holkee 用戶網站最新消息素材庫
	|--------------------------------------------------------------------------
	*/
	'user-news' => [
		'bucket' => 'holkee-user-news-assets',
		'root_path' => 'user-news-assets/',
		'allowed_file' => ['jpg', 'png']
	],
	/*
	|--------------------------------------------------------------------------
	| holkee 用戶網站商品素材庫
	|--------------------------------------------------------------------------
	*/
	'user-product' => [
		'bucket' => 'holkee-user-product-assets',
		'root_path' => 'user-product-assets/',
		'allowed_file' => ['jpg', 'png']
	],
	/*
	|--------------------------------------------------------------------------
	| holkee 用戶網站服務項目素材庫
	|--------------------------------------------------------------------------
	*/
	'user-service' => [
		'bucket' => 'holkee-user-service-assets',
		'root_path' => 'user-service-assets/',
		'allowed_file' => ['jpg', 'png']
	],
	/*
	|--------------------------------------------------------------------------
	| holkee 用戶網站素材庫
	|--------------------------------------------------------------------------
	*/
	'user-website' => [
		'bucket' => 'holkee-user-website-assets',
		'root_path' => 'user-website-assets/',
		'allowed_file' => ['jpg', 'png']
	],
];
