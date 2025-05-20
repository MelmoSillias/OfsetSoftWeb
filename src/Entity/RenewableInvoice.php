<?php

namespace App\Entity;

use App\Repository\RenewableInvoiceRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: RenewableInvoiceRepository::class)]
class RenewableInvoice
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'renewableInvoices')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Client $client = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column]
    #[ORM\JoinColumn(nullable: true)]
    private ?\DateTimeImmutable $updateAt = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $startAt = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $endAt = null;

    #[ORM\Column(type: Types::DATE_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $nextDate = null;

    #[ORM\Column(length: 10)]
    private ?string $state = null;

    /**
     * @var Collection<int, RenewableInvoiceItem>
     */
    #[ORM\OneToMany(targetEntity: RenewableInvoiceItem::class, mappedBy: 'renewableInvoice', orphanRemoval: true)]
    private Collection $items;

    #[ORM\Column]
    private ?int $period_val = null;

    public function __construct()
    {
        $this->items = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
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

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getUpdateAt(): ?\DateTimeImmutable
    {
        return $this->updateAt;
    }

    public function setUpdateAt(\DateTimeImmutable $updateAt): static
    {
        $this->updateAt = $updateAt;

        return $this;
    }

    public function getStartAt(): ?\DateTimeImmutable
    {
        return $this->startAt;
    }

    public function setStartAt(?\DateTimeImmutable $startAt): static
    {
        $this->startAt = $startAt;

        return $this;
    }

    public function getEndAt(): ?\DateTimeImmutable
    {
        return $this->endAt;
    }

    public function setEndAt(?\DateTimeImmutable $endAt): static
    {
        $this->endAt = $endAt;

        return $this;
    }

    public function getNextDate(): ?\DateTimeImmutable
    {
        return $this->nextDate;
    }

    public function setNextDate(?\DateTimeImmutable $nextDate): static
    {
        $this->nextDate = $nextDate;

        return $this;
    }

    public function getState(): ?string
    {
        return $this->state;
    }

    public function setState(string $state): static
    {
        $this->state = $state;

        return $this;
    }

    /**
     * @return Collection<int, RenewableInvoiceItem>
     */
    public function getItems(): Collection
    {
        return $this->items;
    }

    public function addItem(RenewableInvoiceItem $ye): static
    {
        if (!$this->items->contains($ye)) {
            $this->items->add($ye);
            $ye->setRenewableInvoice($this);
        }

        return $this;
    }

    public function removeItem(RenewableInvoiceItem $ye): static
    {
        if ($this->items->removeElement($ye)) {
            // set the owning side to null (unless already changed)
            if ($ye->getRenewableInvoice() === $this) {
                $ye->setRenewableInvoice(null);
            }
        }

        return $this;
    }

    public function getPeriodVal(): ?int
    {
        return $this->period_val;
    }

    public function setPeriodVal(int $period): static
    {
        $this->period_val = $period;

        return $this;
    }
}
