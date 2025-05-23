<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Entity\ReservasAnuladas;
use App\Repository\ReservasAnuladasRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

#[Route('/api/anuladas')]
final class ReservasAnuladasController extends AbstractController
{
    #[Route('', name: 'app_reservas_anuladas_index', methods: ['GET'])]
    public function index(ReservasAnuladasRepository $ReservasAnuladasRepository): Response
    {
        return $this->render('reservas_anuladas/index.html.twig', [
            'reservas_anuladas' => $ReservasAnuladasRepository->findAll(),
        ]);
    }

    #[Route('/list', name: 'app_reservas_anuladas_list', methods: ['GET'])]
    public function getReservasAnuladas(Request $request, EntityManagerInterface $entityManager): JsonResponse
    {
        $anuladas = $entityManager->getRepository(ReservasAnuladas::class)->findAll();
        
        $data = [];
        foreach ($anuladas as $reserva) {
            $data[] = [
                'id' => $reserva->getId(),
                'servicio' => $reserva->getServicio(),
                'peluquero' => $reserva->getPeluquero(),
                'precio' => $reserva->getPrecio(),
                'dia' => $reserva->getDia()->format('Y-m-d'),
                'hora' => $reserva->getHora()->format('H:i'),
                'usuario_id' => $reserva->getUsuarios()?->getId(),
                'fecha_anulada' => $reserva->getFechaAnulada()?->format('Y-m-d') ?? null // Handle null case
            ];
        }

        return new JsonResponse($data);
    }

    #[Route('/delete/{id}', name: 'app_reservas_anulada_delete', methods: ['GET','DELETE'])]
public function delete(ReservasAnuladas $reserva, EntityManagerInterface $entityManager ): JsonResponse
{
    $entityManager->remove($reserva);
    $entityManager->flush();

    
    return new JsonResponse(['status' => 'Reserva anulada']);
}

}
