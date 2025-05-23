<?php

namespace App\Controller;

use App\Entity\Productos;
use App\Form\ProductosType;
use App\Repository\ProductosRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use App\Entity\Usuarios;
use App\Entity\UsuarioProductoFavorito;

#[Route('/api/productos')]
final class ProductosController extends AbstractController
{
    #[Route(name: 'app_productos_index', methods: ['GET'])]
    public function index(ProductosRepository $productosRepository): Response
    {
        return $this->render('productos/index.html.twig', [
            'productos' => $productosRepository->findAll(),
        ]);
    }

    

    #[Route('/list', methods: ['GET'], name: 'list')]
    public function list(EntityManagerInterface $em): JsonResponse{
        $productos = $em->getRepository(Productos::class)->findAll();
        $data = [];
        foreach($productos as $producto){
            $data[] = [
                'id' => $producto->getId(),
                'name' => $producto->getName(),
                'price' => $producto->getPrice(),
                'image' => $producto->getImage(),
                'cantidad'=> $producto->getCantidad(),
                'favorite'=> $producto->isFavorite(),
                'cart'=> $producto->isInsideCart(),
                'date'=> $producto->getFecha(),
                'compras'=> $producto->getCompras(),
                'categorias'=> $producto->getCategoria(),
                'subcategorias' => $producto->getSubCategoria(),

            ];
        }
        return new JsonResponse($data);
    }
   #[Route('/carrito/cantidad/{id}', name: 'actualizar_cantidad_carrito', methods: ['GET','POST'])]
    public function actualizarCantidadCarrito(int $id, Request $request, EntityManagerInterface $em): JsonResponse
    {
        try {
            // Log de la petición recibida
            $content = $request->getContent();
            error_log('Contenido de la petición: ' . $content);
            
            $producto = $em->getRepository(Productos::class)->find($id);
            if (!$producto) {
                return new JsonResponse(['error' => 'Producto no encontrado'], 404);
            }

            $data = json_decode($content, true);
            error_log('Datos decodificados: ' . print_r($data, true));

            if (!isset($data['usuario_id']) || !isset($data['cantidad'])) {
                return new JsonResponse([
                    'error' => 'Datos incompletos',
                    'message' => 'Se requiere usuario_id y cantidad',
                    'received_data' => $data
                ], 400);
            }

            $usuario = $em->getRepository(Usuarios::class)->find($data['usuario_id']);
            if (!$usuario) {
                return new JsonResponse(['error' => 'Usuario no encontrado'], 404);
            }

            $cartRepo = $em->getRepository(UsuarioProductoFavorito::class);
            $cart = $cartRepo->findOneBy(['usuario' => $usuario, 'producto' => $producto]);

            if (!$cart) {
                return new JsonResponse([
                    'error' => 'Producto no encontrado en el carrito',
                    'message' => 'El producto no está en el carrito del usuario'
                ], 400);
            }

            if (!$cart->insideCart()) {
                return new JsonResponse([
                    'error' => 'Producto no está en el carrito',
                    'message' => 'El producto no está marcado como en el carrito'
                ], 400);
            }

            $cart->setCantidad($data['cantidad']);
            $em->flush();

            return new JsonResponse([
                'status' => 'success',
                'message' => 'Cantidad actualizada correctamente',
                'producto' => [
                    'id' => $producto->getId(),
                    'name' => $producto->getName(),
                    'cantidad' => $cart->getCantidad()
                ]
            ]);
        } catch (\Exception $e) {
            error_log('Error en actualizarCantidadCarrito: ' . $e->getMessage());
            return new JsonResponse([
                'status' => 'error',
                'message' => 'Error al actualizar la cantidad',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    #[Route('/carrito/{id}', name: 'agregar_al_carrito', methods: ['GET','POST'])]
    public function agregarAlCarrito(int $id,Request $request,EntityManagerInterface $em): JsonResponse {
       

        $producto = $em->getRepository(Productos::class)->find($id);
        if (!$producto) {
            return new JsonResponse(['error' => 'Producto no encontrado'], 404);
        }
    
        if ($request->isMethod('GET')) {
            return new JsonResponse([
                'status' => 'success',
                'producto' => [
                    'id' => $producto->getId(),
                    'name' => $producto->getName(),
                    'cart' => $producto->isInsideCart()
                ]
            ]);
        }
        $data = json_decode($request->getContent(), true);
        $usuario = isset($data['usuario_id']) ? $em->getRepository(Usuarios::class)->find($data['usuario_id']) : null;
        
        if (!$usuario) {
            return new JsonResponse(['error' => 'Usuario no encontrado o ID faltante'], 400);
        }
        
        $cartRepo = $em->getRepository(UsuarioProductoFavorito::class);
        $cart = $cartRepo->findOneBy(['usuario' => $usuario, 'producto' => $producto]);

        if (!$cart) {
            $cart = new UsuarioProductoFavorito();
            $cart->setUsuario($usuario);
            $cart->setProducto($producto);
            $cart->setCantidad(1);
            $cart->setInsideCart(true);
        } else {
            // Si ya está en carrito, incrementar cantidad
            $cart->setCantidad($cart->getCantidad() + 1);
            $cart->setInsideCart(true);
        }

        $em->persist($cart);
        $em->flush();

        return new JsonResponse([
            'message' => 'Producto agregado al carrito correctamente',
            'producto' => $producto->getName(),
            'cantidad' => $cart->getCantidad()
        ]);
    }
    #[Route('/carrito/usuario/{id}', name: 'get_carrito_usuario', methods: ['GET', 'POST '])]
    public function getCarritoUsuario(int $id, EntityManagerInterface $em): JsonResponse
    {
        $usuario = $em->getRepository(Usuarios::class)->find($id);

        if (!$usuario) {
            return new JsonResponse(['error' => 'Usuario no encontrado'], 404);
        }

        // Buscar todos los favoritos del usuario
        $carrito = $em->getRepository(UsuarioProductoFavorito::class)->findBy([
            'usuario' => $usuario,
            'insideCart' => true
        ]);

        $data = [];
        foreach ($carrito as $cart) {
            $producto = $cart->getProducto();
            $data[] = [
                'id' => $producto->getId(),
                'name' => $producto->getName(),
                'price' => $producto->getPrice(),
                'image' => $producto->getImage(),
                'cantidad' => $producto->getCantidad(),
                'favorite' => $producto->isFavorite(),
                'cart' => $producto->isInsideCart(),
                'date' => $producto->getFecha()->format('Y-m-d'),
                'categoria' => $producto->getCategoria(),
                'subcategoria' => $producto->getSubcategoria()
            ];
        }

        return new JsonResponse([
            'status' => 'success',
            'usuario_id' => $usuario->getId(),
            'carrito' => $data
        ]);
    }
    
    #[Route('/favoritos/{id}', name: 'agregar_a_favoritos', methods: ['GET', 'POST'])]
public function agregarAFavoritos(int $id, Request $request, EntityManagerInterface $em): JsonResponse
{
    $producto = $em->getRepository(Productos::class)->find($id);
    if (!$producto) {
        return new JsonResponse(['error' => 'Producto no encontrado'], 404);
    }

    if ($request->isMethod('GET')) {
        return new JsonResponse([
            'status' => 'success',
            'producto' => [
                'id' => $producto->getId(),
                'name' => $producto->getName(),
                'favorite' => $producto->isFavorite()
            ]
        ]);
    }

    $data = json_decode($request->getContent(), true);
    $usuario = isset($data['usuario_id']) ? $em->getRepository(Usuarios::class)->find($data['usuario_id']) : null;

    if (!$usuario) {
        return new JsonResponse(['error' => 'Usuario no encontrado o ID faltante'], 400);
    }

    $favoritoRepo = $em->getRepository(UsuarioProductoFavorito::class);
    $favorito = $favoritoRepo->findOneBy(['usuario' => $usuario, 'producto' => $producto]);

    if (!$favorito) {
        $favorito = (new UsuarioProductoFavorito())
            ->setUsuario($usuario)
            ->setProducto($producto)
            ->setIsFavorite(true);
    } else {
        $favorito->setIsFavorite(!$favorito->isFavorite());
    }

    try {
        $em->persist($favorito);
        $em->flush();

        return new JsonResponse([
            'status' => 'success',
            'message' => $favorito->isFavorite() ? 'Producto agregado a favoritos' : 'Producto removido de favoritos',
            'producto' => [
                'id' => $producto->getId(),
                'name' => $producto->getName(),
                'favorite' => $favorito->isFavorite()
            ]
        ]);
    } catch (\Exception $e) {
        return new JsonResponse([
            'status' => 'error',
            'message' => 'Error al actualizar el estado de favorito',
            'error' => $e->getMessage()
        ], 500);
    }
}

    #[Route('/favoritos/usuario/{id}', name: 'get_favoritos_usuario', methods: ['GET'])]
    public function getFavoritosUsuario(int $id, EntityManagerInterface $em): JsonResponse
    {
        $usuario = $em->getRepository(Usuarios::class)->find($id);

        if (!$usuario) {
            return new JsonResponse(['error' => 'Usuario no encontrado'], 404);
        }

        // Buscar todos los favoritos del usuario
        $favoritos = $em->getRepository(UsuarioProductoFavorito::class)->findBy([
            'usuario' => $usuario,
            'isFavorite' => true
        ]);

        $data = [];
        foreach ($favoritos as $favorito) {
            $producto = $favorito->getProducto();
            $data[] = [
                'id' => $producto->getId(),
                'name' => $producto->getName(),
                'price' => $producto->getPrice(),
                'image' => $producto->getImage(),
                'cantidad' => $producto->getCantidad(),
                'favorite' => $favorito->isFavorite(),
                'cart' => $producto->isInsideCart(),
                'date' => $producto->getFecha()->format('Y-m-d'),
                'categoria' => $producto->getCategoria(),
                'subcategoria' => $producto->getSubcategoria()
            ];
        }

        return new JsonResponse([
            'status' => 'success',
            'usuario_id' => $usuario->getId(),
            'favoritos' => $data
        ]);
    }

    #[Route('/carrito/eliminar/{id}', name: 'eliminar_del_carrito', methods: ['GET', 'POST'])]
    public function eliminarDelCarrito(int $id, Request $request, EntityManagerInterface $em): JsonResponse
    {
        $producto = $em->getRepository(Productos::class)->find($id);
        if (!$producto) {
            return new JsonResponse(['error' => 'Producto no encontrado'], 404);
        }

        // Obtener el ID del usuario de diferentes formas posibles
        $usuarioId = null;
        
        // Intentar obtener del cuerpo de la petición
        $data = json_decode($request->getContent(), true);
        if (isset($data['usuario_id'])) {
            $usuarioId = $data['usuario_id'];
        }
        
        // Si no está en el cuerpo, intentar obtener de los parámetros de la URL
        if (!$usuarioId && $request->query->has('usuario_id')) {
            $usuarioId = $request->query->get('usuario_id');
        }

        if (!$usuarioId) {
            return new JsonResponse([
                'error' => 'ID de usuario no proporcionado',
                'message' => 'Debe proporcionar un ID de usuario válido'
            ], 400);
        }

        $usuario = $em->getRepository(Usuarios::class)->find($usuarioId);
        if (!$usuario) {
            return new JsonResponse([
                'error' => 'Usuario no encontrado',
                'message' => 'No se encontró un usuario con el ID proporcionado: ' . $usuarioId
            ], 404);
        }

        $cartRepo = $em->getRepository(UsuarioProductoFavorito::class);
        $cart = $cartRepo->findOneBy(['usuario' => $usuario, 'producto' => $producto]);

        if (!$cart || !$cart->insideCart()) {
            return new JsonResponse([
                'error' => 'Producto no encontrado en el carrito',
                'message' => 'El producto no está en el carrito del usuario'
            ], 400);
        }

        try {
            $em->remove($cart);
            $em->flush();

            return new JsonResponse([
                'status' => 'success',
                'message' => 'Producto eliminado del carrito correctamente',
                'producto' => [
                    'id' => $producto->getId(),
                    'name' => $producto->getName()
                ]
            ]);
        } catch (\Exception $e) {
            return new JsonResponse([
                'status' => 'error',
                'message' => 'Error al eliminar el producto del carrito',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    #[Route('/new', name: 'app_productos_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $producto = new Productos();
        $form = $this->createForm(ProductosType::class, $producto);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($producto);
            $entityManager->flush();

            return $this->redirectToRoute('app_productos_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('productos/new.html.twig', [
            'producto' => $producto,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_productos_show', methods: ['GET'])]
    public function show(?Productos $producto): Response
    {
        return $this->render('productos/show.html.twig', [
            'producto' => $producto,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_productos_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, ?Productos $producto, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(ProductosType::class, $producto);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_productos_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('productos/edit.html.twig', [
            'producto' => $producto,
            'form' => $form,
        ]);
    }

    #[Route('/delete/{id}', name: 'app_producto_delete', methods: ['DELETE'])]
    public function delete(Producto $producto, EntityManagerInterface $entityManager): JsonResponse
    {
        $entityManager->remove($producto);
        $entityManager->flush();

        return new JsonResponse(['message' => 'Usuario eliminado con éxito'], 200);
    }

    #[Route('/filter/price', name: 'app_productos_filter_price', methods: ['GET'])]
    public function filterByPrice(Request $request, EntityManagerInterface $em): JsonResponse
    {
        $minPrice = $request->query->get('min_price');
        $maxPrice = $request->query->get('max_price');

        // Validar que al menos uno de los precios esté presente
        if ($minPrice === null && $maxPrice === null) {
            return new JsonResponse([
                'error' => 'Debe proporcionar al menos un precio (min_price o max_price)'
            ], 400);
        }

        $qb = $em->createQueryBuilder();
        $qb->select('p')
           ->from(Productos::class, 'p');

        if ($minPrice !== null) {
            $qb->andWhere('p.price >= :minPrice')
               ->setParameter('minPrice', (int)$minPrice);
        }
        if ($maxPrice !== null) {
            $qb->andWhere('p.price <= :maxPrice')
               ->setParameter('maxPrice', (int)$maxPrice);
        }

        // Ordenar por precio
        $qb->orderBy('p.price', 'ASC');

        $productos = $qb->getQuery()->getResult();
        
        $data = [];
        foreach ($productos as $producto) {
            $data[] = [
                'id' => $producto->getId(),
                'name' => $producto->getName(),
                'price' => $producto->getPrice(),
                'image' => $producto->getImage(),
                'cantidad' => $producto->getCantidad(),
                'favorite' => $producto->isFavorite(),
                'cart' => $producto->isInsideCart(),
                'date' => $producto->getFecha()->format('Y-m-d'),
                'categoria' => $producto->getCategoria(),
                'subcategoria' => $producto->getSubcategoria()
            ];
        }

        return new JsonResponse([
            'status' => 'success',
            'total' => count($data),
            'productos' => $data
        ]);
    }
}
