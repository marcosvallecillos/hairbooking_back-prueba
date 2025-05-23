<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use App\Repository\ValoracionRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\UX\Turbo\Attribute\Broadcast;

#[ORM\Entity(repositoryClass: ValoracionRepository::class)]
#[ApiResource]
#[Broadcast]
class Valoracion
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column]
    private ?int $peluqueroRating = null;

    #[ORM\Column]
    private ?int $servicioRating = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $comentario = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $fecha = null;

    #[ORM\ManyToOne(inversedBy: 'valoracions')]
    #[ORM\JoinColumn(name: 'usuario_id', nullable: false)]
    private ?Usuarios $usuario = null;

    #[ORM\ManyToOne(inversedBy: 'valoracions')]
    private ?Reservas $reserva = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getPeluqueroRating(): ?int
    {
        return $this->peluqueroRating;
    }

    public function setPeluqueroRating(int $peluqueroRating): static
    {
        $this->peluqueroRating = $peluqueroRating;
        return $this;
    }

    public function getServicioRating(): ?int
    {
        return $this->servicioRating;
    }

    public function setServicioRating(int $servicioRating): static
    {
        $this->servicioRating = $servicioRating;
        return $this;
    }

    public function getComentario(): ?string
    {
        return $this->comentario;
    }

    public function setComentario(string $comentario): static
    {
        $this->comentario = $comentario;
        return $this;
    }

    public function getFecha(): ?\DateTimeInterface
    {
        return $this->fecha;
    }

    public function setFecha(\DateTimeInterface $fecha): static
    {
        $this->fecha = $fecha;
        return $this;
    }

    public function getUsuario(): ?Usuarios
    {
        return $this->usuario;
    }

    public function setUsuario(Usuarios $usuario): static
    {
        $this->usuario = $usuario;
        return $this;
    }

    public function getReserva(): ?Reservas
    {
        return $this->reserva;
    }

    public function setReserva(?Reservas $reserva): static
    {
        $this->reserva = $reserva;

        return $this;
    }
}