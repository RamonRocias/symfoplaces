<?php

namespace App\Security;

use App\Entity\Place;

use App\Entity\User;

use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

use Symfony\Component\Security\Core\Security;

class PlaceVoter extends Voter{
    private $security, $operaciones;
    
    public function __construct(Security $security){
        // objeto security, lo necesitaremos para las comprobaciones
        // que requieran evaluar roles
        $this->security = $security;
        
        // lista de operaciones que podra evaluar el voter
        // podemos poner cualquier cosa (search, addActor...)
        $this->operaciones = ['create','update','delete'];
    }
    
    // DEBEMOS IMPLEMENTAR LOS METODOS: supports() y voteOnAttribute()
    
    // supports() comprueba si atributo y sujeto estan soportados por el voter
    // si no lo estan debemos retornar false, que es considerado como
    
    // que el voter "se abstiene" porque no es capaz de evaluar esa autorización
    protected function supports(string $attribute, $subject): bool{
        
        // si la operación (atributo) no esta soportada, retornamos false
        if (!in_array($attribute, $this->operaciones))
            return false;
            
            // si no nos pasan una place (sujeto) retorna false
            if (!$subject instanceof Place)
                
                return false;
                
                return true; // si todo es correcto retorna true
    }
    
    // El método voteOnAttribute() realizará una comprobación sobre el atributo,
    // sujeto y usuario. Retornara true si el voter autoriza o false si no autoriza
    protected function voteOnAttribute(string $attribute,
        $place, TokenInterface $token): bool {
     // Si queremos hacer pruebas y no estar condicionados a los ROLES de los usuarios
     // podemos añadir al código return true; y nos saltamos las comprobaciones.
     // Una vez realizadas las pruebas lo podemos comentar
     // return true;
            $user = $token->getUser(); // recupera el usuario
            
            if (!$user instanceof User) // si el usuario no esta identificado
                return false; // retorna false
                
                // DISPATCHER: llamamos al método adecuado según la comprobación a hacer.
                // Los métodos canEdit(), canCreate() y canDelete los definiremos debajo.
                // Preparamos el nombre a partir del atributo, p.e: view --> canView.
                $method = 'can'.ucfirst($attribute);
                
                return $this->$method($place, $user);
    }
    
    // METODOS PARA LAS DISTINTAS COMPROBACIONES
    // deben llamarse canOperacion(), donde las operaciones son las de la lista
    
    // todos los usuarios identificados pueden crear
    
    private function canCreate(Place $place, User $user): bool {
        return  $user->isVerified();        
    }
    
    // solo el autor o los editores pueden editar una place
    // (también usaremos este método para comprobar si puede añadir actor,
    // eliminar actor o eliminar la imagen)
    private function canUpdate(Place $place, User $user): bool {
        return ($user === $place->getUser() && $user->isVerified() ) || $this->security->isGranted('ROLE_EDITOR');
    }
    
    // si puede editar, puede eliminar la place
    private function canDelete(Place $place, User $user){
        
        return $this->canUpdate($place, $user);
    }
}