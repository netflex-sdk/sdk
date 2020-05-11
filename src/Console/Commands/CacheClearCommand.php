<?php

namespace Netflex\Console\Commands;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Cache\Console\ClearCommand;

class CacheClearCommand extends ClearCommand
{
  /**
   * Execute the console command.
   *
   * @return void
   */
  public function handle()
  {
    parent::handle();
    Artisan::call('view:clear');
  }
}
