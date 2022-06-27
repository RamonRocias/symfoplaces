<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\Request;
// use App\Service\FrasesService;

use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

use App\Form\ContactoFormType;
use Psr\Log\LoggerInterface;

use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mime\Address;
use App\Entity\Place;
use App\Repository\PlaceRepository;

class DefaultController extends AbstractController
{
    
    // Ejemplo de clase
    #[Route('/', name : 'app_portada')]
    public function portada(PlaceRepository $pr){
        return $this->render('portada.html.twig',[
            'places'=> $pr->findLast(
                $this->getParameter('app.portada.covers'))
        ]);
    }
    
    #[Route('/ejemplorequest', name: 'app_ejemplo_request')]
    public function ejemplo(): Response
    {
        //crea el objeto request
        $request= Request::createFromGlobals();
        
        // Si llega el parámetro'nombre' por el método GET..
        if($request->query->has('nombre','apellido'))
            return new Response('El hombre indicado es: '.$request->query->get('nombre').' '.$request->query->get('apellido'));
            
            //si no...
            else
                return new response('No se indicó el parámetro nombre.');
    }
    
    #[Route('/ejemplorequest2', name: 'app_ejemplo_request2')]
    public function ejemplo2(): Response
    {
        //crea el objeto request
        $request= Request::createFromGlobals();
        
        return new Response('El hombre indicado es: '.$request->query->get('nombre','anonimo'));
    }
    
    #[Route('/ejemplorequest3', name:'app_ejemplo_request3')]
    public function ejemplo3(Request $request) :Response{
        
        // crea una peticion simulada
        
        $request = Request::create(
            '/holamundo' ,
            'GET',
            ['nombre' => 'Ramón' ]
            );
        $request->overrideGlobals(); // reescribe las superglobales de PHP
        
        $texto = 'El nombre es: '.$request->query->get('nombre');
        $texto .=' y si lo miramos en $_GET: '.$_GET['nombre'];
        
        return new Response($texto);
    }
    
    #[Route('/ejemplorequest4', name:'app_ejemplo_request4')]
    public function ejemplo4(Request $request) :Response{
        
        // convierte la información de JSON a array
        $datos=$request->toArray();
        
        return new Response('El nombre es '.$datos['nombre']);
    }
    
    #[Route('/getcookie',name:'app_getcookie')]
    public function getCookie(Request $request):Response{
        
        return $request->cookies->has('autor') ?
        new Response("He recuperado: ".$request->cookies->get('autor')) :
        new Response("No existe la cookie con nombre'autor'.");
    }
    
    //Plantilla de email, página 34
    #[Route('/contact',name:'contacto')]
    public function contacto(Request $request, MailerInterface $mailer):Response{
        
        //crea el formulario
        $formulario = $this->createForm(ContactoFormType::class);
        $formulario->handleRequest ($request);
        
        // si el formlario fue enviado y es válido...
        if($formulario->isSubmitted() && $formulario->isValid()) {
            $datos = $formulario->getData(); // recuperamos los datos del formulario
            
            $email = new TemplatedEmail(); // nuevo objeto TemplateEmail
            $email->from(new Address($datos['email'], $datos['nombre']))
            ->to($this->getParameter('app.admin_email'))
            ->subject($datos['asunto'])
            // template que usaremos para el email
            ->htmlTemplate('email/contact.html.twig')
            // variables que se el pasan al template
            ->context([
                'de' => $datos['email'],
                'nombre' => $datos['nombre'],
                'asunto' => $datos['asunto'],
                'mensaje' => $datos['mensaje']
            ]);
            
            $mailer->send($email); // envia el wail
            
            // Flashear mensaje y redirigir a la portada
            $this->addFlash('success', 'Mensaje enviado correctamente');
            return $this->redirectToRoute('app_portada');
            
        }
        
        // muestra la vista con el formulario
        return $this->renderForm("contacto.html.twig",["formulario"=>$formulario]);
    }
}