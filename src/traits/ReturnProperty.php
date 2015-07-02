<?php

trait ReturnPropertyImplementation
{
	/** @var string Name of the property for the return value */
	protected $returnProperty = null;

	/** @var bool Whether to force overwrite of existing property. */
	protected $override = false;

	public function setOverride($v)
	{
		$this->override = (bool)$v;
	}

	public function setReturnProperty($name)
	{
		$this->returnProperty = $name;
	}

	/**
	 * @param $value
	 *
	 * @throws BuildException
	 */
	protected function returnValue($value)
	{
		if (empty($this->returnProperty))
		{
			throw new BuildException("'returnProperty' must be set for {$this->getTaskName()}.");
		}
		if ($this->override)
		{
			$this->getProject()->setProperty($this->returnProperty, $value);
		}
		else
		{
			$this->getProject()->setNewProperty($this->returnProperty, $value);
		}
	}

	/**
	 * @param array $pieces
	 */
	protected function returnArray($pieces)
	{
		$this->returnValue(implode(',', (array)$pieces));
	}
}
