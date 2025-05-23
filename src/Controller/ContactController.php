<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Form\ContactType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Psr\Log\LoggerInterface;

final class ContactController extends AbstractController
{
    #[Route('/contact', name: 'app_contact', methods: ['GET','POST'])]
    public function contactApi(Request $request, MailerInterface $mailer, LoggerInterface $logger): Response
    {
        // Decodifica el JSON recibido
        $data = json_decode($request->getContent(), true);
    
        if (!$data) {
            return $this->json(['status' => 'error', 'message' => 'Datos inválidos'], 400);
        }
    
        // Validación básica (puedes mejorarla)
        if (empty($data['name']) || empty($data['email']) || empty($data['message'])) {
            return $this->json(['status' => 'error', 'message' => 'Faltan campos obligatorios'], 400);
        }
    
        $email = (new Email())
            ->from($data['email'])
            ->to('marcosvalleu@gmail.com')
            ->subject('Nuevo mensaje de contacto: ' . ($data['subject'] ?? ''))
            ->html(
                '<h2>Mensaje de Contacto Enviado</h2>' .
                '<p><strong>Nombre:</strong> ' . htmlspecialchars($data['name']) . '</p>' .
                '<p><strong>Apellidos:</strong> ' . htmlspecialchars($data['apellidos'] ?? '') . '</p>' .
                '<p><strong>Email:</strong> ' . htmlspecialchars($data['email']) . '</p>' .
                '<p><strong>Teléfono:</strong> ' . htmlspecialchars($data['phone'] ?? '') . '</p>' .
                '<p><strong>Asunto:</strong> ' . htmlspecialchars($data['subject'] ?? '') . '</p>' .
                '<p><strong>Mensaje:</strong></p>' .
                '<p>' . nl2br(htmlspecialchars($data['message'])) . '</p>'
            );
    
        try {
            $mailer->send($email);
            $logger->info('Email enviado correctamente.');
            return $this->json(['status' => 'success', 'message' => '¡Tu mensaje ha sido enviado correctamente!']);
        } catch (\Exception $e) {
            $logger->error('Error al enviar el email: ' . $e->getMessage());
            return $this->json(['status' => 'error', 'message' => 'Ha ocurrido un error al enviar el mensaje. Por favor, inténtalo de nuevo.'], 500);
        }
    }
}