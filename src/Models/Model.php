<?php

namespace Models;

use Database\ConnectionManager;
use RuntimeException;

class Model
{
    protected $db;

    protected array $fillable = [];
    protected array $attributes = [];

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