<?php
class RolanddAutoloader
{
	private $baseDir;
	private $baseSegments;

	public function __construct(string $baseDir = __DIR__)
	{
		$this->baseDir = str_replace('\\', '/', $baseDir);
		$this->baseSegments = explode('/', $this->baseDir);
	}

	public function load(string $class)
	{
		$classPath = str_replace('\\', '/', $class) . '.php';
		$classDir = dirname($classPath);
		$classDirSegments = explode('/', $classDir);

		if ($classDirSegments[0] !== end($this->baseSegments))
		{
			return;
		}

		array_shift($classDirSegments);
		$baseDir = $this->baseDir;
		if (count($classDirSegments))
		{
			$baseDir .= '/' . implode('/', $classDirSegments);
		}
		$classFile =  $baseDir . '/' . basename($classPath);

		// If the file exists, require it
		if (file_exists($classFile))
		{
			require_once $classFile;
		}
	}
}

spl_autoload_register([new RolanddAutoloader(), 'load']);
