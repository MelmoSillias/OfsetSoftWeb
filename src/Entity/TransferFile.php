<?php

namespace App\Entity;

use App\Repository\TransferFileRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: TransferFileRepository::class)]
class TransferFile
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'transferFiles')]
    #[ORM\JoinColumn(nullable: false)]
    private ?CaseDocs $file = null;

    #[ORM\Column(type: Types::DATE_IMMUTABLE)]
    private ?\DateTimeImmutable $tranferDate = null;

    #[ORM\Column(length: 255)]
    private ?string $reason = null;

    #[ORM\ManyToOne(inversedBy: 'transferFiles')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $transferResponsible = null;

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

    public function getTranferDate(): ?\DateTimeImmutable
    {
        return $this->tranferDate;
    }

    public function setTranferDate(\DateTimeImmutable $tranferDate): static
    {
        $this->tranferDate = $tranferDate;

        return $this;
    }

    public function getReason(): ?string
    {
        return $this->reason;
    }

    public function setReason(string $reason): static
    {
        $this->reason = $reason;

        return $this;
    }

    public function getTransferResponsible(): ?User
    {
        return $this->transferResponsible;
    }

    public function setTransferResponsible(?User $transferResponsible): static
    {
        $this->transferResponsible = $transferResponsible;

        return $this;
    }
}
