<?php
namespace App\Service;

class EntityFakerService{
    // Retorn mock entities para cuando necesite un objeto cualquiera
    //de un tipo determinado y no tenga ninguno.
    // Por ejemplo, me servirá ppara pasr una película a un voter
    // desde una template de Twig.
    
    public function getMock(string $className) {
        $fullName = '\\App\\Entity\\'.$className;
        return new $fullName;
    }
}