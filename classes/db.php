<?php defined('SYSPATH') or die('No direct script access.');

class DB extends Kohana_DB {
    /**
     * Create a new [Database_Query_Builder_Replace].
     *
     *     // REPLACE INTO users (id, username)
     *     $query = DB::replace('users', array('id', 'username'));
     *
     * @param   string  table to replace into
     * @param   array   list of column names or array($column, $alias) or object
     * @return  Database_Query_Builder_Replace
     */
    public static function replace($table = NULL, array $columns = array()) {
	return new Database_Query_Builder_Replace($table, $columns);
    }
}
