<?php

namespace GreenCape\PhingTasks;

class CombineCoverageTask extends \Task
{
	/** @var \FileSet[] */
	protected $filesets = array(); // all fileset objects assigned to this task

	/** @var bool */
	protected $haltonerror = true; // stop build on errors

	protected $pattern = null;
	protected $replace = null;

	protected $clover = null;
	protected $crap4j = null;
	protected $html = null;
	protected $php = null;
	protected $text = null;

	/**
	 * @param string $pattern
	 */
	public function setPattern($pattern)
	{
		$this->pattern = '~' . str_replace('~', '\\~', $pattern) . '~';
	}

	/**
	 * @param string $replace
	 */
	public function setReplace($replace)
	{
		$this->replace = $replace;
	}

	/**
	 * @param string $clover
	 */
	public function setClover($clover)
	{
		$this->clover = $clover;
	}

	/**
	 * @param string $crap4j
	 */
	public function setCrap4j($crap4j)
	{
		$this->crap4j = $crap4j;
	}

	/**
	 * @param string $html
	 */
	public function setHtml($html)
	{
		$this->html = $html;
	}

	/**
	 * @param string $php
	 */
	public function setPhp($php)
	{
		$this->php = $php;
	}

	/**
	 * @param string $text
	 */
	public function setText($text)
	{
		$this->text = $text;
	}

	/**
	 * Set the haltonerror attribute - when true, will
	 * make the build fail when errors are detected.
	 *
	 * @param bool $haltonerror Flag if the build should be stopped on errors
	 *
	 * @return void
	 */
	public function setHaltonerror($haltonerror)
	{
		$this->haltonerror = (bool)$haltonerror;
	}

	/**
	 * Nested creator, creates a \FileSet for this task
	 *
	 * @param \FileSet $fs Set of files to copy
	 *
	 * @return void
	 */
	public function addFileSet(\FileSet $fs)
	{
		$this->filesets[] = $fs;
	}

	public function main()
	{
		$this->loadPHPUnit();

		$collection = new CoverageCollector();
		foreach ($this->getFilenames() as $file)
		{
			$this->log("Merging $file");
			$coverage = null;
			$code = file_get_contents($file);
			$code = str_replace('PHP_CodeCoverage', 'CoverageCollector', $code);
			if (!empty($this->pattern))
			{
				$code = preg_replace($this->pattern, $this->replace, $code);
			}
			eval('?>' . $code);
			$collection->merge($coverage);
		}
		$this->handleReports($collection->coverage());
	}

	/**
	 * Iterate over all filesets and return the filename of all files.
	 *
	 * @return array an array of filenames
	 */
	private function getFilenames()
	{
		$filenames = array();

		foreach ($this->filesets as $fileset)
		{
			$ds = $fileset->getDirectoryScanner($this->project);
			$ds->scan();

			$files = $ds->getIncludedFiles();

			foreach ($files as $file)
			{
				$filenames[] = $ds->getBaseDir() . "/" . $file;
			}
		}

		return $filenames;
	}

	private function handleReports(\PHP_CodeCoverage $coverage)
	{
		if ($this->clover)
		{
			$this->log("Generating code coverage report in Clover XML format ...");

			$writer = new \PHP_CodeCoverage_Report_Clover;
			$writer->process($coverage, $this->clover);
		}

		if ($this->crap4j)
		{
			$this->log("Generating code coverage report in Crap4J XML format...");

			$writer = new \PHP_CodeCoverage_Report_Crap4j;
			$writer->process($coverage, $this->crap4j);
		}

		if ($this->html)
		{
			$this->log("Generating code coverage report in HTML format ...");

			$writer = new \PHP_CodeCoverage_Report_HTML;
			$writer->process($coverage, $this->html);
		}

		if ($this->php)
		{
			$this->log("Generating code coverage report in PHP format ...");

			$writer = new \PHP_CodeCoverage_Report_PHP;
			$writer->process($coverage, $this->php);
		}

		if ($this->text)
		{
			$writer = new \PHP_CodeCoverage_Report_Text(50, 90, false, false);
			$writer->process($coverage, $this->text);
		}
	}

	private function loadPHPUnit($pharLocation = null)
	{
		if (class_exists('PHP_CodeCoverage'))
		{
			return;
		}

		if (empty($pharLocation))
		{
			$pharLocation = trim(`which phpunit`);
		}

		$GLOBALS['_SERVER']['SCRIPT_NAME'] = '-';
		if (file_exists($pharLocation))
		{
			ob_start();
			include $pharLocation;
			ob_end_clean();

			include_once 'PHPUnit/Autoload.php';
		}

		if (!class_exists('PHP_CodeCoverage'))
		{
			throw new \BuildException("CombineCoverageTask requires PHPUnit to be installed", $this->getLocation());
		}
	}

	/**
	 * @param string $message
	 * @param null   $location
	 *
	 * @throws \BuildException
	 */
	protected function logError($message, $location = null)
	{
		if ($this->haltonerror)
		{
			throw new \BuildException($message, $location);
		}
		else
		{
			$this->log($message, \Project::MSG_ERR);
		}
	}
}
