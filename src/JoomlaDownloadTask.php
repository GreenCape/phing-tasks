<?php

class JoomlaDownloadTask extends Task
{
	protected $version = 'latest';
	protected $versionFile = 'versions.json';
	protected $cachePath = 'build';

	use ReturnPropertyImplementation;

	public function setVersion($version)
	{
		$this->version = $version;
	}

	public function setVersionFile($file)
	{
		$this->versionFile = $file;
	}

	public function setCachePath($path)
	{
		$this->cachePath = $path;
	}

	public function main()
	{
		$tarball = $this->getTarball();

		$this->returnValue($tarball);
	}

	/**
	 * @return null|string
	 */
	protected function getTarball()
	{
		$versions  = json_decode(file_get_contents($this->versionFile), true);
		$requested = $this->version;

		// Resolve alias
		if (isset($versions['alias'][$this->version]))
		{
			$this->version = $versions['alias'][$this->version];
		}

		$tarball = $this->cachePath . '/' . $this->version . '.tar.gz';

		if (file_exists($tarball) && !isset($versions['heads'][$this->version]))
		{
			$this->log("$requested: Joomla $this->version is already in cache", Project::MSG_INFO);

			return $tarball;
		}

		if (isset($versions['heads'][$this->version]))
		{
			// It's a branch, so get it from the original repo
			$url = 'http://github.com/joomla/joomla-cms/tarball/' . $this->version;
		}
		elseif (isset($versions['tags'][$this->version]))
		{
			$url = 'https://github.com/' . $versions['tags'][$this->version] . '/archive/' . $this->version . '.tar.gz';
		}
		else
		{
			$this->log("$requested: Version is unknown", Project::MSG_ERR);

			return null;
		}

		$this->log("$requested: Downloading Joomla $this->version", Project::MSG_INFO);
		$bytes = file_put_contents($tarball, fopen($url, 'r'));
		if ($bytes === false || $bytes == 0)
		{
			$this->log("$requested: Failed to download $url", Project::MSG_ERR);

			return null;
		}

		return $tarball;
	}
}
