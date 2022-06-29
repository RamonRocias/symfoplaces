<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use App\Entity\User;
use App\Repository\UserRepository;
use App\Service\FileService;
use Psr\Log\LoggerInterface;

use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;


use App\Form\UserFormType; 
use App\Form\UserDeleteFormType;

// Sym 32
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
// Para añadir el método list
use App\Service\PaginatorService;
use App\Service\SimpleSearchService;
use App\Form\SearchFormType;



class UserController extends AbstractController
{
    
    #[Route('/users/{pagina}', name: 'user_list', defaults:['pagina'=>1], methods:['GET'] )]
    
    public function list(int $pagina, PaginatorService $paginator): Response{
        // Le indicamos al paginador que queremos trabajar con places
        $paginator->setEntityType('App\Entity\User');
        
        // Le pedimos que nos recupere todas las películas con paginación
        $users = $paginator->findAllEntities($pagina);
        $paginator->setLimit($this->getParameter('app.user_results'));
        
        //retorna la respuesta. Normalmente será una vista
        return $this->renderForm("user/list.html.twig",
            ["users" =>$users, "paginator"=>$paginator]);
        
    }
    
    #[Route('user/search', name:'user_search', methods:['GET','POST'])]
    
    public function search(Request $request, SimpleSearchService $busqueda):Response{
        
        // crea el formulario
        $formulario = $this->createForm(SearchFormType::class, $busqueda, [
            'field_choices' => [
                'ID' => 'id',
                'Email' => 'email',
                'Roles' => 'roles',
                'Álias' => 'displayname'
            ],
            'order_choices' =>[
                'ID' => 'id',
                'Email' => 'email',
                'Roles' => 'roles',
                'Álias' => 'displayname'
            ]
        ]);
        
        // establece el valor selected para los SELECT
        $formulario->get('campo')->setData($busqueda->campo);
        $formulario->get('orden')->setData($busqueda->orden);
        
        // gestiona el formulario y recupera los valores de busqueda
        $formulario->handleRequest($request);
        
        // realiza La busqueda
        $users = $busqueda->search('App\Entity\User');
        
        // retorna la vista con los resultados
        return $this->renderForm("user/buscar.html.twig", [
            "formulario"=>$formulario,
            "users" => $users
        ]);
    }
    
    #[Route('/home', name: 'app_home', methods:['GET'])]
   
    public function home(): Response
    {
        // rechaza usuarios no identificados
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        
        // Carga la vista con la información del usuario
        
        return $this->render('user/home.html.twig', [
            'controller_name' => 'UserController',
        ]);
    }
    
    #[Route('/user/pic/{fotografia}', name: 'pic_show', methods:['GET'])]
    
    public function showPic(string $fotografia)
    {
    
        // no nos dejará ver la imagen si no estamos identificados
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        
        // Ruta donde se encuentra la fotografía
        $ruta = $this->getParameter('app.users_pics_root');
        
        // Prepara la respuesta con la fotografía
        $response = new \Symfony\Component\HttpFoundation\BinaryFileResponse($ruta.'/'.$fotografia);
        $response->trustXSendfileTypeHeader();
        $response->setContentDisposition(
                ResponseHeaderBag::DISPOSITION_INLINE,
                $fotografia,
                iconv('UTF-8', 'ASCII//TRANSLIT', $fotografia)
            );
        // retorna la respuesta
        return $response;
    }
    
    #[Route('/user/update/{id}', name: 'user_update', methods:['GET','POST'])]
    
    /**
     * @IsGranted("update", subject="user")
     */
    
    public function update(
        User $user,
        UserRepository $userRepository,
        Request $request,
        LoggerInterface $appInfoLogger,
        FileService $fileService,
        Filesystem $fileSystem
        ):Response{
        // dd($user);
        
        // comprobación de seguridad usando el voter
        // la quitamos para probar la anotación Sym 32
        // $this->denyAccessUnlessGranted('update', $user);        
            
        // crea el formulario
        $formulario = $this->createForm(UserFormType::class, $user);
/*            // Symfony29 añadimos código para incluir combobox con photoes
            // crea el FormType para añadir photo
            // los datos irán a al url /user/addplace/{iduser}
            $formularioAddPhoto = $this->createForm(UserAddPlaceFormType::class, NULL,[
                'action' => $this->generateUrl('user_add_place', ['id'=>$user->getId()])
            ]);
*/            
        //Recuperamos el nombre del fichero de la ccarátula con uniqid guardado en la BDD
        $fotografiaAntigua=$user->getFotografia();
        //dd($fichero);
        // comprueba si el formulario fué enviado y rellena los datos
        // de la película con los datos que vienen del request
        $formulario->handleRequest($request);
        
        // si el formulario fue enviado y es valido...
        if ($formulario->isSubmitted() && $formulario->isValid()) {
            
            //Si llega una nueva carátula
            if ($uploadedFile = $formulario->get('fotografia')->getData()) {
                // Subida de fichero con servicio SYM 16
                
                // indica al FileService que trabaje con el directorio de fotografías de usuarios (services.yaml)
                $fileService->setTargetDirectory($this->getParameter('app.users_pics_root'));
                
                // remplaza el fichero y guarda el nuevo nombre en la entidad
                $user->setFotografia($fileService->replace($uploadedFile, $fotografiaAntigua, TRUE, 'pic__'));
                
                // si no llega la carátula nueva, seguiremos usando la vieja.
            }else{
                $user->setFotografia($fotografiaAntigua);
            }
            
            // aplica las modificaciones de las películas en la BDD
            $userRepository->add($user,TRUE);
            
            // prepara el mensaje de éxito
            $this->addFlash('success', 'Datos del usuario correctamente.');
            
            // redirigimos a home
           // return $this->redirectToRoute('app_home');
            // redirige a "ver detalles del usuario" para editarlos
            // return $this->redirectToRoute('user_update',['id' => $user->getId()]);
            // redirige a "ver detalles del usuario" 
            return $this->redirectToRoute('user_show',['id' => $user->getId()]);
        }
        
        // carga la vista con el formulario
        return $this->render("user/update.html.twig", [
            "formulario"=>$formulario->createView(),
//              "formularioAddPlace"=>$formularioAddPlace->createView(),
            "user" => $user
        ]);
    }
    
    #[Route('/user/show/{id<\d+>}', name:'user_show')]
    
    public function show(User $user):Response{
        //retorna la respuesta ( normalmente será una vista)
        return $this->render("user/show.html.twig",["user" =>$user]);
    }
    
    #[Route('/user/delete/{id}', name: 'user_delete', methods:['GET','POST'])]
    //Usando la clase del formulario
    
    /**
     * @IsGranted("update", subject="user")
     */
    
    public function delete(
        User $user,
        UserRepository $userRepository,
        Request $request,
        LoggerInterface $appInfoLogger,
        FileService $fileService
        ): Response{
            
            // comprobación de seguridad usando el voter
            // la quitamos para probar la anotación Sym 32
            // $this->denyAccessUnlessGranted('update', $user);
            
            // creación del formulario
            $formulario = $this->createForm(UserDeleteFormType::class, $user);
            $formulario->handleRequest($request);
            
            // si el formulario llega y es valido.
            if ($formulario->isSubmitted() && $formulario->isValid()) {
                
                // Elimina la películas vinculada al formulario
                $userRepository->remove($user,TRUE);
                
                // Si había fotografía, hay que borrar el fichero del sistema de ficheros
                if($fotografía = $user->getFotografia()){ // si hay fotografía
                    
                    $fileService->setTargetDirectory($this->getParameter('app.users_pics_root'))->remove($fotografia);
                }
                
                // prepara un mensaje de éxito
                $mensaje = 'Usuario '.$user->getEmail().' borrado correctamente.';
                $this->addFlash('success', $mensaje);
                $appInfoLogger->info($mensaje);
                
                // redirige a la lista de places
                return $this->redirectToRoute('user_list');
            }
            // muestra la vista con el formulario de borrado
            return $this->renderForm('user/deletebyadmin.html.twig',
                ['user'=>$user,
                    'formulario' =>$formulario
                ]);
    }
}
