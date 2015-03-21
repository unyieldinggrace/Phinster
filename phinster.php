<?php

namespace Phinster;

function run_command($command) {
  echo "$command\n";
  passthru($command, $return = 0);
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

class DependencyCache {

  const DEFAULT_CACHE_PATH = '.phinsterhashes';

  private $dependencyCache = [];
  private $updatedHashes = [];
  private $modifiedFilePaths = [];

  public function __construct() {
    $this->read_dependency_cache();
  }

  public function get_file_has_changed($filePath) {
    if (in_array($filePath, array_keys($this->modifiedFilePaths))) {
      return $this->modifiedFilePaths[$filePath];
    }

    $currentHash = $this->get_file_hash($filePath);
    $cachedHash = $this->get_cached_hash($filePath);

    $modified = ($cachedHash !== $currentHash);
    $this->set_path_modified($filePath, $modified);

    if ($modified) {
      $this->updatedHashes[$filePath] = $currentHash;
    }

    return $modified;
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
      return;
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

function get_file_dependencies($task) {
  $fileDependencies = [];

  if (!isset($task['file_dependencies'])) {
    return $fileDependencies;
  }

  if (is_array($task['file_dependencies'])) {
    $fileDependencies = $task['file_dependencies'];
  } else if (is_callable($task['file_dependencies'])) {
    $fileDependencies = $task['file_dependencies']();
  }

  return $fileDependencies;
}

function requires_rebuild($fileDependencies, $dependencyCache) {
  foreach ($fileDependencies as $filePath) {
    if ($dependencyCache->get_file_has_changed($filePath)) {
      return true;
    }
  }

  return false;
}

function phinster($buildTargets, $args) {
  $dependencyCache = new DependencyCache();
  $argCount = count($args);

  $targets = [];
  if ($argCount === 1) {
    $targets = ['default'];
  } else if ($argCount > 1) {
    $targets = $args;
    array_shift($targets);
    if ($targets[0] === __FILE__) {
      $targets = array_shift($args);
    }
  }

  $alwaysRun = false;
  if (in_array('--always-run', $targets)) {
    $targets = array_filter($targets, function ($target) {
      return ($target !== '--always-run');
    });

    $alwaysRun = true;
  }

  foreach ($targets as $target) {
    if (!isset($buildTargets[$target])) {
      echo "Skipping unknown target \"$target\"\n";
      continue;
    }

    $task = $buildTargets[$target];
    $fileDependencies = get_file_dependencies($task);

    $checkDependencies = (!$alwaysRun and isset($task['file_dependencies']));

    if ($checkDependencies and !requires_rebuild($fileDependencies, $dependencyCache)) {
      echo "Skipping target \"$target\", dependencies have not changed.\n";
      continue;
    }

    echo "Building target \"$target\"\n";
    $buildFunction = $task['build_function'];

    if (is_callable($buildFunction)) {
      $buildSucceeded = $buildFunction($fileDependencies);
    } else if (is_string($buildFunction)) {
      $buildSucceeded = run_command($buildFunction);
    } else {
      die("Failed to build - build function must be a string or a callable.\n");
    }

    if ($buildSucceeded) {
      $dependencyCache->write_dependency_cache($fileDependencies);
      echo "Build command for target \"$target\" completed successfully :)\n";
    } else {
      echo "Build command for target \"$target\" failed :(\n";
    }
  }
}
