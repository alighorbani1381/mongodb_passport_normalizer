<?php

namespace Alighorbani\PassportForMongo;

use Illuminate\Support\ServiceProvider;

class PassportNormalizerServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->commands([LaravelPassportNormalizerForMongodb::class]);
    }
}
