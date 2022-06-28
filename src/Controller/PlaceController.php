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


use Symfony\Component\Routing\Annotation\Route;
use App\Entity\Place;
use App\Form\PlaceFormType;
use App\Form\PlaceDeleteFormType;
use Psr\Log\LoggerInterface;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Filesystem\Exception\IOExceptionInterface;
use Symfony\Component\Filesystem\Filesystem;

use App\Service\FileService;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use App\Repository\PlaceRepository;
use App\Service\PaginatorService;
use App\Service\SimpleSearchService;
use App\Form\SearchFormType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use App\Form\PlaceAddActorFormType;

use App\Entity\Photo;
use App\Entity\Comment;

// Sym 32
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;


class PlaceController extends AbstractController
{
/*    #[Route('/place', name: 'app_place')]
    public function index(): Response
    {
        return $this->render('place/index.html.twig', [
            'controller_name' => 'PlaceController',
        ]);
    }*/
    
    #[Route('/places/{pagina}',
    name:'place_list',
    defaults:['pagina'=>1],
    methods:['GET']
    )]
    
    public function list(int $pagina, PaginatorService $paginator): Response{
        // Le indicamos al paginador que queremos trabajar con places
        $paginator->setEntityType('App\Entity\Place');
        
        // Le pedimos que nos recupere todas las places con paginación
        $places = $paginator->findAllEntities($pagina);
        $paginator->setLimit($this->getParameter('app.movie_results'));
        
        //retorna la respuesta. Normalmente será una vista
        return $this->renderForm("place/list.html.twig",
            ["places" =>$places, "paginator"=>$paginator]);
        
    }
    
    #[Route('/place/create', name: 'place_create', methods:['GET','POST'])]
    
    public function create(
        Request $request,
        PlaceRepository $placeRepository,
        LoggerInterface $appInfoLogger,
        FileService $fileService):Response{
            
            //Crea el objeto tipo Place
            $place= new Place();
            
            // comprobación de seguridad usando el voter
            $this->denyAccessUnlessGranted('create', $place);
            
            // Crea el formulario
            $formulario=$this->createForm(PlaceFormType::class,$place);
            
            // comprueba si el formulario fué enviado
            $formulario->handleRequest($request);
            
            // si el formulario ha sido enviado y es válido
            if ($formulario->isSubmitted() && $formulario->isValid()){
                
                if($uploadedFile = $formulario->get('caratula')->getData()){ // Si hay fichero
                    // indica al FileService que trabaje con el directorio de carátulas
                    $fileService->setTargetDirectory($this->getParameter('app.covers.root'));
                    
                    // sube el fichero al directorio y guarda su nombre en la entidad
                    $place->setCaratula($fileService->upload($uploadedFile, true, 'cover_'));
                    
                    // establece el creador de la nueva place
                    $place->setUser($this->getUser());
                }
                // guarda la nueva place
                
                $placeRepository->add($place, true);
                
                // prepara un mensaje de éxito
                
                $mensaje = 'Place '.$place->getTitulo().' guardado correctamente con id'.$place->getId();
                $this->addFlash('success', $mensaje); // flashea el mensaje
                $appInfoLogger->info($mensaje);	// guarda en log el mensaje
                
                // redirige a los detalles del place
                return $this->redirectToRoute( 'place_show', ['id' => $place->getId()]);
            }
            
            // muestra la vista con el formlario
            return $this->render('place/new-html.twig',[
                'formulario' => $formulario->createView(), "place"=>$place]);
    }
    
    #[Route('place/search', name:'place_search', methods:['GET','POST'])]
    
    public function search(Request $request, SimpleSearchService $busqueda):Response{
        
        // crea el formulario
        $formulario = $this->createForm(SearchFormType::class, $busqueda, [
            'field_choices' => [
                'ID' => 'id',
                'Tipo' => 'type',
                'Nombre' => 'name',
                'Ciudad' => 'city',
                'País' => 'country',
                'Continente' => 'continent'
            ],
            'order_choices' =>[
                'ID' => 'id',
                'Tipo' => 'type',
                'Nombre' => 'name',
                'Ciudad' => 'city',
                'País' => 'country',
                'Continente' => 'continent'
            ]
        ]);
        
        // establece el valor selected para los SELECT
        $formulario->get('campo')->setData($busqueda->campo);
        $formulario->get('orden')->setData($busqueda->orden);
        
        // gestiona el formulario y recupera los valores de busqueda
        $formulario->handleRequest($request);
        
        // realiza La busqueda
        $places = $busqueda->search('App\Entity\Place');
        
        // retorna la vista con los resultados
        return $this->renderForm("place/buscar.html.twig", [
            "formulario"=>$formulario,
            "places" => $places
        ]);
    }
    
    #[Route('/place/update/{id}', name: 'place_update', methods:['GET','POST'])]
    
    /**
     * @IsGranted("update", subject="place")
     */
    
    public function update(
        Place $place,
        PlaceRepository $placeRepository,
        Request $request,
        LoggerInterface $appInfoLogger,
        FileService $fileService,
        Filesystem $fileSystem
        ):Response{
            //dd($place);
            
            // comprobación de seguridad usando el voter
            // la quitamos para probar la anotación Sym 32
            // $this->denyAccessUnlessGranted('update', $place);
            
            // crea el formulario
            $formulario = $this->createForm(PlaceFormType::class, $place);
            
            // Symfony29 añadimos código para incluir combobox con photos            
            // crea el FormType para añadir photos
            // los datos irán a al url /place/addphoto/{idplace}
            $formularioAddPhoto = $this->createForm(PlaceAddPhotoFormType::class, NULL,[
                'action' => $this->generateUrl('place_add_photo', ['id'=>$place->getId()])
            ]);
            
            
            // OJO AQUÍ PARA VER IS ES place_add_commment o comment_add_place.
            // Symfony29 añadimos código para incluir comentarios
            // crea el FormType para añadir comentarios
            // los datos irán a al url /place/addcomment/{idplace}
            $formularioAddComment = $this->createForm(PlaceAddCommentFormType::class, NULL,[
                'action' => $this->generateUrl('place_add_comment', ['id'=>$place->getId()])
            ]);
            
            //Recuperamos el nombre del fichero de la ccarátula con uniqid guardado en la BDD
            $caratulaAntigua=$place->getCaratula();
            //dd($fichero);
            // comprueba si el formulario fué enviado y rellena los datos
            // de la place con los datos que vienen del request
            $formulario->handleRequest($request);
            
            // si el formulario fue enviado y es valido...
            if ($formulario->isSubmitted() && $formulario->isValid()) {
                
                //Si llega una nueva carátula
                if ($uploadedFile = $formulario->get('caratula')->getData()) {
                    // Subida de fichero con servicio SYM 16
                    
                    // indica al FileService que trabaje con el directorio de carátulas
                    $fileService->setTargetDirectory($this->getParameter('app.covers.root'));
                    
                    // remplaza el fichero y guarda el nuevo nombre en la entidad
                    $place->setCaratula($fileService->replace($uploadedFile, $caratulaAntigua, TRUE, 'cover__'));
                    
                    // si no llega la carátula nueva, seguiremos usando la vieja.
                }else{
                    $place->setCaratula($caratulaAntigua);
                }
                
                // aplica las modificaciones de las places en la BDD
                $placeRepository->add($place,TRUE);
                
                // prepara el mensaje de éxito
                $this->addFlash('success', 'Datos de la place actualizados correctamente.');
                // redirige a "ver detalles de la place"
                return $this->redirectToRoute('place_update',['id' => $place->getId()]);
            }
            
            // carga la vista con el formulario
            return $this->render("place/update.html.twig", [
                "formulario"=>$formulario->createView(),
                "formularioAddPhoto"=>$formularioAddPhoto->createView(),
                "formularioAddCommnet"=>$formularioAddCommnet->createView(),
                "place" => $place
            ]);
    }
    
    #[Route('/place/delete/{id}', name: 'place_delete', methods:['GET','POST'])]
    //Usando la clase del formulario
    
    /**
     * @IsGranted("delete", subject="place")
     */
    
    public function delete(
        Place $place,
        PlaceRepository $placeRepository,
        Request $request,
        LoggerInterface $appInfoLogger,
        FileService $fileService
        ): Response{
            
            // comprobación de seguridad usando el voter
            // la quitamos para probar la anotación Sym 32
            // $this->denyAccessUnlessGranted('update', $place);
            
            // creación del formulario
            $formulario = $this->createForm(PlaceDeleteFormType::class, $place);
            $formulario->handleRequest($request);
            
            // si el formulario llega y es valido.
            if ($formulario->isSubmitted() && $formulario->isValid()) {
                
                // Elimina la places vinculada al formulario
                $placeRepository->remove($place,TRUE);
                
                // Si había carátula, hay que borrar el fichero del sistema de ficheros
                if($caratula = $place->getCaratula()){ // si hay caratula
                    
                    $fileService->setTargetDirectory($this->getParameter('app.covers.root'))->remove($caratula);
                }
                
                // prepara un mensaje de éxito
                $mensaje = 'Place '.$place->getTitulo().' borrada correctamente.';
                $this->addFlash('success', $mensaje);
                $appInfoLogger->info($mensaje);
                
                // redirige a la lista de places
                return $this->redirectToRoute('place_list');
            }
            // muestra la vista con el formulario de borrado
            return $this->renderForm('place/delete.html.twig',
                ['place'=>$place,
                    'formulario' =>$formulario
                ]);
    }
    
    //Utiliza el : use Symfony\Component\Filesystem\Filesystem;
    #[Route('/place/deleteimage/{id<\d+>}', name:'place_delete_cover',
    methods:['GET'],
    requirements:['id'=>'\d+']
    )]
    
    /**
     * @IsGranted("update", subject="place")
     */
    
    public function deleteCover(
        Request $request,
        PlaceRepository $placeRepository,
        FileService $fileService,
        EntityManagerInterface $em,
        Place $place) :Response{
            
            if($caratula= $place->getCaratula()){ // si hay caratula
                
                // la borramos del sistma de ficheros
                $fileService->setTargetDirectory($this->getParameter('app.covers.root'))->remove($caratula);
                
                // actualizamos los datos de la place y los guradamos en BDD
                $place->setCaratula(NULL);
                $placeRepository->add($place,TRUE);
                
                // flashear el mensaje
                $mensaje = 'Carátula de la place '.$place->getTitulo().'borrada.';
                $this->addFlash('success', $mensaje);
            }
            // carga la vista con el formulario
            return $this->redirectToRoute('place_update',['id' => $place->getId()]);
            
    }
    
    #[Route('/place/show/{id<\d+>}', name:'place_show')]
    
    public function show(Place $place):Response{
        //retorna la respuesta ( normalmente será una vista)
        return $this->render("place/show.html.twig",["place" =>$place]);
    }
    
    #[Route('/place/addphoto/{id<\d+>}', name:'place_add_photo', methods:['POST'])]
    
    /**
     * @IsGranted("update", subject="place")
     */
    
    public function addPhoto(
        Place $place,
        Request $request,
        EntityManagerInterface $em,
        LoggerInterface $appInfoLogger
        ){
            //tomamos el valor que llega del formulario y lo procesamos
            $formularioAddPhoto = $this->createForm(PlaceAddPhotoFormType::class);
            $formularioAddPhoto->handleRequest($request);
            // $actor = $formularioAddPhotor->getData()['actor'];
            $datos = $formularioAddPhoto->getData();   // añadidp página 67
            
            if(empty($datos['photo'])){
                // Si no nos llega la foto es que tenemos un error
                $this->addFlash('addPhotoError', ' No se indicó una photo válida.');
            }else{
                // Si nos llega la photo
                $photo = $datos['photo'];
                $place->addPhoto($photo); // Añade la photo al place
                $em->flush(); // aplica los cambios en la BDD
                // flashea y loguea mensajes
                $mensaje = 'Foto '.$photo->getTitle();
                $mensaje .= ' añadido a '.$place->getName().' correctamente.';
                $this->addFlash('success', $mensaje) ;
                $appInfoLogger->info($mensaje) ;
            }
            
            // redirecciona de nuevo a la vista de edicién de la place
            return $this->redirectToRoute( 'place_update',['id' => $place->getId()]);
    }
    
    
    
    #[Route('/place/addcomment/{id<\d+>}', name:'place_add_comment', methods:['POST'])]
    
    /**
     * @IsGranted("update", subject="place")
     */
    
    public function addComment(
        Place $place,
        Request $request,
        EntityManagerInterface $em,
        LoggerInterface $appInfoLogger
        ){
            //tomamos el valor que llega del formulario y lo procesamos
            $formularioAddComment = $this->createForm(PlaceAddCommentFormType::class);
            $formularioAddComment->handleRequest($request);
            // $comment = $formularioAddCommnet->getData()['commnet'];
            $datos = $formularioAddComment->getData();   // añadidp página 67
            
            if(empty($datos['commnet'])){
                // Si no nos llega el comentario es que tenemos un error un error
                $this->addFlash('addPhotoError', ' No se indicó una photo válida.');
            }else{
                // Si nos llega la photo
                $comment = $datos['comment'];
                $place->addComment($comment); // Añade el commnet al place
                $em->flush(); // aplica los cambios en la BDD
                // flashea y loguea mensajes
                $mensaje = 'Comentario: '.$comment->getText();
                $mensaje .= ' añadido a '.$place->getName().' correctamente.';
                $this->addFlash('success', $mensaje) ;
                $appInfoLogger->info($mensaje) ;
            }
            
            // redirecciona de nuevo a la vista de edicién de la place
            return $this->redirectToRoute( 'place_update',['id' => $place->getId()]);
    }
    
    #[Route('/place/removephoto/{place<\d+>}/{photo<\d+>}', name:'place_remove_photo', methods:['GET'])]
    
    /**
     * @IsGranted("update", subject="place")
     */
    
    public function removePhoto(
        Place $place,
        Photo $photo,
        EntityManagerInterface $em,
        LoggerInterface $appInfoLogger
        ){
            
            $place->removeActore($photo); // desvincular la foto del lugar
            $em->flush(); // aplica los cambios en la BDD
            
            // flashea y loguea mensajes
            
            $mensaje = 'Photo '.$photo->getTitle();
            $mensaje .= ' eliminado de '.$place->getName().' correctamente.';
            $this->addFlash('success', $mensaje);
            $appInfoLogger->info($mensaje) ;
            
            // redirecciona de nuevo a la vista de edición de la place
            return $this->redirectToRoute( 'place_update',['id' => $place->getId()]);
    }
    
    #[Route('/place/removecomment/{place<\d+>}/{comment<\d+>}', name:'place_remove_comment', methods:['GET'])]
    
    /**
     * @IsGranted("update", subject="place")
     */
    
    public function removeComment(
        Place $place,
        Comment $comment,
        EntityManagerInterface $em,
        LoggerInterface $appInfoLogger
        ){
            
            $place->removeComment($comment); // desvincular el comment del lugar
            $em->flush(); // aplica los cambios en la BDD
            
            // flashea y loguea mensajes
            
            $mensaje = 'Comentario: '.$comment->getText();
            $mensaje .= ' eliminado de '.$place->getName().' correctamente.';
            $this->addFlash('success', $mensaje);
            $appInfoLogger->info($mensaje) ;
            
            // redirecciona de nuevo a la vista de edición de la place
            return $this->redirectToRoute( 'place_update',['id' => $place->getId()]);
    }
    
}
