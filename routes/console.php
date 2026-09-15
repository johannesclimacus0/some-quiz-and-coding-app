<?php

use App\Events\ReverbTest;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('reverb:test', function () {
    ReverbTest::dispatch('it might work idk');

    $this->info('it might work idk');
});
