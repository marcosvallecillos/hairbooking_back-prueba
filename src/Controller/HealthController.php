<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

final class HealthController extends AbstractController
{
    #[Route('/', name: 'app_health')]
    public function index(EntityManagerInterface $em, LoggerInterface $logger): Response
    {
        try {
            $conn = $em->getConnection();
            $conn->connect();
            if ($conn->isConnected()) {
                $logger->info('¡Conexión a la base de datos exitosa!');
                return new Response('Conexión a la base de datos exitosa!');
            }
        } catch (\Exception $e) {
            $logger->error('Error al conectar a la base de datos: '.$e->getMessage());
            return new Response('Error al conectar a la base de datos: '.$e->getMessage(), 500);
        }

        return new Response('No se pudo conectar a la base de datos.', 500);
    }
}
