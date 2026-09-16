<?php

use Illuminate\Support\Facades\Artisan;

Artisan::command('procurement:about', function () {
    $this->info('Laravel Procurement — Philippine Government Procurement Management System');
})->purpose('Display procurement application information');
