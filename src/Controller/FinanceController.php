<?php

namespace App\Controller;

use App\Entity\AccountTransaction;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class FinanceController extends AbstractController
{
    #[Route('/finance', name: 'app_finance')]
    public function index(): Response
    {
        return $this->render('finance/index.html.twig', [
            'controller_name' => 'FinanceController',
        ]);
    }

    private function getLastBalance(string $type, EntityManagerInterface $em): float
    {
        $last = $em->getRepository(AccountTransaction::class)
            ->findBy(['account_type' => $type], ['createdAt' => 'DESC'], 1);
        return $last ? (float)$last[0]->getBalanceValue() : 0.0;
    }

    /**
     * GET /api/account-transactions
     * Query params: accountType, from, to, paymentMethod, status
     */
    #[Route('/api/account-transactions', name: 'list', methods: ['GET'])]
    public function list(Request $req, EntityManagerInterface $em): JsonResponse
    {
        $qb = $em->getRepository(AccountTransaction::class)
            ->createQueryBuilder('t');

        if ($type = $req->query->get('accountType')) {
            $qb->andWhere('t.account_type = :type')->setParameter('type', $type);
        }
        if ($from = $req->query->get('from')) {
            $qb->andWhere('t.createdAt >= :from')->setParameter('from', new \DateTime($from));
        }
        if ($to = $req->query->get('to')) {
            $qb->andWhere('t.createdAt <= :to')->setParameter('to', new \DateTime($to));
        } 
        if ($status = $req->query->get('status')) {
            $qb->andWhere('t.status = :st')->setParameter('st', $status);
        }

        $data = [];
        foreach ($qb->orderBy('t.createdAt','DESC')->getQuery()->getResult() as $t) {
            $data[] = [
                'id'            => $t->getId(),
                'createdAt'     => $t->getCreatedAt()->format('Y-m-d'),
                'income'        => $t->getIncome(),
                'outcome'       => $t->getOutcome(),
                'balanceValue'  => $t->getBalanceValue(),
                'paymentMethod' => $t->getPaymentMethod(),
                'paymentRef'    => $t->getPaymentRef(),
                'status'        => $t->getStatus(),
                'describ'       => $t->getDescrib(),
                'reason'        => $t->getReason(),
            ];
        }

        return $this->json(['data' => $data]);
    }

    #[Route('/api/account-transactions-validations', name: 'list_validation', methods: ['GET'])]
    public function listValidations(Request $req, EntityManagerInterface $em): JsonResponse
    {
        $qb = $em->getRepository(AccountTransaction::class)
            ->createQueryBuilder('t')
            ->andWhere('t.status = :status')->setParameter('status', 'en attente'); 

        $data = [];
        foreach ($qb->orderBy('t.createdAt','DESC')->getQuery()->getResult() as $t) {
            $data[] = [
                'id'            => $t->getId(),
                'createdAt'     => $t->getCreatedAt()->format('Y-m-d'),
                'amount' => $t->getIncome() > 0 
                    ? '+' . $t->getIncome() 
                    : '-' . $t->getOutcome(),
                'user' =>  $t->getUser()->getFullName(),
                'balanceValue'  => $t->getBalanceValue(),
                'account_type' => $t->getAccountType(),
                'paymentMethod' => $t->getPaymentMethod(),
                'paymentRef'    => $t->getPaymentRef(),
                'status'        => $t->getStatus(),
                'describ'       => $t->getDescrib(),
                'reason'        => $t->getReason(),
            ];
        }

        return $this->json(['data' => $data]);
    }

    /**
     * GET /api/account-transactions/{id}
     * Détails pour le reçu
     */
    #[Route('/api/account-transactions/{id}', name: 'get', methods: ['GET'])]
    public function getOne(AccountTransaction $t): JsonResponse
    {
        return $this->json([
            'id'            => $t->getId(),
            'createdAt'     => $t->getCreatedAt()->format('Y-m-d'),
            'income'        => $t->getIncome(),
            'outcome'       => $t->getOutcome(),
            'balanceValue'  => $t->getBalanceValue(),
            'paymentMethod' => $t->getPaymentMethod(),
            'paymentRef'    => $t->getPaymentRef(),
            'status'        => $t->getStatus(),
            'describ'       => $t->getDescrib(),
            'reason'        => $t->getReason(),
        ]);
    }

    /**
     * POST /api/account-transactions
     * Création d'une transaction « supplier » ou « expense »
     * Payload JSON: { createdAt, income, outcome, accountType, paymentMethod, paymentRef, status, describ, reason }
     */
    #[Route('/api/account-transactions', name: 'create', methods: ['POST'])]
    public function create(Request $req, EntityManagerInterface $em): JsonResponse
    {
        $data = json_decode($req->getContent(), true);
        if (!isset(
            $data['createdAt'], $data['income'], $data['outcome'],
            $data['accountType'], $data['paymentMethod'], $data['paymentRef'],
            $data['status'], $data['describ'], $data['reason']
        )) {
            return $this->json(['error' => 'Payload invalide'], 400);
        }

        $balancePrev = $this->getLastBalance($data['accountType'], $em);
        $newBalance  = $balancePrev + (float)$data['income'] - (float)$data['outcome'];

        $t = new AccountTransaction();
        $t->setCreatedAt(new \DateTimeImmutable($data['createdAt']))
          ->setUpdatedAt(new \DateTimeImmutable())
          ->setIncome((string)$data['income'])
          ->setOutcome((string)$data['outcome'])
          ->setAccountType($data['accountType'])
          ->setBalanceValue((string)$newBalance)
          ->setPaymentMethod($data['paymentMethod'])
          ->setPaymentRef($data['paymentRef'])
          ->setStatus($data['status'])
          ->setDescrib($data['describ'])
          ->setReason($data['reason']) 
          ->setUser($this->getUser());

        $em->persist($t);
        $em->flush();

        return $this->json(['success' => true, 'id' => $t->getId()]);
    }

    /**
     * POST /api/account-transactions/transfer
     * Transfert de « supplier » vers « expense »
     * Payload JSON: { amount, reason }
     */
    #[Route('/api/account-transactions/transfer', name: 'transfer', methods: ['POST'])]
    public function transfer(Request $req, EntityManagerInterface $em): JsonResponse
    {
        $data = json_decode($req->getContent(), true);
        if (!isset($data['amount'])) {
            return $this->json(['error' => 'Payload invalide'], 400);
        }

        $amount       = (float)$data['amount'];
        $reason       = $data['reason'] ?? '';
        $user         = $this->getUser();

        // Supplier outcome
        $balSupPrev   = $this->getLastBalance('supplier', $em);
        $balSupNew    = $balSupPrev - $amount;
        $t1 = new AccountTransaction();
        $t1->setCreatedAt(new \DateTimeImmutable())
           ->setUpdatedAt(new \DateTimeImmutable())
           ->setIncome('0')
           ->setOutcome((string)$amount)
           ->setAccountType('supplier')
           ->setBalanceValue((string)$balSupNew)
           ->setStatus('completed')
           ->setPaymentMethod('transfer')
           ->setPaymentRef('to-expense')
           ->setDescrib('Transfert vers dépenses')
           ->setReason($reason)
           ->setUser($user);
        $em->persist($t1);

        // Expense income
        $balExpPrev   = $this->getLastBalance('expense', $em);
        $balExpNew    = $balExpPrev + $amount;
        $t2 = new AccountTransaction();
        $t2->setCreatedAt(new \DateTimeImmutable())
           ->setUpdatedAt(new \DateTimeImmutable())
           ->setIncome((string)$amount)
           ->setOutcome('0')
           ->setAccountType('expense')
           ->setBalanceValue((string)$balExpNew)
           ->setStatus('completed')
           ->setPaymentMethod('transfer')
           ->setPaymentRef('from-supplier')
           ->setDescrib('Transfert depuis approvisionnement')
           ->setReason($reason)
           ->setUser($user);
        $em->persist($t2);

        $em->flush();
        return $this->json(['success' => true]);
    }

    /**
     * POST /api/account-transactions/{id}/validate
     * Valider une transaction en attente
     */
    #[Route('/api/account-transactions/{id}/validate', name: 'validate', methods: ['POST'])]
    public function validate(AccountTransaction $t, EntityManagerInterface $em): JsonResponse
    {
        if ($t->getStatus() !== 'en attente') {
            return $this->json(['error' => 'La transaction n\'est pas en attente'], 400);
        }

        $t->setStatus('validé')
          ->setValidateAt(new \DateTimeImmutable())
          ->setValidationUser($this->getUser())
          ->setUpdatedAt(new \DateTimeImmutable());

        $em->flush();

        return $this->json(['success' => true]);
    }

    #[Route('/transaction/{id}/print', name: 'transaction', methods: ['GET'])]
    public function transactionReceipt(AccountTransaction $transaction): Response
    {
        return $this->render('finance/receipt.html.twig', [
            'transaction' => $transaction,
        ]);
    }
}
