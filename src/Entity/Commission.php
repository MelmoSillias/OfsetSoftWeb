<?php

namespace App\Entity;

use App\Repository\CommissionRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CommissionRepository::class)]
class Commission
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'commissions')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Invoice $invoice = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $created_at = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $taken_at = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    private ?string $amount = null;

    #[ORM\Column(length: 20)]
    private ?string $status = null;

    #[ORM\OneToOne(mappedBy: 'commission', cascade: ['persist', 'remove'])] 
    private ?AccountTransaction $accountTransaction = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2, nullable: true)]
    private ?string $penality = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getInvoice(): ?Invoice
    {
        return $this->invoice;
    }

    public function setInvoice(?Invoice $invoice): static
    {
        $this->invoice = $invoice;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->created_at;
    }

    public function setCreatedAt(\DateTimeImmutable $created_at): static
    {
        $this->created_at = $created_at;

        return $this;
    }

    public function getTakenAt(): ?\DateTimeImmutable
    {
        return $this->taken_at;
    }

    public function setTakenAt(?\DateTimeImmutable $taken_at): static
    {
        $this->taken_at = $taken_at;

        return $this;
    }

    public function getAmount(): ?string
    {
        return $this->amount;
    }

    public function setAmount(string $amount): static
    {
        $this->amount = $amount;

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

    public function getAccountTransaction(): ?AccountTransaction
    {
        return $this->accountTransaction;
    }

    public function setAccountTransaction(?AccountTransaction $accountTransaction): static
    {
        // unset the owning side of the relation if necessary
        if ($accountTransaction === null && $this->accountTransaction !== null) {
            $this->accountTransaction->setCommission(null);
        }

        // set the owning side of the relation if necessary
        if ($accountTransaction !== null && $accountTransaction->getCommission() !== $this) {
            $accountTransaction->setCommission($this);
        }

        $this->accountTransaction = $accountTransaction;

        return $this;
    }

    public function getPenality(): ?string
    {
        return $this->penality;
    }

    public function setPenality(?string $penality): static
    {
        $this->penality = $penality;

        return $this;
    }
}
