<?php

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\UniqueConstraint(name: 'UNIQ_IDENTIFIER_USERNAME', fields: ['username'])]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 180)]
    private ?string $username = null;

    /**
     * @var list<string> The user roles
     */
    #[ORM\Column]
    private array $roles = [];

    /**
     * @var string The hashed password
     */
    #[ORM\Column]
    private ?string $password = null;

    #[ORM\Column]
    private ?bool $isActif = null;

    #[ORM\Column(length: 255)]
    private ?string $FullName = null;

    /**
     * @var Collection<int, Invoice>
     */
    #[ORM\OneToMany(targetEntity: Invoice::class, mappedBy: 'user')]
    private Collection $invoices;

    /**
     * @var Collection<int, AccountTransaction>
     */
    #[ORM\OneToMany(targetEntity: AccountTransaction::class, mappedBy: 'user')]
    private Collection $accountTransactions;

    /**
     * @var Collection<int, CaseDocs>
     */
    #[ORM\OneToMany(targetEntity: CaseDocs::class, mappedBy: 'primaryRecipient')]
    private Collection $caseDocs;

    /**
     * @var Collection<int, ProcessingFile>
     */
    #[ORM\OneToMany(targetEntity: ProcessingFile::class, mappedBy: 'user', orphanRemoval: true)]
    private Collection $processingFiles;

    /**
     * @var Collection<int, TransferFile>
     */
    #[ORM\OneToMany(targetEntity: TransferFile::class, mappedBy: 'transferResponsible', orphanRemoval: true)]
    private Collection $transferFiles;

    /**
     * @var Collection<int, Archiving>
     */
    #[ORM\OneToMany(targetEntity: Archiving::class, mappedBy: 'archivist', orphanRemoval: true)]
    private Collection $archivings;

    /**
     * @var Collection<int, Task>
     */
    #[ORM\OneToMany(targetEntity: Task::class, mappedBy: 'user', orphanRemoval: true)]
    private Collection $tasks;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $jobTitle = null;

    public function __construct()
    {
        $this->invoices = new ArrayCollection();
        $this->accountTransactions = new ArrayCollection();
        $this->caseDocs = new ArrayCollection();
        $this->processingFiles = new ArrayCollection();
        $this->transferFiles = new ArrayCollection();
        $this->archivings = new ArrayCollection();
        $this->tasks = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUsername(): ?string
    {
        return $this->username;
    }

    public function setUsername(string $username): static
    {
        $this->username = $username;

        return $this;
    }

    /**
     * A visual identifier that represents this user.
     *
     * @see UserInterface
     */
    public function getUserIdentifier(): string
    {
        return (string) $this->username;
    }

    /**
     * @see UserInterface
     *
     * @return list<string>
     */
    public function getRoles(): array
    {
        $roles = $this->roles;
        // guarantee every user at least has ROLE_USER
        $roles[] = 'ROLE_USER';

        return array_unique($roles);
    }

    /**
     * @param list<string> $roles
     */
    public function setRoles(array $roles): static
    {
        $this->roles = $roles;

        return $this;
    }

    public function addRole(string $role): static
    {
        $this->roles[] = $role;

        return $this;
    }
 

    /**
     * @see PasswordAuthenticatedUserInterface
     */
    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(string $password): static
    {
        $this->password = $password;

        return $this;
    }

    /**
     * @see UserInterface
     */
    public function eraseCredentials(): void
    {
        // If you store any temporary, sensitive data on the user, clear it here
        // $this->plainPassword = null;
    }

    public function isActif(): ?bool
    {
        return $this->isActif;
    }

    public function setIsActif(bool $isActif): static
    {
        $this->isActif = $isActif;

        return $this;
    }

    public function getFullName(): ?string
    {
        return $this->FullName;
    }

    public function setFullName(string $FullName): static
    {
        $this->FullName = $FullName;

        return $this;
    }

    /**
     * @return Collection<int, Invoice>
     */
    public function getInvoices(): Collection
    {
        return $this->invoices;
    }

    public function addInvoices(Invoice $invoice): static
    {
        if (!$this->invoices->contains($invoice)) {
            $this->invoices->add($invoice);
            $invoice->setUser($this);
        }

        return $this;
    }

    public function removeInvoice(Invoice $invoice): static
    {
        if ($this->invoices->removeElement($invoice)) {
            // set the owning side to null (unless already changed)
            if ($invoice->getUser() === $this) {
                $invoice->setUser(null);
            }
        }

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
            $accountTransaction->setUser($this);
        }

        return $this;
    }

    public function removeAccountTransaction(AccountTransaction $accountTransaction): static
    {
        if ($this->accountTransactions->removeElement($accountTransaction)) {
            // set the owning side to null (unless already changed)
            if ($accountTransaction->getUser() === $this) {
                $accountTransaction->setUser(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, CaseDocs>
     */
    public function getCaseDocs(): Collection
    {
        return $this->caseDocs;
    }

    public function addCaseDoc(CaseDocs $caseDoc): static
    {
        if (!$this->caseDocs->contains($caseDoc)) {
            $this->caseDocs->add($caseDoc);
            $caseDoc->setPrimaryRecipient($this);
        }

        return $this;
    }

    public function removeCaseDoc(CaseDocs $caseDoc): static
    {
        if ($this->caseDocs->removeElement($caseDoc)) {
            // set the owning side to null (unless already changed)
            if ($caseDoc->getPrimaryRecipient() === $this) {
                $caseDoc->setPrimaryRecipient(null);
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
            $processingFile->setUser($this);
        }

        return $this;
    }

    public function removeProcessingFile(ProcessingFile $processingFile): static
    {
        if ($this->processingFiles->removeElement($processingFile)) {
            // set the owning side to null (unless already changed)
            if ($processingFile->getUser() === $this) {
                $processingFile->setUser(null);
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
            $transferFile->setTransferResponsible($this);
        }

        return $this;
    }

    public function removeTransferFile(TransferFile $transferFile): static
    {
        if ($this->transferFiles->removeElement($transferFile)) {
            // set the owning side to null (unless already changed)
            if ($transferFile->getTransferResponsible() === $this) {
                $transferFile->setTransferResponsible(null);
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
            $archiving->setArchivist($this);
        }

        return $this;
    }

    public function removeArchiving(Archiving $archiving): static
    {
        if ($this->archivings->removeElement($archiving)) {
            // set the owning side to null (unless already changed)
            if ($archiving->getArchivist() === $this) {
                $archiving->setArchivist(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Task>
     */
    public function getTasks(): Collection
    {
        return $this->tasks;
    }

    public function addTask(Task $task): static
    {
        if (!$this->tasks->contains($task)) {
            $this->tasks->add($task);
            $task->setUser($this);
        }

        return $this;
    }

    public function removeTask(Task $task): static
    {
        if ($this->tasks->removeElement($task)) {
            // set the owning side to null (unless already changed)
            if ($task->getUser() === $this) {
                $task->setUser(null);
            }
        }

        return $this;
    }

    public function getJobTitle(): ?string
    {
        return $this->jobTitle;
    }

    public function setJobTitle(?string $jobTitle): static
    {
        $this->jobTitle = $jobTitle;

        return $this;
    }

    #[ORM\Column(type: 'string', length: 10, options: ['default' => 'light'])]
    private string $theme = 'light';

    public function getTheme(): string
    {
        return $this->theme;
    }

    public function setTheme(string $theme): self
    {
        // guard to allowed values
        if (!in_array($theme, ['light','dark'], true)) {
            throw new \InvalidArgumentException("Invalid theme “{$theme}”.");
        }
        $this->theme = $theme;
        return $this;
    }
}
