<?php


class ModuleLoaderTest extends UnitTestCase
{

	function setUp()
	{
		$this->ctx =& $this->_reporter->ctx;
	}

	function test_load_modules_spots_Breadcrumbs()
	{
		$expected_class = 'Breadcrumbs';

		$this->ctx->useClass('ModuleLoader');
		$o =& new ModuleLoader();
		$o->initialize($this->ctx);
		$o->load('spots/Breadcrumbs');
		$output =& $o->data;
		$this->assertIsA($output, $expected_class);
		$this->assertEqual($output->class, $expected_class);
		$this->assertEqual($output->a, 1);
	}

}
//SimpleTest::ignore('ModuleLoaderTest');


?>
