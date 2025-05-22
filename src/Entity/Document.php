<?php

namespace App\Entity;

use App\Repository\DocumentRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: DocumentRepository::class)]
class Document
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'documents')]
    #[ORM\JoinColumn(nullable: false)]
    private ?CaseDocs $folder = null;

    #[ORM\Column(length: 255)]
    private ?string $description = null;

    #[ORM\Column]
    private ?int $numberOfCopies = null;

    #[ORM\Column]
    private ?int $numberOfPages = null;

    #[ORM\Column(type: Types::DATE_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $documentDate = null;

    #[ORM\Column(length: 255)]
    private ?string $supportingDocuments = null;

    #[ORM\Column(type: Types::ARRAY)]
    private array $attachedFiles = [];

    #[ORM\Column(length: 255)]
    private ?string $notes = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getFolder(): ?CaseDocs
    {
        return $this->folder;
    }

    public function setFolder(?CaseDocs $folder): static
    {
        $this->folder = $folder;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function getNumberOfCopies(): ?int
    {
        return $this->numberOfCopies;
    }

    public function setNumberOfCopies(int $numberOfCopies): static
    {
        $this->numberOfCopies = $numberOfCopies;

        return $this;
    }

    public function getNumberOfPages(): ?int
    {
        return $this->numberOfPages;
    }

    public function setNumberOfPages(int $numberOfPages): static
    {
        $this->numberOfPages = $numberOfPages;

        return $this;
    }

    public function getDocumentDate(): ?\DateTimeImmutable
    {
        return $this->documentDate;
    }

    public function setDocumentDate(?\DateTimeImmutable $documentDate): static
    {
        $this->documentDate = $documentDate;

        return $this;
    }

    public function getSupportingDocuments(): ?string
    {
        return $this->supportingDocuments;
    }

    public function setSupportingDocuments(string $supportingDocuments): static
    {
        $this->supportingDocuments = $supportingDocuments;

        return $this;
    }

    public function getAttachedFiles(): array
    {
        return $this->attachedFiles;
    }

    public function setAttachedFiles(array $attachedFiles): static
    {
        $this->attachedFiles = $attachedFiles;

        return $this;
    }

    public function getNotes(): ?string
    {
        return $this->notes;
    }

    public function setNotes(string $notes): static
    {
        $this->notes = $notes;

        return $this;
    }
}
