<?php

namespace Breme\Lexepa\Sql;

set_time_limit( 0 );

ini_set( 'auto_detect_line_endings', true);

define('T_SQL_INSERT',        0);
define('T_SQL_INTO',          1);
define('T_SQL_VALUES',        2);
define('T_SQL_OPEN_BRACKET',  3);
define('T_SQL_CLOSE_BRACKET', 4);
define('T_SQL_FIELD_NAME',    5);
define('T_SQL_COMMA',         6);
define('T_SQL_STICK',         7);
define('T_SQL_SEMICOLON',     8);
define('T_SQL_APEX',          9);
define('T_SQL_DOUBLE_APEX',   10);
define('T_SQL_VALUE',         11);

/**
 * Lexepa Sql Class.
 *
 * Class for lexing and parsing a SQL INSERT query.
 *
 */
class Lexepa_Sql
{
	/**
	 * Resource of the file to analyse.
	 *
	 * @var int
	 */
	private $fp = null;

	/**
	 * Name of the file to be analyzed.
	 *
	 * @var string
	 */
	private $file_name = '';

	/**
	 * Index of the tokens array.
	 *
	 * @var int
	 */
	private $idx = 0;

	/**
	 * Offset.
	 *
	 * @var int
	 */
	private $offset = 0;

	/**
	 * Pieces of the string to be parsed.
	 *
	 * @var string
	 */
	private $chunk = '';

	/**
	 * Current character.
	 *
	 * @var string
	 */
	private $curr_char = '';

	/**
	 * Default line endings.
	 *
	 * @var int
	 */
	private $line_endings = "\n";

	/**
	 * If we are inside of an INSERT query.
	 *
	 * @var bool
	 */
	private $insert_state = false;

	/**
	 * If we are in the "get all characters" state.
	 *
	 * @var bool
	 */
	private $catch_all_state = false;

	/**
	 * If we are in the "escape" state.
	 *
	 * @var bool
	 */
	private $escape_state = false;

	/**
	 * If we are in the "comment" state.
	 *
	 * @var bool
	 */
	private $comment_state = false;

	/**
	 * Reference to the object that implements the Lexepa_Sql_Interface interface.
	 *
	 * @var Lexepa_Sql_Interface
	 */
	private $lexepa_sql = null;

	/**
	 * Maximum number of characters to be parsed. If zero, all characters are to be parsed
	 *
	 * @var int
	 */
	private $chars_to_parse = 0;

	/**
	 * Tokens of the string to be parsed.
	 *
	 * @var array
	 */
	private $tokens = array();

	/**
	 * Characters reserved for parsing.
	 *
	 * @var array
	 */
	private $reserved = array(
		"INSERT" => T_SQL_INSERT,
		"INTO"   => T_SQL_INTO,
		"VALUES" => T_SQL_VALUES,
		"'"      => T_SQL_APEX,
		"\""     => T_SQL_DOUBLE_APEX,
		"`"      => T_SQL_STICK,
		"("      => T_SQL_OPEN_BRACKET,
		")"      => T_SQL_CLOSE_BRACKET,
		","      => T_SQL_COMMA,
		";"      => T_SQL_SEMICOLON,
		"NULL"   => T_SQL_VALUE
	);

	/**
	 * Load the object that implements Lexepa_Sql_Interface interface, the file name to be parsed and an initial offset.
	 *
	 * @param Lexepa_Sql_Interface $lexepa_sql     Reference to the object that implements the Lexepa_Sql_Interface interface.
	 * @param string               $file_name      File name to be parsed. Optional.
	 * @param int                  $initial_offset Initial offset. Optional.
	 */
	public function __construct( Lexepa_Sql_Interface $lexepa_sql, $file_name = '', $initial_offset = 0 )
	{
		$this->lexepa_sql = $lexepa_sql;
		$this->file_name  = $file_name;
		$this->offset     = $initial_offset;

		if ( file_exists( $this->file_name ) ) {

			$this->fp = fopen( $this->file_name, 'rb' );

			if ( ! $this->fp ) {
				throw new Exception( sprintf( _( 'Could not open the file %s' ), $file_name ) );
			}

			$this->detect_line_endings();

		} else {
			throw new Exception( sprintf( _( 'The file %s to be analyzed does not exist' ), $file_name ) );
		}
	}

	/**
	 * Set the file name to be parsed.
	 *
	 * @param string $file_name File name to be parsed.
	 */
	public function set_file_name( $file_name )
	{
		$this->file_name = $file_name;
	}

	/**
	 * Get the file name to be parsed.
	 *
	 * @return string File name to be parsed.
	 */
	public function get_file_name()
	{
		return $this->file_name;
	}

	/**
	 * Set the string to be parsed.
	 *
	 * @param int $initial_offset Initial offset.
	 */
	public function set_initial_offset( $initial_offset )
	{
		$this->offset = $initial_offset;
	}

	/**
	 * Get the initial offset.
	 *
	 * @return int Initial offset.
	 */
	public function get_initial_offset()
	{
		return $this->offset;
	}

	/**
	 * Set the maximum number of characters to be parsed.
	 *
	 * @param int $chars_to_parse Maximum number of characters to be parsed.
	 */
	public function set_chars_to_parse( $chars_to_parse )
	{
		$this->chars_to_parse = $chars_to_parse;
	}

	/**
	 * Get the maximum number of characters to be parsed.
	 *
	 * @return int Maximum number of characters to be parsed.
	 */
	public function get_chars_to_parse()
	{
		return $this->chars_to_parse;
	}

	/**
	 * Begin the process of lexing and parsing.
	 *
	 */
	public function parse_sql()
	{
		$apex_type  = '';
		$max_offset = 0;

		while ( false !== ( $this->curr_char = fgetc( $this->fp ) ) ) {

			if ( $this->comment_state ) {
				switch ( $this->curr_char ) {
					case "\r":
					case "\n":
						if ( $this->curr_char === $this->line_endings ) {
							$this->comment_state = false;
						}
						break;
					default:
						break;
				}
			} else if ( $this->catch_all_state ) {
				switch ( $this->curr_char ) {
					case "\"":
					case "'":
						if ( ! $this->escape_state && $this->curr_char === $apex_type ) {
							if ( '' === $this->chunk && $apex_type === $this->tokens[ count( $this->tokens ) - 1 ]['V'] ) { // case of empty string
								$this->tokens[] = array( 'T' => T_SQL_VALUE, 'V' => '', 'O' => $this->offset + 1 );
							}
							$this->add_token( $this->curr_char );
							$this->catch_all_state = false;
						} else {
							$this->escape_state = false;
							$this->chunk .= $this->curr_char;
						}
						break;
					case "\\":
						$this->escape_state = ! $this->escape_state;
						$this->chunk .= $this->curr_char;
						break;
					default:
						$this->escape_state = false;
						$this->chunk .= $this->curr_char;
						break;
				}
			} else {
				switch ( $this->curr_char ) {
					case "\t":
					case "\r":
					case "\n":
					case " ":
						$this->add_token();
						break;
					case "\"":
					case "'":
						if ( $this->insert_state ) {
							$this->catch_all_state = true;
						}
						$apex_type = $this->curr_char;
					case "`":
					case "(":
					case ")":
					case ",":
					case ";":
						$this->add_token( $this->curr_char );
						break;
					default:
						$this->chunk .= $this->curr_char;
						break;
				}
			}

			if ( $this->chars_to_parse > 0 && $this->offset >= $this->chars_to_parse && ! $this->insert_state && $this->curr_char === $this->line_endings ) {
				break;
			}

			$this->offset++;
		}

		$this->lexepa_sql->end_parsing( $this->offset );

		fclose( $this->fp );
	}

	/**
	 * Add a token.
	 *
	 * @param string $char Character index for reserved characters. Optional.
	 */
	private function add_token( $char = null )
	{
		if ( '' !== $this->chunk ) {
			if ( $this->insert_state ) {
				if ( $this->catch_all_state ) {
					$this->tokens[] = array( 'T' => T_SQL_VALUE, 'V' => $this->chunk, 'O' => ( $this->offset - strlen( $this->chunk ) ) );
				} else if ( isset( $this->reserved[ strtoupper( $this->chunk ) ] ) ) {
					$this->tokens[] = array( 'T' => $this->reserved[ strtoupper( $this->chunk ) ], 'V' => strtoupper( $this->chunk ), 'O' => ( $this->offset - strlen( strtoupper( $this->chunk ) ) ) );
				} else if ( is_numeric( $this->chunk ) ) {
					$this->tokens[] = array( 'T' => T_SQL_VALUE, 'V' => $this->chunk, 'O' => ( $this->offset - strlen( $this->chunk ) ) );
				} else if ( $this->is_valid_field_name( $this->chunk ) ) {
					$this->tokens[] = array( 'T' => T_SQL_FIELD_NAME, 'V' => $this->chunk, 'O' => ( $this->offset - strlen( $this->chunk ) ) );
				} else {
					$this->lexepa_sql->set_error( sprintf( _( 'Unknown character string at offset %d' ), $this->offset ) );
				}
			} else {
				if ( '-- ' === $this->chunk.$this->curr_char ) {
					$this->comment_state = true;
				} else if ( 'INSERT' === strtoupper( $this->chunk ) ) {
					$this->insert_state = true;
					$this->tokens[] = array( 'T' => $this->reserved[ strtoupper( $this->chunk ) ], 'V' => strtoupper( $this->chunk ), 'O' => ( $this->offset - strlen( strtoupper( $this->chunk ) ) ) );
				}
			}
			$this->chunk = '';
		}
		if ( ! is_null( $char ) && $this->insert_state ) {
			$this->tokens[] = array( 'T' => $this->reserved[ $char ], 'V' => $char, 'O' => $this->offset );
			if ( ';' === $char ) {
				$this->parse_tokens();
				$this->insert_state    = false;
				$this->catch_all_state = false;
				$this->escape_state    = false;
				$this->comment_state   = false;
			} else if ( ',' === $char && isset( $this->tokens[ count( $this->tokens ) - 2 ] ) && ')' === $this->tokens[ count( $this->tokens ) - 2 ]['V'] ) {
				$this->parse_tokens();
			}
		}
	}

	/**
	 * Begin parsing the SQL INSERT query.
	 *
	 */
	private function parse_tokens()
	{
		$this->idx = 0;

		if ( isset( $this->tokens[ $this->idx ] ) ) {
			if ( T_SQL_INSERT === $this->tokens[ $this->idx ]['T'] ) {
				$this->lexepa_sql->begin_insert( $this->tokens[ $this->idx ]['O'] );
				if ( isset( $this->tokens[ ++$this->idx ] ) ) {
					if ( T_SQL_INTO === $this->tokens[ $this->idx ]['T'] ) {
						if ( $this->is_field_name( 'table_name' ) ) {
							if ( isset( $this->tokens[ ++$this->idx ] ) ) {
								if ( T_SQL_OPEN_BRACKET === $this->tokens[ $this->idx ]['T'] ) {
									if ( $this->are_field_blocks() ) {
										if ( isset( $this->tokens[ ++$this->idx ] ) ) {
											if ( T_SQL_VALUES === $this->tokens[ $this->idx ]['T'] ) {
												$this->are_value_blocks();
											} else {
												$this->lexepa_sql->set_error( sprintf( _( 'It is expected the "VALUES" statement at offset %d' ), $this->tokens[ $this->idx ]['O'] ) );
											}
										} else {
											$this->lexepa_sql->set_error( sprintf( _( 'It is expected the "VALUES" statement at offset %d' ), $this->tokens[ $this->idx - 1 ]['O'] + strlen( $this->tokens[ $this->idx - 1 ]['V'] ) ) );
										}
									}
								} else if ( T_SQL_VALUES === $this->tokens[ $this->idx ]['T'] ) {
									$this->are_value_blocks();
								} else {
									$this->lexepa_sql->set_error( sprintf( _( 'It is expected the character "(" or the "VALUES" statement at offset %d' ), $this->tokens[ $this->idx ]['O'] ) );
								}
							} else {
								$this->lexepa_sql->set_error( sprintf( _( 'It is expected the character "(" or the "VALUES" statement at offset %d' ), $this->tokens[ $this->idx - 1 ]['O'] + strlen( $this->tokens[ $this->idx - 1 ]['V'] ) ) );
							}
						}
					} else {
						$this->lexepa_sql->set_error( sprintf( _( 'It is expected the "INTO" statement at offset %d' ), $this->tokens[ $this->idx ]['O'] ) );
					}
				} else {
					$this->lexepa_sql->set_error( sprintf( _( 'It is expected the "INTO" statement at offset %d' ), $this->tokens[ $this->idx - 1 ]['O'] + strlen( $this->tokens[ $this->idx - 1 ]['V'] ) ) );
				}
			} else if ( T_SQL_OPEN_BRACKET === $this->tokens[ $this->idx ]['T'] ) {
				--$this->idx;
				$this->are_value_blocks();
			} else {
				$this->lexepa_sql->set_error( sprintf( _( 'It is expected the character "(" or the "INSERT" statement at offset %d' ), $this->tokens[ $this->idx ]['O'] ) );
			}
		} else {
			$this->lexepa_sql->set_error( sprintf( _( 'It is expected the character "(" or the "INSERT" statement at offset %d' ), $this->tokens[ $this->idx - 1 ]['O'] + strlen( $this->tokens[ $this->idx - 1 ]['V'] ) ) );
		}

		$this->tokens = array();
	}

	/**
	 * If the tokens are blocks of valid values to be inserted in the fields of a table
	 *
	 * @return bool True if the tokens are blocks of valid values.
	 */
	private function are_value_blocks()
	{
		$are_value_blocks = false;
		if ( isset( $this->tokens[ ++$this->idx ] ) ) {
			if ( T_SQL_OPEN_BRACKET === $this->tokens[ $this->idx ]['T'] ) {
				if ( $this->are_values() ) {
					if ( isset( $this->tokens[ ++$this->idx ] ) ) {
						if ( T_SQL_COMMA === $this->tokens[ $this->idx ]['T'] ) {
							$are_value_blocks = true;
						} else if ( T_SQL_SEMICOLON === $this->tokens[ $this->idx ]['T'] ) {
							$this->lexepa_sql->end_insert( $this->tokens[ $this->idx ]['O'] + 1 );
							$are_value_blocks = true;
						} else {
							$this->lexepa_sql->set_error( sprintf( _( 'It is expected the character "," or ";" at offset %d' ), $this->tokens[ $this->idx ]['O'] ) );
						}
					} else {
						$this->lexepa_sql->set_error( sprintf( _( 'It is expected the character "," or ";" at offset %d' ), $this->tokens[ $this->idx - 1 ]['O'] + strlen( $this->tokens[ $this->idx - 1 ]['V'] ) ) );
					}
				}
			} else {
				$this->lexepa_sql->set_error( sprintf( _( 'It is expected the character "(" at offset %d' ), $this->tokens[ $this->idx ]['O'] ) );
			}
		} else {
			$this->lexepa_sql->set_error( sprintf( _( 'It is expected the character "(" at offset %d' ), $this->tokens[ $this->idx - 1 ]['O'] + strlen( $this->tokens[ $this->idx - 1 ]['V'] ) ) );
		}
		return $are_value_blocks;
	}

	/**
	 * If the tokens are valid values to be inserted in the fields of a table
	 *
	 * @return bool True if the tokens are valid values.
	 */
	private function are_values()
	{
		$are_values = false;
		if ( $this->is_value() ) {
			if ( isset( $this->tokens[ ++$this->idx ] ) ) {
				if ( T_SQL_COMMA === $this->tokens[ $this->idx ]['T'] ) {
					if ( $this->are_values() ) {
						$are_values = true;
					}
				} else if ( T_SQL_CLOSE_BRACKET === $this->tokens[ $this->idx ]['T'] ) {
					$are_values = true;
				} else {
					$this->lexepa_sql->set_error( sprintf( _( 'It is expected the character "," or ")" at offset %d' ), $this->tokens[ $this->idx ]['O'] ) );
				}
			} else {
				$this->lexepa_sql->set_error( sprintf( _( 'It is expected the character "," or ")" at offset %d' ), $this->tokens[ $this->idx - 1 ]['O'] + strlen( $this->tokens[ $this->idx - 1 ]['V'] ) ) );
			}
		}
		return $are_values;
	}

	/**
	 * If the token ia a valid value to be inserted in the field of a table
	 *
	 * @return bool True if the token is a valid value.
	 */
	private function is_value()
	{
		$is_value = false;
		if ( isset( $this->tokens[ ++$this->idx ] ) ) {
			if ( T_SQL_APEX === $this->tokens[ $this->idx ]['T'] || T_SQL_DOUBLE_APEX === $this->tokens[ $this->idx ]['T'] ) {
				$apex_type = $this->tokens[ $this->idx ]['T'];
				if ( isset( $this->tokens[ ++$this->idx ] ) ) {
					if ( T_SQL_VALUE === $this->tokens[ $this->idx ]['T'] ) {
						$this->lexepa_sql->field_value( $this->tokens[ $this->idx ]['V'], $this->tokens[ $this->idx ]['O'] );
						if ( isset( $this->tokens[ ++$this->idx ] ) ) {
							if ( $apex_type === $this->tokens[ $this->idx ]['T'] ) {
								$is_value = true;
							} else {
								$this->lexepa_sql->set_error( sprintf( _( 'It is expected the character "%s" at offset %d' ), $apex_type, $this->tokens[ $this->idx ]['O'] ) );
							}
						} else {
							$this->lexepa_sql->set_error( sprintf( _( 'It is expected the character "%s" at offset %d' ), $apex_type, $this->tokens[ $this->idx - 1 ]['O'] + strlen( $this->tokens[ $this->idx - 1 ]['V'] ) ) );
						}
					} else {
						$this->lexepa_sql->set_error( sprintf( _( 'It is expected a field value at offset %d' ), $this->tokens[ $this->idx ]['O'] ) );
					}
				} else {
					$this->lexepa_sql->set_error( sprintf( _( 'It is expected a field value at offset %d' ), $this->tokens[ $this->idx - 1 ]['O'] + strlen( $this->tokens[ $this->idx - 1 ]['V'] ) ) );
				}
			} else if ( T_SQL_VALUE === $this->tokens[ $this->idx ]['T'] ) {
				$this->lexepa_sql->field_value( $this->tokens[ $this->idx ]['V'], $this->tokens[ $this->idx ]['O'] );
				$is_value = true;
			} else {
				$this->lexepa_sql->set_error( sprintf( _( 'It is expected a single or double apex, or a field value at offset %d' ), $this->tokens[ $this->idx ]['O'] ) );
			}
		} else {
			$this->lexepa_sql->set_error( sprintf( _( 'It is expected a single or double apex, or a field value at offset %d' ), $this->tokens[ $this->idx - 1 ]['O'] + strlen( $this->tokens[ $this->idx - 1 ]['V'] ) ) );
		}
		return $is_value;
	}

	/**
	 * If the tokens are blocks of valid fields of a table
	 *
	 * @return bool True if the tokens are blocks of valid fields.
	 */
	private function are_field_blocks()
	{
		$are_field_blocks = false;
		if ( $this->is_field_name( $this->idx ) ) {
			if ( isset( $this->tokens[ ++$this->idx ] ) ) {
				if ( T_SQL_COMMA === $this->tokens[ $this->idx ]['T'] ) {
					if ( $this->are_field_blocks( $this->idx ) ) {
						$are_field_blocks = true;
					}
				} else if ( T_SQL_CLOSE_BRACKET === $this->tokens[ $this->idx ]['T'] ) {
					$are_field_blocks = true;
				} else {
					$this->lexepa_sql->set_error( sprintf( _( 'It is expected the character "," or ")" at offset %d' ), $this->tokens[ $this->idx ]['O'] ) );
				}
			} else {
				$this->lexepa_sql->set_error( sprintf( _( 'It is expected the character "," or ")" at offset %d' ), $this->tokens[ $this->idx - 1 ]['O'] + strlen( $this->tokens[ $this->idx - 1 ]['V'] ) ) );
			}
		}
		return $are_field_blocks;
	}

	/**
	 * If the tokens contain a valid field name or table name of a table
	 *
	 * @param  string $type Type of field to be checked ('field_name' or 'table_name'). Optional.
	 * @return bool True if the tokens contain a valid field name o table name.
	 */
	private function is_field_name( $type = 'field_name' )
	{
		$is_field_name = false;
		if ( isset( $this->tokens[ ++$this->idx ] ) ) {
			if ( T_SQL_STICK === $this->tokens[ $this->idx ]['T'] ) {
				if ( isset( $this->tokens[ ++$this->idx ] ) ) {
					if ( T_SQL_FIELD_NAME === $this->tokens[ $this->idx ]['T'] ) {
						if ( 'table_name' === $type) {
							$this->lexepa_sql->table_name( $this->tokens[ $this->idx ]['V'], $this->tokens[ $this->idx ]['O'] );
						} else {
							$this->lexepa_sql->field_name( $this->tokens[ $this->idx ]['V'], $this->tokens[ $this->idx ]['O'] );
						}
						if ( isset( $this->tokens[ ++$this->idx ] ) ) {
							if ( T_SQL_STICK === $this->tokens[ $this->idx ]['T'] ) {
								$is_field_name = true;
							} else {
								$this->lexepa_sql->set_error( sprintf( _( 'It is expected the character "`" at offset %d' ), $this->tokens[ $this->idx ]['O'] ) );
							}
						} else {
							$this->lexepa_sql->set_error( sprintf( _( 'It is expected the character "`" at offset %d' ), $this->tokens[ $this->idx - 1 ]['O'] + strlen( $this->tokens[ $this->idx - 1 ]['V'] ) ) );
						}
					} else {
						$this->lexepa_sql->set_error( sprintf( _( 'It is expected a table or field name at offset %d' ), $this->tokens[ $this->idx ]['O'] ) );
					}
				} else {
					$this->lexepa_sql->set_error( sprintf( _( 'It is expected a table or field name at offset %d' ), $this->tokens[ $this->idx - 1 ]['O'] + strlen( $this->tokens[ $this->idx - 1 ]['V'] ) ) );
				}
			} else if ( T_SQL_FIELD_NAME === $this->tokens[ $this->idx ]['T'] ) {
				if ( 'table_name' === $type) {
					$this->lexepa_sql->table_name( $this->tokens[ $this->idx ]['V'], $this->tokens[ $this->idx ]['O'] );
				} else {
					$this->lexepa_sql->field_name( $this->tokens[ $this->idx ]['V'], $this->tokens[ $this->idx ]['O'] );
				}
				$is_field_name = true;
			} else {
				$this->lexepa_sql->set_error( sprintf( _( 'It is expected the character "`" or a table or field name at offset %d' ), $this->tokens[ $this->idx ]['O'] ) );
			}
		} else {
			$this->lexepa_sql->set_error( sprintf( _( 'It is expected the character "`" or a table or field name at offset %d' ), $this->tokens[ $this->idx - 1 ]['O'] + strlen( $this->tokens[ $this->idx - 1 ]['V'] ) ) );
		}
		return $is_field_name;
	}

	/**
	 * If field name is a valid field name or table name of a table
	 *
	 * @param  string $field_name Field name to be checked.
	 * @return bool True if field name is a valid field name o table name.
	 */
	private function is_valid_field_name( $field_name )
	{
		return ( 1 === (int) preg_match( '/^[a-zA-Z_][a-zA-Z0-9_]*$/', $field_name ) );
	}

	/**
	 * Detect and set the line endings of the file to be parsed.
	 *
	 */
	private function detect_line_endings()
	{
		$line = fgets( $this->fp );
		rewind( $this->fp );
		$last_character = $line[ strlen( $line ) - 1 ];
		if ( "\r" === $last_character || "\n" === $last_character ) {
			$this->line_endings = $last_character;
		}
	}
}

?>