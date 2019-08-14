<?php

namespace GreenCape\PhingTasks;

use BuildException;
use Task;

class UmlFilter extends Task
{
	const OP_EXTENDS = '<|--';
	const OP_IMPLEMENTS = '<|..';

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

	/**
	 * @throws BuildException
	 */
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

	/**
	 * @throws BuildException
	 */
	protected function validate()
	{
		if (empty($this->jar))
		{
			throw new BuildException('Please provide location of plantuml.jar');
		}

		if (count($this->fileSets) === 0 && count($this->fileLists) === 0)
		{
			throw new BuildException('Need either nested fileset or nested filelist to iterate through');
		}
	}

	/**
	 * @return array
	 * @throws BuildException
	 */
	protected function handleFiles()
	{
		$aggregate = array();

		foreach ($this->getFileSetFiles() as $file)
		{
			$code = file_get_contents($file);

			foreach ($this->generateDiagramSource($code) as $group => $fragments)
			{
				$aggregate[$group] = array_merge((array) $aggregate[$group], $fragments);
			}
		}

		return $aggregate;
	}

	/**
	 * @param $code
	 *
	 * @return array
	 */
	protected function generateDiagramSource($code)
	{
		$namespace = $this->findNamespace($code);
		$uses      = $this->findUseStatements($code);
		$classes   = $this->findClassDeclaration($code);
		$aggregate = $this->prepareGroups($namespace);

		foreach ($classes as $info)
		{
			$currentClass = $this->fullyQualifiedName($namespace, $info['classname'], $uses);
			$filename     = $this->dir . '/class-' . $currentClass . '.puml';
			$uml          = "{$info['declaration']} {$currentClass}\n";

			if (!empty($info['extends']))
			{
				$uml .= $this->handleReference($currentClass, self::OP_EXTENDS, $this->fullyQualifiedName($namespace, $info['extends'], $uses));
			}

			foreach ($info['implements'] as $interface)
			{
				$uml .= $this->handleReference($currentClass, self::OP_IMPLEMENTS, $this->fullyQualifiedName($namespace, $interface, $uses));
			}

			$this->writePuml($filename, trim($uml));
			$this->log("Generated class diagram for {$currentClass}");

			foreach ($aggregate as $level => $levelCode)
			{
				if ($aggregate[$level] === '')
				{
					$aggregate[$level] = [];
				}

				$aggregate[$level][] = $uml;
			}

			$this->handleMethods($currentClass, $info['code']);
		}

		return $aggregate;
	}

	/**
	 * @param $class
	 * @param $op
	 * @param $reference
	 *
	 * @return string
	 */
	protected function handleReference($class, $op, $reference)
	{
		return $this->includeReferencedClass($reference, $op) . "{$reference} {$op} {$class}\n";
	}

	/**
	 * @param $class
	 *
	 * @param $op
	 *
	 * @return string
	 */
	protected function includeReferencedClass($class, $op)
	{
		if ($this->includeRef)
		{
			$filename = "{$this->dir}/class-{$class}.puml";
			$uml      = ($op === self::OP_IMPLEMENTS ? 'interface ' : 'class ') . $class;
			file_put_contents($filename, "@startuml\n!include skin.puml\n{$uml}\n@enduml\n");

			return "!include {$filename}\n";
		}

		return '';
	}

	/**
	 * @param $class
	 * @param $code
	 */
	protected function handleMethods($class, $code)
	{
		$pattern = "~@startuml\n(.*?)@enduml.*?(private|protected|public|static|final)?\s+function\s+(\S+)\s*\(~sm";

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
	}

	/**
	 * @param $filename
	 * @param $uml
	 */
	protected function writePuml($filename, $uml)
	{
		if (empty($uml))
		{
			return;
		}

		file_put_contents($filename, "@startuml\n!include skin.puml\n{$uml}\n@enduml\n");
	}

	/**
	 * @param $uml
	 *
	 * @return array
	 */
	protected function removeIncludes($uml)
	{
		$uml = array_filter(explode("\n", implode("\n", $uml)), static function ($line) {
			return !preg_match('~^!include~', $line);
		});

		return $uml;
	}

	protected function render()
	{
		$this->log('Rendering ...');
		shell_exec("java -jar '{$this->jar}' -tsvg -quiet '{$this->dir}/*.puml'");
		$this->log('... done.');
	}

	/**
	 * @param $namespace
	 *
	 * @return array[]
	 */
	protected function prepareGroups($namespace)
	{
		$aggregate = array('global' => []);
		$currLevel = '';
		$parts     = explode('.', $namespace);
		while (!empty($parts))
		{
			$currLevel             = trim($currLevel . '.' . array_shift($parts), '.');
			$aggregate[$currLevel] = [];
		}

		return $aggregate;
	}

	/**
	 * @param string $namespace
	 * @param string $classname
	 * @param array  $uses
	 *
	 * @return mixed|string
	 */
	protected function fullyQualifiedName($namespace, $classname, array $uses)
	{
		if ($classname[0] === '\\')
		{
			return $this->dotNotation(substr($classname, 1));
		}

		if (isset($uses[$classname]))
		{
			return $this->dotNotation($uses[$classname]);
		}

		return $this->dotNotation($namespace . $classname);
	}

	protected function findUseStatements($code)
	{
		$uses       = [];
		$identifier = '([\S]+)';

		if (preg_match_all('~\b' . 'use\s+([a-z\\\]+?)(?:\s+as\s+' . $identifier . ')?;~i', $code, $useStatements, PREG_SET_ORDER))
		{
			foreach ($useStatements as $use)
			{
				if (isset($use[2]))
				{
					$alias = $use[2];
				}
				else
				{
					$alias = preg_replace('~.*\\\~', '', $use[1]);
				}

				$uses[$alias] = $use[1];
			}
		}

		return $uses;
	}

	protected function findNamespace($code)
	{
		$namespace = '';

		if (preg_match('~\b' . 'namespace\s+([a-z\\\]+?);~i', $code, $match))
		{
			$namespace = $match[1] . '\\';
		}

		return $namespace;
	}

	protected function findClassDeclaration($code)
	{
		$result = [];

		$identifier  = '([\S]+)';
		$declaration = '(abstract\s+class|interface|trait|class)\s+' . $identifier;
		$extends     = '\s+extends\s+' . $identifier;
		$implements  = '\s+implements\s+' . $identifier . '(:?\s*,\s*' . $identifier . ')*';
		$pattern     = "~{$declaration}(:?{$extends})?(:?{$implements})?\s*\{~";

		if (!preg_match_all($pattern, $code, $matches, PREG_OFFSET_CAPTURE | PREG_SET_ORDER))
		{
			return array();
		}

		// Separate classes
		foreach ($matches as $i => $match)
		{
			if (isset($matches[$i + 1]))
			{
				$body = substr($code, $match[0][1], $matches[$i + 1][0][1] - $match[0][1]);
			}
			else
			{
				$body = substr($code, $match[0][1]);
			}

			$implements = [];
			for ($index = 6; isset($match[$index]); $index += 2)
			{
				$implements[] = $match[$index][0];
			}

			$result[$i] = [
				'declaration' => $match[1][0],
				'classname'   => $match[2][0],
				'extends'     => isset($match[4]) ? $match[4][0] : null,
				'implements'  => $implements,
				'code'        => $body
			];
		}

		return $result;
	}

	/**
	 * @param $subject
	 *
	 * @return string
	 */
	protected function dotNotation($subject)
	{
		return trim(str_replace('\\', '.', $subject), '.');
	}
}
