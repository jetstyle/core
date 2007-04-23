<?php

/**
 * Аспекты
 *
 * Пробую связать их с контекстом.
 *
 * Пусть в контекстах содержаться аспекты
 *
 * Контекст, это некий потусторонний объект, который содержит в себе иформацию 
 * о необходимых ему сервисах. Скажем -- поключение к БД, инстанцию шаблонного 
 * движка и т.п.
 *
 * Обычно объект связывается с контекстом в момент своей инициализации.
 *
 * После этого он может получить доступ к сервисам, зная их имя:
 *
 * $db =& $this->ctx->db;
 * 
 */


// from simpletest/compability.php
/**
 * Вернуть True если один и второй являются ссылками на один и тот же объект
 *
 *
 * $self =& new StdClass();
 * $self->ctx =& $self;
 * PHP4: 
 *		$self === $self->ctx 
 *		вызывает ошибку "Nesting level too deep - recursive dependency?"
 */
function is_reference(&$first, &$second) {
	static $is_php5;
	if (!isset($is_php5)) $is_php5 = version_compare(phpversion(), '5', '>=');

	if ($is_php5 && is_object($first)) {
		return ($first === $second);
	}

	$temp = $first;
	if (is_object($first) && is_object($second))
		// lucky: константа не объект
		$first = True;
	else
		// lucky: униальная вещь, но долго считать и долго сравнивать %)
		$first = uniqid("test");

	$is_ref = ($first === $second);
	$first = $temp;
	return $is_ref;
}

/**
 * Найти аспект с именем $name в контексте данного объекта
 */
function &get_aspect(&$self, $name)
{
	$ctx =& $self->ctx;

	while (	isset($ctx) 
			&& !isset($ctx->$name) 
			&& !is_reference($ctx, $ctx->ctx)) 
		$ctx =& $ctx->ctx;

	return $ctx->$name;
}

/**
 * Найти все встречающиеся аспекты с именем $name в контекстах данного объекта
 */
function find_aspects(&$self, $name)
{
	$ctx =& $self->ctx;
	$res = array();

	while (isset($ctx))
	{
		if (isset($ctx->$name)) $res[] =& $ctx->$name;
		if (is_reference($ctx, $ctx->ctx)) unset($ctx);
		else $ctx =& $ctx->ctx;
	}

	return $res;
}

/**
 * Найти все контексты данного объекта, определяющие аспект с именем $name
 */
function find_contexts_with_aspect(&$self, $name)
{
	$ctx =& $self->ctx;
	$res = array();

	while (isset($ctx))
	{
		if (isset($ctx->$name)) $res[] =& $ctx;
		if (is_reference($ctx, $ctx->ctx)) unset($ctx);
		else $ctx =& $ctx->ctx;
	}

	return $res;
}

?>
