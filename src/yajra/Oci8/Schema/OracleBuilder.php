<?php namespace yajra\Oci8\Schema;

use Closure;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Connection;

class OracleBuilder extends \Illuminate\Database\Schema\Builder {

	/**
	 * Create a new table on the schema.
	 *
	 * @param  string   $table
	 * @param  Closure  $callback
	 * @return \Illuminate\Database\Schema\Blueprint
	 */
	public function create($table, Closure $callback)
	{
		$blueprint = $this->createBlueprint($table);

		$blueprint->create();

		$callback($blueprint);

		$this->build($blueprint);

		// *** auto increment hack ***
		// create sequence and trigger object
		$db = $this->connection;
		$columns = $blueprint->getColumns();

		// search for primary key / autoIncrement column
		foreach ($columns as $column) {
			// if column is autoIncrement set the primary col name
			if ($column->autoIncrement) {
				$col = $column->name;
			}
		}

		// if primary key col is set, create auto increment objects
		if (isset($col)) {
	      	// create sequence for auto increment
			$db->createSequence("{$table}_{$col}_seq");
	        // create trigger for auto increment work around
			$db->createAutoIncrementTrigger($table, $col);
		}

	}

	/**
	 * Drop a table from the schema.
	 *
	 * @param  string  $table
	 * @return \Illuminate\Database\Schema\Blueprint
	 */
	public function drop($table)
	{
		$blueprint = $this->createBlueprint($table);

		$blueprint->drop();

		$this->build($blueprint);

		// *** auto increment hack rollback ***
		// drop sequence and trigger object
		$db = $this->connection;
		// @todo: get the actual primary column name from table
		$col = 'id';
		// if primary key col is set, drop auto increment objects
		if (isset($col)) {
	      	// drop sequence for auto increment
			$db->dropSequence("{$table}_{$col}_seq");
	        // drop trigger for auto increment work around
			$db->dropTrigger("{$table}_{$col}_trg");
		}
	}

	/**
	 * Create a new command set with a Closure.
	 *
	 * @param  string   $table
	 * @param  Closure  $callback
	 * @return \Illuminate\Database\Schema\Blueprint
	 */
	protected function createBlueprint($table, Closure $callback = null)
	{
		return new OracleBlueprint($table, $callback);
	}

}

