<?php

namespace Phinster;

require('GlobalFunctions.php');

class Phinster {

	private $dependencyCache;

	public function __construct(IDependencyCache $dependencyCache) {
		$this->dependencyCache = $dependencyCache;
	}

	public function BuildTargets($buildTargets, $args) {
		if (count($args) === 1) {
			$this->printTargetList($buildTargets);
			return;
		}

		foreach ($this->getTargetsFromArgs($args) as $target) {
			if (!isset($buildTargets[$target])) {
				$this->printMessage("Skipping unknown target \"$target\"");
				continue;
			}

			$this->runTask($buildTargets[$target], $target);
		}
	}

	private function runTask($task, $targetName) {
		$fileDependencies = $this->getFileDependencies($task);
		$filesHaveChanged = $this->dependencyCache->UpdateDependencyHashes($fileDependencies);

		if (isset($task['FileDependencies']) and !$filesHaveChanged) {
			$this->printMessage("Skipping target \"$targetName\", dependencies have not changed.");
			return;
		}

		$this->printMessage("Building target \"$targetName\"...");
		$buildFunction = $task['BuildFunction'];

		if (is_callable($buildFunction)) {
			$buildSucceeded = $buildFunction($fileDependencies);
		} else if (is_string($buildFunction)) {
			$buildSucceeded = run_command($buildFunction);
		} else {
			die("Failed to build - build function for \"$targetName\" must be a string or a callable.\n");
		}

		if ($buildSucceeded) {
			$this->printMessage("Build command for target \"$targetName\" completed successfully :)");
			$this->dependencyCache->WriteDependencyCache($fileDependencies);
		} else {
			$this->printMessage("Build command for target \"$targetName\" failed :(");
		}
	}

	private function getFileDependencies($task) {
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

	private function getTargetsFromArgs($args) {
		$targets = $args;
		array_shift($targets);
		if ($targets[0] === __FILE__) {
			$targets = array_shift($args);
		}

		return $targets;
	}

	private function printMessage($message) {
		echo $message.PHP_EOL;
	}

	private function printTargetList($buildTargets) {
		$this->printMessage('Available Targets:');

		foreach ($buildTargets as $target => $task) {
			$message = '   * '.$target;
			if (isset($task['Description']) and is_string($task['Description'])) {
				$message .= " - ".$task['Description'];
			}

			$this->printMessage($message);
		}
	}

}
