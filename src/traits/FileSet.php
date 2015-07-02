<?php

trait FileSetImplementation
{
	/** @var FileSet[] */
	protected $fileSets = array();

	/** @var FileList[] */
	protected $fileLists = array();

	/**
	 * Nested adder, adds a set of files (nested fileset attribute).
	 *
	 * @param FileSet $fs
	 *
	 * @return void
	 */
	public function addFileSet(FileSet $fs)
	{
		$this->fileSets[] = $fs;
	}

	/**
	 * Supports embedded <filelist> element.
	 *
	 * @return FileList
	 */
	public function createFileList()
	{
		$num = array_push($this->fileLists, new FileList());

		return $this->fileLists[$num - 1];
	}

	protected function getFileSetFiles()
	{
		if (count($this->fileSets) == 0 && count($this->fileLists) == 0)
		{
			throw new BuildException("Need either nested fileset or nested filelist to iterate through");
		}

		$srcFiles = array();
		foreach ($this->fileLists as $fl)
		{
			$dir = $fl->getDir($this->project);
			foreach ($fl->getFiles($this->project) as $file)
			{
				$srcFiles[] = $dir .'/' . $file;
			}
		}

		foreach ($this->fileSets as $fs)
		{
			$dir = $fs->getDir($this->project);
			foreach ($fs->getDirectoryScanner($this->project)->getIncludedFiles() as $file)
			{
				$srcFiles[] = $dir . '/' . $file;
			}
		}

		return $srcFiles;
	}

	protected function getFileSetDirectories()
	{
		if (count($this->fileSets) == 0 && count($this->fileLists) == 0)
		{
			throw new BuildException("Need either list, nested fileset or nested filelist to iterate through");
		}

		$srcDirs = array();

		foreach ($this->fileSets as $fs)
		{
			$srcDirs  = array_merge($srcDirs, $fs->getDirectoryScanner($this->project)->getIncludedDirectories());
		}

		return $srcDirs;
	}
}
