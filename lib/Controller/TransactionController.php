<?php

namespace OCA\Money\Controller;

use OCP\IRequest;
use OCP\IDBConnection;

use OCA\Money\Controller\MoneyController;
use OCA\Money\Service\TransactionService;
use OCA\Money\Service\SplitService;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;

class TransactionController extends MoneyController {

  private TransactionService $transactionService;
  private SplitService $splitService;

  public function __construct(string $AppName, IRequest $request, IDBConnection $db, $UserId, TransactionService $transactionService, SplitService $splitService) {
    parent::__construct($AppName, $request, $db, $UserId);

    $this->transactionService = $transactionService;
    $this->splitService = $splitService;
  }

  /**
  * @param int $accountId
  * @param int $resultOffset
  * @param int $resultLimit
  */
  #[NoAdminRequired]
  public function getTransactionsForAccount($accountId, $resultOffset = 0, $resultLimit = 50) {
    $transactions = $this->transactionService->findTransactionsOfAccount($accountId, $this->userId, $resultOffset, $resultLimit);

    $result = [];

    foreach($transactions as &$transaction) {
      $number = array_push($result, $transaction->jsonSerialize());
      $result[$number-1]['splits'] = $this->getSplitsForTransaction($transaction->id);
    }
    unset($transaction);

    return $result;
  }

  /**
  * @param int $accountId
  * @param string $startDate
  * @param string $endDate
  */
  #[NoAdminRequired]
  public function getTransactionsForAccountByDate($accountId, $startDate, $endDate) {
    $transactions = $this->transactionService->findTransactionsOfAccountByDate($accountId, $this->userId, $startDate, $endDate);

    $result = [];

    foreach($transactions as &$transaction) {
      $number = array_push($result, $transaction->jsonSerialize());
      $result[$number-1]['splits'] = $this->getSplitsForTransaction($transaction->id);
    }
    unset($transaction);

    return $result;
  }

  /**
  * @param int $transactionId
  */
  #[NoAdminRequired]
  public function getTransaction($transactionId) {
    $transaction = $this->transactionService->find($transactionId, $this->userId);

    $result = $transaction->jsonSerialize();
    $result['splits'] = $this->getSplitsForTransaction($transactionId);

    return $result;
  }

  /**
   * @param int $id
   */
  #[NoAdminRequired]
  public function updateTransaction($id, $description, $date) {
    return $this->handleNotFound(function() use ($id, $description, $date) {
      $transaction = $this->transactionService->update($id, $description, $date, $this->userId);
      return $this->getTransaction($transaction->id);
    });
  }

  /**
   * @param int $id
   */
  #[NoAdminRequired]
  public function deleteTransaction($id) {
    return $this->handleNotFound(function() use ($id) {
      $transaction = $this->transactionService->find($id, $this->userId);
      return $this->transactionService->delete($transaction);
    });
  }

  /**
   * @param $date
   * @param string $description
   */
  #[NoAdminRequired]
  public function addTransaction($date, $description) {
    return $this->transactionService->create($description, $date, $this->userId);
  }

  /**
   * @param int $resultOffset
   * @param int $resultLimit
   */
  #[NoAdminRequired]
  public function getUnbalancedTransactions($resultOffset = 0, $resultLimit = 50) {
    $transactions = $this->transactionService->findUnbalancedTransactions($this->userId, $resultOffset, $resultLimit);

    $results = [];

    foreach($transactions as &$transaction) {
      $resultItem = $transaction->jsonSerialize();
      $resultItem['splits'] = $this->getSplitsForTransaction($resultItem['id']);

      array_push($results, $resultItem);
    }

    return $results;
  }

  private function getSplitsForTransaction($transactionId) {
    return $this->splitService->findSplitsOfTransaction($transactionId, $this->userId);
  }

}
