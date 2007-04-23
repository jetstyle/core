<?php

class Aspect_is_referecne_Test extends UnitTestCase
{

	function test_self_ctx()
	{
		$self =& new StdClass();
		$self->ctx =& $self;

		$output = is_reference($self, $self->ctx);

		$this->assertTrue($output);
		// lucky: ммдя, это по-прежщему мы?
		$this->assertIsA($self, 'stdclass');
	}


}

class Aspect_get_aspect_Test extends UnitTestCase
{

	function test_unknown()
	{
		$db =& new StdClass();
		$expected = NULL;

		$self =& new StdClass();
		$self->ctx =& $self;
		$self->db =& $db;

		$output =& get_aspect($self, 'dab');
		$this->assertEqual($output, $expected);
	}

	function test_level1()
	{
		$db =& new StdClass();
		$expected =& $db;

		$ctx =& new StdClass();
		$ctx->db =& $db;
		$ctx->ctx =& $ctx;

		$self =& new StdClass();
		$self->ctx =& $ctx;

		$output =& get_aspect($self, 'db');
		$this->assertReference($output, $expected);
	}

	function test_level2()
	{
		$db =& new StdClass();
		$expected =& $db;

		$ctx =& new StdClass();
		$ctx->ctx =& $ctx;
		$ctx->db =& $db;

		$self =& new StdClass();
		$self->ctx =& $ctx;

		$output =& get_aspect($self, 'db');
		$this->assertReference($output, $expected);
	}

}

class Aspect_find_aspect_Test extends UnitTestCase
{

	function test_one_aspect()
	{
		$db =& new StdClass();
		$expected = array(&$db);

		$self =& new StdClass();
		$self->ctx =& $self;
		$self->db =& $db;

		$output = find_aspects($self, 'db');
		$this->assertEqual($output, $expected);
	}

	function test_2_db_aspect()
	{
		$db =& new StdClass();

		$expected = array(&$db, &$db);

		$ctx =& new StdClass();
		$ctx->ctx =& $ctx;
		$ctx->db =& $db;

		$ctx2 =& new StdClass();
		$ctx2->ctx =& $ctx;
		$ctx2->db =& $db;

		$self =& new StdClass();
		$self->ctx =& $ctx2;

		$output = find_aspects($self, 'db');
		$this->assertEqual($output, $expected);

		$this->assertReference($self->ctx,		 $ctx2);
		$this->assertReference($ctx2->ctx,		 $ctx);
		$this->assertReference($ctx->ctx,		 $ctx);
		$this->assertReference($ctx->ctx->ctx,  $ctx);
	}

}

class Aspect_find_contexts_with_aspect_Test extends UnitTestCase
{

	function test_one_context()
	{
		$db =& new StdClass();

		$ctx =& new StdClass();
		$ctx->ctx =& $ctx;
		$ctx->db =& $db;

		$expected = array(&$ctx);

		$self =& new StdClass();
		$self->ctx =& $ctx;

		$output = find_contexts_with_aspect($self, 'db');

		$this->assertEqual(count($output), count($expected));
		$this->assertReference($output[0], $expected[0]);
		$this->assertReference($output[0], $ctx);
	}

	function test_2_contexts()
	{
		$db =& new StdClass();

		$ctx =& new StdClass();
		$ctx->ctx =& $ctx;
		$ctx->db =& $db;

		$ctx2 =& new StdClass();
		$ctx2->ctx =& $ctx;
		$ctx2->db =& $db;

		$expected = array(&$ctx2, &$ctx);

		$self =& new StdClass();
		$self->ctx =& $ctx2;

		$output = find_contexts_with_aspect($self, 'db');

		$this->assertEqual(count($output), count($expected));
		$this->assertReference($output[0], $expected[0]);
		$this->assertReference($output[1], $expected[1]);
		$this->assertReference($output[0], $ctx2);
		$this->assertReference($output[1], $ctx);

	}

}

?>
