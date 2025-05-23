<?php

namespace App\Entity;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use ApiPlatform\Metadata\Delete;
use App\Repository\UsuariosRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
#[ORM\Entity(repositoryClass: UsuariosRepository::class)]
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
class Usuarios
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $nombre = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $apellidos = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $email = null;
    #[ORM\Column(length: 255)]
    private ?string $password = null;
    #[ORM\Column(nullable: true)]
    private ?int $telefono = null;

    /**
     * @var Collection<int, Reservas>
     */
  #[ORM\OneToMany(targetEntity: Reservas::class, mappedBy: 'usuario')]
    private Collection $reservas;

    /**
     * @var Collection<int, Productos>
     */
    #[ORM\ManyToMany(targetEntity: Productos::class)]
    #[ORM\JoinTable(name: 'usuarios_productos_favoritos')]
    private Collection $productosFavoritos;

    /**
     * @var Collection<int, Compra>
     */
    #[ORM\OneToMany(targetEntity: Compra::class, mappedBy: 'usuario')]
    private Collection $compras;

    /**
     * @var Collection<int, Valoracion>
     */
    #[ORM\OneToMany(targetEntity: Valoracion::class, mappedBy: 'usuario')]
    private Collection $valoracions;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $rol = null;

    /**
     * @var Collection<int, ReservasAnuladas>
     */
    #[ORM\OneToMany(targetEntity: ReservasAnuladas::class, mappedBy: 'usuarios')]
    private Collection $reservasAnuladas;

    public function __construct()
    {
        $this->reservas = new ArrayCollection();
        $this->productosFavoritos = new ArrayCollection();
        $this->compras = new ArrayCollection();
        $this->valoracions = new ArrayCollection();
        $this->reservasAnuladas = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNombre(): ?string
    {
        return $this->nombre;
    }

    public function setNombre(?string $nombre): static
    {
        $this->nombre = $nombre;

        return $this;
    }

    public function getApellidos(): ?string
    {
        return $this->apellidos;
    }

    public function setApellidos(?string $apellidos): static
    {
        $this->apellidos = $apellidos;

        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(?string $email): static
    {
        $this->email = $email;

        return $this;
    }

    public function getTelefono(): ?int
    {
        return $this->telefono;
    }

    public function setTelefono(?int $telefono): static
    {
        $this->telefono = $telefono;

        return $this;
    }


    /**
     * @return Collection<int, Reservas>
     */
    public function getReservas(): Collection
    {
        return $this->reservas;
    }

    public function addReserva(Reservas $reserva): static
    {
        if (!$this->reservas->contains($reserva)) {
            $this->reservas->add($reserva);
            $reserva->setUsuario($this);
        }

        return $this;
    }

    public function removeReserva(Reservas $reserva): static
    {
        if ($this->reservas->removeElement($reserva)) {
            // set the owning side to null (unless already changed)
            if ($reserva->getUsuario() === $this) {
                $reserva->setUsuario(null);
            }
        }

        return $this;
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(string $password): static
    {
        $this->password = $password;

        return $this;
    }

    /**
     * @return Collection<int, Productos>
     */
    public function getProductosFavoritos(): Collection
    {
        return $this->productosFavoritos;
    }

    public function addProductoFavorito(Productos $producto): static
    {
        if (!$this->productosFavoritos->contains($producto)) {
            $this->productosFavoritos->add($producto);
        }

        return $this;
    }

    public function removeProductoFavorito(Productos $producto): static
    {
        $this->productosFavoritos->removeElement($producto);

        return $this;
    }

    /**
     * @return Collection<int, Compra>
     */
    public function getCompras(): Collection
    {
        return $this->compras;
    }

    public function addCompra(Compra $compra): static
    {
        if (!$this->compras->contains($compra)) {
            $this->compras->add($compra);
            $compra->setUsuario($this);
        }

        return $this;
    }

    public function removeCompra(Compra $compra): static
    {
        if ($this->compras->removeElement($compra)) {
            // set the owning side to null (unless already changed)
            if ($compra->getUsuario() === $this) {
                $compra->setUsuario(null);
            }
        }

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
            $valoracion->setUsuario($this);
        }

        return $this;
    }

    public function removeValoracion(Valoracion $valoracion): static
    {
        if ($this->valoracions->removeElement($valoracion)) {
            // set the owning side to null (unless already changed)
            if ($valoracion->getUsuario() === $this) {
                $valoracion->setUsuario(null);
            }
        }

        return $this;
    }

    public function getRol(): ?string
    {
        return $this->rol;
    }

    public function setRol(?string $rol): static
    {
        $this->rol = $rol;

        return $this;
    }

    /**
     * @return Collection<int, ReservasAnuladas>
     */
    public function getReservasAnuladas(): Collection
    {
        return $this->reservasAnuladas;
    }

    public function addReservasAnulada(ReservasAnuladas $reservasAnulada): static
    {
        if (!$this->reservasAnuladas->contains($reservasAnulada)) {
            $this->reservasAnuladas->add($reservasAnulada);
            $reservasAnulada->setUsuarios($this);
        }

        return $this;
    }

    public function removeReservasAnulada(ReservasAnuladas $reservasAnulada): static
    {
        if ($this->reservasAnuladas->removeElement($reservasAnulada)) {
            // set the owning side to null (unless already changed)
            if ($reservasAnulada->getUsuarios() === $this) {
                $reservasAnulada->setUsuarios(null);
            }
        }

        return $this;
    }
}
