<?php
namespace App\Service;

class PlaceQuoteService{
    // método que retorna una frase aleatoria de lugares
    public function random(): string{
        // las mapeo en un array para el ejemplo, las podríamos
        // tener también en BDD.
        $placeQuotes = [
            'Porque venir al mundo para hacer esto, no valía la pena haber venido.',
            'Si hay que ir se va, pero ir pa ná es tontería.',
            'Si lo sé no vengo.',
            'Me lo imaginabamás grande',
            'Venir tan lejos para tan poco',
            'Louis, creo que éste es el inicio de una hermosa amistad.',
            'El de mi pueblo es más gande.',
            'Bueno, nadie es perfecto.',
            'Volveré',
            'Sayonara baby.',
            'A Diós pongo por testigo que no volveré a pasar por aquí.',
            'Aquí todo es carísimo.',
            'Está muy sucio',
            'Lo tienen abandonado.',
            'Está sobrevalorado.',
            'Me parece que ya no estamos en Kansas.' 
        ];
        
        return $placeQuotes[array_rand($placeQuotes)];
    } 
}







