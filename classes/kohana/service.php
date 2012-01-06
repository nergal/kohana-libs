<?php defined('SYSPATH') or die('No direct script access.');

/**
 * Kohana service layer
 *
 * Provides simple CRUD actions. May be extended.
 *
 * @author nergal
 * @package service
 */
abstract class Kohana_Service
{
    /**
     * @var ORM
     */
    protected $orm;

    /**
     * ORM binding
     *
     * @param  ORM $mapper
     * @return void
     */
    public function __construct(ORM $mapper)
    {
        $this->orm = $mapper;
    }

    /**
     * Create operation
     *
     * @param  array $data
     * @return ORM
     */
    public function create(Array $data = array())
    {
        foreach ($data as $column => $value) {
            $this->orm->set($column, $value);
        }

        return $this->orm->save();
    }

    /**
     * Read operation
     *
     * @param  int $id
     * @return ORM
     */
    public function read($id)
    {
        return $this->orm
                    ->where($this->orm->object_name().'.'.$this->orm->primary_key(), '=', $id)
                    ->find();
    }

    /**
     * Update operation
     *
     * @param  int   $id
     * @param  array $data
     * @return ORM
     */
    public function update($id, Array $data = array())
    {
        $this->orm = $this->read($id);
        return $this->create($data);
    }

    /**
     * Delete operation
     *
     * @param  int $id
     * @return ORM
     */
    public function delete($id)
    {
        return $this->read($id)->delete();
    }
}
