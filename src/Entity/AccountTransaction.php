<?php

namespace App\Entity;

use App\Repository\AccountTransactionRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\JoinColumn;

#[ORM\Entity(repositoryClass: AccountTransactionRepository::class)]
class AccountTransaction
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column]
    #[JoinColumn(nullable: true)]
    private ?\DateTimeImmutable $updatedAt = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    private ?string $income = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    private ?string $outcome = null;

    #[ORM\Column(length: 30)]
    private ?string $account_type = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    private ?string $balance_value = null;

    #[ORM\Column(length: 30)]
    private ?string $status = null;

    #[ORM\Column(length: 255)]
    private ?string $paymentMethod = null;

    #[ORM\Column(length: 255)]
    private ?string $paymentRef = null;

    #[ORM\ManyToOne(inversedBy: 'accountTransactions')]
    private ?User $user = null;

    #[ORM\ManyToOne(inversedBy: 'accountTransactions')]
    #[ORM\JoinColumn(nullable: true)]
    private ?Invoice $invoice = null;

    #[ORM\ManyToOne(inversedBy: 'accountTransactions')]
    #[ORM\JoinColumn(nullable: true)]
    private ?Client $client = null;

    #[ORM\OneToOne(inversedBy: 'accountTransaction', cascade: ['persist', 'remove'])]
    #[ORM\JoinColumn(nullable: true)]
    private ?Commission $commission = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $validate_at = null;

    #[ORM\ManyToOne(inversedBy: 'accountTransactions')]
    #[ORM\JoinColumn(nullable: true)]
    private ?User $validation_user = null;

    #[ORM\Column(length: 255)]
    private ?string $describ = null;

    #[ORM\Column(length: 255)]
    private ?string $reason = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(\DateTimeImmutable $updatedAt): static
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    public function getIncome(): ?string
    {
        return $this->income;
    }

    public function setIncome(string $income): static
    {
        $this->income = $income;

        return $this;
    }

    public function getOutcome(): ?string
    {
        return $this->outcome;
    }

    public function setOutcome(string $outcome): static
    {
        $this->outcome = $outcome;

        return $this;
    }

    public function getAccountType(): ?string
    {
        return $this->account_type;
    }

    public function setAccountType(string $account_type): static
    {
        $this->account_type = $account_type;

        return $this;
    }

    public function getBalanceValue(): ?string
    {
        return $this->balance_value;
    }

    public function setBalanceValue(string $balance_value): static
    {
        $this->balance_value = $balance_value;

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

    public function getPaymentMethod(): ?string
    {
        return $this->paymentMethod;
    }

    public function setPaymentMethod(string $paymentMethod): static
    {
        $this->paymentMethod = $paymentMethod;

        return $this;
    }

    public function getPaymentRef(): ?string
    {
        return $this->paymentRef;
    }

    public function setPaymentRef(string $paymentRef): static
    {
        $this->paymentRef = $paymentRef;

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

    public function getInvoice(): ?Invoice
    {
        return $this->invoice;
    }

    public function setInvoice(?Invoice $invoice): static
    {
        $this->invoice = $invoice;

        return $this;
    }

    public function getClient(): ?Client
    {
        return $this->client;
    }

    public function setClient(?Client $client): static
    {
        $this->client = $client;

        return $this;
    }

    public function getCommission(): ?Commission
    {
        return $this->commission;
    }

    public function setCommission(?Commission $commission): static
    {
        $this->commission = $commission;

        return $this;
    }

    public function getValidateAt(): ?\DateTimeImmutable
    {
        return $this->validate_at;
    }

    public function setValidateAt(?\DateTimeImmutable $validate_at): static
    {
        $this->validate_at = $validate_at;

        return $this;
    }

    public function getValidationUser(): ?User
    {
        return $this->validation_user;
    }

    public function setValidationUser(?User $validation_user): static
    {
        $this->validation_user = $validation_user;

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

    public function getReason(): ?string
    {
        return $this->reason;
    }

    public function setReason(string $reason): static
    {
        $this->reason = $reason;

        return $this;
    }
}
