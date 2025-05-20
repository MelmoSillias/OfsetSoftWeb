<?php

namespace App\Controller;

use App\Entity\AccountTransaction;
use App\Entity\Client;
use App\Entity\Invoice;
use App\Entity\InvoiceItem;
use App\Entity\RenewableInvoice;
use App\Entity\RenewableInvoiceItem;
use App\Repository\ClientRepository;
use Doctrine\Migrations\Tools\TransactionHelper;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class ClientController extends AbstractController
{ 

    #[Route('/client', name: 'app_client')]
    public function index(): Response
    {
        return $this->render('client/index.html.twig', [
            'controller_name' => 'ClientController',
        ]);
    }

    #[Route('/client/{id}/modify', name: 'client_modify_submit', methods: ['POST'])]
    public function modifySubmit(Client $client, Request $request, EntityManagerInterface $em): JsonResponse
    {
        // Récupération des champs
        $client->setCompanyName($request->request->get('companyName'))
               ->setDelegate($request->request->get('delegate'))
               ->setPhoneNumber($request->request->get('phoneNumber'))
               ->setAddress($request->request->get('address'))
               ->setType($request->request->get('type'))
               ->setCommittee($request->request->get('committee'));

        $em->flush();
        return $this->json(['success' => true]);
    }

    #[Route('/client/{id}/details', name: 'client_details_modal', methods: ['GET'])]
    public function ShowClient(Client $client): Response
    {
        // On peut récupérer ici factures ou transactions si besoin
        return $this->render('client/client_show.html.twig', [
            'controller_name' => 'ClientController',
            'client' => $client,
        ]);
    }

    #[Route('/api/client/{id}/smalldetails', name: 'client_small_details', methods: ['GET'])]
    public function smallDetails(Client $client ,Request $request, EntityManagerInterface $em): JsonResponse
    {
        if (!$client) {
            return $this->json(['error' => 'Client not found'], 404);
        }

        return $this->json([
            'id' => $client->getId(),
            'companyName' => $client->getCompanyName(),
            'phoneNumber' => $client->getPhoneNumber(),
            'type' => $client->getType(),
            'address' => $client->getAddress(),
            'delegate' => $client->getDelegate(),
            'committee' => $client->getCommittee(), 
        ]);
    }

    #[Route('/client/{id}/accompte', name: 'client_accompte_submit', methods: ['POST'])]
    public function accompteSubmit(Client $client, Request $request, EntityManagerInterface $em): JsonResponse
    {
        $amount = (float) $request->request->get('amount', 0);
        $ref_payment = $request->request->get('reference', ''); // Référence de paiement
        $method_payment = $request->request->get('mode', ''); // Méthode de paiement
        $note = $request->request->get('note', ''); // Note de la transaction
        // Récupérer le dernier solde (balance_at) de ce client
        $lastTransaction = $em->getRepository(AccountTransaction::class)
            ->findOneBy(['client' => $client], ['id' => 'DESC']);

        $last_balance_at = $lastTransaction ? $lastTransaction->getBalanceValue() : 0;

        $new_balance_at = $last_balance_at + $amount;

        if ($amount <= 0) {
            return $this->json(['error' => 'Invalid amount'], 400);
        }

        // 1. Créer un enregistrement de transaction (acompte client)
        $tx = new AccountTransaction();
        $tx->setClient($client)
           ->setIncome($amount)
           ->setBalanceValue($new_balance_at)
           ->setOutcome(0)
           ->setAccountType('client')
           ->setReason('Versement compte client')
           ->setPaymentRef($ref_payment)
            ->setDescrib($note)
           ->setStatus('validé')
           ->setCreatedAt(new \DateTimeImmutable())
           ->setPaymentMethod($method_payment)// à adapter si besoin
           ->setUser($this->getUser());
        $em->persist($tx);

        $em->flush();
        return $this->json(['success' => true]);
    }

    #[Route('/api/clients', name: 'api_clients_list', methods: ['GET'])]
    public function clientsList(EntityManagerInterface $em): JsonResponse
    {
        $clients = $em->getRepository(Client::class)->findAll();
        $data = [];

        foreach ($clients as $client) {
    
            // Récupérer le solde (balance_value) de la dernière transaction du client
            $lastTransaction = $em->getRepository(AccountTransaction::class)
                ->findOneBy(['client' => $client], ['id' => 'DESC']);
            $balance = $lastTransaction ? $lastTransaction->getBalanceValue() : 0;
            

            $data[] = [
                'id'           => $client->getId(),
                'type'         => $client->getType(),
                'companyName'  => $client->getCompanyName(),
                'phoneNumber'  => $client->getPhoneNumber(),
                'balance'      => $balance,
            ];
        }

        return $this->json(['data' => $data]);
    }

    #[Route('/api/clients/stats', name: 'api_clients_stats', methods: ['GET'])]
    public function clientsStats(EntityManagerInterface $em): JsonResponse
    {
        $repo = $em->getRepository(Client::class);
        $total  = count($repo->findAll());
        $gesta  = count($repo->findBy(['type' => 'gesta']));
        $intern = count($repo->findBy(['type' => 'intern']));

        return $this->json([
            'total'  => $total,
            'gesta'  => $gesta,
            'intern' => $intern,
        ]);
    }

    #[Route('/api/client/add', name: 'api_client_add', methods: ['POST'])]
    public function clientAdd(Request $request, EntityManagerInterface $em): JsonResponse
    {
        $client = new Client();
        $client->setCompanyName($request->request->get('companyName'))
               ->setDelegate($request->request->get('delagate'))
               ->setPhoneNumber($request->request->get('phoneNumber'))
               ->setAddress($request->request->get('address'))
               ->setType($request->request->get('type'))
               ->setCommittee($request->request->get('committee'))
               ->setISActive(true); 
        $em->persist($client);
        $em->flush();
        return $this->json(['success' => true]);
    }

    #[Route('/api/client/{id}/deactivate', name: 'api_client_deactivate', methods: ['POST'])]
    public function clientDeactivate(Client $client, EntityManagerInterface $em): JsonResponse
    {
        // Désactivation soft
        $client->setIsActive(false);
        $em->flush();
        return $this->json(['success' => true]);
    }

    #[Route('/api/client/{id}/stats', name: 'client_stats', methods: ['GET'])]
    public function stats(Client $client, EntityManagerInterface $em): JsonResponse
    {
        // Factures du client
        $invoiceRepo = $em->getRepository(Invoice::class);
        $allInvoices   = $invoiceRepo->findBy(['client' => $client]);
        $totalInvoices = count($allInvoices);
        $unpaidInvoices= count($invoiceRepo->findBy(['client'=>$client, 'status'=>'en cours']));

        $lastTransaction = $em->getRepository(AccountTransaction::class)
                ->findOneBy(['client' => $client], ['id' => 'DESC']);
            $balance = $lastTransaction ? $lastTransaction->getBalanceValue() : 0;

        // Renouvelables actives
        $renewRepo = $em->getRepository(RenewableInvoice::class);
        $activeRenew = count($renewRepo->findBy(['client'=>$client, 'state'=>'active']));

        return $this->json([
            'balance'          => $balance,
            'totalInvoices'    => $totalInvoices,
            'unpaidInvoices'   => $unpaidInvoices,
            'activeRenewables' => $activeRenew,
        ]);
    }

    /**
     * Liste des factures renouvelables du client
     */
    #[Route('/api/client/{id}/renewable-factures', name: 'client_renewables', methods: ['GET'])]
    public function listRenewables(Client $client, EntityManagerInterface $em): JsonResponse
    {
        $list = [];
        $repo = $em->getRepository(RenewableInvoice::class);
        foreach ($repo->findBy(['client'=>$client]) as $rf) {
            $list[] = [
                'id'       => $rf->getId(),
                'period'   => $rf->getPeriod(),
                'amount'   => $rf->getAmount(),
                'nextDate' => $rf->getNextDate()->format('Y-m-d'),
                'status'   => $rf->getStatus(),
            ];
        }
        return $this->json($list);
    }

    /**
     * Créer une nouvelle facture renouvelable pour le client
     */
    #[Route('/api/client/{id}/renewable-facture', name: 'client_renewable_add', methods: ['POST'])]
    public function addRenewable(Client $client, Request $request, EntityManagerInterface $em): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        if (!$data) {
            return $this->json(['error'=>'Payload invalide'], 400);
        }

        try {
            $rf = new RenewableInvoice();
            $rf->setClient($client)
               ->setPeriodVal($data['period'])
               ->setStartAt(new \DateTimeImmutable($data['startDate'])) 
               ->setState('active')
               ->setNextDate(new \DateTimeImmutable($data['startDate'])); // initial

            $em->persist($rf);
            // Items
            foreach ($data['items'] as $item) {
                $ri = new RenewableInvoiceItem();
                $ri->setRenewableInvoice($rf)
                   ->setDescrib($item['description'])
                   ->setAmount($item['amount'])
                   ->setQuantity($item['quantity']);
                $em->persist($ri);
            }

            $em->flush();
            return $this->json(['success'=>true]);
        } catch (\Exception $e) {
            return $this->json(['error'=>'Erreur création'], 500);
        }
    }

    /**
     * Supprimer une facture renouvelable
     */
    #[Route('/api/renewable-facture/{id}', name: 'renewable_delete', methods: ['DELETE'])]
    public function deleteRenewable(int $id, EntityManagerInterface $em): JsonResponse
    {
        $rf = $em->getRepository(RenewableInvoice::class)->find($id);
        if (!$rf) {
            return $this->json(['error'=>'Introuvable'], 404);
        }
        $em->remove($rf);
        $em->flush();
        return $this->json(['success'=>true]);
    }

    /**
     * Liste des factures ponctuelles du client (+ filtres)
     */
    #[Route('/api/client/{id}/invoices', name: 'client_invoices', methods: ['GET'])]
    public function listInvoices(Client $client, Request $request, EntityManagerInterface $em): JsonResponse
    {
        $qb = $em->getRepository(Invoice::class)
                 ->createQueryBuilder('i')
                 ->where('i.client = :client')
                 ->setParameter('client', $client);

        if ($status = $request->query->get('status')) {
            $qb->andWhere('i.status = :status')
               ->setParameter('status', $status);
        }
        if ($from = $request->query->get('from')) {
            $qb->andWhere('i.createdAt >= :from')
               ->setParameter('from', new \DateTime($from));
        }
        if ($to = $request->query->get('to')) {
            $qb->andWhere('i.createdAt <= :to')
               ->setParameter('to', new \DateTime($to));
        }

        $list = [];
        foreach ($qb->orderBy('i.createdAt','DESC')->getQuery()->getResult() as $inv) {
            $list[] = [
                'id'         => $inv->getId(),
                'reference'  => $inv->getId(), // ou un champ spécifique
                'amount'     => $inv->getAmount(),
                'remain'     => $inv->getRemain(),
                'status'     => $inv->getStatus(),
                'createdAt'  => $inv->getCreatedAt()->format('Y-m-d'),
            ];
        }
        return $this->json(['data'=>$list]);
    }

    /**
     * Liste des items d'une facture
     */
    #[Route('/api/invoice/{id}/items', name: 'invoice_items', methods: ['GET'])]
    public function invoiceItems(int $id, EntityManagerInterface $em): JsonResponse
    {
        $inv = $em->getRepository(Invoice::class)->find($id);
        if (!$inv) {
            return $this->json(['error'=>'Introuvable'],404);
        }
        $items = $em->getRepository(InvoiceItem::class)->findBy(['facture'=>$inv]);
        $out = [];
        foreach ($items as $it) {
            $out[] = [
                'description'=> $it->getDescription(),
                'amount'     => $it->getAmount(),
                'quantity'   => $it->getQuantity(),
            ];
        }
        return $this->json($out);
    }

    /**
     * Créer une facture ponctuelle pour le client
     */
    #[Route('/api/client/{id}/invoice', name: 'client_invoice_add', methods: ['POST'])]
    public function addInvoice(Client $client, Request $request, EntityManagerInterface $em): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        if (!$data) {
            return $this->json(['error'=>'Payload invalide'],400);
        }
        try {
            $inv = new Invoice();
            $inv->setClient($client)
                ->setCreatedAt(new \DateTimeImmutable($data['createdAt']))
                ->setUpdatedAt(new \DateTimeImmutable())
                ->setAmount($data['amount'])
                ->setRemain($data['amount'])
                ->setStatus($data['status']);

            $em->persist($inv);
            // Items
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
            return $this->json(['error'=>'Erreur création facture'],500);
        }
    }

    /**
     * Liste des transactions du client (+ filtre type)
     */
    #[Route('/api/client/{id}/transactions', name: 'client_transactions', methods: ['GET'])]
    public function listTransactions(Client $client, Request $request, EntityManagerInterface $em): JsonResponse
    {
        $type = $request->query->get('type');
        $repo = $em->getRepository(AccountTransaction::class);

        $qb = $repo->createQueryBuilder('t')
            ->where('t.client = :client')
            ->setParameter('client', $client);

        if ($type === 'entrée') {
            $qb->andWhere('t.income > 0');
        } elseif ($type === 'sortie') {
            $qb->andWhere('t.outcome > 0');
        }

        $qb->orderBy('t.createdAt', 'DESC');
        $transactions = $qb->getQuery()->getResult();

        $list = [];
        foreach ($transactions as $tx) {
            // Déterminer le type dynamiquement
            $txType = $tx->getIncome() > 0 ? 'entrée' : ($tx->getOutcome() > 0 ? 'sortie' : 'autre');
            $list[] = [
                'date'             => $tx->getCreatedAt()->format('Y-m-d'),
                'type'             => $txType,
                'amount'           => $tx->getIncome() - $tx->getOutcome(),
                'paymentMethod'    => $tx->getPaymentMethod(),
                'paymentReference' => $tx->getPaymentRef(),
            ];
        }
        return $this->json(['data' => $list]);
    }

}
