<?php

namespace App\Modules\NotifierEvents\Services;

use App\Modules\NotifierEvents\Contracts\DestinationPublisherInterface;
use RuntimeException;

class DestinationPublisherRegistry
{
    /**
     * @var array<string, DestinationPublisherInterface>
     */
    private array $publishers = [];

    /**
     * @param iterable<DestinationPublisherInterface> $publishers
     */
    public function __construct(iterable $publishers)
    {
        foreach ($publishers as $publisher) {
            $this->publishers[$publisher->destination()] = $publisher;
        }
    }

    public function resolve(string $destination): DestinationPublisherInterface
    {
        if (! array_key_exists($destination, $this->publishers)) {
            throw new RuntimeException("No hay publisher registrado para el destino [{$destination}].");
        }

        return $this->publishers[$destination];
    }
}
