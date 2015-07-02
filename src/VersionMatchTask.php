<?php

class VersionMatchTask extends Task
{
	protected $version;
	protected $path;
	protected $pattern;

	use ReturnPropertyImplementation;

	public function setVersion($version)
	{
		$this->version = $version;
	}

	public function setDir($path)
	{
		$this->path = $path;
	}

	public function setPattern($pattern)
	{
		$this->pattern = $pattern;
	}

	public function main()
	{
		$file = $this->findBestMatch($this->pattern, $this->path, $this->version);

		$this->returnValue($file);
	}

	public function findBestMatch($pattern, $path, $version)
	{
		$bestVersion = '0';
		$bestFile    = null;
		foreach (glob("$path/*") as $filename)
		{
			if (preg_match("/{$pattern}/", $filename, $match))
			{
				if (version_compare($bestVersion, $match[1], '<') && version_compare($match[1], $version, '<='))
				{
					$bestVersion = $match[1];
					$bestFile    = $filename;
				}
			}
		}

		return $bestFile;
	}
}
