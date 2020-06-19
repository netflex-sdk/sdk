<?php

namespace Netflex\SDK\Process;

use Symfony\Component\Process\Process;

class ExposeProcessBuilder
{
    /**
     * Current working directory.
     *
     * @var string
     */
    protected $cwd;

    /** @var string */
    protected $subdomain;

    /**
     * @param string $cwd
     */
    public function __construct(string $cwd = null)
    {
        $this->setWorkingDirectory($cwd);
    }

    /**
     * Set the current working directory.
     *
     * @param string $cwd
     */
    public function setWorkingDirectory(string $cwd) : void
    {
        $this->cwd = $cwd;
    }

    /**
     * Get the current working directory.
     *
     * @return string
     */
    public function getWorkingDirectory() : string
    {
        return $this->cwd;
    }

    /**
     * Build expose command.
     *
     * @param string $host
     * @param string $port
     * @return \Symfony\Component\Process\Process
     */
    public function buildProcess(string $host = '', string $port = '8080', $subdomain = null) : Process
    {
        if (!$this->subdomain) {
          $this->subdomain = $subdomain ?? explode('-', uuid())[0];
        }

        $command = ['expose', 'share'];

        if ($host !== '') {
            $command[] = $host . ':' . $port ?: '8080';
        }

        $command[] = '--subdomain';
        $command[] = $this->subdomain;

        return new Process($command, $this->getWorkingDirectory(), null, null, null);
    }

    /**
     * @return string
     */
    public function getProxyUrl()
    {
      return 'https://' . $this->subdomain . '.proxy.netflexapp.com';
    }
}
