<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\ChangePasswordFormType;
use App\Form\ResetPasswordRequestFormType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use SymfonyCasts\Bundle\ResetPassword\Controller\ResetPasswordControllerTrait;
use SymfonyCasts\Bundle\ResetPassword\Exception\ResetPasswordExceptionInterface;
use SymfonyCasts\Bundle\ResetPassword\ResetPasswordHelperInterface;

#[Route('/reset-password')]
class ResetPasswordController extends AbstractController
{
    use ResetPasswordControllerTrait;

    private $resetPasswordHelper;
    private $entityManager;

    public function __construct(ResetPasswordHelperInterface $resetPasswordHelper, EntityManagerInterface $entityManager)
    {
        $this->resetPasswordHelper = $resetPasswordHelper;
        $this->entityManager = $entityManager;
    }

    /**
     * Display & process form to request a password reset.
     */
    #[Route('', name:'app_forgot_password_request')]
    public function request(Request $request, MailerInterface $mailer): Response
    {
        $form = $this->createForm(ResetPasswordRequestFormType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            return $this->processSendingPasswordResetEmail(
                $form->get('email')->getData(),
                $mailer
            );
        }

        return $this->renderForm('reset_password/request.html.twig', [
            'requestForm' => $form
        ]);
    }

    /**
     * Confirmation page after a user has requested a password reset.
     */
    #[Route('/check-email', name: 'app_check_email')]
    public function checkEmail(): Response
    {
        // Generate a fake token if the user does not exist or someone hit this page directly.
        // This prevents exposing whether or not a user was found with the given email address or not
        // REcupera el token de la sesión y si no lo hay genera uno falso
        
        if (null === ($resetToken = $this->getTokenObjectFromSession())) {
            $resetToken = $this->resetPasswordHelper->generateFakeResetToken();
        }
        
        // Muestra la vista de comprueba tu email
        return $this->render('reset_password/check_email.html.twig', [
            'resetToken' => $resetToken,
        ]);
    }

    /**
     * Validates and process the reset URL that the user clicked in their email.
     */
    #[Route('/reset/{token}', name: 'app_reset_password')]
    public function reset(Request $request, UserPasswordHasherInterface $userPasswordHasher, string $token = null): Response
    {
        
        // Si nos viene el token por la url 
        // tiene que venir al haber hecho click en el enlace del email
        if ($token) {
            // We store the token in session and remove it from the URL, to avoid the URL being
            // loaded in a browser and potentially leaking the token to 3rd party JavaScript.
            
            // Por seguridad borra el token de la url y lo guara en la infomración de sesión
            $this->storeTokenInSession($token);
            
            // Nos redirige de nuevo a esta misma operación
            return $this->redirectToRoute('app_reset_password');
        }
        
        // ahora si que recupera el token
        $token = $this->getTokenFromSession();
        
        // Pero si no lo hay...
        if (null === $token) {
            throw $this->createNotFoundException('No reset password token found in the URL or in the session. / No se encontró el token');
        }

        try {
            // valida el token y recupera el usuario a partir del mismo
            // ( busca el token en la tabla reset_password_request de la BDD
            $user = $this->resetPasswordHelper->validateTokenAndFetchUser($token);
        
        } catch (ResetPasswordExceptionInterface $e) {
            $this->addFlash('reset_password_error', sprintf(
                'Hubo un problema al validar tu petición - %s',
          //      ResetPasswordExceptionInterface::MESSAGE_PROBLEM_VALIDATE,
                $e->getReason()
            ));

            return $this->redirectToRoute('app_forgot_password_request');
        }

        // The token is valid; allow the user to change their password.
        // Si el token es válido, genera y procesa el formulario
        $form = $this->createForm(ChangePasswordFormType::class);
        $form->handleRequest($request);
        
        // Si el formulario ha sido enviado
        if ($form->isSubmitted() && $form->isValid()) {
            // A password reset token should be used only once, remove it.
            // Eliminamos el token de la BDD
            $this->resetPasswordHelper->removeResetRequest($token);

            // Encode(hash) the plain password, and set it.
            // $encodedPassword = $userPasswordHasher->hashPassword(
            // Codificamos el password usando el passwordHasher)
            $hashedPassword = $userPasswordHasher->hashPassword(
                $user,
                $form->get('plainPassword')->getData()
            );
            
            // actualiza el nuevo password del usuario
            $user->setPassword($hashedPassword);
            // $this->entityManager->flush();
            $this->getDoctrine()->getManager()->flush();

            // The session is cleaned up after the password has been changed.
            // limpiamos los datos de la sesión
            $this->cleanSessionAfterReset();
            
            // flasheamos el mensaje
            $this->addFlash('success', 'Tu petición se ha procesado con éxito');

            // Redirigimos
            return $this->redirectToRoute('app_login');
        }
        
        // Mostramos el formulario
        return $this->render('reset_password/reset.html.twig', [
            'resetForm' => $form->createView(),
        ]);
    }
    // este método es privado y solamente es llamado desde dentro de esta misma clase por el método request()
    private function processSendingPasswordResetEmail(string $emailFormData, MailerInterface $mailer): RedirectResponse
    {
/* original        $user = $this->entityManager->getRepository(User::class)->findOneBy([
            'email' => $emailFormData,
        ]); */
        
        // Comprueba que el usuario existe por email
        $user = $this->getDoctrine()->getRepository(User::class)->findOneBy([
            'email' => $emailFormData,
        ]);

        // Do not reveal whether a user account was found or not.
        
        // si no existe redirige igualmente a "check email" sin llegar a enviar el email
        // esto se hace para no dar pistas de si un email está registrado o no
        if (!$user) {
            return $this->redirectToRoute('app_check_email');
        }

        try {
            // genera el token para adjuntarlo en el email y verificar la operación
            $resetToken = $this->resetPasswordHelper->generateResetToken($user);
        } catch (ResetPasswordExceptionInterface $e) {
            // If you want to tell the user why a reset email was not sent, uncomment
            // the lines below and change the redirect to 'app_forgot_password_request'.
            // Caution: This may reveal if a user is registered or not.
            //
            // $this->addFlash('reset_password_error', sprintf(
            //     '%s - %s',
            //     ResetPasswordExceptionInterface::MESSAGE_PROBLEM_HANDLE,
            //     $e->getReason()
            // ));

            return $this->redirectToRoute('app_check_email');
        }

        // prepara el email mediante TEmplate
        $email = (new TemplatedEmail())
            ->from(new Address('resetpassword@symfofilms.com', 'Reset Password'))
            ->to($user->getEmail())
            ->subject('Your password reset request / Petición para restablecer clave')
            ->htmlTemplate('email/reset_password.html.twig')
            ->context([
                'resetToken' => $resetToken,
            ])
        ;

        $mailer->send($email);

        // Store the token object in session for retrieval in check-email route.
        $this->setTokenObjectInSession($resetToken);

        return $this->redirectToRoute('app_check_email');
    }
}
