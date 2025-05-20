<?php

namespace App\Entity;

use App\Repository\RenewableInvoiceItemRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: RenewableInvoiceItemRepository::class)]
class RenewableInvoiceItem
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'yes')]
    #[ORM\JoinColumn(nullable: false)]
    private ?RenewableInvoice $renewableInvoice = null;

    #[ORM\Column(length: 255)]
    private ?string $describ = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    private ?string $amount = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    private ?string $quantity = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getRenewableInvoice(): ?RenewableInvoice
    {
        return $this->renewableInvoice;
    }

    public function setRenewableInvoice(?RenewableInvoice $renewableInvoice): static
    {
        $this->renewableInvoice = $renewableInvoice;

        return $this;
    }

    public function getDescrib(): ?string
    {
        return $this->describ;
    }

    public function setDescrib(string $describ): static
    {
        $this->describ = $describ;

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

    public function getQuantity(): ?string
    {
        return $this->quantity;
    }

    public function setQuantity(string $quantity): static
    {
        $this->quantity = $quantity;

        return $this;
    }
}
