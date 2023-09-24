<?php
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
// Routes with RedirectIfNotAuthenticated middleware
Route::get('/', 'AdminController@showLoginPage')->name('login');
Route::post('/login', 'AdminController@login')->name('admin.login');

Route::get('/payment-confirmation', 'JdbController@confirmation')->name('page.confirmation');
Route::get('/payment-failed', 'JdbController@failed')->name('page.cancellation');
Route::get('/payment-cancellation', 'JdbController@cancellation')->name('page.failed');
Route::get('/payment-backend', 'JdbController@backend')->name('page.backend');

Route::middleware(['auth'])->group(function () {

  // Protected routes
  Route::get('/admin/reset-password-page', 'AdminController@showResetPasswordPage')->name('admin.resetPasswordPage');
  Route::post('/admin/reset-password', 'AdminController@resetPassword')->name('admin.resetPassword');
  Route::get('/admin/logout', 'AdminController@logout')->name('admin.logout');

  Route::get('/admin/home', 'JdbController@payment')->name('page.home');
  Route::get('/admin/paymentPage', 'JdbController@payment')->name('page.payment');
  Route::get('/admin/reportPage', 'JdbController@report')->name('page.report');

  Route::get('/admin/apikey', 'ApiKeyController@showDashboard')->name('page.apikey.dashboard');
  Route::post('/admin/apikey-add-partner', 'ApiKeyController@addNewPartner')->name('apikey.add.partner');
  Route::post('/admin/apikey-get-partner', 'ApiKeyController@getPartner')->name('apikey.get.partner');
  Route::post('/admin/apikey-update-partner', 'ApiKeyController@updatePartner')->name('apikey.update.partner');
  Route::post('/admin/apikey-delete-partner', 'ApiKeyController@deletePartner')->name('apikey.delete.partner');
  Route::post('/admin/apikey-get-apikey', 'ApiKeyController@getApiKey')->name('apikey.get.apikey');
  Route::post('/admin/apikey-apply-apikey', 'ApiKeyController@applyApiKey')->name('apikey.apply.apikey');

  Route::get('/admin/fireblocks-showGetAddressPage', 'FireBlocksController@showGetAddressPage')->name('page.fireblocks.showGetAddressPage');
  Route::get('/admin/fireblocks-showReportPage', 'FireBlocksController@showReportPage')->name('page.fireblocks.showReportPage');
  Route::get('/admin/fireblocks-showCronJobPage', 'FireBlocksController@showCronJobPage')->name('page.fireblocks.showCronJobPage');
  Route::get('/admin/fireblocks-test', 'FireBlocksController@showTestPage')->name('page.fireblocks.test');
  Route::post('/admin/fireblocks-get-account', 'FireBlocksController@getAccount')->name('fireblocks.getAccount');
  Route::post('/admin/fireblocks-get-account-balance', 'FireBlocksController@getAccountBalance');
  Route::post('/admin/fireblocks-get-supported-assets', 'FireBlocksController@getSupportedAssets');
});

