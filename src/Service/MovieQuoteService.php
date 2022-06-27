<?php
namespace App\Service;

class MovieQuoteService{
    // método que retorna una frase aleatoria de película
    public function random(): string{
        // las mapeo en un array para el ejemplo, las podríamos
        // tener también en BDD.
        $movieQuotes = [
            'Francamente, querida, me importa un bledo.',
            'Le haré una oferta que no podrá rechazar.',
            'Que la Fuerza te acompañe.',
            '¿Hablas conmigo?',
            '¡Me encanta el olor del napalm por la mañana!',
            'Louis, creo que éste es el inicio de una hermosa amistad.',
            'Bond. James Bond.',
            'Bueno, nadie es perfecto.',
            'Volveré',
            'Sayonara baby.',
            'A Diós pongo por testigo que no volveré a pasar hambre.',
            'Nací cuando ella me besó, morí el día que me abandonó, y viví el tiempo que me amó.',
            'Todos nos volvemos locos alguna vez.',
            'Dices que quieres morir por amor, pero no sabes nada de la muerte, ni sabes nada del amor.',
            'Hay tres maneras de hacer las cosas: la correcta, la incorrecta y la mía.',
            'Puede que no sea muy listo, pero sé lo que es el amor.',
            'Qué importante es poder contar en la vida con buenos amigos…',
            'Nuestro nombre no importa, se nos conoce por nuestros actos.',
            'Las causas perdidas son las únicas por las que merece la pena luchar.',
            'Me haces querer ser un hombre mejor.',
            'Son las decisiones las que nos hacen ser quienes somos, y siempre podemos optar por hacer lo correcto',
            'Que la fuerza te acompañe',
            'La juventud pasa. La inmadurez se supera, la ignorancia se cura con la educación y la embriaguez con sobriedad. Pero la estupidez.... La estupidez dura para siempre',
            'Plata o plomo.',
            'Le voy a hacer una oferta que no va a poder rechezar.',
            'Me parece que ya no estamos en Kansas.' 
        ];
        
        return $movieQuotes[array_rand($movieQuotes)];
    } 
}







