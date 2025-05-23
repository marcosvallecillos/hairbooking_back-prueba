<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use App\Repository\CompraProductoRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CompraProductoRepository::class)]
#[ApiResource]
class CompraProducto
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column]
    private ?int $cantidad = null;

    #[ORM\Column]
    private ?float $precio = null;

    #[ORM\ManyToOne(targetEntity: Compra::class, inversedBy: 'detalles')]
    private ?Compra $compra;

    #[ORM\ManyToOne(targetEntity: Productos::class)]
    private ?Productos $producto;
    public function getId(): ?int
    {
        return $this->id;
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

    public function getPrecio(): ?float
    {
        return $this->precio;
    }

    public function setPrecio(float $precio): static
    {
        $this->precio = $precio;

        return $this;
    }
    public function getProducto()
    {
        return $this->producto;
    }

    public function setProducto($producto)
    {
        $this->producto = $producto;

        return $this;
    }
    public function getCompra()
    {
        return $this->compra;
    }

    public function setCompra($compra)
    {
        $this->compra = $compra;

        return $this;
    }

    public function getTotal(): float
    {
        return $this->cantidad * $this->precio;
    }
}
