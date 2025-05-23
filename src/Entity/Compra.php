<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use ApiPlatform\Metadata\Delete;
use App\Repository\CompraRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CompraRepository::class)]
#[ApiResource(
    operations: [
        new Get(),
        new GetCollection(),
        new Post(),
        new Put(),
        new Delete()
    ],
    routePrefix: '/api'
)]
class Compra
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    #[ORM\Column(length: 255)]
    private ?string $image = null;

    #[ORM\Column]
    private ?int $cantidad = null;

    #[ORM\Column]
    private ?float $price = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $fecha = null;

    #[ORM\OneToMany(mappedBy: 'compra', targetEntity: CompraProducto::class, cascade: ['persist'], orphanRemoval: true)]
    private Collection $detalles;

    #[ORM\ManyToMany(targetEntity: Productos::class, inversedBy: 'compras')]
    private Collection $productos;

    #[ORM\ManyToOne(inversedBy: 'compras')]
    private ?Usuarios $usuario = null;

    #[ORM\Column(nullable: true)]
    private ?float $descuento = null;

    public function __construct()
    {
        $this->productos = new ArrayCollection();
        $this->detalles = new ArrayCollection();

    }

    public function getDetalles(): Collection { return $this->detalles; }

    public function addDetalle(CompraProducto $detalle): static
    {
        if (!$this->detalles->contains($detalle)) {
            $this->detalles->add($detalle);
            $detalle->setCompra($this);
        }
        return $this;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getImage(): ?string
    {
        return $this->image;
    }

    public function setImage(string $image): static
    {
        $this->image = $image;

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

    public function getPrice(): ?float
    {
        return $this->price;
    }

    public function setPrice(float $price): static
    {
        $this->price = $price;

        return $this;
    }

    public function getFecha(): ?\DateTimeInterface
    {
        return $this->fecha;
    }

    public function getFechaFormateada(): ?string
    {
        return $this->fecha ? $this->fecha->format('d-m-Y') : null;
    }

    public function setFecha(\DateTimeInterface|string $fecha): static
    {
        if (is_string($fecha)) {
            // Si es string, convertir de d-m-Y a DateTime
            $this->fecha = \DateTime::createFromFormat('d-m-Y', $fecha);
        } else {
            $this->fecha = $fecha;
        }
        return $this;
    }

    public function getProductos(): Collection
    {
        return $this->productos;
    }

    public function addProducto(Productos $producto): static
    {
        if (!$this->productos->contains($producto)) {
            $this->productos->add($producto);
        }

        return $this;
    }

    public function removeProducto(Productos $producto): static
    {
        $this->productos->removeElement($producto);

        return $this;
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

    public function getDescuento(): ?float
    {
        return $this->descuento;
    }

    public function setDescuento(?float $descuento): static
    {
        $this->descuento = $descuento;

        return $this;
    }
}
