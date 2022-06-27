<?php
namespace App\Service;

use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\File\Exception\FileException;


class FileService_antiguo{
    // PROPIEDADES
    public $targetDirectory; // directorio de trabajo
    
    // CONSTRUCTOR
    
    // Recibe el directorio sobre el que querenos trabajar
    
    public function __construct(String $targetDirectory){
        
        $this->targetDirectory = $targetDirectory;
    }
    // MÉTODOS
    public function upload(UploadedFile $file, bool $nombreUnico = true) : ?string{
        
        // Nombre para el fichero, dependiendo de si queremos nombre único o no.
        
        $fichero = $nombreUnico ?
        
        uniqid().'.'.$file->guessExtension()	:	// nonbre único
        $file->getClientOriginalName(); 	// monbre original
        
        //intenta mover el fichero a su ubicacién final
        try{
            $file->move($this->targetDirectory, $fichero);
            
            // si no se pudo subir, retorna N
        }catch(FileException $e){
            return NULL;
        }
        // si todo fue correcto, retorna el nonbre del fichero subido
        return $fichero;
    }
    
    // el método para remplazar también recibirá el nombre del fichero anterior
    // para que lo podamos eliminar del sistema de ficheros ( puede ser string o NULL)
    public function replace(UploadedFile $file, ?string $anterior = NULL,
        bool $nombreUnico = true) : ?string{
        
            // monbre para el fichero, dependiendo de si queremos nombre único o no.
            $fichero = $nombreUnico?
                uniqid().'.'.$file->guessExtension()    :   // nombre único
                $file->getClientOriginalName();     // nombre original
                // intenta mover el nuevo fichero
                try{
                    $file->move($this->targetDirectory, $fichero);
                    
                    if($anterior){ 	// si habia fichero anterior, bórralo
                        $fileSystem = new Filesystem();
                        $fileSystem->remove("$this->targetDirectory/$anterior");
                    }
                    // si falló la subida del nuevo fichero
                }catch(FileException $e){
                    return $anterior; // seguiremos usando el anterior
                }
                
                // si el nuevo fichero se subié bien retorna su nombre
                return $fichero;    
            
    }
    
    //método para borrar ficheros
    public function delete(string $fichero){
        
        //borra el fichero indicado
        $fileSystem = new Filesystem();
        $fileSystem->remove("$this->targetDirectory/$fichero");
    }    
}