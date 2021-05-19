<?php

require_once __DIR__ . '/vendor/autoload.php';

use Breme\Lexepa\Sql\Lexepa_Sql_Abstract;
use Breme\Lexepa\Sql\Lexepa_Sql;

class Example_Sql extends Lexepa_Sql_Abstract
{
	/**
	 * Begin of the SQL INSERT query.
	 *
	 * @param int    $begin_offset Offset of the SQL INSERT query.
	 */
	public function begin_insert( $begin_offset )
	{
		echo 'Offset of the SQL INSERT query: ' . $begin_offset . '<br />';
	}

	/**
	 * Table name found
	 *
	 * @param string $table_name Table name.
	 * @param int    $offset Offset of the table name.
	 */
	public function table_name( $table_name, $offset )
	{
		echo 'Table name: ' . $table_name . '<br />';
	}

	/**
	 * Field name found
	 *
	 * @param string $field_name Field name.
	 * @param int    $offset Offset of the field name.
	 */
	public function field_name( $field_name, $offset )
	{
		echo 'Field name: ' . $field_name . '<br />';
	}

	/**
	 * Field value found
	 *
	 * @param string $field_value Field value.
	 * @param int    $offset Offset of the field value.
	 */
	public function field_value( $field_value, $offset )
	{
		echo 'Field value: ' . $field_value . '<br />';
	}

	/**
	 * End of the SQL INSERT query
	 *
	 * @param int $end_offset Offset of the end of the SQL INSERT query.
	 */
	public function end_insert( $end_offset )
	{
		echo 'Offset of the end of the SQL INSERT query: ' . $end_offset . '<br />';
	}

	/**
	 * End of the file parsing
	 *
	 * @param bool $offset Offset of the end of the file parsing.
	 */
	public function end_parsing( $offset )
	{
		echo 'Offset of the end of the file parsing: ' . $end_offset . '<br />';
	}

	/**
	 * Set error parsing the file.
	 *
	 * @param string $error Error parsing the file.
	 */
	public function set_error( $error )
	{
		echo $error . '<br />';
	}
}

$example_sql = new Example_Sql();
$lexepa_sql  = new Lexepa_Sql( $example_sql, 'insert.sql' );

$lexepa_sql->parse_sql();

?>