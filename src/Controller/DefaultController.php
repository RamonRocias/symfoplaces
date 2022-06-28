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