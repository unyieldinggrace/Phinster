<?php

namespace Phinster;

include_once(__DIR__.'/phinster.php');

$buildTargets = [
  'clean' => [
    'build_function' => function () {
      clear_dependency_hashes();
      return run_command('rm -rf myapp *.o *.c');
    },
  ],

  'myapp' => [
    'file_dependencies' => function () {
      $valaFilePattern = '/.*\.vala$/';
      return get_files_under_directory_matching_pattern(__DIR__.'/src', $valaFilePattern);
    },
    'build_function' => function ($fileDependencies) {
      $filePaths = array_map(function ($filePath) {
        return substr($filePath, strlen(__DIR__.'/')); // convert to relative paths
      }, $fileDependencies);

      $filePaths = implode(' ', $filePaths);

      return run_command("valac -o myapp --pkg libvala-0.24 $filePaths");
    },
  ],
];

phinster($buildTargets, $argv);
