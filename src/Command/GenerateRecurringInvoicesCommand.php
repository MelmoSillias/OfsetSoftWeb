<?php

namespace App\Command;

use App\Service\RecurringInvoiceGenerator;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'app:generate-recurring-invoices',
    description: 'Génère les factures ponctuelles dues à partir des factures renouvelables.'
)]
class GenerateRecurringInvoicesCommand extends Command
{
    public function __construct(private RecurringInvoiceGenerator $generator)
    {
        parent::__construct();
    }

    protected function execute(InputInterface $in, OutputInterface $out): int
    {
        $today = new \DateTimeImmutable('today');
        $n = $this->generator->generateDue($today);
        $out->writeln("✅ $n facture(s) générée(s).");
        return Command::SUCCESS;
    }
}
