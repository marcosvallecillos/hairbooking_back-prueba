<?php

namespace App\Entity;

use App\Repository\UsuarioProductoFavoritoRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: UsuarioProductoFavoritoRepository::class)]
class UsuarioProductoFavorito
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Usuarios::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?Usuarios $usuario = null;

    #[ORM\ManyToOne(targetEntity: Productos::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?Productos $producto = null;

    #[ORM\Column]
    private ?bool $isFavorite = false;

    #[ORM\Column]
    private ?bool $insideCart = false;

    #[ORM\Column(type: 'integer')]
    private ?int $cantidad = 1;
    
   
    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUsuario(): ?Usuarios
    {
        return $this->usuario;
    }

    public function setUsuario(?Usuarios $usuario): static
    {
        $this->usuario = $usuario;
        return $this;
    }

    public function getProducto(): ?Productos
    {
        return $this->producto;
    }

    public function setProducto(?Productos $producto): static
    {
        $this->producto = $producto;
        return $this;
    }

    public function isFavorite(): ?bool
    {
        return $this->isFavorite;
    }

    public function setIsFavorite(bool $isFavorite): static
    {
        $this->isFavorite = $isFavorite;
        return $this;
    }
    public function insideCart(): ?bool
    {
        return $this->insideCart;
    }

    public function setInsideCart(bool $insideCart): static
    {
        $this->insideCart = $insideCart;
        return $this;
    }
    public function getCantidad(): ?int
    {
        return $this->cantidad;
    }
    
    public function setCantidad(int $cantidad): static
    {
        $this->cantidad = $cantidad;
        return $this;
    }
} 