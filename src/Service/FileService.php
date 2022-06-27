<?php
/*
 * Servicio FileService para facilitar el trabajo con ficheros
 * 
 * AUTHOR: Robert Sallent
 * LAST UPDATE: 29/05/2022
 * 
 * */

namespace App\Service;

use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\File\Exception\FileException;

class FileService{
    // PROPIEDADES
    private $targetDirectory; // directorio de trabajo
    
    // CONSTRUCTOR, recibe el directorio sobre el que queremos trabajar
    public function __construct(string $targetDirectory){
        $this->targetDirectory = $targetDirectory;
    }
    
    // setter de la propiedad targetDirectory
    public function setTargetDirectory(string $targetDirectory): self{
        $this->targetDirectory = $targetDirectory;
        return $this;
    }
    
    // getter de la propiedad targetDirectory
    public function getTargetDirectory():string{
        return $this->targetDirectory;
    }
     
    // método usado para calcular un nombre único (de uso interno)
    private function uniqueName(UploadedFile $file, string $prefix = ''): string{
        return uniqid($prefix).'.'.$file->guessExtension();
    }
    
    // metodo que usaremos para subir los ficheros
    public function upload(
        UploadedFile $uploadedFile,     // datos del fichero recuperados del input (obligatorio)
        bool $uniqueName = true,        // ¿generar nombre único?  (opcional) 
        string $prefix = ''             // prefijo para el nombre único (opcional)
    ):?string{
       
        // nombre para el fichero, dependiendo de si queremos nombre único o no
        $nombreFichero = $uniqueName?
                        $this->uniqueName($uploadedFile, $prefix) :    // nombre único
                        $uploadedFile->getClientOriginalName();        // nombre original 
         
        // intenta mover el fichero a su ubicación final. El try-catch evitará que se propague 
        // la excepción, si falla simplemente retornaremos NULL.
        try{
            $uploadedFile->move($this->targetDirectory, $nombreFichero);
        }catch(FileException $e){
            return NULL;
        }
        
        return $nombreFichero; // retorna el nombre del fichero subido (o NULL si no se pudo)
    }
    
    
    // método para borrar ficheros
    public function remove(string $fichero){
        $fileSystem = new Filesystem();
        $fileSystem->remove("$this->targetDirectory/$fichero");
    }
    

    // el método para reemplazar recibirá también el nombre del fichero antiguo
    public function replace(
        UploadedFile $uploadedFile,  // datos del fichero recuperados del input (obligatorio)
        ?string $oldFileName = NULL, // fichero a borrar (opcinal)
        bool $uniqueName = true,     // ¿generar nombre único?  (opcional)
        string $prefix = ''          // prefijo para el nombre único (opcional)
    ):?string{
        
        // sube el nuevo fichero
        $nombreFichero = $this->upload($uploadedFile, $uniqueName, $prefix);
        
        // borra el fichero anterior
        // solamente si nos lo han indicado y no falla la subida del nuevo
        if($oldFileName && $nombreFichero)
            $this->remove($oldFileName);
                  
        // si el nuevo fichero se subió bien retorna su nombre, sino retorna el del antiguo
        return $nombreFichero ?? $oldFileName;
    }
}



