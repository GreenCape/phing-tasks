<?php

class HelpTask extends Task
{
	private $verbose = false;

	/**
	 * @param boolean $verbose
	 */
	public function setVerbose($verbose)
	{
		$this->verbose = $verbose;
	}

	public function main()
	{
		$fmt     = "  %-22s %s\n";

		`phing -h`;
		echo "\nTargets:\n";

		$vars = array();
		$properties = $this->getProject()->getProperties();
		foreach ($properties as $key => $value)
		{
			if (preg_match('~^phing.file~', $key))
			{
				$vars[$key] = $value;
			}
		}
		$vars = array_unique($vars);

		$targets = array();
		foreach ($vars as $file)
		{
			$targets += $this->parse($file);
		}
		ksort($targets);

		foreach ($targets as $target)
		{
			$source = 'defined in ' . str_replace($properties['phing.dir'] . '/', '', $target['source']);

			if (empty($target['description']))
			{
				$target['description'] = '';
			}
			if ($target['hidden'] == 'true')
			{
				$target['description'] = trim($target['description'] . ' (hidden)');
			}
			printf($fmt, $target['name'], $target['description']);

			if (!empty($target['depends']))
			{
				$deps = array_values(array_intersect(array_keys($targets), preg_split('~\s*,\s*~', $target['depends'])));

				if (count($deps) == 0)
				{
					$deps = 'only hidden targets';
				}
				elseif (count($deps) == 1)
				{
					$deps = $deps[0];
				}
				elseif (count($deps) == 2)
				{
					$deps = implode(' and ', $deps);
				}
				else
				{
					$deps[count($deps) - 1] = 'and ' . $deps[count($deps) - 1];
					$deps                   = implode(', ', $deps);
				}
				printf($fmt, '', 'Uses ' . $deps . '; ' . $source);
			}
			else
			{
				printf($fmt, '', ucfirst($source));
			}
		}
	}

	private function parse($file)
	{
		$xml     = file_get_contents($file);
		$targets = array();
		preg_match_all('~<target\s+(.*?)/?>~sim', $xml, $matches, PREG_SET_ORDER);
		foreach ($matches as $match)
		{
			$attributes = $this->parseAttributes($match[1]);
			if ($this->verbose || empty($attributes['hidden']) || $attributes['hidden'] == 'false')
			{
				$attributes['source']         = $file;
				$targets[$attributes['name']] = $attributes;
			}
		}

		return $targets;
	}

	private function parseAttributes($attr)
	{
		$attributes = array();
		preg_match_all('~\b(\w+)=(["\'])(.*?)\2~', $attr, $matches, PREG_SET_ORDER);
		foreach ($matches as $match)
		{
			$attributes[$match[1]] = $match[3];
		}

		return $attributes;
	}
}
