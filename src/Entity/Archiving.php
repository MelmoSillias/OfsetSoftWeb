<?php

namespace App\Entity;

use App\Repository\ArchivingRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ArchivingRepository::class)]
class Archiving
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'archivings')]
    #[ORM\JoinColumn(nullable: false)]
    private ?CaseDocs $file = null;

    #[ORM\Column(type: Types::DATE_IMMUTABLE)]
    private ?\DateTimeImmutable $archivingDate = null;

    #[ORM\Column(length: 255)]
    private ?string $warehouseOffice = null;

    #[ORM\ManyToOne(inversedBy: 'archivings')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $archivist = null;

    #[ORM\Column(length: 255)]
    private ?string $archivingCoordinate = null;

    #[ORM\Column(length: 512, nullable: true)]
    private ?string $archivingNotes = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getFile(): ?CaseDocs
    {
        return $this->file;
    }

    public function setFile(?CaseDocs $file): static
    {
        $this->file = $file;

        return $this;
    }

    public function getArchivingDate(): ?\DateTimeImmutable
    {
        return $this->archivingDate;
    }

    public function setArchivingDate(\DateTimeImmutable $archivingDate): static
    {
        $this->archivingDate = $archivingDate;

        return $this;
    }

    public function getWarehouseOffice(): ?string
    {
        return $this->warehouseOffice;
    }

    public function setWarehouseOffice(string $warehouseOffice): static
    {
        $this->warehouseOffice = $warehouseOffice;

        return $this;
    }

    public function getArchivist(): ?User
    {
        return $this->archivist;
    }

    public function setArchivist(?User $archivist): static
    {
        $this->archivist = $archivist;

        return $this;
    }

    public function getArchivingCoordinate(): ?string
    {
        return $this->archivingCoordinate;
    }

    public function setArchivingCoordinate(string $archivingCoordinate): static
    {
        $this->archivingCoordinate = $archivingCoordinate;

        return $this;
    }

    public function getArchivingNotes(): ?string
    {
        return $this->archivingNotes;
    }

    public function setArchivingNotes(?string $archivingNotes): static
    {
        $this->archivingNotes = $archivingNotes;

        return $this;
    }
}
