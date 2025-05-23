<?php

namespace App\Service;

use App\Entity\Invoice;
use App\Entity\InvoiceItem;
use App\Repository\RenewableInvoiceRepository;
use Doctrine\ORM\EntityManagerInterface;

class RecurringInvoiceGenerator
{
    public function __construct(
        private RenewableInvoiceRepository $repo,
        private EntityManagerInterface   $em
    ) {}

    /**
     * Génère toutes les factures dues au jour $today.
     *
     * @return int Le nombre de factures générées
     */
    public function generateDue(\DateTimeImmutable $today): int
    {
        // 1. récupérer les renouvelables à échéance
        $dueList = $this->repo->createQueryBuilder('r')
            ->andWhere('r.nextDate <= :today')
            ->setParameter('today', $today)
            ->getQuery()
            ->getResult();

        $count = 0;
        foreach ($dueList as $renew) {
            // 2a. créer la facture ponctuelle
            $invoice = new Invoice();
            $invoice->setClient($renew->getClient())
                    ->setCreatedAt($today)
                    ->setStatus('pending')
                    ->setRef($this->generateRef())
                    ->setAmount(0)  // mis à jour après ajout des lignes
                    ->setRemain(0)
                    ->setMonthStr($today->format('Y-m'));

            // 2b. copier chaque ligne
            $total = 0;
            foreach ($renew->getItems() as $ri) {
                $item = new InvoiceItem();
                $item->setInvoice($invoice)
                     ->setDescrib($ri->getDescrib())
                     ->setAmount($ri->getAmount())
                     ->setQuantity((int)$ri->getQuantity());
                $invoice->addInvoiceItem($item);
                $total += (float)$ri->getAmount() * $ri->getQuantity();
            }

            // 2c. finaliser montants
            $invoice->setAmount($total);
            $invoice->setRemain($total);

            $this->em->persist($invoice);

            // 2d. avancer la prochaine échéance
            $next = $renew->getNextDate()
                          ->modify('+' . $renew->getPeriodVal() . ' MONTH');
            $renew->setNextDate(\DateTimeImmutable::createFromMutable($next));

            $count++;
        }

        // 3. persister en base
        $this->em->flush();
        return $count;
    }

    private function generateRef(): string
    {
        // ex. FAC-20250523-001
        return 'FAC-'.date('Ymd').'-'.random_int(1, 999);
    }
}
