# Phinster
Self-contained build system in PHP

![Picture of "Finster" from Power Rangers](http://www.rovang.org/wiki/finster.jpg "Phinster is named after the monster-maker 'Finster' from Power Rangers")

## Example usage
See the ```build.php``` file in the root of the repository for an example build script.

## How to Use
Put phinster.php and build.php in the root directory of your app.  
* phinster.php - contains all the code to actually run build commands.  
* build.php - contains the definitions of targets for your project and the commands to build them.

After you edit the build.php file to suit your project, you can run phinster like this:
```php build.php myapp```

Where "myapp" is the name of the build target you want to build.

No other code is necessary, nothing needs to be installed, except that PHP has to be available on your system.

## The build.php file
The build.php file in the repository is an example of a build file used to build a simple Vala app.  It's pretty self-explanatory.

The keys of the $buildTargets array are the names of targets that you can specify on the command line.  The sub-array for each target has three possible child keys:

### file_dependencies
This can either be an array of file paths, or a function that returns an array of file paths.  In the included example file, a function is used to
return all the files ending in ".vala" under the "src" directory.

Phinster will take a hash of each file and compare it to a cached hash.  If the hash of one or more files has changed since the last time the build command succeeded, the build command will be run.  Phinster stores the cached hashes of previous builds in a hidden file ".phinsterhashes".

If the file_dependencies key does not exist, the build command will always be run.

### build_function
This can be a string, in which case that string will be run as system command.  Alternatively, this can be a function which must either return true (indicating that the build succeeded) or false (indicating that the build failed).

### always_run
If this key is present, and set to true, the build command will always be run even if all file dependencies are up to date (can be useful for things like running the test suite).

The build.php script should finish with a call to ```phinster($buildTargets, $argv);```, which will actually start the build process based on the command line arguments.

## Helper functions
The phinster.php file defines some helper functions which can make your file_dependencies and build_function a bit shorter:
* *run_command($command)*

  runs the specified command string, passing any output through to stdout.  Returns true if the return code from the operating system was zero, otherwise returns false to indicate a failed build.
* *get_all_paths_under_directory($path)*

  Recursively scans the given directory path and returns a list of all files underneath it.
* *get_files_under_directory_matching_pattern($path, $pattern)*

  Same as get_all_paths_under_directory(), but only returns files that match the given regexp pattern.
