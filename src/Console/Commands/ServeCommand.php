<?php

namespace Netflex\Console\Commands;

use Closure;
use Exception;

use Netflex\API\Client;
use Netflex\API\Facades\API;
use Netflex\Foundation\Variable;

use Dotenv\Dotenv;

use Illuminate\Foundation\Console\ServeCommand as Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Artisan;
use Netflex\SDK\Process\ExposeProcessBuilder;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Process\Exception\ProcessSignaledException;
use Symfony\Component\Process\Process;

class ServeCommand extends Command
{
  /**
   * Process builder.
   *
   * @var \Netflex\SDK\Process\ExposeProcessBuilder
   */
  protected $processBuilder;

  /**
   * Netflex Proxy Configuration Variable
   *
   * @var \Netflex\Foundation\Variable
   */
  protected $variable;

  /**
   * Netflex Proxy Configuration
   *
   * @var object
   */
  protected $configuration;

  /**
   * Netflex User ID
   *
   * @var int
   */
  protected $userId;

  /**
   * Netflex Client authenticated as User
   *
   * @var \Netflex\API\Client
   */
  protected $userClient;

  /**
   * Progressbar
   *
   * @var Symfony\Component\Console\Helper\ProgressBar
   */
  protected $progressBar;

  /**
   * The file holding the Netflex credentials
   *
   * @var string
   */
  protected $credentialsFile = '.netflexrc';

  /**
   * Get the port for the command.
   *
   * @return string
   */
  protected function port()
  {
    $port = $this->input->getOption('port') ?: 8000;

    return $port + $this->portOffset;
  }

  protected function client()
  {
    if (!$this->userClient) {
      try {
        Dotenv::createMutable($_SERVER['HOME'], $this->credentialsFile)->safeLoad();

        $username = env('NETFLEX_USERNAME');
        $password = env('NETFLEX_PASSWORD');

        if (!$username || !$password) {
          return $this->promptCredentials(function () {
            return $this->client();
          });
        }

        $this->userClient = new Client([
          'auth' => [
            $username,
            $password
          ]
        ]);
      } catch (Exception $e) {
        return $this->promptCredentials(function () {
          return $this->client();
        });
      }
    }

    return $this->userClient;
  }

  protected function userId()
  {
    if (!$this->userId) {
      try {
        $response = $this->client()->get('user/auth');
        $this->userId = (int) $response->user->id;
      } catch (Exception $e) {
        $this->progressBar->clear();
        $this->error('Invalid credentials');
        $this->deleteCredentials();
        $this->userId = null;
        $this->userClient = null;
        $this->progressBar->display();

        return $this->userId();
      }
    }

    return $this->userId;
  }

  protected function siteAlias()
  {
    return basename(app()->basePath());
  }

  protected function variable()
  {
    if (!$this->variable) {
      $this->progressBar->advance(1);
      Artisan::call('cache:clear');
      $this->variable = Variable::retrieve('netflex_editor_proxy');
    }

    return $this->variable;
  }

  protected function proxyConfiguration()
  {
    if (!$this->variable()) {
      $this->configuration = (object) [
        'default' => 'https://' . $this->siteAlias() . '.netflex.dev',
        'authorization' => 'jwt',
        'path' => '.well-known/netflex',
        'proxies' => Collection::make([])
      ];
    } else {
      $this->configuration = $this->variable()->value;
      $this->configuration->proxies = Collection::make($this->configuration->proxies);
    }

    return $this->configuration;
  }

  protected function addProxy($uri)
  {
    $configuration = $this->proxyConfiguration();

    $configuration->proxies = $configuration->proxies->filter(function ($proxy) {
      return $proxy->id !== $this->userId();
    });

    $configuration->proxies->push((object) [
      'id' => $this->userId(),
      'uri' => $uri
    ]);

    $this->saveProxyConfiguration($configuration);
  }

  protected function removeProxy()
  {
    $this->variable = null;
    $configuration = $this->proxyConfiguration();

    $configuration->proxies = $configuration->proxies->filter(function ($proxy) {
      return $proxy->id !== $this->userId();
    });

    $this->saveProxyConfiguration($configuration);
  }

  protected function saveProxyConfiguration($configuration)
  {
    $this->configuration = $configuration;
    $this->configuration->proxies = $this->configuration->proxies->values();

    $payload = (object) [
      'name' => 'Editor Proxy',
      'alias' => 'netflex_editor_proxy',
      'format' => 'json',
      'value' => json_encode($this->configuration, JSON_PRETTY_PRINT)
    ];

    if ($this->variable) {
      API::put("foundation/variables/{$this->variable()->id}", $payload);
    } else {
      API::post("foundation/variables", $payload);
    }

    $this->progressBar->advance(1);
  }

  protected function deleteCredentials()
  {
    $path = $_SERVER['HOME'] . DIRECTORY_SEPARATOR . $this->credentialsFile;

    if (file_exists($path)) {
      unlink($path);
    }
  }

  protected function promptCredentials(Closure $callback)
  {
    $path = $_SERVER['HOME'] . DIRECTORY_SEPARATOR . $this->credentialsFile;

    $this->deleteCredentials();

    $this->progressBar->clear();
    $this->error('Please setup your Netflex credentials');
    $username = $this->ask('Username');
    $password = $this->secret('Password');
    $this->progressBar->display();

    $config = "NETFLEX_USERNAME=\"$username\"\nNETFLEX_PASSWORD=\"{$password}\"\n";
    file_put_contents($path, $config);

    return $callback();
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
    if ($this->input->getOption('local')) {
      passthru($this->serverCommand(), $status);

      if ($status && $this->canTryAnotherPort()) {
        $this->portOffset += 1;

        return $this->handle();
      }

      return $status;
    }

    $this->progressBar = $this->output->createProgressBar(4);

    if ('\\' !== \DIRECTORY_SEPARATOR || 'Hyper' === getenv('TERM_PROGRAM')) {
      $this->progressBar->setEmptyBarCharacter(' ');
      $this->progressBar->setProgressCharacter('');
      $this->progressBar->setBarCharacter('<fg=green>█</>');
    }

    $this->progressBar->setRedrawFrequency(1);
    $this->progressBar->minSecondsBetweenRedraws(0.0);
    $this->progressBar->start();

    declare(ticks=1); // Handle async signals PHP 7.1
    pcntl_async_signals(true); // Handle async signals PHP ^7.1
    pcntl_signal(SIGINT, [$this, 'shutdown']); // Call $this->shutdown() on SIGINT
    pcntl_signal(SIGTERM, [$this, 'shutdown']); // Call $this->shutdown() on SIGTERM

    $this->processBuilder = new ExposeProcessBuilder(app()->basePath());
    $this->progressBar->advance(1);

    return $this->runProcess(
      $this->processBuilder->buildProcess(
        $this->host(),
        $this->port()
      )
    );
  }

  private function shutdown()
  {
    $this->progressBar->finish();
    $this->progressBar->clear();
    $this->line('<info>👋 Server stopped, bye!</info>');
  }

  private function runProcess(Process $process): int
  {
    $tunnelStarted = false;

    try {
      $process->run(function ($type, $data) use (&$process, &$tunnelStarted) {
        if (!$tunnelStarted) {
          $tunnel = $this->processBuilder->getProxyUrl();
          $tunnelStarted = true;

          $this->progressBar->advance(1);

          $this->addProxy($tunnel);

          chdir(public_path());

          $this->progressBar->finish();
          $this->progressBar->clear();

          $this->line("<info>⚡ Ready: </info><fg=cyan;options=underscore>http://{$this->host()}:{$this->port()}</>");
          $this->line('<info>🌐 Proxy: </info><fg=cyan;options=underscore>' . $tunnel . '</> -> <fg=cyan;options=underscore>http://' . $this->host() . ':' . $this->port() . '</>');
          $this->line('');

          passthru($this->serverCommand(), $status);

          if ($status && $this->canTryAnotherPort()) {
            $this->portOffset += 1;

            return $this->handle();
          }

          return $status;
        }

        if (Process::OUT === $type) {
          $process->clearOutput();
        } else {
          $this->error($data);
          $process->clearErrorOutput();
        }
      });

      $this->error($process->getErrorOutput());
    } catch (ProcessSignaledException $e) {
      return 0;
    }

    return $process->getExitCode();
  }

  /**
   * Get the console command options.
   *
   * @return array
   */
  protected function getOptions()
  {
    $options = parent::getOptions();
    $options[] = [
      'local', "l", InputOption::VALUE_OPTIONAL, 'Only serve locally, skips proxy', (env("NETFLEX_SKIP_PROXY") ?? false)
    ];

    return $options;
  }
}
