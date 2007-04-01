<?php
$TEST_TOOLS='/home/lucky/public_html/redarmy/core.nop/libs';

require_once($TEST_TOOLS.'/simpletest/unit_tester.php');
require_once($TEST_TOOLS.'/simpletest/web_tester.php');
require_once($TEST_TOOLS.'/simpletest/mock_objects.php');
require_once($TEST_TOOLS.'/simpletest/reporter.php');
require_once('Config.php');
require_once('Configurable.php');


class UnitTester extends Configurable
{

	function addTests($path,$recursive)
	{
		$dir=opendir($path);
		while(($entry=readdir($dir))!==false)
		{
			if(is_file($path.'/'.$entry) && preg_match('#^(.+)Test\.php$#', $entry, $matches))
			{
				$this->ctx->useClass($matches[1]);
				$this->test->addTestFile($path.'/'.$entry);
			}
			else 
			if($recursive && $entry!=='.' && $entry!=='..' && $entry!=='.svn' && is_dir($path.'/'.$entry))
			{
				$this->addTests($path.'/'.$entry,$recursive);
			}
		}
		closedir($dir);
	}

	function initialize(&$ctx, $config=NULL)
	{
		parent::initialize($ctx, $config);

		config_set($this, 'recursive', True);
		config_set($this, 'namespaces', array('classes'));

		config_set($this, 'test', new GroupTest($ctx->project_name));
		foreach ($ctx->DIRS as $dir)
		{
			foreach ($this->namespaces as $namespace)
			{
				$fullpath = $dir.'/'.$namespace.'/';
				if (is_dir($fullpath))
					$this->addTests($fullpath, $this->recursive);
			}
		}
		/*
			$testClass=basename($fullpath,'.php');
			include_once($fullpath);
			$test=new $testClass(basename($rootPath)."/$target");
		 */
		config_set($this, 'reporter', new TextReporter());
		// lucky: да простят мя. ;)
		// однако в тестах бывает нужен $ctx
		config_set($this->reporter, 'ctx', &$ctx);
	}

	function run()
	{
		$this->test->run($this->reporter);
	}

}

?>
