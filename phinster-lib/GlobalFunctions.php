<?php

namespace Phinster;

require('DependencyCache.php');

function run_command($command) {
	echo "$command\n";
	$return = 0;
	passthru($command, $return);
	return ($return === 0);
}

function get_all_paths_under_directory($path) {
	$filePaths = [];
	$fileNames = scandir($path);
	foreach ($fileNames as $fileName) {
		$fullPath = $path.'/'.$fileName;
		if (in_array($fileName, ['.', '..'])) {
			continue;
		}

		if (is_dir($fullPath)) {
			$filePaths = array_merge($filePaths, get_all_paths_under_directory($fullPath));
			continue;
		}

		$filePaths[] = $fullPath;
	}

	return $filePaths;
}

function get_files_under_directory_matching_pattern($directory, $pattern) {
	$files = get_all_paths_under_directory($directory);
	return array_filter($files, function ($file) use ($pattern) {
		return preg_match($pattern, $file);
	});
}

$cacheCleared = false;

function clear_dependency_hashes() {
	global $cacheCleared;
	$cacheCleared = true;

	@unlink(DependencyCache::DEFAULT_CACHE_PATH);
}

function get_file_dependencies($task) {
	$fileDependencies = [];

	if (!isset($task['FileDependencies'])) {
		return $fileDependencies;
	}

	if (is_array($task['FileDependencies'])) {
		$fileDependencies = $task['FileDependencies'];
	} else if (is_callable($task['FileDependencies'])) {
		$fileDependencies = $task['FileDependencies']();
	}

	return $fileDependencies;
}
