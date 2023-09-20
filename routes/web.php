<?php
  use App\Http\Controllers\LanguageController;
  use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/
Route::get('/', 'JdbController@home')->name('page.home');
Route::get('/paymentPage', 'JdbController@payment')->name('page.payment');
Route::get('/reportPage', 'JdbController@report')->name('page.report');
Route::get('/payment-confirmation', 'JdbController@confirmation')->name('page.confirmation');
Route::get('/payment-failed', 'JdbController@failed')->name('page.cancellation');
Route::get('/payment-cancellation', 'JdbController@cancellation')->name('page.failed');
Route::get('/payment-backend', 'JdbController@backend')->name('page.backend');

Route::get('/apikey-dashboard', 'ApiKeyController@showDashboard')->name('page.apikey.dashboard');
Route::post('/apikey-add-partner', 'ApiKeyController@addNewPartner')->name('apikey.add.partner');
Route::post('/apikey-get-partner', 'ApiKeyController@getPartner')->name('apikey.get.partner');
Route::post('/apikey-update-partner', 'ApiKeyController@updatePartner')->name('apikey.update.partner');
Route::post('/apikey-delete-partner', 'ApiKeyController@deletePartner')->name('apikey.delete.partner');
Route::post('/apikey-get-apikey', 'ApiKeyController@getApiKey')->name('apikey.get.apikey');
Route::post('/apikey-apply-apikey', 'ApiKeyController@applyApiKey')->name('apikey.apply.apikey');

Route::get('/fireblocks-showGetAddressPage', 'FireBlocksController@showGetAddressPage')->name('page.fireblocks.showGetAddressPage');
Route::get('/fireblocks-showReportPage', 'FireBlocksController@showReportPage')->name('page.fireblocks.showReportPage');
Route::get('/fireblocks-showCronJobPage', 'FireBlocksController@showCronJobPage')->name('page.fireblocks.showCronJobPage');
Route::get('/fireblocks-test', 'FireBlocksController@showTestPage')->name('page.fireblocks.test');
Route::post('/fireblocks-get-account', 'FireBlocksController@getAccount')->name('fireblocks.getAccount');
Route::post('/fireblocks-get-account-balance', 'FireBlocksController@getAccountBalance');
Route::post('/fireblocks-get-supported-assets', 'FireBlocksController@getSupportedAssets');

//web hook https://api.kaiserpayment.com/fireblocks/webhook
Route::post('/fireblocks/webhook', 'FireBlocksController@webhook');
Route::get('/fireblocks/webhook', 'FireBlocksController@webhook');
Route::put('/fireblocks/webhook', 'FireBlocksController@webhook');

Route::post('/v1/transaction_callback', 'FireBlocksController@webhook');
Route::get('/v1/transaction_callback', 'FireBlocksController@webhook');
Route::put('/v1/transaction_callback', 'FireBlocksController@webhook');
