<?php

namespace Breme\Lexepa\Sql;

use Breme\Lexepa\Sql\Lexepa_Sql_Interface;

/**
 * Lexepa Abstract Class.
 *
 * Abstract class that implements functions defined by Lexepa_Sql_Interface interface.
 *
 */
abstract class Lexepa_Sql_Abstract implements Lexepa_Sql_Interface
{
	/**
	 * Begin of the SQL INSERT query.
	 *
	 * @param int    $begin_offset Offset of the SQL INSERT query.
	 */
	public function begin_insert( $begin_offset ) {}

	/**
	 * Table name found
	 *
	 * @param string $table_name Table name.
	 * @param int    $offset Offset of the table name.
	 */
	public function table_name( $table_name, $offset ) {}

	/**
	 * Field name found
	 *
	 * @param string $field_name Field name.
	 * @param int    $offset Offset of the field name.
	 */
	public function field_name( $field_name, $offset ) {}

	/**
	 * Field value found
	 *
	 * @param string $field_value Field value.
	 * @param int    $offset Offset of the field value.
	 */
	public function field_value( $field_value, $offset ) {}

	/**
	 * End of the SQL INSERT query
	 *
	 * @param int $end_offset Offset of the end of the SQL INSERT query.
	 */
	public function end_insert( $end_offset ) {}

	/**
	 * End of the file parsing
	 *
	 * @param bool $offset Offset of the end of the file parsing.
	 */
	public function end_parsing( $offset ) {}

	/**
	 * Set error parsing the file.
	 *
	 * @param string $error Error parsing the file.
	 */
	public function set_error( $error ) {}
}

?>