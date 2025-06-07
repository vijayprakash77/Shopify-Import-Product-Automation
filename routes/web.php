<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\MainController;

Route::get('/', function () {
    return view('auth.login');
});

Route::controller(MainController::class)->group(function() {
    
      Route::get('/new-import','newImportGet')->name('new-import');
      Route::post('/process-import', 'processImport')->name('process-import');
      Route::get('/import-progress/{uploadId}', 'getImportProgress')->name('import-progress');
      Route::post('/pause-import/{uploadId}','pauseImport')->name('pause-import');
      Route::post('/resume-import/{uploadId}', 'resumeImport')->name('resume-import');
      Route::get('/download-sample-csv', 'downloadSampleCsv')->name('download-sample-csv');

      Route::get('/import-details/{id}', 'getImportDetails');
      Route::post('/import-again/{id}', 'importAgain');
      Route::delete('/delete-import/{id}', 'deleteImport');
      Route::get('/import-stats', 'getImportStats');
      
      Route::post('/import-list-ajax','import_list_ajax')->name('import-list-ajax');

      Route::get('/new-import', 'new_import_get')->name('new-import-get');
      Route::get('/import-list', 'import_list_get')->name('import-list-get');
      Route::get('/dashboard', 'admin_dashboard_get')->name('admin-dashboard-get');  

      Route::get('/login', 'signin_get')->name('signin-get');
      Route::post('/logged-in', 'logged_in_user')->name('logged-in-user');

      Route::get('/logout', 'logout_user')->name('logout-user');
     
 });
