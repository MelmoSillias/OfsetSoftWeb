<?php

namespace App\Controller;

use App\Entity\AccountTransaction;
use App\Entity\Commission;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class CommissionController extends AbstractController
{
    #[Route('/dashboard/commission', name: 'app_commission')]
    public function index(): Response
    {
        return $this->render('commission/index.html.twig', [
            'controller_name' => 'CommissionController',
        ]);
    }

    #[Route('/api/commissions', name: 'commissions_list', methods: ['GET'])]
    public function list(Request $req, EntityManagerInterface $em): JsonResponse
    {
        $qb = $em->getRepository(Commission::class)
            ->createQueryBuilder('c')
            ->join('c.invoice', 'i')->addSelect('i')
            ->join('i.client', 'cl')->addSelect('cl');

        if ($name = $req->query->get('clientName')) {
            $qb->andWhere('cl.company_name LIKE :cn')->setParameter('cn', "%{$name}%");
        }
        if ($committee = $req->query->get('committee')) {
            $qb->andWhere('cl.committee LIKE :cm')->setParameter('cm', "%{$committee}%");
        }
        if ($from = $req->query->get('from')) {
            $qb->andWhere('c.created_at >= :from')->setParameter('from', new \DateTime($from));
        }
        if ($to = $req->query->get('to')) {
            $qb->andWhere('c.created_at <= :to')->setParameter('to', new \DateTime($to));
        }

        $data = [];
        foreach ($qb->orderBy('c.created_at', 'DESC')->getQuery()->getResult() as $c) {
            $data[] = [
                'id'            => $c->getId(),
                'clientName'    => $c->getInvoice()->getClient()->getCompanyName(),
                'committeeName' => $c->getInvoice()->getClient()->getCommittee(),
                'amount'        => $c->getAmount(),
                'status' => $c->getStatus(),
                'taken_at'      => $c->getTakenAt(),
                'penality'       => $c->getPenality(),
                'date'          => $c->getCreatedAt(),
            ];
        }
        return $this->json(['data' => $data]);
    }

    /**
     * POST /api/commission/{id}/take
     * Payload JSON: { amount, penalty, reason }
     */
    #[Route('/api/commission/{id}/take', name: 'commission_take', methods: ['POST'])]
    public function take(int $id, Request $req, EntityManagerInterface $em): JsonResponse
    {
        $commission = $em->getRepository(Commission::class)->find($id);
        if (!$commission) {
            return $this->json(['error' => 'Commission introuvable'], 404);
        }

        $data = json_decode($req->getContent(), true);
        if (!isset($data['amount'])) {
            return $this->json(['error' => 'Payload invalide'], 400);
        }

        $amount  = (float)$data['amount'];
        $penalty = isset($data['penality']) ? (float)$data['penality'] : 0.0; 

        // appliquer le prélèvement
        if ($amount > $commission->getAmount()) {
            return $this->json(['error' => 'Montant > commission'], 400);
        } 
        $commission->setPenality($penalty); 
        $commission->setStatus('payé');
        $commission->setTakenAt(new \DateTimeImmutable());

        $repo = $em->getRepository(AccountTransaction::class);
        $lastExpenseTx = $repo->findOneBy(
            ['account_type' => 'expense'],
            ['id' => 'DESC']
        );
        
        $balance_expense = $lastExpenseTx ? $lastExpenseTx->getBalanceValue() : 0;
        $newBalance_expense = $balance_expense - $amount - $penalty;

        // enregistre une transaction liée à l'invoice
        $invoice = $commission->getInvoice();
        if ($invoice) {
            $tx = new AccountTransaction();
            $tx->setOutcome($amount - $penalty) 
                ->setIncome(0) 
               ->setAccountType('expense')
               ->setPaymentMethod('Espèces')
               ->setPaymentRef("Comm#{$commission->getId()}")
               ->setCreatedAt(new \DateTimeImmutable())
               ->setUpdatedAt(new \DateTimeImmutable())
               ->setBalanceValue($newBalance_expense)
               ->setCommission($commission)
               ->setDescrib('Prélèvement de la commission #'.$commission->getId())
               ->setStatus('en attente')
               ->setUser($this->getUser())
               ->setReason("Prélèvement de la commission");
            
            $em->persist($tx);
        }

        $em->flush();
        return $this->json(['success' => true]);
    }

    /**
     * GET /api/commission/{id}/invoice
     * Retourne les détails de la facture liée à la commission
     */
    #[Route('/api/commission/{id}/invoice', name: 'commission_invoice_info', methods: ['GET'])]
    public function invoiceInfo(int $id, EntityManagerInterface $em): JsonResponse
    {
        $commission = $em->getRepository(Commission::class)->find($id);
        if (!$commission || !$commission->getInvoice()) {
            return $this->json(['error' => 'Données introuvables'], 404);
        }

        $inv = $commission->getInvoice();
        $items = [];
        foreach ($inv->getInvoiceItems() as $it) {
            $items[] = [
                'description' => $it->getDescrib(),
                'amount'      => $it->getAmount(),
                'quantity'    => $it->getQuantity(),
            ];
        }

        return $this->json([
            'reference' => $inv->getRef(),
            'date'      => $inv->getCreatedAt()->format('Y-m-d'),
            'amount'    => $inv->getAmount(),
            'remain'    => $inv->getRemain(),
            'status'    => $inv->getStatus(),
            'items'     => $items,
        ]);
    }

    #[Route('/commission/{id}/receipt', name: 'commission', methods: ['GET'])]
    public function commissionReceipt(Commission $commission): Response
    {

        return $this->render('commission/receipt.html.twig', [
            'commission' => $commission,
        ]);
    }
}
