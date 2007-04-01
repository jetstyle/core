<?php


class TemplateEngineCompilerTest extends UnitTestCase
{

	function setUp()
	{
		$this->compiller =& new TemplateEngineCompiler($this->ctx->tpl);
	}

	function test_ParseParam()
	{
		$this->compiller->_template_name = 'ttt/index';
		$data = array(
			array('@:template',
										array(
										  TE_TYPE => TE_TYPE_TEMPLATE,
										  TE_VALUE => 'ttt/index.html:template'
											)
			),
			array('@ttt/index.html:template',
										array(
										  TE_TYPE => TE_TYPE_TEMPLATE,
										  TE_VALUE => 'ttt/index.html:template'
											)
			),
			array("'nothing else metters'",
										array(
										  TE_TYPE => TE_TYPE_STRING,
										  TE_VALUE => 'nothing else metters',
											)
			),
			array('"it\'s a final coundown"',
										array(
										  TE_TYPE => TE_TYPE_STRING,
										  TE_VALUE => "it's a final coundown",
											)
			),
			array('*',
										array(
										  TE_TYPE => TE_TYPE_VARIABLE,
										  TE_VALUE => "*",
											)
			),
			array('*foo.bar',
										array(
										  TE_TYPE => TE_TYPE_VARIABLE,
										  TE_VALUE => '*foo.bar',
											)
			),
		);

		foreach ($data as $v)
		{
			list($test, $result) = $v;
			$res = $this->compiller->_parseParam($test);
			$this->assertEqual($result, $res);
		}
	}

	function test_ParseParams_let_star_is_local_template()
	{
		$this->compiller->_template_name = 'ttt/index';

		$input = '*=@:template';
		$expect = array ( 

					'*'=> array(
									  TE_TYPE => TE_TYPE_TEMPLATE,
									  TE_VALUE => 'ttt/index.html:template'
									),

		);
		$output = $this->compiller->_parseParams($input);
		$this->assertEqual($expect, $output);
	}

	function test_ParseParams_let_name_is_local_template()
	{
		$this->compiller->_template_name = 'ttt/index';

		$input = 'name=@:template';
		$expect = array ( 

					'name'=> array(
									  TE_TYPE => TE_TYPE_TEMPLATE,
									  TE_VALUE => 'ttt/index.html:template'
									),

		);
		$output = $this->compiller->_parseParams($input);
		$this->assertEqual($expect, $output);
	}

	function test_ParseParams_let_nameA_is_local_template_and_name2_is_local_template()
	{
		$this->compiller->_template_name = 'ttt/index';

		$input = 'name_a=@:template_a name_2=@:template_2';
		$expect = array ( 

					'name_a'=> array(
									  TE_TYPE => TE_TYPE_TEMPLATE,
									  TE_VALUE => 'ttt/index.html:template_a',
									),
					'name_2'=> array(
									  TE_TYPE => TE_TYPE_TEMPLATE,
									  TE_VALUE => 'ttt/index.html:template_2',
									),

		);
		$output = $this->compiller->_parseParams($input);
		$this->assertEqual($expect, $output);
	}

	function test_ParseParams_let_nameA_is_start_variable()
	{
		$input = 'name_a=*foo.bar';
		$expect = array ( 

					'name_a'=> array(
									  TE_TYPE => TE_TYPE_VARIABLE,
									  TE_VALUE => '*foo.bar',
									),

		);
		$output = $this->compiller->_parseParams($input);
		$this->assertEqual($expect, $output);
	}

	function test_ParseParams_let_star_is_double_quoted_string()
	{
		$input = '*="abc"';
		$expect = array ( 

					'*'=> array(
									  TE_TYPE => TE_TYPE_STRING,
									  TE_VALUE => 'abc',
									),

		);
		$output = $this->compiller->_parseParams($input);
		$this->assertEqual($expect, $output);
	}

	function test_ParseParams_let_star_is_single_quoted_string()
	{
		$input = "name='abc'";
		$expect = array ( 

					'name'=> array(
									  TE_TYPE => TE_TYPE_STRING,
									  TE_VALUE => 'abc',
									),

		);
		$output = $this->compiller->_parseParams($input);
		$this->assertEqual($expect, $output);
	}

	function test_ParseParams_let_star_is_single_quoted_string_and_name_is_single_quoted_string()
	{
		$input = "*='bcd' name='abc'";
		$expect = array ( 

					'*'=> array(
									  TE_TYPE => TE_TYPE_STRING,
									  TE_VALUE => 'bcd',
									),

					'name'=> array(
									  TE_TYPE => TE_TYPE_STRING,
									  TE_VALUE => 'abc',
									),

		);
		$output = $this->compiller->_parseParams($input);
		$this->assertEqual($expect, $output);
	}

	function test_ParseParams_nameA_is_start_variable_and_name2_is_string()
	{
		$input = 'name_a=*foo.bar name_2="abc"';
		$expect = array ( 

					'name_a'=> array(
									  TE_TYPE => TE_TYPE_VARIABLE,
									  TE_VALUE => '*foo.bar',
									),
					'name_2'=> array(
									  TE_TYPE => TE_TYPE_STRING,
									  TE_VALUE => 'abc',
									),

		);
		$output = $this->compiller->_parseParams($input);
		$this->assertEqual($expect, $output);
	}

	function test_ParseParams_template()
	{
		$this->compiller->_template_name = 'ttt/index';

		$input = '@ttt/index.html:template_a';
		$expect = array ( 

					'0'=> array(
									  TE_TYPE => TE_TYPE_TEMPLATE,
									  TE_VALUE => 'ttt/index.html:template_a',
									),

		);
		$output = $this->compiller->_parseParams($input);
		$this->assertEqual($expect, $output);
	}

	function test_ParseParams_string()
	{
		$input = '"string"';
		$expect = array ( 

					'0'=> array(
									  TE_TYPE => TE_TYPE_STRING,
									  TE_VALUE => 'string',
									),

		);
		$output = $this->compiller->_parseParams($input);
		$this->assertEqual($expect, $output);
	}

	function test_ParseParams_variable()
	{
		$input = 'fooo.barr';
		$expect = array ( 

					'0'=> array(
									  TE_TYPE => TE_TYPE_VARIABLE,
									  TE_VALUE => 'fooo.barr',
									),

		);
		$output = $this->compiller->_parseParams($input);
		$this->assertEqual($expect, $output);
	}

	function test_ParseParams_variable_AND_string()
	{
		$input = 'fooo.barr "string"';
		$expect = array ( 

					'0'=> array(
									  TE_TYPE => TE_TYPE_VARIABLE,
									  TE_VALUE => 'fooo.barr',
									),
					'1'=> array(
									  TE_TYPE => TE_TYPE_STRING,
									  TE_VALUE => 'string',
									),

		);
		$output = $this->compiller->_parseParams($input);
		$this->assertEqual($expect, $output);
	}

	function test_ParseParams_template_and_let_nameA_is_variable()
	{
		$this->compiller->_template_name = 'ttt/index';

		$input = '@ttt/index.html:template_a name_a=*foo.bar';
		$expect = array ( 

					'0'=> array(
									  TE_TYPE => TE_TYPE_TEMPLATE,
									  TE_VALUE => 'ttt/index.html:template_a',
									),
					'name_a'=> array(
									  TE_TYPE => TE_TYPE_VARIABLE,
									  TE_VALUE => '*foo.bar',
									),

		);
		$output = $this->compiller->_parseParams($input);
		$this->assertEqual($expect, $output);
	}

	function test_ParseParams_space_AND_template_AND_let_nameA_is_variable_AND_let_nameB_is_string()
	{
		$this->compiller->_template_name = 'ttt/index';

		$input = ' @ttt/index.html:template_a name_a=*foo.bar name_b="70"';
		$expect = array ( 

					'0'=> array(
									  TE_TYPE => TE_TYPE_TEMPLATE,
									  TE_VALUE => 'ttt/index.html:template_a',
									),
					'name_a'=> array(
									  TE_TYPE => TE_TYPE_VARIABLE,
									  TE_VALUE => '*foo.bar',
									),
					'name_b'=> array(
									  TE_TYPE => TE_TYPE_STRING,
									  TE_VALUE => '70',
									),

		);
		$output = $this->compiller->_parseParams($input);
		$this->assertEqual($expect, $output);
	}

}


?>
