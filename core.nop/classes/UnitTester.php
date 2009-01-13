<?php
$TEST_TOOLS=dirname(dirname(__FILE__)).'/libs';

require_once($TEST_TOOLS.'/simpletest/unit_tester.php');
require_once($TEST_TOOLS.'/simpletest/web_tester.php');
require_once($TEST_TOOLS.'/simpletest/mock_objects.php');
require_once($TEST_TOOLS.'/simpletest/reporter.php');
//require_once('Config.php');
//require_once('Configurable.php');


/**
 * Класс UnitTester -- автоматизирует поиск и выполнение юнит-тестов.
 *
 * Юнит-тест должен находиться в той же директории, что и сам юнит, и иметь 
 * суффикс названия Test.
 *
 *  например:
 *		classes/Aspect.php			-- юнит
 *		classes/AspectTest.php		-- тест для юнита
 * 
 */
class UnitTester
{

	var $tests = array();
	/*
	var $recursive = NULL;
	var $test = NULL;
	var $reporter = NULL;
	var $namespaces = NULL;
	 */

	function addTests($path,$recursive)
	{
		$full_path = $this->base_dir.$path;
		$dir=opendir($full_path);
		while(($entry=readdir($dir))!==false)
		{
			if(is_file($full_path.$entry) && preg_match('#^(.+)Test\.php$#', $entry, $matches))
			{
				$unit = $matches[1];
				if (empty($this->tests) || in_array($unit, $this->tests))
				{
//					$this->ctx->useClass($path.$unit."Test");
					$this->test->addTestFile($full_path.$entry);
				}
			}
			else 
			if($recursive 
				&& $entry !== '.' && $entry !== '..' 
				&& $entry !== '.svn' && is_dir($full_path.$entry))
			{
				$this->addTests($path.$entry.'/',$recursive);
			}
		}
		closedir($dir);
	}

	function initialize()
	{
		$this->recursive = true;
		$this->namespaces = array('classes');
		$this->test = new GroupTest(Config::get('app_name'));
		$this->reporter = new TextReporter();
	}

	function run()
	{
		$this->test->run($this->reporter);
	}

}

//class CtxUnitTestCase extends UnitTestCase
//{
//
//	function setUp()
//	{
//		$this->ctx =& $this->_reporter->ctx;
//		parent::setUp();
//	}
//
//}
?>