<?php

namespace SDK\Build\PGO\PHP;

use SDK\Build\PGO\Interfaces;
use SDK\Build\PGO\Abstracts;
use SDK\Build\PGO\Config as PGOConfig;
use SDK\{Config as SDKConfig, Exception, FileOps};
use SDK\Build\PGO\Tool\PackageWorkman;

class FCGI extends Abstracts\PHP implements Interfaces\PHP
{
	protected $conf;
	protected $is_tcp;

	public function __construct(PGOConfig $conf, bool $is_tcp)
	{
		if (!$is_tcp) {
			throw new Exception("FCGI training other than through TCP is not implemented yet.");
		}

		$this->conf = $conf;
		$this->is_tcp = $is_tcp;
		$this->scenario = $conf->getScenario();

		$this->setupPaths();
	}

	public function getExeFilename() : string
	{
		$exe = $this->getRootDir() . DIRECTORY_SEPARATOR . "php-cgi.exe";

		if (!file_exists($exe)) {
			throw new Exception("Path '$exe' doesn't exist.");
		}

		return $exe;
	}

	protected function createEnv() : array
	{
		$env = parent::createEnv();

		$fcgi_env = (array)$this->conf->getSectionItem("php", "fcgi:env");

		foreach ($fcgi_env as $k => $v) {
			$env[$k] = $v;
		}

		return $env;
	}

	public function prepareInit(PackageWorkman $pw, bool $force = false) : void
	{
	}

	public function init() : void
	{
/*		echo "Initializing PHP FCGI.\n";
echo "PHP FCGI initialization done.\n";*/
	}

	public function up() : void
	{
		echo "Starting PHP FCGI.\n";

		$exe  = $this->getExeFilename();
		$ini  = $this->getIniFilename();
		$host = $this->conf->getSectionItem("php", "fcgi", "host");
		$port = $this->conf->getSectionItem("php", "fcgi", "port");

		$cmd = "start /b $exe -n -c $ini -b $host:$port";

		$desc = array(
			0 => array("file", "php://stdin", "r"),
			1 => array("file", "php://stdout", "w"),
			2 => array("file", "php://stderr", "w")
		);

		$p = proc_open($cmd, $desc, $pipes, $this->getRootDir(), $this->createEnv());
		$c = proc_close($p);

		if ($c) {
			throw new Exception("PHP FCGI process exited with code '$c'.");
		}

		/* Give some time, it might be slow on PGI enabled proc. */
		sleep(1);

		/* XXX for Opcache, setup also file cache. */

		echo "PHP FCGI started.\n";
	}

	public function down(bool $force = false) : void
	{
		echo "Stopping PHP FCGI.\n";

		exec("taskkill /f /im php-cgi.exe >nul 2>&1");

		echo "PHP FCGI stopped.\n";
	}
}

