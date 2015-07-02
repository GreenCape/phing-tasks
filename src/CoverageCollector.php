<?php

class CoverageCollector
{
	private $data = array();
	private $tests = array();
	private $whiteList = array();
	private $blackList = array();

	/**
	 * @return array
	 */
	public function getData()
	{
		return $this->data;
	}

	/**
	 * @return array
	 */
	public function getTests()
	{
		return $this->tests;
	}

	/**
	 * @return array
	 */
	public function getWhiteList()
	{
		return $this->whiteList;
	}

	/**
	 * @return array
	 */
	public function getBlackList()
	{
		return $this->blackList;
	}

	public function setData($data)
	{
		foreach ($data as $file => $lines)
		{
			if (!file_exists($file))
			{
				continue;
			}

			foreach ($lines as $line => $tests)
			{
				if (!is_array($tests))
				{
					continue;
				}
				if (!isset($this->data[$file][$line]))
				{
					$this->data[$file][$line] = array();
				}
				$this->data[$file][$line] = array_unique(array_merge($this->data[$file][$line], $tests));
			}
		}
	}

	public function setTests($tests)
	{
		$this->tests = array_merge($this->tests, $tests);
	}

	public function filter()
	{
		return $this;
	}

	public function setBlacklistedFiles($list)
	{
		$this->blackList = array_merge($this->blackList, $list);
	}

	public function setWhitelistedFiles($list)
	{
		$this->whiteList = array_merge($this->whiteList, $list);
	}

	public function merge(CoverageCollector $coverage)
	{
		$this->setData($coverage->getData());
		$this->setTests($coverage->getTests());
		$this->setWhitelistedFiles($coverage->getWhiteList());
		$this->setBlacklistedFiles($coverage->getBlackList());
	}

	/**
	 * @return PHP_CodeCoverage
	 */
	public function coverage()
	{
		$coverage = new PHP_CodeCoverage;
		$coverage->setData($this->data);
		$coverage->setTests($this->tests);
		$filter = $coverage->filter();
		$filter->setBlacklistedFiles($this->blackList);
		$filter->setWhitelistedFiles($this->whiteList);

		return $coverage;
	}
}
