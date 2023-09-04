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
Route::get('/', 'TestController@home')->name('page.home');
Route::get('/paymentPage', 'TestController@payment')->name('page.payment');
Route::get('/reportPage', 'TestController@report')->name('page.report');
Route::get('/payment-confirmation', 'TestController@confirmation')->name('page.confirmation');
Route::get('/payment-failed', 'TestController@failed')->name('page.cancellation');
Route::get('/payment-cancellation', 'TestController@cancellation')->name('page.failed');
Route::get('/payment-backend', 'TestController@backend')->name('page.backend');
Route::get('/admin-dashboard', 'AdminController@showDashboard')->name('page.admin.dashboard');
Route::post('/admin-add-partner', 'AdminController@addNewPartner')->name('admin.add.partner');
