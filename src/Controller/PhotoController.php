<?php

namespace App\Controller;

use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\DateType;

use Symfony\Component\Routing\Annotation\Route;
use App\Form\PhotoFormType;
use App\Form\PhotoDeleteFormType;
use Psr\Log\LoggerInterface;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Filesystem\Exception\IOExceptionInterface;
use Symfony\Component\Filesystem\Filesystem;

use App\Service\FileService;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use App\Repository\PhotoRepository;
use App\Service\PaginatorService;
use App\Service\SimpleSearchService;
use App\Form\SearchFormType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use App\Form\PhotoAddPlaceFormType;

use App\Entity\Place;
use App\Entity\Photo;
use App\Entity\Comment;

// Sym 32
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;


class PhotoController extends AbstractController
{
/*
    #[Route('/photo', name: 'app_photo')]
    public function index(): Response
    {
        return $this->render('photo/index.html.twig', [
            'controller_name' => 'PhotoController',
        ]);
    }
    */
    
    #[Route('/photos/{pagina}', name:'photo_list', defaults:['pagina'=>1], methods:['GET'] )]
    
    public function list(int $pagina, PaginatorService $paginator): Response{
        
        // Le indicamos al paginador que queremos trabajar con places
        $paginator->setEntityType('App\Entity\Photo');
        $paginator->setLimit($this->getParameter('app.photo_results'));
        
        // Le pedimos que nos recupere todas las fotos con paginación
        $photos = $paginator->findAllEntities($pagina);
        
        //retorna la respuesta. Normalmente será una vista
        return $this->renderForm("photo/list.html.twig",
            ["photos" =>$photos, "paginator"=>$paginator]);
    }
    
    
    #[Route('/photo/create/{place}', name: 'photo_create', methods:['GET','POST'])]
    
    public function create(
        Request $request,
        Place $place,
        PhotoRepository $photoRepository,
        LoggerInterface $appInfoLogger,
        FileService $fileService):Response{   
        
            //Crea el objeto tipo Photo
            $photo= new Photo();            
            // comprobación de seguridad usando el voter
            $this->denyAccessUnlessGranted('create', $photo);            
            //Crea el formulario            
            $formPhoto = $this->createForm(PhotoFormType::class, $photo);            
            // comprueba si el formulario fué enviado
            $formPhoto->handleRequest($request);            
            // si el formulario ha sido enviado y es vélido
            if ($formPhoto->isSubmitted() && $formPhoto->isValid()){                
                if($uploadedFile = $formPhoto->get('picture')->getData()){ // Si hay fichero
                    // indica al FileService que trabaje con el directorio de pictures
                    $fileService->setTargetDirectory($this->getParameter('app.portraits.root'));
                    
                    // sube el fichero al directorio y guarda su nombre en la entidad
                    $photo->setPicture($fileService->upload($uploadedFile, true, 'picture_place_'));
                // asocio la photo al place. Método addPhoto en entidadPlace
                $place->addPhoto($photo);                
                }
                // guarda la nueva place                
                $photoRepository->add($photo, true);
                
                // prepara un mensaje de éxito                
                $mensaje = 'Imagen '.$photo->getTitle().' guardado correctamente.';
                $this->addFlash('success', $mensaje); // flashea el mensaje
                $appInfoLogger->info($mensaje);	// guarda en log el mensaje
                
                // redirige a los detalles de el place
                return $this->redirectToRoute( 'place_update', ['place' => $place->getId()]);
            }
            
            // muestra la vista con el formlario
            return $this->render('photo/new-html.twig',[
                "formulario"=>$formulario->createView(),
                "formularioAddPlace"=>$formularioAddPlace->createView(),
                "photo" =>$photo                
            ]);            
    }
    
    #[Route('photo/search', name:'photo_search', methods:['GET','POST'])]
    
    public function search(Request $request, SimpleSearchService $busqueda):Response{
        
        // crea el formulario
        $formulario = $this->createForm(SearchFormType::class, $busqueda, [
            'field_choices' => [
                'ID' => 'id',
                'Título' => 'title',
                'Fecha' => 'date'
            ],
            'order_choices' =>[
                'ID' => 'id',
                'Título' => 'title',
                'Fecha' => 'date'
            ]
        ]);
        
        // establece el valor selected para los SELECT
        $formulario->get('campo')->setData($busqueda->campo);
        $formulario->get('orden')->setData($busqueda->orden);
        
        // gestiona el formulario y recupera los valores de busqueda
        $formulario->handleRequest($request);
        
        // realiza La busqueda
        $photos = $busqueda->search( 'App\Entity\Photo');
        
        // retorna la vista con los resultados
        return $this->renderForm("photo/buscar.html.twig", [
            "formulario"=>$formulario,
            "photos" => $photos
        ]);
    }
    
    #[Route('/photo/delete/{photo}', name: 'photo_delete', methods:['GET','POST'])]
    //Usando la clase del formulario
    
    public function delete(
        Photo $photo,
        PhotoRepository $photoRepository,
        Request $request,
        LoggerInterface $appInfoLogger,
        FileService $fileService
        ): Response{            
                        
            $place = $photo->getPlace();
            $this->denyAccessUnlessGranted('update', $place);
            
            // creación del formulario
            $formulario = $this->createForm(PhotoDeleteFormType::class, $photo);
            $formulario->handleRequest($request);
            
            // si el formulario llega y es valido.
            if ($formulario->isSubmitted() && $formulario->isValid()) {
                
                // Elimina el places vinculada al formulario
                $photoRepository->remove($photo,TRUE);
                
                // Si había picture, hay que borrar el fichero del sistema de ficheros
                if($picture = $photo->getPicture()){ // si hay picture
                    
                    $fileService->setTargetDirectory($this->getParameter('app.portraits.root'))->remove($picture);
                }
                
                // prepara un mensaje de éxito
                $mensaje = 'Photo '.$photo->getTitle().' borrado correctamente.';
                $this->addFlash('success', $mensaje);
                $appInfoLogger->info($mensaje);
                
                // redirige a la lista de places
                return $this->redirectToRoute('place_update', ['place' => $place->getId()]);
            }
            // muestra la vista con el formulario de borrado
            return $this->renderForm('photo/delete.html.twig',
                ['photo'=>$photo,
                    'formulario' =>$formulario
                ]);
    }
    
    // Utiliza el : use Symfony\Component\Filesystem\Filesystem;
    #[Route('/photo/deleteimage/{id<\d+>}', name:'photo_delete_portrait',
    methods:['GET'],
    requirements:['id'=>'\d+']
    )]
    
    /**
     * @IsGranted("update", subject="photo")
     */
    // Método para eliminar el archivo del campo picture de una instancia de la entidad PHOTO
    public function deletePortrait(Photo $photo,
        Request $request,
        PhotoRepository $photoRepository,
        FileService $fileService,
        EntityManagerInterface $em) :Response{
            
            
            if($picture= $photo->getPicture()){ // si hay picture
                
                // la borramos del sistma de ficheros
                $fileService->setTargetDirectory($this->getParameter('app.portraits.root'))->remove($picture);
                
                // actuualizamos los datos de el place y los guradamos en BDD
                $photo->setPicture(NULL);
                $photoRepository->add($photo,TRUE);
                
                // flashear el mensaje
                $mensaje = 'Archivo de la photo '.$photo->getNombre().'borrado.';
                $this->addFlash('success', $mensaje);
            }
            // carga la vista con el formulario
            return $this->redirectToRoute('photo_show',['id' => $photo->getId()]);
            
    }
    
    #[Route('/photo/show/{photo<\d+>}', name:'photo_show')]
    
    public function show(Photo $photo):response{
        
        //retorna la respuesta ( normalmente será una vista)
        return $this->render("photo/show.html.twig",["photo" =>$photo]);
    }
    
    /*
     #[Route('/place/duracion/{min<\d*>}/{max<\d*>}', name:'place_duracion', defaults:[['min'=>0],['max'=>9999999]])]
     public function duracion(int $min, int $max){
     
     $repositorio = $this->getDoctrine()->getRepository(Place::class);
     $pelis = $repositorio->findAllByDuration($min, $max) ;
     
     // carga la vista de listado de places, pasándole toda la información
     return $this->renderForm("place/list.html.twig", ['places' =>$pelis ]);
     }
     */
    #[Route('/photo/addPlace/{id<\d+>}', name:'photo_add_place', methods:['POST'])]
    
    /**
     * @IsGranted("update", subject="photo")
     */
    
    public function addPlace(
        Photo $photo,
        Request $request,
        EntityManagerInterface $em,
        LoggerInterface $appInfoLogger
        ){
            //tomamos el valor que llega del formulario y lo procesamos
            $formularioAddPlace = $this->createForm(PhotoAddPlaceFormType::class);
            $formularioAddPlace->handleRequest($request);
            // $place = $formularioAddPlace->getData()['place'];
            $datos = $formularioAddPlace->getData();   // añadidp página 67
            
            if(empty($datos['place'])){
                // Si no nos llega el place es que tenemos un error
                $this->addFlash('addPlaceError', ' No se indicó un nombre de place válido.');
            }else{
                // Si nos llega el place
                $place = $datos['place'];
                $photo->addPlace($place); // añade el place a la photo
                $em->flush(); // aplica los cambios en la BDD
                // flashea y loguea mensajes
                $mensaje = 'Lugar '.$place->getName();
                $mensaje .= ' añadido a '.$photo->getTitle().' correctamente.';
                $this->addFlash('success', $mensaje) ;
                $appInfoLogger->info($mensaje) ;
            }
            
            // redirecciona de nuevo a la vista de edicién de la peli
            return $this->redirectToRoute( 'photo_show',['id' => $photo->getId()]);
    }
    
    #[Route('/photo/removeplace/{photo<\d+>}/{place<\d+>}', name:'photo_remove_place', methods:['GET'])]
    
    /**
     * @IsGranted("update", subject="photo")
     */
    
    public function removePlace(
        Place $place,
        Photo $photo,
        EntityManagerInterface $em,
        LoggerInterface $appInfoLogger
        ){
            
            $place->removePlace($place); // desvincular el place
            $em->flush(); // aplica los cambios en la BDD
            
            // flashea y loguea mensajes
            
            $mensaje = 'Lugar '.$place->getName();
            $mensaje .= ' eliminado a '.$photo->getTitle().' correctamente.';
            $this->addFlash('success', $mensaje);
            $appInfoLogger->info($mensaje) ;
            
            // redirecciona de nuevo a la vista de edicién de la peli
            return $this->redirectToRoute( 'photo_show',['id' => $photo->getId()]);
    }
}
