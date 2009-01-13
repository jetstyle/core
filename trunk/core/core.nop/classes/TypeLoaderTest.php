<?php

class TypeLoaderTest extends CtxUnitTestCase
{

	function test_loadConfig_types_String()
	{
		$expected = array(
			'String' =>
				array(
					'class' => 'TypeString',
				),
		);

		$this->ctx->useClass('TypeLoader');
		$o =& new TypeLoader();
		$o->initialize($this->ctx);
		$output = $o->loadConfig('types/String');
		$this->assertEqual($output, $expected);
	}

	function test_load_types_String()
	{
		$expected_class = 'TypeString';

		$this->ctx->useClass('TypeLoader');
		$o =& new TypeLoader();
		$o->initialize($this->ctx);
		$o->load('types/String');
		$output =& $o->data;
		$this->assertIsA($output, $expected_class);
		$this->assertEqual($output->class, $expected_class);
	}


}
//SimpleTest::ignore('TypeLoaderTest');



?>
