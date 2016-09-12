<?php

namespace Phinster;

class DependencyCache {

	const DEFAULT_CACHE_PATH = '.phinsterhashes';

	private $dependencyCache = [];
	private $updatedHashes = [];
	private $modifiedFilePaths = [];

	public function __construct() {
		$this->read_dependency_cache();
	}

	public function update_dependency_hashes($filePaths) {
		$filesModified = false;

		foreach ($filePaths as $filePath) {
			$filesModified = ($this->update_file_hash($filePath) or $filesModified);
		}

		return $filesModified;
	}

	public function write_dependency_cache($fileDependencies, $cachePath = null) {
		foreach ($fileDependencies as $hashedFile) {
			if (isset($this->updatedHashes[$hashedFile])) {
				$this->dependencyCache[$hashedFile] = $this->updatedHashes[$hashedFile];
			}
		}

		if (is_null($cachePath)) {
			$cachePath = self::DEFAULT_CACHE_PATH;
		}

		file_put_contents($cachePath, json_encode($this->dependencyCache));
	}

	private function update_file_hash($filePath) {
		$currentHash = $this->get_file_hash($filePath);
		$cachedHash = $this->get_cached_hash($filePath);

		$modified = ($cachedHash !== $currentHash);
		$this->set_path_modified($filePath, $modified);

		if ($modified) {
			$this->updatedHashes[$filePath] = $currentHash;
		}

		return $modified;
	}

	private function read_dependency_cache($filePath = null) {
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

	private function get_file_hash($filePath) {
		if (!file_exists($filePath)) {
			return null;
		}

		$content = file_get_contents($filePath);
		if ($content) {
			return $this->hash_content($content);
		}

		return null;
	}

	private function get_cached_hash($filePath) {
		if (isset($this->dependencyCache[$filePath])) {
			return $this->dependencyCache[$filePath];
		}

		return null;
	}

	private function set_path_modified($filePath, $modified) {
		if (!isset($this->modifiedFilePaths[$filePath])) {
			$this->modifiedFilePaths[$filePath] = $modified;
		}
	}

	private function hash_content($content) {
		return sha1($content);
	}

}
