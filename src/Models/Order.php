<?php 

namespace App\Models;

class Order 
{

    //class fiels
    private int $id;
    private string $destination;
    private string $status;
    private float $weight;


    //constructor
    public function __construct(int $id, string $destination, string $status, float $weight)
    {

        if (empty(trim($destination))) {
            throw new \InvalidArgumentException('Пункт назначения не может быть пустым');
        }

        $this->id = $id;
        $this->destination = $destination;
        $this->status = $status;
        $this->weight = $weight;
    }

    //obj to massive for saving as json
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'destination' => $this->destination,
            'status' => $this->status,
            'weight' => $this->weight
        ];
    }
}