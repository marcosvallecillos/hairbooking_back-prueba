<?php

namespace App\Controller;

use App\Entity\Compra;
use App\Entity\CompraProducto;
use App\Entity\Usuarios;
use App\Entity\Productos;
use App\Repository\UsuariosRepository;
use App\Repository\ProductosRepository;
use App\Repository\CompraRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Psr\Log\LoggerInterface;

#[Route('/api/compras')]
class ComprasController extends AbstractController
{
    public function __construct(
        private readonly LoggerInterface $logger
    ) {}

    #[Route(name: 'app_compras_index', methods: ['GET'])]
    public function index(CompraRepository $comprasRepository): JsonResponse
    {
        $compras = $comprasRepository->findAll();
        $data = [];
        
        foreach ($compras as $compra) {
            $data[] = [
                'id' => $compra->getId(),
                'nombre' => $compra->getName(),
                'imagen' => $compra->getImage(),
                'cantidad' => $compra->getCantidad(),
                'precio' => $compra->getPrice(),
                'descuento'=> $compra->getDescuento(),
                'fecha' => $compra->getFecha()->format('Y-m-d'),
                'detalles' => array_map(function (CompraProducto $detalle) {
                    return [
                        'productoId' => $detalle->getProducto()->getId(),
                        'nombre' => $detalle->getProducto()->getName(),
                        'cantidad' => $detalle->getCantidad(),
                        'precioUnitario' => $detalle->getPrecio(),
                        'total' => $detalle->getTotal()
                    ];
                }, $compra->getDetalles()->toArray()),
                'usuario' => $compra->getUsuario() ? [
                    'id' => $compra->getUsuario()->getId(),
                    'nombre' => $compra->getUsuario()->getNombre(),
                    'apellidos' => $compra->getUsuario()->getApellidos(),
                    'email' => $compra->getUsuario()->getEmail(),
                    'telefono' => $compra->getUsuario()->getTelefono(),
                ] : null,
            ];
        }
        
        return new JsonResponse($data);
    }

    #[Route('/usuarios/{usuarioId}/compras', name: 'realizar_compra', methods: ['GET','POST'])]
    public function realizarCompra(
        int $usuarioId,
        Request $request,
        EntityManagerInterface $em,
        UsuariosRepository $usuarioRepo,
        ProductosRepository $productoRepo,
        MailerInterface $mailer
    ): JsonResponse {
        try {
            $usuario = $usuarioRepo->find($usuarioId);
            if (!$usuario) {
                return $this->json(['error' => 'Usuario no encontrado'], 404);
            }
        
            $data = json_decode($request->getContent(), true);
            if ($data === null) {
                return $this->json(['error' => 'JSON inválido'], 400);
            }
        
            $items = $data['productos'] ?? [];
            if (empty($items)) {
                return $this->json(['error' => 'No se proporcionaron productos'], 400);
            }
        
            $compra = new Compra();
            $compra->setUsuario($usuario);
            $compra->setFecha(new \DateTime());
            $compra->setImage("default.jpg");
            $compra->setCantidad(0);
            $compra->setPrice(0);
            $compra->setDescuento(0);
        
            $totalCantidad = 0;
            $totalPrecio = 0;
            $primerProducto = null;
            $detallesCompra = [];
        
            foreach ($items as $item) {
                $producto = $productoRepo->find($item['productoId']);
                if (!$producto) {
                    return $this->json(['error' => "Producto ID {$item['productoId']} no encontrado"], 404);
                }
        
                $cantidad = (int) $item['cantidad'];
                if ($cantidad <= 0) {
                    return $this->json(['error' => "Cantidad inválida para producto ID {$item['productoId']}"], 400);
                }
        
                $precioUnidad = $producto->getPrice();
                $totalCantidad += $cantidad;
                $totalPrecio += ($precioUnidad * $cantidad);
        
                $detalle = new CompraProducto();
                $detalle->setProducto($producto);
                $detalle->setCantidad($cantidad);
                $detalle->setPrecio($precioUnidad);
                $compra->addDetalle($detalle);
                $detalle->setCompra($compra);
                
                $compra->addProducto($producto);
                
                if ($primerProducto === null) {
                    $primerProducto = $producto;
                    $compra->setName($producto->getName());
                }

                // Guardar detalles para el email
                $detallesCompra[] = [
                    'nombre' => $producto->getName(),
                    'cantidad' => $cantidad,
                    'precioUnitario' => $precioUnidad,
                    'total' => $precioUnidad * $cantidad
                ];
            }
        
            $compra->setCantidad($totalCantidad);
            $compra->setPrice($totalPrecio);
        
            $em->persist($compra);
            $em->flush();

            // Enviar email de confirmación
            try {
                $htmlContent = '<h2>¡Gracias por tu compra!</h2>';
                $htmlContent .= '<p>Hola ' . htmlspecialchars($usuario->getNombre()) . ',</p>';
                $htmlContent .= '<p>Tu compra ha sido registrada con éxito. Aquí están los detalles:</p>';
                $htmlContent .= '<table style="width: 100%; border-collapse: collapse;">';
                $htmlContent .= '<tr style="background-color: #f8f9fa;"><th style="padding: 8px; border: 1px solid #dee2e6;">Producto</th><th style="padding: 8px; border: 1px solid #dee2e6;">Cantidad</th><th style="padding: 8px; border: 1px solid #dee2e6;">Precio Unitario</th><th style="padding: 8px; border: 1px solid #dee2e6;">Total</th></tr>';
                
                foreach ($detallesCompra as $detalle) {
                    $htmlContent .= '<tr>';
                    $htmlContent .= '<td style="padding: 8px; border: 1px solid #dee2e6;">' . htmlspecialchars($detalle['nombre']) . '</td>';
                    $htmlContent .= '<td style="padding: 8px; border: 1px solid #dee2e6;">' . $detalle['cantidad'] . '</td>';
                    $htmlContent .= '<td style="padding: 8px; border: 1px solid #dee2e6;">' . number_format($detalle['precioUnitario'], 2) . '€</td>';
                    $htmlContent .= '<td style="padding: 8px; border: 1px solid #dee2e6;">' . number_format($detalle['total'], 2) . '€</td>';
                    $htmlContent .= '</tr>';
                }
                
                $htmlContent .= '<tr style="background-color: #f8f9fa;">';
                $htmlContent .= '<td colspan="3" style="padding: 8px; border: 1px solid #dee2e6; text-align: right;"><strong>Total:</strong></td>';
                $htmlContent .= '<td style="padding: 8px; border: 1px solid #dee2e6;"><strong>' . number_format($totalPrecio, 2) . '€</strong></td>';
                $htmlContent .= '</tr>';
                $htmlContent .= '</table>';
                
                $htmlContent .= '<p>Fecha de compra: ' . $compra->getFecha()->format('d/m/Y H:i') . '</p>';
                $htmlContent .= '<p>¡Gracias por confiar en nosotros!</p>';
                $htmlContent .= '<p>Saludos,<br>El equipo de HairBooking</p>';

                $email = (new Email())
                    ->from('marcosvalleu@gmail.com')
                    ->to($usuario->getEmail())
                    ->subject('Confirmación de Compra - HairBooking')
                    ->html($htmlContent);

                $mailer->send($email);
                $this->logger->info('Email de confirmación de compra enviado correctamente a ' . $usuario->getEmail());
            } catch (\Exception $e) {
                $this->logger->error('Error al enviar el email de confirmación de compra: ' . $e->getMessage());
            }
        
            return $this->json([
                'mensaje' => 'Compra registrada',
                'compraId' => $compra->getId(),
                'total' => $totalPrecio,
                'cantidadTotal' => $totalCantidad
            ], 201);
        } catch (\Exception $e) {
            return $this->json(['error' => 'Error interno: ' . $e->getMessage()], 500);
        }
    }

    #[Route('/usuarios/{usuarioId}/historial', name: 'historial_compras', methods: ['GET'])]
    public function getHistorialCompras(
        int $usuarioId,
        EntityManagerInterface $em,
        UsuariosRepository $usuarioRepo
    ): JsonResponse {
        try {
            $usuario = $usuarioRepo->find($usuarioId);
            if (!$usuario) {
                return $this->json(['error' => 'Usuario no encontrado'], 404);
            }

            $compras = $em->getRepository(Compra::class)->findBy(['usuario' => $usuario], ['fecha' => 'DESC']);
            
            $data = [];
            foreach ($compras as $compra) {
                $detalles = [];
                foreach ($compra->getDetalles() as $detalle) {
                    $producto = $detalle->getProducto();
                    $detalles[] = [
                        'productoId' => $producto->getId(),
                        'nombre' => $producto->getName(),
                        'cantidad' => $detalle->getCantidad(),
                        'precioUnitario' => $detalle->getPrecio(),
                        'total' => $detalle->getTotal()
                    ];
                }

                $data[] = [
                    'id' => $compra->getId(),
                    'nombre' => $compra->getName(),
                    'fecha' => $compra->getFecha()->format('Y-m-d'),
                    'total' => $compra->getPrice(),
                    'descuento' => $compra->getDescuento(),
                    'cantidadTotal' => $compra->getCantidad(),
                    'detalles' => $detalles
                ];
            }

            return $this->json([
                'status' => 'success',
                'usuario_id' => $usuarioId,
                'compras' => $data
            ]);
        } catch (\Exception $e) {
            return $this->json(['error' => 'Error interno: ' . $e->getMessage()], 500);
        }
    }

    #[Route('/carrito/cantidad/{productoId}', name: 'actualizar_cantidad', methods: ['POST'])]
    public function actualizarCantidad(
        int $productoId,
        Request $request,
        EntityManagerInterface $em,
        ProductosRepository $productoRepo
    ): JsonResponse {
        try {
            $data = json_decode($request->getContent(), true);
            if (!isset($data['usuario_id']) || !isset($data['cantidad'])) {
                return $this->json(['error' => 'Datos incompletos'], 400);
            }

            $producto = $productoRepo->find($productoId);
            if (!$producto) {
                return $this->json(['error' => 'Producto no encontrado'], 404);
            }

            // Aquí puedes agregar la lógica para actualizar la cantidad en tu base de datos
            // Por ejemplo, si tienes una tabla de carrito:
            $carrito = $em->getRepository(Compra::class)->findOneBy([
                'usuario' => $data['usuario_id'],
                'producto' => $productoId
            ]);

            if ($carrito) {
                $carrito->setCantidad($data['cantidad']);
                $em->flush();
                return $this->json([
                    'status' => 'success',
                    'message' => 'Cantidad actualizada correctamente'
                ]);
            }

            return $this->json(['error' => 'Producto no encontrado en el carrito'], 404);
        } catch (\Exception $e) {
            return $this->json(['error' => 'Error interno: ' . $e->getMessage()], 500);
        }
    }

    #[Route('/filter/date', name: 'app_compras_filter_date', methods: ['GET'])]
    public function filterByDate(Request $request, EntityManagerInterface $em): JsonResponse
    {
        $fecha = $request->query->get('fecha');

        if ($fecha === null) {
            return new JsonResponse([
                'error' => 'Debe proporcionar una fecha (fecha)'
            ], 400);
        }

        try {
            // Convertir la fecha del formato d-m-Y a DateTime
            $fechaDateTime = \DateTime::createFromFormat('d-m-Y', $fecha);
            if (!$fechaDateTime) {
                // Intentar convertir si viene en formato Y-m-d
                $fechaDateTime = \DateTime::createFromFormat('Y-m-d', $fecha);
                if (!$fechaDateTime) {
                    return new JsonResponse([
                        'error' => 'Formato de fecha inválido. Use el formato dd-mm-yyyy'
                    ], 400);
                }
            }

            // Construir la consulta
            $qb = $em->createQueryBuilder();
            $qb->select('c')
               ->from(Compra::class, 'c')
               ->leftJoin('c.usuario', 'u')
               ->leftJoin('c.detalles', 'd')
               ->leftJoin('d.producto', 'p');

            // Establecer el rango de tiempo para el día completo
            $fechaInicio = clone $fechaDateTime;
            $fechaInicio->setTime(0, 0, 0);
            $fechaFin = clone $fechaDateTime;
            $fechaFin->setTime(23, 59, 59);

            // Filtrar por la fecha específica
            $qb->andWhere('c.fecha >= :fechaInicio')
               ->andWhere('c.fecha <= :fechaFin')
               ->setParameter('fechaInicio', $fechaInicio)
               ->setParameter('fechaFin', $fechaFin);

            // Ordenar por fecha descendente (más reciente primero)
            $qb->orderBy('c.fecha', 'DESC');

            $compras = $qb->getQuery()->getResult();
            
            $data = [];
            foreach ($compras as $compra) {
                $detalles = [];
                foreach ($compra->getDetalles() as $detalle) {
                    $producto = $detalle->getProducto();
                    $detalles[] = [
                        'productoId' => $producto->getId(),
                        'nombre' => $producto->getName(),
                        'cantidad' => $detalle->getCantidad(),
                        'precioUnitario' => $detalle->getPrecio(),
                        'total' => $detalle->getTotal()
                    ];
                }

                $data[] = [
                    'id' => $compra->getId(),
                    'nombre' => $compra->getName(),
                    'fecha' => $compra->getFechaFormateada(),
                    'hora' => $compra->getFecha()->format('H:i:s'),
                    'total' => $compra->getPrice(),
                    'descuento' => $compra->getDescuento(),
                    'cantidadTotal' => $compra->getCantidad(),
                    'detalles' => $detalles,
                    'usuario' => $compra->getUsuario() ? [
                        'id' => $compra->getUsuario()->getId(),
                        'nombre' => $compra->getUsuario()->getNombre(),
                        'apellidos' => $compra->getUsuario()->getApellidos(),
                        'email' => $compra->getUsuario()->getEmail(),
                        'telefono' => $compra->getUsuario()->getTelefono(),
                    ] : null,
                ];
            }

            return new JsonResponse([
                'status' => 'success',
                'total' => count($data),
                'compras' => $data
            ]);
        } catch (\Exception $e) {
            return new JsonResponse([
                'error' => 'Error al procesar la fecha: ' . $e->getMessage()
            ], 400);
        }
    }

    #[Route('/delete/{id}', name: 'app_compras_delete', methods: ['DELETE'])]
    public function delete(Compra $compra, EntityManagerInterface $entityManager): JsonResponse
    {
        $entityManager->remove($compra);
        $entityManager->flush();

        return new JsonResponse(['message' => 'Usuario eliminado con éxito'], 200);
    }

    #[Route('/carrito/{usuarioId}', name: 'ver_carrito', methods: ['GET'])]
    public function verCarrito(
        int $usuarioId,
        EntityManagerInterface $em,
        UsuariosRepository $usuarioRepo
    ): JsonResponse {
        try {
            $usuario = $usuarioRepo->find($usuarioId);
            if (!$usuario) {
                return $this->json(['error' => 'Usuario no encontrado'], 404);
            }

            // Obtener los items del carrito para el usuario
            $carritoItems = $em->getRepository(Compra::class)->findBy(['usuario' => $usuario]);
            
            if (empty($carritoItems)) {
                return $this->json([
                    'status' => 'success',
                    'message' => 'El carrito está vacío',
                    'items' => []
                ]);
            }

            $items = [];
            $totalCarrito = 0;

            foreach ($carritoItems as $item) {
                $productos = $item->getProductos();
                foreach ($productos as $producto) {
                    $detalle = $item->getDetalles()->filter(function($d) use ($producto) {
                        return $d->getProducto()->getId() === $producto->getId();
                    })->first();

                    if ($detalle) {
                        $subtotal = $producto->getPrice() * $detalle->getCantidad();
                        $totalCarrito += $subtotal;

                        $items[] = [
                            'id' => $item->getId(),
                            'producto' => [
                                'id' => $producto->getId(),
                                'nombre' => $producto->getName(),
                                'precio' => $producto->getPrice(),
                                'imagen' => $producto->getImage(),
                            ],
                            'cantidad' => $detalle->getCantidad(),
                            'subtotal' => $subtotal
                        ];
                    }
                }
            }

            return $this->json([
                'status' => 'success',
                'usuario_id' => $usuarioId,
                'total_carrito' => $totalCarrito,
                'cantidad_items' => count($items),
                'items' => $items
            ]);

        } catch (\Exception $e) {
            $this->logger->error('Error al obtener el carrito: ' . $e->getMessage());
            return $this->json(['error' => 'Error interno al obtener el carrito: ' . $e->getMessage()], 500);
        }
    }
}

