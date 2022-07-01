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
use App\Entity\Comment;
use App\Form\CommentFormType;
use App\Form\CommentDeleteFormType;
use Psr\Log\LoggerInterface;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Filesystem\Exception\IOExceptionInterface;
use Symfony\Component\Filesystem\Filesystem;

use App\Service\FileService;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use App\Repository\CommentRepository;
use App\Service\PaginatorService;
use App\Service\SimpleSearchService;
use App\Form\SearchFormType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use App\Form\CommentAddPlaceFormType;

use App\Entity\Place;

// Sym 32
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;


class CommentController extends AbstractController
{
/*    #[Route('/comment', name: 'app_comment')]
    public function index(): Response
    {
        return $this->render('comment/index.html.twig', [
            'controller_name' => 'CommentController',
        ]);
    }
    */
    
    #[Route('/comments/{pagina}',
    name:'comment_list',
    defaults:['pagina'=>1],
    methods:['GET']
    )]
    
    public function list(int $pagina, PaginatorService $paginator): Response{
        
        // Le indicamos al paginador que queremos trabajar con comments
        $paginator->setEntityType('App\Entity\Comment');
        $paginator->setLimit($this->getParameter('app.comment_results'));
        
        // Le pedimos que nos recupere todas las lugars con paginación
        $comments = $paginator->findAllEntities($pagina);
        
        //retorna la respuesta. Normalmente será una vista
        return $this->renderForm("comment/list.html.twig",
            ["comments" =>$comments, "paginator"=>$paginator]);
    }
    
    #[Route('/comment/create/{place}', name: 'comment_create', methods:['GET','POST'])]
    
    public function create(
        Request $request,
        Place $place,
        CommentRepository $commentRepository,
        LoggerInterface $appInfoLogger,
        FileService $fileService):Response{
            
            //Crea el objeto tipo Comment
            $comment= new Comment();            
            // comprobación de seguridad usando el voter
            $this->denyAccessUnlessGranted('create', $comment);            
            //Crea el formulario
            $formComment=$this->createForm(CommentFormType::class,$comment);            
            // comprueba si el formulario fué enviado
            $formComment->handleRequest($request);            
            // si el formulario ha sido enviado y es vélido
            if ($formComment->isSubmitted() && $formComment->isValid()){                
               
                
               $comment->setUser($this->getUser()) ;
               $comment->setPlace($place);
               
               // guarda el nuevo comment                
                $commentRepository->add($comment, true);
                
                // prepara un mensaje de éxito                
                $mensaje = 'Comentario '.$comment->getId().' guardado correctamente.';
                $this->addFlash('success', $mensaje); // flashea el mensaje
                $appInfoLogger->info($mensaje);	// guarda en log el mensaje
               
                // redirige a los detalles de la place
                return $this->redirectToRoute( 'place_show', ['place' => $place->getId()]);
            }
            
            // muestra la vista con el formlario
          return $this->renderForm('comment/new-html.twig',[
                'place' => $place->getId(),
                'formulario' => $formComment]);
          
    }

    #[Route('/comment/delete/{id}', name: 'comment_delete', methods:['GET','POST'])]
    //Usando la clase del formulario
    
    /**
     * @IsGranted("delete", subject="comment")
     */
    
    public function delete(
        Comment $comment,
        CommentRepository $commentRepository,
        Request $request,
        LoggerInterface $appInfoLogger,
        FileService $fileService
        ): Response{
            
            // comprobación de seguridad usando el voter
            // la quitamos para probar la anotación Sym 32
            // $this->denyAccessUnlessGranted('delete', $comment);
            
            // creación del formulario
            $formulario = $this->createForm(CommentDeleteFormType::class, $comment);
            $formulario->handleRequest($request);
            
            // si el formulario llega y es valido.
            if ($formulario->isSubmitted() && $formulario->isValid()) {
                
                // Elimina el lugars vinculada al formulario
                $commentRepository->remove($comment,TRUE);  
                
                // prepara un mensaje de éxito
                $mensaje = 'Comentario '.$comment->getId().' borrado correctamente.';
                $this->addFlash('success', $mensaje);
                $appInfoLogger->info($mensaje);
                
                // redirige a la lista de places cuando se borramos el comentario
                // desde la vista comment/delete.html.twig'
                return $this->redirectToRoute('place_show', ['place' => $comment->getPlace()->getId()]);
            }
            // muestra la vista con el formulario de borrado vacio, sin rellenar para ser rellenado
            return $this->renderForm('comment/delete.html.twig',
                ['comment'=>$comment,
                    'formulario' =>$formulario
                ]);
    }
    

    #[Route('/comment/show/{id<\d+>}', name:'comment_show')]
    
    public function show(Comment $comment):response{
        //retorna la respuesta ( normalmente será una vista)
        return $this->render("comment/show.html.twig",["comment" =>$comment]);
    }
    

    #[Route('/comment/addPlace/{id<\d+>}', name:'comment_add_place', methods:['POST'])]
    
    /**
     * @IsGranted("update", subject="comment")
     */
    // Añade el lugar al comenatario en caso de que queramos recuperara el ocmentario y que se vea a que lugar pertenece
    public function addPlace(
        Comment $comment,
        Request $request,
        EntityManagerInterface $em,
        LoggerInterface $appInfoLogger
        ){
            //tomamos el valor que llega del formulario y lo procesamos
            $formularioAddPlace = $this->createForm(CommentAddPlaceFormType::class);
            $formularioAddPlace->handleRequest($request);
            // $place = $formularioAddPlace->getData()['place'];
            $datos = $formularioAddPlace->getData();   // añadidp página 67
            
            if(empty($datos['place'])){
                // Si no nos llega el lugar es que tenemos un error
                $this->addFlash('addPlaceError', ' No se indicó un nombre de lugar válido.');
            }else{
                // Si nos llega el lugar
                $place = $datos['place'];
                $comment->addPlace($place); // añade el lugar al comment
                $em->flush(); // aplica los cambios en la BDD
                // flashea y loguea mensajes
                $mensaje = 'Lugar '.$place->getName();
                $mensaje .= ' añadido al comentario con id: '.$comment->getId().', correctamente.';
                $this->addFlash('success', $mensaje) ;
                $appInfoLogger->info($mensaje) ;
            }
            
            // redirecciona de nuevo a la vista de edición de la peli
            return $this->redirectToRoute( 'comment_show',['id' => $comment->getId()]);
    }
    
    #[Route('/comment/removeplace/{comment<\d+>}/{place<\d+>}', name:'comment_remove_place', methods:['GET'])]
    
    /**
     * @IsGranted("update", subject="comment")
     */
    
    public function removePlace(
        Place $place,
        Comment $comment,
        EntityManagerInterface $em,
        LoggerInterface $appInfoLogger
        ){
            
            $place->removePlace($place); // desvincular el lugar
            $em->flush(); // aplica los cambios en la BDD
            
            // flashea y loguea mensajes
            
            $mensaje = 'Lugar '.$place->getName();
            $mensaje .= ' desvinculado del comentario con id: '.$comment->getId().', correctamente.';
            $this->addFlash('success', $mensaje);
            $appInfoLogger->info($mensaje) ;
            
            // redirecciona de nuevo a la vista de edicién de la peli
            return $this->redirectToRoute( 'comment_show',['id' => $comment->getId()]);
    }
    
}
