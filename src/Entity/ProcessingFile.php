<?php

namespace App\Entity;

use App\Repository\ProcessingFileRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ProcessingFileRepository::class)]
class ProcessingFile
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'processingFiles')]
    #[ORM\JoinColumn(nullable: false)]
    private ?CaseDocs $file = null;

    #[ORM\ManyToOne(inversedBy: 'processingFiles')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    #[ORM\Column(type: Types::DATE_IMMUTABLE)]
    private ?\DateTimeImmutable $processingDate = null;

    #[ORM\Column(length: 512)]
    private ?string $observations = null;

    #[ORM\Column(length: 10)]
    private ?string $action = null;

    #[ORM\Column(length: 512, nullable: true)]
    private ?string $ProcessingNote = null;

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

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): static
    {
        $this->user = $user;

        return $this;
    }

    public function getProcessingDate(): ?\DateTimeImmutable
    {
        return $this->processingDate;
    }

    public function setProcessingDate(\DateTimeImmutable $processingDate): static
    {
        $this->processingDate = $processingDate;

        return $this;
    }

    public function getObservations(): ?string
    {
        return $this->observations;
    }

    public function setObservations(string $observations): static
    {
        $this->observations = $observations;

        return $this;
    }

    public function getAction(): ?string
    {
        return $this->action;
    }

    public function setAction(string $action): static
    {
        $this->action = $action;

        return $this;
    }

    public function getProcessingNote(): ?string
    {
        return $this->ProcessingNote;
    }

    public function setProcessingNote(?string $ProcessingNote): static
    {
        $this->ProcessingNote = $ProcessingNote;

        return $this;
    }
}
