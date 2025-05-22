<?php

namespace App\Entity;

use App\Repository\ClientRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ClientRepository::class)]
class Client
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $company_name = null;

    #[ORM\Column(length: 255)]
    private ?string $delegate = null;

    #[ORM\Column(length: 255)]
    private ?string $address = null;

    #[ORM\Column(length: 255)]
    private ?string $phone_number = null;

    #[ORM\Column(length: 255)]
    private ?string $type = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $committee = null;
 
    /**
     * @var Collection<int, AccountTransaction>
     */
    #[ORM\OneToMany(targetEntity: AccountTransaction::class, mappedBy: 'client')]
    private Collection $accountTransactions;

    /**
     * @var Collection<int, RenewableInvoice>
     */
    #[ORM\OneToMany(targetEntity: RenewableInvoice::class, mappedBy: 'client', orphanRemoval: true)]
    private Collection $renewableInvoices;

    #[ORM\Column]
    private ?bool $is_active = null;

    /**
     * @var Collection<int, Invoice>
     */
    #[ORM\OneToMany(targetEntity: Invoice::class, mappedBy: 'client', orphanRemoval: true)]
    private Collection $invoices;

    public function __construct()
    { 
        $this->accountTransactions = new ArrayCollection();
        $this->renewableInvoices = new ArrayCollection();
        $this->invoices = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCompanyName(): ?string
    {
        return $this->company_name;
    }

    public function setCompanyName(string $company_name): static
    {
        $this->company_name = $company_name;

        return $this;
    }

    public function getDelegate(): ?string
    {
        return $this->delegate;
    }

    public function setDelegate(string $delegate): static
    {
        $this->delegate = $delegate;

        return $this;
    }

    public function getAddress(): ?string
    {
        return $this->address;
    }

    public function setAddress(string $address): static
    {
        $this->address = $address;

        return $this;
    }

    public function getPhoneNumber(): ?string
    {
        return $this->phone_number;
    }

    public function setPhoneNumber(string $phone_number): static
    {
        $this->phone_number = $phone_number;

        return $this;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(string $type): static
    {
        $this->type = $type;

        return $this;
    }

    public function getCommittee(): ?string
    {
        return $this->committee;
    }

    public function setCommittee(?string $committee): static
    {
        $this->committee = $committee;

        return $this;
    }

     
    /**
     * @return Collection<int, AccountTransaction>
     */
    public function getAccountTransactions(): Collection
    {
        return $this->accountTransactions;
    }

    public function addAccountTransaction(AccountTransaction $accountTransaction): static
    {
        if (!$this->accountTransactions->contains($accountTransaction)) {
            $this->accountTransactions->add($accountTransaction);
            $accountTransaction->setClient($this);
        }

        return $this;
    }

    public function removeAccountTransaction(AccountTransaction $accountTransaction): static
    {
        if ($this->accountTransactions->removeElement($accountTransaction)) {
            // set the owning side to null (unless already changed)
            if ($accountTransaction->getClient() === $this) {
                $accountTransaction->setClient(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, RenewableInvoice>
     */
    public function getRenewableInvoices(): Collection
    {
        return $this->renewableInvoices;
    }

    public function addRenewableInvoice(RenewableInvoice $renewableInvoice): static
    {
        if (!$this->renewableInvoices->contains($renewableInvoice)) {
            $this->renewableInvoices->add($renewableInvoice);
            $renewableInvoice->setClient($this);
        }

        return $this;
    }

    public function removeRenewableInvoice(RenewableInvoice $renewableInvoice): static
    {
        if ($this->renewableInvoices->removeElement($renewableInvoice)) {
            // set the owning side to null (unless already changed)
            if ($renewableInvoice->getClient() === $this) {
                $renewableInvoice->setClient(null);
            }
        }

        return $this;
    }

    public function isActive(): ?bool
    {
        return $this->is_active;
    }

    public function setIsActive(bool $is_active): static
    {
        $this->is_active = $is_active;

        return $this;
    }

    /**
     * @return Collection<int, Invoice>
     */
    public function getInvoices(): Collection
    {
        return $this->invoices;
    }

    public function addInvoice(Invoice $invoice): static
    {
        if (!$this->invoices->contains($invoice)) {
            $this->invoices->add($invoice);
            $invoice->setClient($this);
        }

        return $this;
    }

    public function removeInvoice(Invoice $invoice): static
    {
        if ($this->invoices->removeElement($invoice)) {
            // set the owning side to null (unless already changed)
            if ($invoice->getClient() === $this) {
                $invoice->setClient(null);
            }
        }

        return $this;
    }

    public function getBalance(): float
    {
        $lastTransaction = $this->accountTransactions->last();
        if ($lastTransaction === false) {
            return 0.0;
        }
        return $lastTransaction->getBalanceValue();
    }
}
