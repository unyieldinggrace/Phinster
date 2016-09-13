# Phinster
Self-contained build system in PHP

![Picture of "Finster" from Power Rangers](http://www.rovang.org/wiki/finster.jpg "Phinster is named after the monster-maker 'Finster' from Power Rangers")

## Example build.php file

```
<?php

namespace Phinster;

global $buildTargets;

$buildTargets['myapp'] = [
	'FileDependencies' => function () {
		$valaFilePattern = '/.*\.vala$/';
		return get_files_under_directory_matching_pattern(__DIR__.'/src', $valaFilePattern);
	},
	'BuildFunction' => function ($fileDependencies) {
		$filePaths = array_map(function ($filePath) {
			return substr($filePath, strlen(__DIR__.'/')); // convert to relative paths
		}, $fileDependencies);

		$filePaths = implode(' ', $filePaths);

		return run_command("valac -o myapp --pkg libvala-0.24 $filePaths");
	},
	'Description' => 'Builds the application with the Vala compiler.',
];

$buildTargets['clean'] = [
	'BuildFunction' => function () {
		clear_dependency_hashes();
		return run_command('rm -rf myapp *.o *.c');
	},
	'Description' => 'Deletes all build files, the next build will be done from scratch.',
];

$buildTargets['networktest'] = [
	'BuildFunction' => 'ping -c 4 8.8.8.8',
	'Description' => 'Check that there is an active network connection.',
];
```

## How to Install
If you clone this repository, you can install Phinster in Linux by running the ```install.sh``` script, which simply does the following:

```
sudo cp -r phinster /usr/local/lib
sudo cp run-phinster.sh /usr/local/bin/
sudo mv /usr/local/bin/run-phinster.sh /usr/local/bin/phinster
sudo chmod +x /usr/local/bin/phinster
```

Note that PHP needs to be available on your system for Phinster to run.

## How to Use
Create a ```build.php``` file for your app (you can see some examples of ```build.php``` files for different types of projects in the "examples" directory).

After you edit the build.php file to suit your project, you can run phinster like this:
```phinster myapp```

Where "myapp" is the name of the build-target that you want to build.  Phinster looks for ```build.php``` in the current directory.

## The build.php file
The example ```build.php``` file should be pretty self-explantory. 

The keys of the ```$buildTargets``` array are the names of targets that you can specify on the command line.  If no target is specified, Phinster will list the available targets.

The sub-array for each target has several child keys:

### BuildFunction (Required)
This can be a string, in which case that string will be run as system command.  Alternatively, this can be a function which must either return true (indicating that the build succeeded) or false (indicating that the build failed).

### FileDependencies (Optional)
This can either be an array of file paths, or a function that returns an array of file paths.  In the included example build.php for a Vala project, a function is used to return all the files ending in ".vala" under the "src" directory.

Phinster will take a hash of each file and compare it to a cached hash.  If the hash of one or more files has changed since the last time the build command succeeded, the build command will be run.  Phinster stores the cached hashes of previous builds in a hidden file named ".phinsterhashes".

If the FileDependencies key does not exist, the build command will always be run.

### Description (Optional)
This is a string that Phinster will display when it lists available build targets (this can help give a brief explanation to people who are trying to figure out what your build script does). 

## Helper functions
Phinster provides some helper functions which can make your FileDependencies and BuildFunction functions a bit shorter:
* ```run_command($command)```

  runs the specified command string, passing any output through to stdout.  Returns true if the return code from the operating system was zero, otherwise returns false to indicate a failed build.
* ```get_all_paths_under_directory($path)```

  Recursively scans the given directory path and returns a list of all files underneath it.
* ```get_files_under_directory_matching_pattern($path, $pattern)```

  Same as get_all_paths_under_directory(), but only returns files that match the given regexp pattern.
* ```clear_dependency_hashes()```

   Clears the cached hashes of file dependencies.  If this is run during a build command, the hidden file ```.phinsterhashes``` will be deleted so that the next build command run will treat all files as modified.  I usually include a call to this function in my ```clean``` target, so that builds done after "cleaning" are builds done from scratch.
