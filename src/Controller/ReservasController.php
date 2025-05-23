<?php

namespace App\Controller;

use App\Entity\Reservas;
use App\Entity\Usuarios;
use App\Entity\Valoracion;
use App\Entity\ReservasAnuladas;
use App\Form\ReservasType;
use App\Repository\ReservasRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Psr\Log\LoggerInterface;

use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/reservas')]
final class ReservasController extends AbstractController
{
    public function __construct(
        private readonly LoggerInterface $logger
    ) {}

    #[Route(name: 'app_reservas_index', methods: ['GET'])]
    public function index(ReservasRepository $reservasRepository): JsonResponse
    {
        $reservas = $reservasRepository->findAll();
        $data = [];
        
        foreach ($reservas as $reserva) {
            $valoracion = $reserva->getValoracions()->first() ? $reserva->getValoracions()->first()->getId() : null;
            $comentario = $reserva->getValoracions()->first() ? $reserva->getValoracions()->first()->getComentario() : null;
            $servicioRating = $reserva->getValoracions()->first() ? $reserva->getValoracions()->first()->getServicioRating() : null;
            $peluqueroRating = $reserva->getValoracions()->first() ? $reserva->getValoracions()->first()->getPeluqueroRating() : null;

            $data[] = [
                'id' => $reserva->getId(),
                'servicio' => $reserva->getServicio(),
                'peluquero' => $reserva->getPeluquero(),
                'precio' => $reserva->getPrecio(),
                'dia' => $reserva->getDia()->format('Y-m-d'),
                'hora' => $reserva->getHora()->format('H:i'),
                'usuario_id' => $reserva->getUsuario() ? $reserva->getUsuario()->getId() : null,
                'valoracion' => $valoracion,
                'valoracion_comentario' => $comentario,
                'valoracion_servicio' => $servicioRating,
                'valoracion_peluquero' => $peluqueroRating
            ];
        }
        
        return new JsonResponse($data);
    }

    #[Route('/new', name: 'app_reservas_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager, MailerInterface $mailer): Response
    {
        $data = json_decode($request->getContent(), true);
    
        if ($data === null) {
            return new JsonResponse(['status' => 'JSON inválido'], 400);
        }
    
        $requiredFields = ['servicio', 'peluquero', 'dia', 'hora', 'usuario_id', 'precio'];
        foreach ($requiredFields as $field) {
            if (empty($data[$field])) {
                return new JsonResponse(['status' => "El campo '$field' es obligatorio"], 400);
            }
        }
    
        // Validar fecha
        $dia = \DateTime::createFromFormat('Y-m-d', $data['dia']);
        if (!$dia) {
            return new JsonResponse(['status' => 'Formato de fecha inválido (Y-m-d)'], 400);
        }
    
        // Validar hora
        $hora = \DateTime::createFromFormat('H:i', $data['hora']);
        if (!$hora) {
            return new JsonResponse(['status' => 'Formato de hora inválido (H:i)'], 400);
        }
    
        // Validar usuario
        $usuario = $entityManager->getRepository(Usuarios::class)->find($data['usuario_id']);
        if (!$usuario) {
            return new JsonResponse(['status' => 'Usuario no encontrado'], 404);
        }
    
        $reserva = new Reservas();
        $reserva->setServicio($data['servicio']);
        $reserva->setPeluquero($data['peluquero']);
        $reserva->setDia($dia);
        $reserva->setHora($hora);
        $reserva->setUsuario($usuario);
        $reserva->setPrecio($data['precio']);
    
        $entityManager->persist($reserva);
        $entityManager->flush();

        try {
            $email = (new Email())
                ->from('marcosvalleu@gmail.com')
                ->to($usuario->getEmail())
                ->subject('Confirmación de Reserva - HairBooking')
                ->html(
                    '<h2>¡Tu reserva ha sido confirmada!</h2>' .
                    '<p>Hola ' . htmlspecialchars($usuario->getNombre()) . ',</p>' .
                    '<p>Tu reserva ha sido confirmada con los siguientes detalles:</p>' .
                    '<ul>' .
                    '<li><strong>Servicio:</strong> ' . htmlspecialchars($reserva->getServicio()) . '</li>' .
                    '<li><strong>Peluquero:</strong> ' . htmlspecialchars($reserva->getPeluquero()) . '</li>' .
                    '<li><strong>Fecha:</strong> ' . $reserva->getDia()->format('d/m/Y') . '</li>' .
                    '<li><strong>Hora:</strong> ' . $reserva->getHora()->format('H:i') . '</li>' .
                    '<li><strong>Precio:</strong> ' . number_format($reserva->getPrecio(), 2) . '€</li>' .
                    '</ul>' .
                    '<p>¡Gracias por confiar en nosotros!</p>' .
                    '<p>Saludos,<br>El equipo de HairBooking</p>'
                );

            $mailer->send($email);
            $this->logger->info('Email de confirmación enviado correctamente a ' . $usuario->getEmail());
        } catch (\Exception $e) {
            $this->logger->error('Error al enviar el email de confirmación: ' . $e->getMessage());
        }
    

        return new JsonResponse(['status' => 'Reserva creada', 'reserva_id' => $reserva->getId()], 201);
    }
    
        #[Route('/usuario/{id}', name: 'app_reservas_by_usuario', methods: ['GET'])]
        public function reservasPorUsuario(int $id, ReservasRepository $reservasRepository): JsonResponse
        {
            $reservas = $reservasRepository->findBy(['usuario' => $id]);
            $data = [];

            foreach ($reservas as $reserva) {
                $valoracion = $reserva->getValoracions()->first() ? $reserva->getValoracions()->first()->getId() : null;
                $comentario = $reserva->getValoracions()->first() ? $reserva->getValoracions()->first()->getComentario() : null;
                $servicioRating = $reserva->getValoracions()->first() ? $reserva->getValoracions()->first()->getServicioRating() : null;
            $peluqueroRating = $reserva->getValoracions()->first() ? $reserva->getValoracions()->first()->getPeluqueroRating() : null;

                $data[] = [
                    'id' => $reserva->getId(),
                    'servicio' => $reserva->getServicio(),
                    'peluquero' => $reserva->getPeluquero(),
                    'precio'=> $reserva->getPrecio(),
                    'dia' => $reserva->getDia()->format('Y-m-d'),
                    'hora' => $reserva->getHora()->format('H:i'),
                    'usuario_id' => $reserva->getUsuario() ? $reserva->getUsuario()->getId() : null,
                    'valoracion' => $valoracion,
                    'valoracion_comentario' => $comentario,
                    'valoracion_servicio' => $servicioRating,
                    'valoracion_peluquero' => $peluqueroRating
                    

                ];
            }

            return new JsonResponse($data);
        }


   #[Route('/{id<\d+>}', name: 'app_reservas_show', methods: ['GET'])]
public function show(int $id, ReservasRepository $reservasRepository): JsonResponse{
    $reserva = $reservasRepository->find($id);
    if (!$reserva) {
        return new JsonResponse(['error' => 'Reserva no encontrada'], 404);
    }

        $data = [
            'id' => $reserva->getId(),
            'servicio' => $reserva->getServicio(),
            'peluquero' => $reserva->getPeluquero(),
            'precio' => $reserva->getPrecio(),
            'dia' => $reserva->getDia()->format('Y-m-d'),
            'hora' => $reserva->getHora()->format('H:i'),
            'usuario_id' => $reserva->getUsuario() ? $reserva->getUsuario()->getId() : null
        ];
        
        return new JsonResponse($data);
    }

    #[Route('/{id}/edit', name: 'app_reservas_edit', methods: ['GET', 'PUT'])]
public function edit(Request $request, int $id, ReservasRepository $reservasRepository, EntityManagerInterface $entityManager): JsonResponse
{
    $reserva = $reservasRepository->find($id);
    if (!$reserva) {
        return new JsonResponse(['error' => 'Reserva no encontrada'], 404);
    }
        // If it's a GET request, return the reservation data
        if ($request->getMethod() === 'GET') {
            $data = [
                'id' => $reserva->getId(),
                'servicio' => $reserva->getServicio(),
                'peluquero' => $reserva->getPeluquero(),
                'precio' => $reserva->getPrecio(),
                'dia' => $reserva->getDia()->format('Y-m-d'),
                'hora' => $reserva->getHora()->format('H:i'),
                'usuario_id' => $reserva->getUsuario() ? $reserva->getUsuario()->getId() : null
            ];
            
            return new JsonResponse($data);
        }
        
        // For PUT requests, update the reservation
        $data = json_decode($request->getContent(), true);
        
        if ($data === null) {
            return new JsonResponse(['status' => 'JSON inválido'], 400);
        }
        
        if (isset($data['servicio'])) {
            $reserva->setServicio($data['servicio']);
        }
        
        if (isset($data['peluquero'])) {
            $reserva->setPeluquero($data['peluquero']);
        }

        if (isset($data['precio'])) {
            $reserva->setPrecio($data['precio']);
        } 
        if (isset($data['dia'])) {
            try {
                $dia = new \DateTime($data['dia']);
                $reserva->setDia($dia);
            } catch (\Exception $e) {
                return new JsonResponse(['status' => 'Formato de fecha inválido'], 400);
            }
        }
        
        if (isset($data['hora'])) {
            try {
                $hora = \DateTime::createFromFormat('H:i', $data['hora']);
                if ($hora === false) {
                    return new JsonResponse(['status' => 'Formato de hora inválido'], 400);
                }
                $reserva->setHora($hora);
            } catch (\Exception $e) {
                return new JsonResponse(['status' => 'Formato de hora inválido'], 400);
            }
        }
        
        if (isset($data['usuario_id'])) {
            $usuario = $entityManager->getRepository(Usuarios::class)->find($data['usuario_id']);
            if (!$usuario) {
                return new JsonResponse(['status' => 'Usuario no encontrado'], 404);
            }
            $reserva->setUsuario($usuario);
        }
        
        $entityManager->flush();
        
        return new JsonResponse(['status' => 'Reserva actualizada']);
    }

   #[Route('/delete/{id}', name: 'app_reservas_delete', methods: ['GET','DELETE'])]
public function delete(int $id, ReservasRepository $reservasRepository, EntityManagerInterface $entityManager, MailerInterface $mailer): JsonResponse
{
    $reserva = $reservasRepository->find($id);
    if (!$reserva) {
        return new JsonResponse(['status' => 'Reserva no encontrada'], 404);
    }

        try {
            // Obtener el usuario de la reserva antes de eliminarla
            $usuario = $reserva->getUsuario();
            
            // Crear una nueva instancia de ReservasAnuladas
            $reservaAnulada = new ReservasAnuladas();
            $reservaAnulada->setServicio($reserva->getServicio());
            $reservaAnulada->setPeluquero($reserva->getPeluquero());
            $reservaAnulada->setPrecio($reserva->getPrecio());
            $reservaAnulada->setDia($reserva->getDia());
            $reservaAnulada->setHora($reserva->getHora());
            $reservaAnulada->setUsuarios($usuario);
            $reservaAnulada->setFechaAnulada(new \DateTime());

            // Primero persistimos la reserva anulada
            $entityManager->persist($reservaAnulada);
            $entityManager->flush();

            // Luego eliminamos la reserva original
            $entityManager->remove($reserva);
            $entityManager->flush();

            // Enviar email solo si hay un usuario asociado
            if ($usuario && $usuario->getEmail()) {
                try {
                    $email = (new Email())
                        ->from('marcosvalleu@gmail.com')
                        ->to($usuario->getEmail())
                        ->subject('Reserva Anulada - HairBooking')
                        ->html(
                            '<h2>¡Tu reserva ha sido anulada!</h2>' .
                            '<p>Hola ' . htmlspecialchars($usuario->getNombre()) . ',</p>' .
                            '<p>Tu reserva ha sido anulada con los siguientes detalles:</p>' .
                            '<ul>' .
                            '<li><strong>Servicio:</strong> ' . htmlspecialchars($reserva->getServicio()) . '</li>' .
                            '<li><strong>Peluquero:</strong> ' . htmlspecialchars($reserva->getPeluquero()) . '</li>' .
                            '<li><strong>Fecha:</strong> ' . $reserva->getDia()->format('d/m/Y') . '</li>' .
                            '<li><strong>Hora:</strong> ' . $reserva->getHora()->format('H:i') . '</li>' .
                            '<li><strong>Precio:</strong> ' . number_format($reserva->getPrecio(), 2) . '€</li>' .
                            '</ul>' .
                            '<p>Saludos,<br>El equipo de HairBooking</p>'
                        );

                    $mailer->send($email);
                    $this->logger->info('Email de anulación enviado correctamente a ' . $usuario->getEmail());
                } catch (\Exception $e) {
                    $this->logger->error('Error al enviar el email de anulación: ' . $e->getMessage());
                    // No lanzamos la excepción aquí para que no afecte al proceso de eliminación
                }
            }
            
            return new JsonResponse([
                'status' => 'success',
                'message' => 'Reserva anulada y registrada correctamente',
                'reserva_id' => $reserva->getId()
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Error al anular la reserva: ' . $e->getMessage());
            return new JsonResponse([
                'status' => 'error',
                'message' => 'Error al anular la reserva: ' . $e->getMessage()
            ], 500);
        }
    }

  #[Route('/eliminar/{id}', name: 'app_reservas_eliminar', methods: ['GET','DELETE'])]
public function eliminar(int $id, ReservasRepository $reservasRepository, EntityManagerInterface $entityManager): JsonResponse
{
    $reserva = $reservasRepository->find($id);
    if (!$reserva) {
        return new JsonResponse(['status' => 'Reserva no encontrada'], 404);
    }
        try {
            $entityManager->remove($reserva);
            $entityManager->flush();
            return new JsonResponse(['status' => 'Reserva eliminada correctamente']);
        } catch (\Exception $e) {
            $this->logger->error('Error al eliminar la reserva: ' . $e->getMessage());
            return new JsonResponse(['status' => 'Error al eliminar la reserva'], 500);
        }
    }


    #[Route('/admin/new', name: 'app_reservas_admin_new', methods: ['GET', 'POST'])]
    public function adminNew(Request $request, EntityManagerInterface $entityManager, MailerInterface $mailer): Response
    {
        // Si es una petición GET, devolvemos la lista de usuarios disponibles
        if ($request->getMethod() === 'GET') {
            $usuarios = $entityManager->getRepository(Usuarios::class)->findAll();
            $usuariosData = [];
            
            foreach ($usuarios as $usuario) {
                $usuariosData[] = [
                    'id' => $usuario->getId(),
                    'nombre' => $usuario->getNombre(),
                    'apellidos' => $usuario->getApellidos(),
                    'email' => $usuario->getEmail(),
                    'telefono' => $usuario->getTelefono()
                ];
            }
            
            return new JsonResponse([
                'status' => 'success',
                'usuarios' => $usuariosData
            ]);
        }
        
        // Si es una petición POST, procesamos la creación de la reserva
        $data = json_decode($request->getContent(), true);
    
        if ($data === null) {
            return new JsonResponse(['status' => 'JSON inválido'], 400);
        }
    
        $requiredFields = ['servicio', 'peluquero', 'dia', 'hora', 'usuario_id', 'precio'];
        foreach ($requiredFields as $field) {
            if (empty($data[$field])) {
                return new JsonResponse(['status' => "El campo '$field' es obligatorio"], 400);
            }
        }
    
        // Validar fecha
        $dia = \DateTime::createFromFormat('Y-m-d', $data['dia']);
        if (!$dia) {
            return new JsonResponse(['status' => 'Formato de fecha inválido (Y-m-d)'], 400);
        }
    
        // Validar hora
        $hora = \DateTime::createFromFormat('H:i', $data['hora']);
        if (!$hora) {
            return new JsonResponse(['status' => 'Formato de hora inválido (H:i)'], 400);
        }
    
        // Validar usuario y asegurarnos de que existe
        $usuario = $entityManager->getRepository(Usuarios::class)->find($data['usuario_id']);
        if (!$usuario) {
            return new JsonResponse(['status' => 'Usuario no encontrado'], 404);
        }

        // Verificar que el usuario no es un administrador
        if ($usuario->getRol() === 'ROLE_ADMIN') {
            return new JsonResponse(['status' => 'No se pueden crear reservas para usuarios administradores'], 400);
        }
    
        $reserva = new Reservas();
        $reserva->setServicio($data['servicio']);
        $reserva->setPeluquero($data['peluquero']);
        $reserva->setDia($dia);
        $reserva->setHora($hora);
        $reserva->setUsuario($usuario); // Asignamos el usuario seleccionado
        $reserva->setPrecio($data['precio']);
    
        $entityManager->persist($reserva);
        $entityManager->flush();

        // Enviar email de confirmación
        try {
            $email = (new Email())
                ->from('marcosvalleu@gmail.com')
                ->to($usuario->getEmail())
                ->subject('Nueva Reserva Creada - HairBooking')
                ->html(
                    '<h2>¡Se ha creado una nueva reserva para ti!</h2>' .
                    '<p>Hola ' . htmlspecialchars($usuario->getNombre()) . ',</p>' .
                    '<p>Se ha creado una nueva reserva con los siguientes detalles:</p>' .
                    '<ul>' .
                    '<li><strong>Servicio:</strong> ' . htmlspecialchars($reserva->getServicio()) . '</li>' .
                    '<li><strong>Peluquero:</strong> ' . htmlspecialchars($reserva->getPeluquero()) . '</li>' .
                    '<li><strong>Fecha:</strong> ' . $reserva->getDia()->format('d/m/Y') . '</li>' .
                    '<li><strong>Hora:</strong> ' . $reserva->getHora()->format('H:i') . '</li>' .
                    '<li><strong>Precio:</strong> ' . number_format($reserva->getPrecio(), 2) . '€</li>' .
                    '</ul>' .
                    '<p>¡Gracias por confiar en nosotros!</p>' .
                    '<p>Saludos,<br>El equipo de HairBooking.</p>'
                );

            $mailer->send($email);
            $this->logger->info('Email de confirmación enviado correctamente a ' . $usuario->getEmail());
        } catch (\Exception $e) {
            $this->logger->error('Error al enviar el email de confirmación: ' . $e->getMessage());
        }
    
        return new JsonResponse([
            'status' => 'Reserva creada por administrador',
            'reserva_id' => $reserva->getId(),
            'usuario' => [
                'id' => $usuario->getId(),
                'nombre' => $usuario->getNombre(),
                'email' => $usuario->getEmail()
            ]
        ], 201);
    }

  #[Route('/filter', name: 'app_reservas_filter', methods: ['GET'])]
public function filter(Request $request, ReservasRepository $reservasRepository): JsonResponse
{
    $tipo = $request->query->get('tipo');
    $timezone = new \DateTimeZone('Europe/Madrid');
    $now = new \DateTime('now', $timezone);

    $this->logger->info('Fecha y hora actual: ' . $now->format('Y-m-d H:i:s'));

    if (!in_array($tipo, ['activas', 'expiradas'])) {
        return new JsonResponse(['error' => 'Tipo de filtro no válido'], 400);
    }

    $reservas = $reservasRepository->findAll();
    $data = [];

    foreach ($reservas as $reserva) {
        $fecha = $reserva->getDia();
        $hora = $reserva->getHora();

        if (!$fecha || !$hora) {
            continue;
        }

        // Crear DateTime para la reserva
        $fechaHoraReserva = new \DateTime($fecha->format('Y-m-d') . ' ' . $hora->format('H:i:s'), $timezone);
        
        $this->logger->info('Reserva ID ' . $reserva->getId() . ': ' . $fechaHoraReserva->format('Y-m-d H:i:s'));
        
        // Comparación simple
        $esExpirada = $fechaHoraReserva <= $now;
        
        $this->logger->info('Reserva ID ' . $reserva->getId() . ' es expirada: ' . ($esExpirada ? 'Sí' : 'No'));

        if (
            ($tipo === 'activas' && !$esExpirada) ||
            ($tipo === 'expiradas' && $esExpirada)
        ) {
            $valoracion = $reserva->getValoracions()->first() ?: null;
            $data[] = [
                'id' => $reserva->getId(),
                'servicio' => $reserva->getServicio(),
                'peluquero' => $reserva->getPeluquero(),
                'precio' => $reserva->getPrecio(),
                'dia' => $fecha->format('Y-m-d'),
                'hora' => $hora->format('H:i'),
                'usuario_id' => $reserva->getUsuario()?->getId(),
                'valoracion' => $valoracion?->getId(),
                'valoracion_comentario' => $valoracion?->getComentario(),
                'valoracion_servicio' => $valoracion?->getServicioRating(),
                'valoracion_peluquero' => $valoracion?->getPeluqueroRating()
            ];
        }
    }

    $this->logger->info('Total de reservas encontradas para ' . $tipo . ': ' . count($data));
    
    return new JsonResponse($data);
}
}