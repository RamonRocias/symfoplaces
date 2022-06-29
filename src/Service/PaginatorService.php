<?php

namespace App\Service;

use Doctrine\ORM\Tools\Pagination\Paginator;
use Doctrine\ORM\EntityManagerInterface;

class PaginatorService{
    
    // propiedades que necesitaré
    private $limit, $entityManager, $entityType = '';
    private $paginaActual = 1, $total = 0;
    
    // CONSTRUCTOR
    // como usaré autowiring indicaré los valores por defecto en services.yaml
    public function __construct(int $limit, EntityManagerInterface $entityManager){
        $this->limit = $limit;
        $this->entityManager = $entityManager;
    }
    
    // SETTERS Y GETTERS
    // establece el tipo de entidad con el que va a trabajar el paginador
    public function setEntityType(string $entityType){
        $this->entityType = $entityType;
    }
    // cambiar el límite
    public function setLimit(int $limit){
        $this->limit = $limit;
    }
    // recupera la página actual
    public function getPaginaActual():int{
        return $this->paginaActual;
    }
    // recupera el total de resultados    
    public function getTotal():int{
        return $this->total;
    }
    // recupera el total de páginas ceil(resultados / límite)
    public function getTotalPages():int{
        return ceil($this->total / $this->limit);
    }
    
    // MÉTODOS
    // método que pagina los resultados
    public function paginate($dql, $page = 1):Paginator{
        $paginator = new Paginator($dql);     // crea el paginador a partir del DQL
        
        $paginator->getQuery()                // toma la consulta y...
            ->setFirstResult($this->limit * ($page - 1))        // le añade el offset
            ->setMaxResults($this->limit);                      // le añade el limit
        
        $this->paginaActual = $page; // almacena la página actual 
        $this->total = $paginator->count(); // almacena el total de resultados
        
        return $paginator; // retorna el objeto Paginator
    }
    
    // método que recupera todas las entidades con paginación
    // podríamos tener otros métodos distintos, para aplicar otros filtros 
    public function findAllEntities(int $paginaActual = 1):Paginator{
        // preparo la consulta usando DQL, indicando la entidad mediante la
        // propiedad entityType. Podría ser App\Entity\Place, App\Entity\Photo...
        $consulta = $this->entityManager->createQuery(
            "SELECT p 
             FROM $this->entityType p
             ORDER BY p.id DESC");
        
        // retornamos los resultados paginados, llamando al método anterior
        return $this->paginate($consulta, $paginaActual);
    }
}



