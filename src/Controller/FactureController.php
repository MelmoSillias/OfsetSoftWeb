<?php

namespace App\Controller;

use App\Entity\AccountTransaction;
use App\Entity\Client;
use App\Entity\Commission;
use App\Entity\Invoice;
use App\Entity\InvoiceItem;
use App\Repository\AccountTransactionRepository;
use Doctrine\ORM\EntityManagerInterface;
use NumberFormatter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class FactureController extends AbstractController
{
    #[Route('/facture', name: 'app_facture')]
    public function index(): Response
    {
        return $this->render('facture/index.html.twig', [
            'controller_name' => 'FactureController',
        ]);
    }

    /**
     * GET /api/invoices/stats
     * Stats des factures
     */
    #[Route('/api/invoices/stats', name: 'invoice_stats', methods: ['GET'])]
    public function stats(Request $req, EntityManagerInterface $em): JsonResponse
    {
        $from = $req->query->get('from');
        $to   = $req->query->get('to');

        // 1) Total
        $qbTotal = $em->getRepository(Invoice::class)
            ->createQueryBuilder('i')
            ->select('COUNT(i.id)');
        if ($from) { $qbTotal->andWhere('i.createdAt >= :from')->setParameter('from', new \DateTime($from)); }
        if ($to)   { $qbTotal->andWhere('i.createdAt <= :to')  ->setParameter('to',   new \DateTime($to)); }
        $total = (int) $qbTotal->getQuery()->getSingleScalarResult();

        // 2) Intern
        $qbIntern = $em->createQueryBuilder()
            ->select('COUNT(i.id)')
            ->from(Invoice::class, 'i')
            ->join('i.client','c')
            ->where('c.type = :intern')
            ->setParameter('intern','intern');
        if ($from) { $qbIntern->andWhere('i.createdAt >= :from')->setParameter('from', new \DateTime($from)); }
        if ($to)   { $qbIntern->andWhere('i.createdAt <= :to')  ->setParameter('to',   new \DateTime($to)); }
        $intern = (int) $qbIntern->getQuery()->getSingleScalarResult();

        // 3) Gesta
        $qbGesta = $em->createQueryBuilder()
            ->select('COUNT(i.id)')
            ->from(Invoice::class, 'i')
            ->join('i.client','c')
            ->where('c.type = :gesta')
            ->setParameter('gesta','gesta');
        if ($from) { $qbGesta->andWhere('i.createdAt >= :from')->setParameter('from', new \DateTime($from)); }
        if ($to)   { $qbGesta->andWhere('i.createdAt <= :to')  ->setParameter('to',   new \DateTime($to)); }
        $gesta = (int) $qbGesta->getQuery()->getSingleScalarResult();

        // 4) Impayées
        $qbUnpaid = $em->getRepository(Invoice::class)
            ->createQueryBuilder('i')
            ->select('COUNT(i.id)')
            ->where('i.status != :paid')
            ->setParameter('paid','payé');
        if ($from) { $qbUnpaid->andWhere('i.createdAt >= :from')->setParameter('from', new \DateTime($from)); }
        if ($to)   { $qbUnpaid->andWhere('i.createdAt <= :to')  ->setParameter('to',   new \DateTime($to)); }
        $unpaid = (int) $qbUnpaid->getQuery()->getSingleScalarResult();

        // 5) Récentes (derniers 5 jours, statut impayé)
        $since = new \DateTimeImmutable('-5 days');
        $qbRecent = $em->getRepository(Invoice::class)
            ->createQueryBuilder('i')
            ->select('COUNT(i.id)')
            ->where('i.createdAt >= :since')
            ->andWhere('i.status = :unpaid')
            ->setParameter('since', $since)
            ->setParameter('unpaid','impayé');
        $recent = (int) $qbRecent->getQuery()->getSingleScalarResult();

        return $this->json([
            'total'  => $total,
            'intern' => $intern,
            'gesta'  => $gesta,
            'unpaid' => $unpaid,
            'recent' => $recent,
        ]);
    }


    #[Route('/api/invoice/{id}/print', name: 'invoice_print', methods: ['GET'])]
    public function print(Invoice $invoice): Response
    {
        $fmt = new NumberFormatter('fr', NumberFormatter::SPELLOUT);
        // on récupère le montant float
        $amountFloat = (float) $invoice->getAmount();
        // on fait la mise en forme
        $amountInWords = ucfirst($fmt->format($amountFloat));

        return $this->render('facture/print.html.twig', [
            'invoice' => $invoice,
            'amountInWords' => $amountInWords,
        ]);
    }

    

    /**
     * GET /api/invoices
     * Liste des factures avec filtres : from,to,clientType,clientId,reference,status
     */
    #[Route('/api/invoices', name: 'invoices_list', methods: ['GET'])]
    public function listInvoices(Request $req, EntityManagerInterface $em): JsonResponse
    {
        $qb = $em->getRepository(Invoice::class)
                 ->createQueryBuilder('i')
                 ->join('i.client','c')
                 ->addSelect('c');

        if ($from = $req->query->get('from')) {
            $qb->andWhere('i.createdAt >= :from')->setParameter('from', new \DateTime($from));
        }
        if ($to = $req->query->get('to')) {
            $qb->andWhere('i.createdAt <= :to')->setParameter('to', new \DateTime($to));
        }
        if ($ct = $req->query->get('clientType')) {
            $qb->andWhere('c.type = :ct')->setParameter('ct', $ct);
        }
        if ($cid = $req->query->get('clientId')) {
            $qb->andWhere('i.client = :cid')->setParameter('cid',$cid);
        }
        if ($ref = $req->query->get('reference')) {
            $qb->andWhere('i.ref LIKE :ref')->setParameter('ref', "%$ref%");
        }
        if ($st = $req->query->get('status')) {
            $qb->andWhere('i.status = :st')->setParameter('st',$st);
        }

        $invoices = $qb->orderBy('i.createdAt','DESC')->getQuery()->getResult();
        $data = [];
        foreach ($invoices as $inv) {
            $data[] = [
                'id'          => $inv->getId(),
                'reference'   => $inv->getId(),
                'companyName' => $inv->getClient()->getCompanyName(),
                'clientType'  => $inv->getClient()->getType(),
                'clientSolde' => $inv->getClient()->getBalance(),
                'amount'      => $inv->getAmount(),
                'remain'      => $inv->getRemain(),
                'status'      => $inv->getStatus(),
                'createdAt'   => $inv->getCreatedAt()->format('Y-m-d'),
                'clientId'    => $inv->getClient()->getId(),
            ];
        }
        return $this->json(['data'=>$data]);
    }
    
     #[Route('/api/invoice/{id}/items', name: 'invoice_items', methods: ['GET'])]
    public function invoiceItems(int $id, EntityManagerInterface $em): JsonResponse
    {
        $inv = $em->getRepository(Invoice::class)->find($id);
        if (!$inv) {
            return $this->json(['error'=>'Introuvable'],404);
        }
        $items = $inv->getInvoiceItems();
        $out = [];
        foreach ($items as $it) {
            $out[] = [
                'description'=> $it->getDescrib(),
                'amount'     => $it->getAmount(),
                'quantity'   => $it->getQuantity(),
            ];
        }
        return $this->json($out);
    }
    /**
     * POST /api/invoice/{id}/pay
     * Payload: { amount, useBalance, [paymentMethod], [paymentReference] }
     */
    #[Route('/api/invoice/{id}/pay', name: 'invoice_pay', methods: ['POST'])]
    public function pay(int $id, Request $req, EntityManagerInterface $em, AccountTransactionRepository $repo): JsonResponse
    {
        $inv = $em->getRepository(Invoice::class)->find($id);
        if (!$inv) {
            return $this->json(['error'=>'Facture introuvable'],404);
        }

        $data = json_decode($req->getContent(), true); 

        $amount     = (float)$data['amount'];
        $useBalance = (isset($data['paymentMethod']) && $data['paymentMethod'] === 'compte') ? true : false;

        // 1. Appliquer sur la facture
        $remain = $inv->getRemain();
        if ($amount > $remain) {
            return $this->json(['error'=>'Montant > restant'],400);
        }

        $inv->setRemain($remain - $amount);
        if ((int)$inv->getRemain() === 0) {
            $inv->setStatus('payé');
        } else {
            $inv->setStatus('partiellement payé');
        }

        // 2. Si useBalance on débite le solde client
        $client = $inv->getClient();
        if ($useBalance) {

            $lastTransaction = $em->getRepository(AccountTransaction::class)
                ->findOneBy(['client' => $client], ['id' => 'DESC']);
            $balance = $lastTransaction ? $lastTransaction->getBalanceValue() : 0;

            $newBalance = $balance - $amount;

            $ctx = new AccountTransaction();
            $ctx->setIncome(0)
            ->setOutcome($amount)
            ->setAccountType('client')
            ->setPaymentMethod('espèce')
            ->setPaymentRef('')
            ->setReason('Débit solde client')
            ->setUpdatedAt(new \DateTimeImmutable())
            ->setDescrib('Débit solde client pour paiement facture')
            ->setClient($client)
            ->setBalanceValue($newBalance)
            ->setCreatedAt(new \DateTimeImmutable())
            ->setUser($this->getUser())
            ->setStatus('validé');
            
            $em->persist($ctx);
        }

        // Prendre la dernière transaction de type supplier pour ce client
        $lastSupplierTx = $repo->findOneBy(
            ['account_type' => 'supplier'],
            ['id' => 'DESC']
        );
        
        $balance_supplier = $lastSupplierTx ? $lastSupplierTx->getBalanceValue() : 0;
        $newBalance_supplier = $balance_supplier + $amount;

        if ($client->getType() === 'gesta' && $client->getCommittee() !== null && $client->getCommittee() !== '') {
            $com = new Commission();

            $com->setInvoice($inv) 
                ->setAmount($amount * 10/100)
                ->setCreatedAt(new \DateTimeImmutable())
                ->setStatus('en attente');

            $em->persist($com);
        }

        // 3. Enregistrer la transaction
        $tx = new AccountTransaction();
        $tx->setInvoice($inv)  // présuppose relation Invoice dans Transaction
           ->setIncome($amount)
           ->setOutcome(0)
           ->setAccountType('supplier')
           ->setReason('Paiement de facture')
            ->setUpdatedAt(new \DateTimeImmutable())
            ->setDescrib('Paiement de facture #'.$inv->getRef())
            ->setBalanceValue($newBalance_supplier)
           ->setPaymentMethod($useBalance ? 'compte local' : $data['paymentMethod']) 
           ->setPaymentRef($data['paymentReference'] ?? '')
           ->setStatus('en attente')
           ->setUser($this->getUser()) 
           ->setCreatedAt(new \DateTimeImmutable());
        $em->persist($tx);

        $em->flush();
        return $this->json(['success'=>true]);
    }

    /**
     * POST /api/invoice/{id}/cancel
     * Payload: { reason }
     */
    #[Route('/api/invoice/{id}/cancel', name: 'invoice_cancel', methods: ['POST'])]
    public function cancel(int $id, Request $req, EntityManagerInterface $em): JsonResponse
    {
        $inv = $em->getRepository(Invoice::class)->find($id);
        if (!$inv) {
            return $this->json(['error'=>'Facture introuvable'],404);
        }
        $data = json_decode($req->getContent(), true);
        if (empty($data['reason'])) {
            return $this->json(['error'=>'Justification requise'],400);
        }
        $inv->setStatus('annulé');
        $inv->setCancelReason($data['reason']); // présuppose champ cancelReason
        $em->flush();
        return $this->json(['success'=>true]);
    }

    /**
     * GET /api/invoice/{id}/transactions
     * Historique des paiements d'une facture
     */
    #[Route('/api/invoice/{id}/transactions', name: 'invoice_transactions', methods: ['GET'])]
    public function transactions(int $id, EntityManagerInterface $em): JsonResponse
    {
        $inv = $em->getRepository(Invoice::class)->find($id);
        if (!$inv) {
            return $this->json(['error'=>'Facture introuvable'],404);
        }
        $out = [];
        foreach ($inv->getAccountTransactions() as $tx) {
            $out[] = [
                'date'             => $tx->getCreatedAt()->format('Y-m-d'),
                'amount' => $tx->getAccountType() === 'supplier' ? $tx->getIncome() : $tx->getOutcome(),
                'paymentMethod'    => $tx->getPaymentMethod(),
                'paymentReference' => $tx->getPaymentRef(),
            ];
        }
        return $this->json($out);
    }

    /**
     * POST /api/invoice/add
     * Création d'une facture avec items
     * Payload: { createdAt, clientId, status, amount, items: [...] }
     */
    #[Route('/api/invoice/add', name: 'invoice_add', methods: ['POST'])]
    public function add(Request $req, EntityManagerInterface $em): JsonResponse
    {
        $data = json_decode($req->getContent(), true);
        if (!$data || !isset($data['createdAt'],$data['clientId'],$data['status'],$data['amount'],$data['items'])) {
            return $this->json(['error'=>'Payload invalide'],400);
        }
        $client = $em->getRepository(Client::class)->find($data['clientId']);
        if (!$client) {
            return $this->json(['error'=>'Client introuvable'],404);
        }
        try {
            $inv = new Invoice();
            $inv->setClient($client)
                ->setCreatedAt(new \DateTimeImmutable($data['createdAt']))
                ->setAmount($data['amount'])
                ->setRemain($data['amount'])
                ->setRef(substr('F'.date('ymdHis').mt_rand(10,99), 0, 10))
                ->setStatus($data['status']);
            $em->persist($inv);

            foreach ($data['items'] as $item) {
                $ii = new InvoiceItem();
                $ii->setInvoice($inv)
                   ->setDescrib($item['description'])
                   ->setAmount($item['amount'])
                   ->setQuantity($item['quantity']);
                $em->persist($ii);
            }

            $em->flush();
            return $this->json(['success'=>true]);
        } catch (\Exception $e) {
            return $this->json(['error'=>'Erreur création'],500);
        }
    }
}
