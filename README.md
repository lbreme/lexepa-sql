# Lexepa-Sql
Library for lexing and parsing a SQL INSERT query.

<h1>Installing Lexepa-Sql</h1>
<p>First, get <a href="https://getcomposer.org/download/">Composer</a>, if you don't already use it.</p>
<p>Next, run the following command inside the directory of your project:</p>
<pre>composer require lbreme/lexepa-sql</pre>

<h1>How does it work?</h1>
<p>The Lexepa-Sql library analyzes any file that contains one or more INSERT SQL queries. During the analysis a series of callback functions are called to which the elements that constitute the query are passed as arguments.</p>

<p>We clarify with an example, which is contained in the file <a href="https://github.com/lbreme/lexepa-sql/blob/main/class-example-sql.php">class-example-sql.php</a>, which to make it work is to copy in the root of your project:</p>

<pre>
/*
We create a class derived from the Lexepa_Sql_Abstract class, which implements all the
callback functions that will be called by the analysis of the SQL INSERT query
*/
class Example_Sql extends Lexepa_Sql_Abstract
{
	/**
	 * Begin of the SQL INSERT query.
	 *
	 * @param int    $begin_offset Offset of the SQL INSERT query.
	 */
	public function begin_insert( $begin_offset )
	{
		echo 'Offset of the SQL INSERT query: ' . $begin_offset;
	}

	/**
	 * Table name found
	 *
	 * @param string $table_name Table name.
	 * @param int    $offset Offset of the table name.
	 */
	public function table_name( $table_name, $offset )
	{
		echo 'Table name: ' . $table_name;
	}

	/**
	 * Field name found
	 *
	 * @param string $field_name Field name.
	 * @param int    $offset Offset of the field name.
	 */
	public function field_name( $field_name, $offset )
	{
		echo 'Field name: ' . $field_name;
	}

	/**
	 * Field value found
	 *
	 * @param string $field_value Field value.
	 * @param int    $offset Offset of the field value.
	 */
	public function field_value( $field_value, $offset )
	{
		echo 'Field value: ' . $field_value;
	}

	/**
	 * End of the SQL INSERT query
	 *
	 * @param int $end_offset Offset of the end of the SQL INSERT query.
	 */
	public function end_insert( $end_offset )
	{
		echo 'Offset of the end of the SQL INSERT query: ' . $end_offset;
	}

	/**
	 * End of the file parsing
	 *
	 * @param bool $offset Offset of the end of the file parsing.
	 */
	public function end_parsing( $offset )
	{
		echo 'Offset of the end of the file parsing: ' . $end_offset;
	}

	/**
	 * Set error parsing the file.
	 *
	 * @param string $error Error parsing the file.
	 */
	public function set_error( $error )
	{
		echo $error;
	}
}

$example_sql = new Example_Sql();

/*
We instantiate the Lexepa-Sql library class, passing as arguments the $example_sql object
containing the callback functions and the file name to be parsed
*/
$lexepa_sql  = new Lexepa_Sql( $example_sql, 'insert.sql' );

// Let's start the analysis
$lexepa_sql->parse_sql();

</pre>

<p>The result of this example is as follows:</p>

<pre>

</pre>

<p>The callback functions implemented by the Lexepa_Sql_Abstract class are contained and documented in the interface file <a href="https://github.com/lbreme/lexepa-sql/blob/main/src/class-lexepa-sql-interface.php">class-lexepa-sql-interface.php</a></p>
