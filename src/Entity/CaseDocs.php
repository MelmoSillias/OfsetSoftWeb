<?php

namespace App\Entity;

use App\Repository\CaseDocsRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CaseDocsRepository::class)]
class CaseDocs
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 12)]
    private ?string $reference = null;

    #[ORM\Column(length: 255)]
    private ?string $senderName = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $senderContact = null;

    #[ORM\Column(type: Types::DATE_IMMUTABLE)]
    private ?\DateTimeImmutable $dateReception = null;

    #[ORM\Column(length: 255)]
    private ?string $modeTransmission = null;

    #[ORM\Column(length: 20)]
    private ?string $urgency = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $sender = null;

    #[ORM\ManyToOne(inversedBy: 'caseDocs')]
    private ?User $primaryRecipient = null;

    #[ORM\ManyToOne(inversedBy: 'caseDocs')]
    private ?User $owner = null;

    #[ORM\Column(length: 255)]
    private ?string $status = null;

    #[ORM\Column(length: 512)]
    private ?string $generalObservations = null;

    /**
     * @var Collection<int, Document>
     */
    #[ORM\OneToMany(targetEntity: Document::class, mappedBy: 'folder', orphanRemoval: true)]
    private Collection $documents;

    /**
     * @var Collection<int, ProcessingFile>
     */
    #[ORM\OneToMany(targetEntity: ProcessingFile::class, mappedBy: 'file', orphanRemoval: true)]
    private Collection $processingFiles;

    /**
     * @var Collection<int, TransferFile>
     */
    #[ORM\OneToMany(targetEntity: TransferFile::class, mappedBy: 'file', orphanRemoval: true)]
    private Collection $transferFiles;

    /**
     * @var Collection<int, Archiving>
     */
    #[ORM\OneToMany(targetEntity: Archiving::class, mappedBy: 'file', orphanRemoval: true)]
    private Collection $archivings;

    public function __construct()
    {
        $this->documents = new ArrayCollection();
        $this->processingFiles = new ArrayCollection();
        $this->transferFiles = new ArrayCollection();
        $this->archivings = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getReference(): ?string
    {
        return $this->reference;
    }

    public function setReference(string $reference): static
    {
        $this->reference = $reference;

        return $this;
    }

    public function getSenderName(): ?string
    {
        return $this->senderName;
    }

    public function setSenderName(string $senderName): static
    {
        $this->senderName = $senderName;

        return $this;
    }

    public function getSenderContact(): ?string
    {
        return $this->senderContact;
    }

    public function setSenderContact(?string $senderContact): static
    {
        $this->senderContact = $senderContact;

        return $this;
    }

    public function getDateReception(): ?\DateTimeImmutable
    {
        return $this->dateReception;
    }

    public function setDateReception(\DateTimeImmutable $dateReception): static
    {
        $this->dateReception = $dateReception;

        return $this;
    }

    public function getModeTransmission(): ?string
    {
        return $this->modeTransmission;
    }

    public function setModeTransmission(string $modeTransmission): static
    {
        $this->modeTransmission = $modeTransmission;

        return $this;
    }

    public function getUrgency(): ?string
    {
        return $this->urgency;
    }

    public function setUrgency(string $urgency): static
    {
        $this->urgency = $urgency;

        return $this;
    }

    public function getSender(): ?string
    {
        return $this->sender;
    }

    public function setSender(?string $sender): static
    {
        $this->sender = $sender;

        return $this;
    }

    public function getPrimaryRecipient(): ?User
    {
        return $this->primaryRecipient;
    }

    public function setPrimaryRecipient(?User $primaryRecipient): static
    {
        $this->primaryRecipient = $primaryRecipient;

        return $this;
    }

    public function getOwner(): ?User
    {
        return $this->owner;
    }

    public function setOwner(?User $owner): static
    {
        $this->owner = $owner;

        return $this;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(string $status): static
    {
        $this->status = $status;

        return $this;
    }

    public function getGeneralObservations(): ?string
    {
        return $this->generalObservations;
    }

    public function setGeneralObservations(string $generalObservations): static
    {
        $this->generalObservations = $generalObservations;

        return $this;
    }

    /**
     * @return Collection<int, Document>
     */
    public function getDocuments(): Collection
    {
        return $this->documents;
    }

    public function addDocument(Document $document): static
    {
        if (!$this->documents->contains($document)) {
            $this->documents->add($document);
            $document->setFolder($this);
        }

        return $this;
    }

    public function removeDocument(Document $document): static
    {
        if ($this->documents->removeElement($document)) {
            // set the owning side to null (unless already changed)
            if ($document->getFolder() === $this) {
                $document->setFolder(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, ProcessingFile>
     */
    public function getProcessingFiles(): Collection
    {
        return $this->processingFiles;
    }

    public function addProcessingFile(ProcessingFile $processingFile): static
    {
        if (!$this->processingFiles->contains($processingFile)) {
            $this->processingFiles->add($processingFile);
            $processingFile->setFile($this);
        }

        return $this;
    }

    public function removeProcessingFile(ProcessingFile $processingFile): static
    {
        if ($this->processingFiles->removeElement($processingFile)) {
            // set the owning side to null (unless already changed)
            if ($processingFile->getFile() === $this) {
                $processingFile->setFile(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, TransferFile>
     */
    public function getTransferFiles(): Collection
    {
        return $this->transferFiles;
    }

    public function addTransferFile(TransferFile $transferFile): static
    {
        if (!$this->transferFiles->contains($transferFile)) {
            $this->transferFiles->add($transferFile);
            $transferFile->setFile($this);
        }

        return $this;
    }

    public function removeTransferFile(TransferFile $transferFile): static
    {
        if ($this->transferFiles->removeElement($transferFile)) {
            // set the owning side to null (unless already changed)
            if ($transferFile->getFile() === $this) {
                $transferFile->setFile(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Archiving>
     */
    public function getArchivings(): Collection
    {
        return $this->archivings;
    }

    public function addArchiving(Archiving $archiving): static
    {
        if (!$this->archivings->contains($archiving)) {
            $this->archivings->add($archiving);
            $archiving->setFile($this);
        }

        return $this;
    }

    public function removeArchiving(Archiving $archiving): static
    {
        if ($this->archivings->removeElement($archiving)) {
            // set the owning side to null (unless already changed)
            if ($archiving->getFile() === $this) {
                $archiving->setFile(null);
            }
        }

        return $this;
    }

}
