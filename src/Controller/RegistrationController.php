<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\RegistrationFormType;
use App\Repository\UserRepository;
use App\Security\EmailVerifier;
use App\Security\LoginFormAuthenticator;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mime\Address;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Authentication\UserAuthenticatorInterface;
use SymfonyCasts\Bundle\VerifyEmail\Exception\VerifyEmailExceptionInterface;

use Symfony\Component\Filesystem\Exception\IOExceptionInterface;
use Symfony\Component\Filesystem\Filesystem;
use App\Service\FileService;
use Psr\Log\LoggerInterface;
use App\Form\UserDeleteFormType;

// Sym 32
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;



class RegistrationController extends AbstractController
{
    private EmailVerifier $emailVerifier;

    public function __construct(EmailVerifier $emailVerifier)
    {
        $this->emailVerifier = $emailVerifier;
    }

    #[Route('/register', name: 'app_register')]
    
    
    
    public function register(
        Request $request, 
        UserPasswordHasherInterface $passwordHasher, 
        FileService $fileService,
        UserAuthenticatorInterface $userAuthenticator, 
        LoginFormAuthenticator $authenticator,
        LoggerInterface $appUserInfoLogger,
        EntityManagerInterface $entityManager): Response
    {
        $user = new User();
        
        // comprobación de seguridad usando el voter
        // la quitamos para probar la anotación Sym 32
        // $this->denyAccessUnlessGranted('register', $user);
        
        
        $form = $this->createForm(RegistrationFormType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // encode the plain password
            $user->setPassword(
            $passwordHasher->hashPassword(
            // $userPasswordHasher->hashPassword(
                    $user,
                    $form->get('plainPassword')->getData()
                )
                );
            
            // cambia el directorio configurado
            $fileService->setTargetDirectory($this->getParameter('app.users_pics_root'));
            
            //recuperación del fichero
            $file = $form->get('fotografia')->getData();
            
            if($file) // si hay fichero...
                $user->setFotografia($fileService->upload($file)); //Sube la foto y actualiza datos.
                
            // guardamos el nuevo usuario en la BDD
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($user);
            $entityManager->flush();

            // generate a signed url and email it to the user
            $this->emailVerifier->sendEmailConfirmation('app_verify_email', $user,
                (new TemplatedEmail())
                    ->from(new Address('no-reply@symfoplaces.com', 'Registro de usuarios'))
                    ->to($user->getEmail())
                    ->subject('Please Confirm your Email')
                    ->htmlTemplate('email/register_verification.html.twig')
                );
            
            //flasheo el mensaje a mostrar
            $this->addFlash('success','Operación realizada, revisa tu email y
                haz click en el enlace para completar la operación de registro.');
            
            // loguear el mensaje
            $mensaje = 'Usuario '.$user->getDisplayname().' se ha dado de alta.';
            $appUserInfoLogger->info($mensaje);
            
            return $this->redirectToRoute('app_portada');   // Redirijo a la portada
            
            /*
             // do anything else you need here, like send an email
             
             return $userAuthenticator->authenticateUser(
             $user,
             $authenticator,
             $request
             );
             */
        }

        return $this->render('user/register.html.twig', [
            'registrationForm' => $form->createView(),
        ]);
    }
    
    #[Route('/verify/email', name: 'app_verify_email')]
    public function verifyUserEmail(Request $request, UserRepository $userRepository): Response
    {
        $id = $request->get('id');  // recupera el ID
        
        
        if (null === $id)   // Si no hay ID, redirección
            return $this->redirectToRoute('app_register');
            
            $user = $userRepository->find($id); //REcupera el usuario con ese ID
            
            if (null === $user) {   // si no hay usuario, redirección
                return $this->redirectToRoute('app_register');
            }
            
            //        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
            
            // validate email confirmation link, sets User::isVerified=true and persists
            try {
                // valida el email mediante EmailVerifier
                // el método handleEmailCOnfirmation pondrá el is_verified a true,
                // y guardará los coambios.
                $this->emailVerifier->handleEmailConfirmation($request, $user);
                
            } catch (VerifyEmailExceptionInterface $exception) {
                // flashea los mensajes de error
                $this->addFlash('verify_email_error', $exception->getReason());
                
                return $this->redirectToRoute('app_register');  //redirige
            }
            
            // @TODO Change the redirect on success and handle or remove the flash message in your templates
            // flaseha el emensaje de exito
            $this->addFlash('success', 'Tu email ha sido verificado.');
            
            // redireccionamos a la home, si no está logeado, le aparecerá
            // la vista en el formulario de identificación
            return $this->redirectToRoute('app_home');
    }
    
    #[Route('resendverificationemail', name: 'resend_verification', methods:['GET'])]
    public function resendVerificationEmail(Request $request): Response
    {
        // rechaza usuarios no identificados
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        
        $user = $this->getUser(); // recupera el usuario identificado
        
        // genera una URL firmada y se la manda por email al nuevo usuario
        $this->emailVerifier->sendEmailConfirmation('app_verify_email', $user,
            (new TemplatedEmail())
            ->from(new Address(
                'no-reply@symfoplaces.com',
                'Registro de usuarios'))
            ->to($user->getEmail())
            ->subject('Por favor, confirma tu email')
            ->htmlTemplate('email/register_verification.html.twig')
            );
        // flasheo el mensaje a mostrar
        $mensaje = 'Operación realizada, revisa tu email y haz
                    click en el enlace para completar la operación de registro.
                    El mensaje de advertencia desaparecerá tras completar el proceso';
        $this->addFlash('success', $mensaje);
        
        return $this->redirectToRoute('app_home');  // redirijo a la home
    }
    
    #[Route('/unsubscribe', name: 'app_unsubscribe', methods:['GET','POST'])]
    public function unsubscribe(Request $request,
        LoggerInterface $appUserInfoLogger,
        FileService $fileService): Response{
            
            // rechaza usuarios no identificados
            $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
            
            $usuario = $this->getUser(); // recupera el usuario
            
            // creacion del formulario de confirmacion
            $formulario = $this->createForm(UserDeleteFormType::class, $usuario);
            $formulario->handleRequest ($request) ;
            
            // si el formulario llega y es valido...
            if ($formulario->isSubmitted() && $formulario->isValid()) {
                
                // pone a NULL el user_id de las películas relacionadas
                foreach($usuario->getPlaces() as $place)
                    $usuario->removePlace($place);
                
                // pone a NULL el user_id de las películas relacionadas
                foreach($usuario->getComments() as $comment)
                        $usuario->removeComment($comment);    
                
                // modifica el directorio de destino para los ficheros
                $fileService->setTargetDirectory($this->getParameter('app.users_pics_root'));
                
                if($usuario->getFotografia()) // si hay foto
                    $fileService->remove($usuario->getFotografia()); // borra el fichero
                    
                    $entityManager = $this->getDoctrine()->getManager();
                    $entityManager->remove($usuario); // borrar el usuario
                    $entityManager->flush(); // aplicamos los cambios
                    
                    // cerrar la sesion
                    $this->container->get('security.token_storage')->setToken(null) ;
                    $this->container->get('session')->invalidate();
                    // flashear el mensaje
                    $mensaje = 'Usuario '.$usuario->getDisplayname().' eliminado correctamente. ';
                    $this->addFlash('success', $mensaje);
                    
                    // loguear el mensaje
                    $mensaje = 'Usuario '.$usuario->getDisplayname().' se ha dado de baja.';
                    $appUserInfoLogger->warning($mensaje);
                    
                    // redirigimos a portada
                    return $this->redirectToRoute('app_portada');
                    
            }
            
            // muestra el formulario de confirmacion de borrado
            return $this->renderForm("user/delete.html.twig", [
                "formulario"=>$formulario,
                "usuario" => $usuario
            ]);
    }
}
