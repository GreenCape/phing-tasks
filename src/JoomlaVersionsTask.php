<?php

class JoomlaVersionsTask extends Task
{
	protected $file = 'versions.json';

	public function setVersionFile($file)
	{
		$this->file = $file;
	}

	public function main()
	{
		// GreenCape first, so entries get overwritten if provided by Joomla
		$repos = array(
			'greencape/joomla-legacy',
			'joomla/joomla-cms',
		);

		$versions = array();
		foreach ($repos as $repo)
		{
			$command = "git ls-remote https://github.com/{$repo}.git | grep -E 'refs/(tags|heads)' | grep -v '{}'";
			$result  = shell_exec($command);
			$refs    = explode(PHP_EOL, $result);
			$pattern = '/^[0-9a-f]+\s+refs\/(heads|tags)\/([a-z0-9\.\-_]+)$/im';
			foreach ($refs as $ref)
			{
				if (preg_match($pattern, $ref, $match))
				{
					if ($match[1] == 'tags')
					{
						if (!preg_match('/^\d+\.\d+\.\d+$/m', $match[2]))
						{
							continue;
						}
						$parts = explode('.', $match[2]);
						$this->checkAlias($versions, $parts[0], $match[2]);
						$this->checkAlias($versions, $parts[0] . '.' . $parts[1], $match[2]);
						$this->checkAlias($versions, 'latest', $match[2]);
					}
					$versions[$match[1]][$match[2]] = $repo;
				}
			}
		}

		// Special case: 1.6 and 1.7 belong to 2.x
		$versions['alias']['1'] = $versions['alias']['1.5'];

		file_put_contents($this->file, json_encode($versions, JSON_PRETTY_PRINT));
	}

	/**
	 * @param $versions
	 * @param $alias
	 * @param $version
	 *
	 * @return mixed
	 */
	protected function checkAlias(&$versions, $alias, $version)
	{
		if (!isset($versions['alias'][$alias]) || version_compare($versions['alias'][$alias], $version, '<'))
		{
			$versions['alias'][$alias] = $version;
		}
	}
}
