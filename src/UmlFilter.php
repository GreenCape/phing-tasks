<?php

namespace GreenCape\PhingTasks;

class UmlFilter extends \Task
{
	protected $file;
	protected $dir;
	protected $skin;
	protected $jar;
	protected $includeRef = true;

	use FileSetImplementation;

	public function setFile($file)
	{
		$this->file = $file;
	}

	public function setDir($dir)
	{
		$this->dir = $dir;
	}

	public function setSkin($skin)
	{
		$this->skin = $skin;
	}

	/**
	 * @param mixed $jar
	 */
	public function setJar($jar)
	{
		$this->jar = $jar;
	}

	public function main()
	{
		$this->validate();

		$aggregate = $this->handleFiles();

		foreach ($aggregate as $group => $fragments)
		{
			$this->writePuml(
				$this->dir . '/package-' . $group . '.puml',
				implode("\n", array_unique($this->removeIncludes($fragments))) . "\n"
			);
		}

		$this->render();
	}

	protected function validate()
	{
		if (empty($this->jar))
		{
			throw new \BuildException("Please provide location of plantuml.jar");
		}

		if (count($this->fileSets) == 0 && count($this->fileLists) == 0)
		{
			throw new \BuildException("Need either nested fileset or nested filelist to iterate through");
		}
	}

	/**
	 * @return array
	 * @throws \BuildException
	 */
	protected function handleFiles()
	{
		$aggregate = array();

		foreach ($this->getFileSetFiles() as $file)
		{
			$code = file_get_contents($file);

			foreach ($this->generateDiagramSource($code) as $group => $fragments)
			{
				$aggregate[$group] = array_merge((array)$aggregate[$group], $fragments);
			}
		}

		return $aggregate;
	}

	/**
	 * @param $matches
	 */
	protected function generateDiagramSource($code)
	{
		$identifier = '([\S]+)';

		$namespace = '';

		if (preg_match('~namespace\s+(.*?);~', $code, $match))
		{
			$namespace = trim(str_replace('\\', '.', $match[1]), '.') . '.';
		}

		$declaration = '(abstract\s+class|interface|trait|class)\s+' . $identifier;
		$extends     = '\s+extends\s+' . $identifier;
		$implements  = '\s+implements\s+' . $identifier . '(:?\s*,\s*' . $identifier . ')*';
		$pattern     = "~{$declaration}(:?{$extends})?(:?{$implements})?\s*\{~";

		if (!preg_match_all($pattern, $code, $matches, PREG_OFFSET_CAPTURE | PREG_SET_ORDER))
		{
			return array();
		}

		$classes = array();

		for ($i = 0, $n = count($matches); $i < $n; $i++)
		{
			if (isset($matches[$i + 1]))
			{
				$classes[$i] = substr($code, $matches[$i][0][1], $matches[$i + 1][0][1] - $matches[$i][0][1]);
			}
			else
			{
				$classes[$i] = substr($code, $matches[$i][0][1]);
			}
		}

		$aggregate = $this->prepareGroups($namespace);

		foreach ($matches as $i => $match)
		{
			$uml          = '';
			$currentClass = $namespace . $match[2][0];
			$filename     = $this->dir . '/class-' . $currentClass . '.puml';
			$uml .= "{$match[1][0]} {$currentClass}\n";

			if (!empty($match[4][0]))
			{
				$uml .= $this->handleReference($namespace, $currentClass, '<|--', $match[4][0]);
			}

			if (!empty($match[6][0]))
			{
				$uml .= $this->handleReference($namespace, $currentClass, '<|..', $match[6][0]);
			}

			$this->writePuml($filename, $uml);
			$this->log("Generated class diagram for {$currentClass}");

			foreach ($aggregate as $level => $levelCode)
			{
				$aggregate[$level][] = $uml;
			}

			$this->handleMethods($currentClass, $classes[$i]);
		}

		return $aggregate;
	}

	/**
	 * @param $namespace
	 * @param $class
	 * @param $op
	 * @param $reference
	 *
	 * @return string
	 */
	protected function handleReference($namespace, $class, $op, $reference)
	{
		$reference = str_replace('\\', '.', $reference);
		$reference = $reference[0] == '.' ? substr($reference, 1) : $namespace . $reference;
		$uml       = "{$reference} {$op} {$class}\n";
		$uml .= $this->includeReferencedClass($reference);

		return $uml;
	}

	/**
	 * @param $class
	 *
	 * @return array
	 */
	protected function includeReferencedClass($class)
	{
		$uml = '';

		if ($this->includeRef)
		{
			$file = "{$this->dir}/class-{$class}.puml";
			$uml  = "!include {$file}\n";
			touch($file);
		}

		return $uml;
	}

	/**
	 * @param $class
	 * @param $code
	 */
	protected function handleMethods($class, $code)
	{
		$pattern = "~@startuml\n(.*?)@enduml.*?(private|protected|public)?\s+function\s+(\S+)\s*\(~sm";

		if (!preg_match_all($pattern, $code, $matches, PREG_SET_ORDER))
		{
			return;
		}

		foreach ($matches as $match)
		{
			$methodName = $class . '.' . $match[3];
			$this->writePuml($this->dir . '/seq-' . $methodName . '.puml', implode("\n", preg_split("~\s+\*\s+~", $match[1])) . "\n");
			$this->log("Extracted diagram for {$methodName}()");
		}

		return;
	}

	/**
	 * @param $filename
	 * @param $uml
	 */
	protected function writePuml($filename, $uml)
	{
		file_put_contents($filename, "@startuml\n!include skin.puml\n{$uml}@enduml\n");
	}

	/**
	 * @param $uml
	 *
	 * @return array
	 */
	protected function removeIncludes($uml)
	{
		$uml = array_filter(explode("\n", implode("\n", $uml)), function ($line)
		{
			return !preg_match('~^!include~', $line);
		});

		return $uml;
	}

	protected function render()
	{
		$this->log("Rendering ...");
		`java -jar '{$this->jar}' -tsvg '{$this->dir}/*.puml'`;
		$this->log("... done.");
	}

	/**
	 * @param $namespace
	 *
	 * @return array
	 */
	protected function prepareGroups($namespace)
	{
		$aggregate = array('global' => '');
		$currLevel = '';
		$parts     = explode('.', $namespace);
		while (!empty($parts))
		{
			$currLevel             = trim($currLevel . '.' . array_shift($parts), '.');
			$aggregate[$currLevel] = '';
		}

		return $aggregate;
}
}
