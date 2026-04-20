<?php

namespace Models;

use Database\ConnectionManager;
use RuntimeException;

/**
 *  Base Model class
 *
 * Provides core functionality for data handling
 * Child models should define their own $fillable and $attributes variables
 */
class Model
{
    protected $db;

    /**
     * @var array|string[]
     */
    protected array $fillable = [];

    /**
     * @var array|string[]
     */
    protected array $attributes = [];

    /**
     * Model constructor. Pass in data array to fill in the values of the model
     * @param array $data
     */
    public function __construct(array $data = [])
    {
        $this->db = ConnectionManager::get('default');
        $this->fill($data);
    }

    /**
     * Loads model with data
     * @param array $data
     * @return void
     */
    protected function fill(array $data)
    {
        foreach ($data as $key => $value) {
            if (!in_array($key, $this->fillable, true) && !in_array($key, $this->attributes, true)) {
                continue;
            }
        }
    }
}