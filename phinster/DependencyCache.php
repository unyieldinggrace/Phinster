<?php

namespace Phinster;

require('IDependencyCache.php');

class DependencyCache implements IDependencyCache {

	const DEFAULT_CACHE_PATH = '.phinsterhashes';

	private $dependencyCache = [];
	private $updatedHashes = [];
	private $modifiedFilePaths = [];
	private $cachePath;

	private static $cacheCleared = false;

	public function __construct() {
		$this->readDependencyCache();
		$this->cachePath = getcwd().DIRECTORY_SEPARATOR.self::DEFAULT_CACHE_PATH;
	}

	public function UpdateDependencyHashes($filePaths) {
		$filesModified = false;

		foreach ($filePaths as $filePath) {
			$filesModified = ($this->updateFileHash($filePath) or $filesModified);
		}

		return $filesModified;
	}

	public function WriteDependencyCache($fileDependencies) {
		if (self::$cacheCleared) {
			return;
		}

		foreach ($fileDependencies as $hashedFile) {
			if (isset($this->updatedHashes[$hashedFile])) {
				$this->dependencyCache[$hashedFile] = $this->updatedHashes[$hashedFile];
			}
		}

		file_put_contents($this->cachePath, json_encode($this->dependencyCache));
	}

	public static function SetCacheCleared() {
		self::$cacheCleared = true;
	}

	private function updateFileHash($filePath) {
		$currentHash = $this->getFileHash($filePath);
		$cachedHash = $this->getCachedHash($filePath);

		$modified = ($cachedHash !== $currentHash);
		$this->setPathModified($filePath, $modified);

		if ($modified) {
			$this->updatedHashes[$filePath] = $currentHash;
		}

		return $modified;
	}

	private function readDependencyCache($filePath = null) {
		if (is_null($filePath)) {
			$filePath = self::DEFAULT_CACHE_PATH;
		}

		if (!file_exists($filePath)) {
			return;
		}

		$json = file_get_contents($filePath);
		if ($json) {
			$this->dependencyCache = json_decode($json, true);
		}
	}

	private function getFileHash($filePath) {
		if (!file_exists($filePath)) {
			return null;
		}

		$content = file_get_contents($filePath);
		if ($content) {
			return $this->hashContent($content);
		}

		return null;
	}

	private function getCachedHash($filePath) {
		if (isset($this->dependencyCache[$filePath])) {
			return $this->dependencyCache[$filePath];
		}

		return null;
	}

	private function setPathModified($filePath, $modified) {
		if (!isset($this->modifiedFilePaths[$filePath])) {
			$this->modifiedFilePaths[$filePath] = $modified;
		}
	}

	private function hashContent($content) {
		return sha1($content);
	}

}
