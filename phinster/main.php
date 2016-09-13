<?php

namespace Phinster;

require('Phinster.php');

if (!file_exists('build.php')) {
	die ('No build.php file found.'.PHP_EOL);
}

$buildTargets = [];
require('build.php');

$phinster = new Phinster(new DependencyCache());
$phinster->BuildTargets($buildTargets, $argv);
