<?php

namespace Netflex\Console\Commands;

class ServeCommand extends \Illuminate\Foundation\Console\ServeCommand
{
  /**
   * Get the port for the command.
   *
   * @return string
   */
  protected function port()
  {
    $port = $this->input->getOption('port') ?: 8080;

    return $port + $this->portOffset;
  }

  /**
   * Execute the console command.
   *
   * @return int
   *
   * @throws \Exception
   */
  public function handle()
  {
    chdir(public_path());

    $this->line("<info>Netflex development server started:</info> http://{$this->host()}:{$this->port()}");

    passthru($this->serverCommand(), $status);

    if ($status && $this->canTryAnotherPort()) {
      $this->portOffset += 1;

      return $this->handle();
    }

    return $status;
  }
}
