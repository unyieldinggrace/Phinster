<?php

namespace Phinster;

interface IDependencyCache {

	public function UpdateDependencyHashes($filePaths);
	public function WriteDependencyCache($fileDependencies);

}
