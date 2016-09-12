<?php

namespace Phinster;

require('GlobalFunctions.php');

function phinster($buildTargets, $args) {
  global $cacheCleared;

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

  foreach ($targets as $target) {
    if (!isset($buildTargets[$target])) {
      echo "Skipping unknown target \"$target\"\n";
      continue;
    }

    $task = $buildTargets[$target];
    $fileDependencies = get_file_dependencies($task);
    $filesHaveChanged = $dependencyCache->update_dependency_hashes($fileDependencies);
    $alwaysRun = (isset($task['AlwaysRun']) and $task['AlwaysRun']);

    $checkDependencies = (!$alwaysRun and isset($task['FileDependencies']));

    if ($checkDependencies and !$filesHaveChanged) {
      echo "Skipping target \"$target\", dependencies have not changed.\n";
      continue;
    }

    echo "Building target \"$target\"\n";
    $buildFunction = $task['BuildFunction'];

    if (is_callable($buildFunction)) {
      $buildSucceeded = $buildFunction($fileDependencies);
    } else if (is_string($buildFunction)) {
      $buildSucceeded = run_command($buildFunction);
    } else {
      die("Failed to build - build function must be a string or a callable.\n");
    }

    if ($buildSucceeded) {
      echo "Build command for target \"$target\" completed successfully :)\n";
      if (!$cacheCleared) {
        $dependencyCache->write_dependency_cache($fileDependencies);
      }
    } else {
      echo "Build command for target \"$target\" failed :(\n";
    }
  }
}

if (!file_exists('build.php')) {
	die ('No build.php file found.'.PHP_EOL);
}

$buildTargets = [];
require('build.php');
phinster($buildTargets, $argv);
