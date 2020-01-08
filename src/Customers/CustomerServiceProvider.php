<?php

namespace Netflex\Customers;

use Netflex\Customers\Customer;

use Illuminate\Foundation\AliasLoader;
use Illuminate\Support\ServiceProvider;

class CustomerServiceProvider extends ServiceProvider
{
  public function register()
  {
    $loader = AliasLoader::getInstance();
    $loader->alias('Customer', Customer::class);
  }
}
