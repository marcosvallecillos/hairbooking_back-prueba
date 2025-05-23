<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use App\Repository\ReservasAnuladasRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ReservasAnuladasRepository::class)]
#[ApiResource]
class ReservasAnuladas
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $servicio = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $peluquero = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $dia = null;

    #[ORM\Column(type: Types::TIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $hora = null;

    #[ORM\Column(nullable: true)]
    private ?float $precio = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $fecha_anulada = null;

    #[ORM\ManyToOne(inversedBy: 'reservasAnuladas')]
    private ?Usuarios $usuarios = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getServicio(): ?string
    {
        return $this->servicio;
    }

    public function setServicio(?string $servicio): static
    {
        $this->servicio = $servicio;

        return $this;
    }

    public function getPeluquero(): ?string
    {
        return $this->peluquero;
    }

    public function setPeluquero(?string $peluquero): static
    {
        $this->peluquero = $peluquero;

        return $this;
    }

    public function getDia(): ?\DateTimeInterface
    {
        return $this->dia;
    }

    public function setDia(?\DateTimeInterface $dia): static
    {
        $this->dia = $dia;

        return $this;
    }

    public function getHora(): ?\DateTimeInterface
    {
        return $this->hora;
    }

    public function setHora(?\DateTimeInterface $hora): static
    {
        $this->hora = $hora;

        return $this;
    }

    public function getPrecio(): ?float
    {
        return $this->precio;
    }

    public function setPrecio(?float $precio): static
    {
        $this->precio = $precio;

        return $this;
    }

    public function getFechaAnulada(): ?\DateTimeInterface
    {
        return $this->fecha_anulada;
    }

    public function setFechaAnulada(?\DateTimeInterface $fecha_anulada): static
    {
        $this->fecha_anulada = $fecha_anulada;

        return $this;
    }

    public function getUsuarios(): ?Usuarios
    {
        return $this->usuarios;
    }

    public function setUsuarios(?Usuarios $usuarios): static
    {
        $this->usuarios = $usuarios;

        return $this;
    }
}
