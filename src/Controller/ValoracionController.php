<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\Valoracion;
use App\Entity\Usuarios;
use Psr\Log\LoggerInterface;
use App\Entity\Reservas;
#[Route('/api/valoracion')]
final class ValoracionController extends AbstractController
{
    private LoggerInterface $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    #[Route('', name: 'app_valoracion')]
    public function index(): Response
    {
        return $this->render('valoracion/index.html.twig', [
            'controller_name' => 'ValoracionController',
        ]);
    }

    #[Route('/valoraciones', name: 'api_crear_valoracion', methods: ['GET','POST'])]
    public function crear(Request $request, EntityManagerInterface $em): JsonResponse
    {
        // Unificar obtención de datos para JSON y form-data
        $data = $request->request->all();
        if (empty($data)) {
            $content = $request->getContent();
            if (!empty($content)) {
                $data = json_decode($content, true);
            }
        }

        if (empty($data)) {
            $this->logger->error('Datos recibidos vacíos', [
                'content' => $request->getContent(),
                'form_data' => $request->request->all(),
                'headers' => $request->headers->all(),
                'method' => $request->getMethod()
            ]);
            return new JsonResponse(['error' => 'No se recibieron datos'], 400);
        }

        // Validar campos obligatorios
        $required = ['servicioRating', 'peluqueroRating', 'comentario', 'usuario_id', 'reserva_id'];
        foreach ($required as $field) {
            if (!isset($data[$field]) || $data[$field] === '') {
                $this->logger->error('Campo obligatorio faltante o vacío', ['campo' => $field, 'recibido' => $data]);
                return new JsonResponse(['error' => 'Campo obligatorio faltante o vacío: ' . $field], 400);
            }
        }

        // Validar rangos
        if (!is_numeric($data['servicioRating']) || $data['servicioRating'] < 1 || $data['servicioRating'] > 5) {
            return new JsonResponse(['error' => 'servicioRating debe ser un número entre 1 y 5'], 400);
        }
        if (!is_numeric($data['peluqueroRating']) || $data['peluqueroRating'] < 1 || $data['peluqueroRating'] > 5) {
            return new JsonResponse(['error' => 'peluqueroRating debe ser un número entre 1 y 5'], 400);
        }
        if (empty($data['comentario'])) {
            return new JsonResponse(['error' => 'El comentario no puede estar vacío'], 400);
        }

        // Buscar usuario y reserva
        $usuario = $em->getRepository(Usuarios::class)->find($data['usuario_id']);
        if (!$usuario) {
            return new JsonResponse(['error' => 'Usuario no encontrado'], 404);
        }
        $reserva = $em->getRepository(Reservas::class)->find($data['reserva_id']);
        if (!$reserva) {
            return new JsonResponse(['error' => 'Reserva no encontrada'], 404);
        }

        // Crear y persistir la valoración
        try {
            $valoracion = new Valoracion();
            $valoracion->setServicioRating((int) $data['servicioRating']);
            $valoracion->setPeluqueroRating((int) $data['peluqueroRating']);
            $valoracion->setComentario($data['comentario']);
            $valoracion->setFecha(new \DateTime());
            $valoracion->setUsuario($usuario);
            $valoracion->setReserva($reserva);

            $em->persist($valoracion);
            $em->flush();

            return new JsonResponse([
                'id' => $valoracion->getId(),
                'servicioRating' => $valoracion->getServicioRating(),
                'peluqueroRating' => $valoracion->getPeluqueroRating(),
                'comentario' => $valoracion->getComentario(),
                'fecha' => $valoracion->getFecha()->format(\DateTimeInterface::ISO8601),
                'usuario_id' => $valoracion->getUsuario()->getId(),
                'reserva_id' => $valoracion->getReserva()->getId(),
            ], 201);
        } catch (\Exception $e) {
            $this->logger->error('Error al guardar la valoración', [
                'exception' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'data' => $data
            ]);
            return new JsonResponse([
                'error' => 'Error al guardar la valoración',
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'data' => $data
            ], 500);
        }
    }

    #[Route('/list', name: 'api_listar_valoraciones', methods: ['GET'])]
public function listar(EntityManagerInterface $em): JsonResponse
{
    $valoraciones = $em->getRepository(Valoracion::class)->findAll();

    $data = [];
    foreach ($valoraciones as $valoracion) {
        if (!$valoracion->getFecha() || !$valoracion->getUsuario()) {
            $this->logger->error('Valoración con datos inválidos', [
                'id' => $valoracion->getId(),
                'fecha' => $valoracion->getFecha(),
                'usuario' => $valoracion->getUsuario()
            ]);
            continue;
        }
        $reserva = $valoracion->getReserva();
        $usuario = $valoracion->getUsuario();
        $data[] = [
            'id' => $valoracion->getId(),
            'servicioRating' => $valoracion->getServicioRating(),
            'peluqueroRating' => $valoracion->getPeluqueroRating(),
            'comentario' => $valoracion->getComentario(),
            'fecha' => $valoracion->getFecha()->format('Y-m-d'),
            'usuario_id' => $valoracion->getUsuario()->getId(),
            'reserva' => $reserva ? [
                'id' => $reserva->getId(),
                'servicio' => $reserva->getServicio(),
                'peluquero' => $reserva->getPeluquero(),
                'dia' => $reserva->getDia()->format('Y-m-d'),
                'precio' => $reserva->getPrecio(),
                'hora' => $reserva->getHora()->format('H:i'),
                'usuario' => $reserva->getUsuario() ? $reserva->getUsuario()->getId() : null,
            ] : null,
            'usuario' => $usuario ? [
                'id' => $usuario->getId(),
                'nombre' => $usuario->getNombre(),
                'apellidos' => $usuario->getApellidos(),
                'email' => $usuario->getEmail(),
                'telefono' => $usuario->getTelefono(),
                'rol' => $usuario->getRol(),
                
            ] : null,
        ];
    }

    return new JsonResponse(['valoraciones' => $data], 200);
}
#[Route('/delete/{id}', name: 'app_valoracion_delete', methods: ['GET','DELETE'])]
public function delete(Valoracion $valoracion, EntityManagerInterface $entityManager): JsonResponse
{
    $entityManager->remove($valoracion);
    $entityManager->flush();

    return new JsonResponse(['message' => 'valoracion eliminado con éxito'], 200);
}
}