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
            'Me lo imaginaba más grande',
            'Venir tan lejos para tan poco',
            'Louis, creo que éste es el inicio de una hermosa amistad.',
            'El de mi pueblo es más gande.',
            'Bueno, nadie es perfecto.',
            'Volveré',            
            'A Diós pongo por testigo que no volveré a pasar por aquí.',
            'Aquí todo es carísimo.',
            'Está muy sucio',
            'Lo tienen abandonado.',
            'Está sobrevalorado.',
            'Una vez al año viaja a un lugar en el que nunca hayas estado antes.',
            'La única regla del viaje es: no vuelvas como te fuiste. Vuelve diferente.',
            'Si no escalas la montaña, jamás podrás disfrutar del paisaje.',
            'Un viaje de mil millas comienza con un primer paso.',
            'La vida es un viaje y quien viaja vive dos veces.',
            'No he estado en todos los sitios, pero están en mi lista.',
            'Cada cien metros, el mundo cambia.',
            'Viaja, el dinero se recupera el tiempo no.',
            'No me digas lo viejo que eres o lo bien educado que estás. Dime cuanto has viajado y te diré cuanto sabes.',
            'Un viaje se vive 3 veces: cuando lo soñamos, cuando lo vivimos y cuando lo recordamos.',
            'El fascismo se cura leyendo, el racismo se cura viajando.',
            'Recuerda que la felicidad es una forma de viajar, no un destino.',
            'No guardes rencores, mejor guarda dinero para viajar.',
            'No hay viaje que no te cambie algo.',
            'Viajar es descubrir que todos están equivocados acerca de otros países.',
            'El mundo es un libro y aquellos que no viajan leen sólo una página.',
            'Una vez que el virus viajero te pica, no hay antídoto posible y sé que estaré felizmente contagiado para el resto de mi vida.',
            'Cuanta menos rutina, más vida.',
            'Si crees que la aventura es peligrosa, prueba la rutina. Es mortal.'
        ];
        
        return $placeQuotes[array_rand($placeQuotes)];
    } 
}







