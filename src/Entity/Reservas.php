<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;
use App\Controller\ReservasController;
use App\Repository\ReservasRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ReservasRepository::class)]
    #[ApiResource(
        operations: [
            new Get(),
            new GetCollection(),
            new Post(), 
            new Post(
                uriTemplate: '/reservas/new',
                controller: ReservasController::class . '::new',
                name: 'app_reservas_new'
            )
        ],
        routePrefix: '/api'
    )]
class Reservas
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $servicio = null;

    #[ORM\Column(length: 255)]
    private ?string $peluquero = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    private ?\DateTimeInterface $dia = null;

    #[ORM\Column(type: Types::TIME_MUTABLE)]
    private ?\DateTimeInterface $hora = null;

    #[ORM\ManyToOne(inversedBy: 'reservas')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Usuarios $usuario = null;

    #[ORM\Column]
    private ?float $precio = null;

    /**
     * @var Collection<int, Valoracion>
     */
    #[ORM\OneToMany(targetEntity: Valoracion::class, mappedBy: 'reserva')]

    private Collection $valoracions;

    public function __construct()
    {
        $this->valoracions = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getServicio(): ?string
    {
        return $this->servicio;
    }

    public function setServicio(string $servicio): static
    {
        $this->servicio = $servicio;

        return $this;
    }

    public function getPeluquero(): ?string
    {
        return $this->peluquero;
    }

    public function setPeluquero(string $peluquero): static
    {
        $this->peluquero = $peluquero;

        return $this;
    }

    public function getDia(): ?\DateTimeInterface
    {
        return $this->dia;
    }

    public function setDia(\DateTimeInterface $dia): static
    {
        $this->dia = $dia;

        return $this;
    }

    public function getHora(): ?\DateTimeInterface
    {
        return $this->hora;
    }

    public function setHora(\DateTimeInterface $hora): static
    {
        $this->hora = $hora;

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

    public function getPrecio(): ?float
    {
        return $this->precio;
    }

    public function setPrecio(float $precio): static
    {
        $this->precio = $precio;

        return $this;
    }

    /**
     * @return Collection<int, Valoracion>
     */
    public function getValoracions(): Collection
    {
        return $this->valoracions;
    }

    public function addValoracion(Valoracion $valoracion): static
    {
        if (!$this->valoracions->contains($valoracion)) {
            $this->valoracions->add($valoracion);
            $valoracion->setReserva($this);
        }

        return $this;
    }

    public function removeValoracion(Valoracion $valoracion): static
    {
        if ($this->valoracions->removeElement($valoracion)) {
            // set the owning side to null (unless already changed)
            if ($valoracion->getReserva() === $this) {
                $valoracion->setReserva(null);
            }
        }

        return $this;
    }
}