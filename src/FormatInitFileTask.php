<?php

class FormatInitFileTask extends Task
{
	private $file;
	private $level = Project::MSG_DEBUG;

	public function setFile($file)
	{
		$this->file = $file;
	}

	public function setLevel($level)
	{
		static $levels = array(
			'info'    => Project::MSG_INFO,
			'debug'   => Project::MSG_DEBUG,
			'error'   => Project::MSG_ERR,
			'verbose' => Project::MSG_VERBOSE,
			'warning' => Project::MSG_WARN,
		);
		$this->level = $levels[$level];
	}

	public function main()
	{
		$this->log("Formatting file {$this->file}");
		$tmp = tempnam(dirname($this->file), 'tmp');

		$src = fopen($this->file, 'r');
		if (!$src)
		{
			$this->log("Unable to open {$this->file}", Project::MSG_WARN);
			return 1;
		}
		$dst = fopen($tmp, 'w');
		if (!$dst)
		{
			$this->log("Unable to open {$tmp}", Project::MSG_WARN);
			fclose($src);
			return 1;
		}

		$prefix = '';
		$buffer = '';
		$insidePara = false;
		$insideStat = false;
		$incomplete = false;
		do
		{
			$line = trim(fgets($src));
			if (empty($line))
			{
				$this->dump("Skipping empty line", $line);
			}
			elseif (preg_match('~^(#|--)~', $line))
			{
				$this->dump("Skipping comment", $line);
			}
			elseif ($incomplete)
			{
				$this->dump("Incomplete:", $line);
				$prefix     = trim("$prefix $line");
				$incomplete = !preg_match('~VALUES$~i', $line);
			}
			elseif (preg_match('~;$~', $line))
			{
				$this->dump("Encountered ';':", $line);
				$buffer = trim("$buffer $prefix $line");
				fwrite($dst, "$buffer\n");
				$buffer = $prefix = '';
				$insidePara = false;
				$insideStat = false;
			}
			elseif ($insidePara)
			{
				$this->dump("inside (), adding:", $line);
				$buffer = trim("$buffer $prefix $line");
			}
			elseif (preg_match('~\($~', $line))
			{
				$this->dump("Encountered '(':", $line);
				$buffer = trim("$buffer $prefix $line");
				$insidePara = true;
			}
			elseif (preg_match('~^(INSERT|REPLACE)~i', $line, $match))
			{
				$this->dump("Multiline '{$match[1]}':", $line);
				$prefix = $line;
				$insideStat = true;
				$incomplete = !preg_match('~VALUES$~i', $line);
			}
			elseif ($insideStat && preg_match('~^\((.*)\)\s*,~', $line, $match))
			{
				$this->dump("inside statement, adding:", $line);
				$buffer .= trim("$prefix ($match[1]);");
				fwrite($dst, "$buffer\n");
				$buffer = '';
			}
			else
			{
				$this->dump("Line not handled:", $line, Project::MSG_WARN);
				$buffer = trim("$buffer $line");
			}
		}
		while (!feof($src));

		fclose($dst);
		fclose($src);

		copy($tmp, $this->file);
		unlink($tmp);

		return 0;
	}

	private function dump($label, $value, $level = null)
	{
		if (is_null($level))
		{
			$level = $this->level;
		}
		$this->log(sprintf("%-30s %s", $label, substr($value, 0, 80)), $level);
	}
}
