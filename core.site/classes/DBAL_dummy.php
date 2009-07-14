<?php
class DBAL_dummy
{
	public function connect(){}
	public function close(){}
	public function quote( $value ){}
	public function query( $sql, $limit=0, $offset=0 )
	{
        return null;
	}

	public function insertId()
	{ return 0; }

	public function fetchAssoc( $handle )
	{ return false; }

	public function fetchObject( $handle )
	{ return false; }

	public function freeResult( $handle )
	{ }

	public function affectedRows()
	{ }

	public function getNumRows($handle)
	{ return 0; }
}
?>