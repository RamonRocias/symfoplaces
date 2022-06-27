<?php
namespace App\Service;

use Doctrine\ORM\EntityManagerInterface;

class SimpleSearchService{
    
    // PROPIEDADES 
    // coloco las propiedades como public para ahorrarme setters y getters
    // y hacer el ejemplo más corto
    // los valores por defecto están pensados para funcionar sin cambios
    public $campo='id', $valor='%', $orden='id', $sentido='DESC', $limite=5;
    private $entityManager;
    
    // CONSTRUCTOR
    public function __construct(EntityManagerInterface $entityManager){
        $this->entityManager = $entityManager;
    }
         
    // MÉTODOS
    // Método para buscar entidades
    public function search(string $entityType): array{
        // preparamos la consulta usando DQL
        $consulta = $this->entityManager->createQuery(
            "SELECT p
             FROM $entityType p
             WHERE p.$this->campo LIKE :valor
             ORDER BY p.$this->orden $this->sentido")
           ->setParameter('valor', '%'.$this->valor.'%')
           ->setMaxResults($this->limite);
        
        // ejecuta la consulta y retorna el resultado
        return $consulta->getResult();
    }
}




