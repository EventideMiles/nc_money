<?php

namespace OCA\Money\Service;

use Exception;

use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Db\MultipleObjectsReturnedException;

use OCA\Money\Db\Split;
use OCA\Money\Db\SplitMapper;
use OCA\Money\Db\TransactionMapper;
use OCA\Money\Db\AccountMapper;

use OCA\Money\AuditTrait;

class SplitService {

  use AuditTrait;

  private $splitMapper;
  private $transactionMapper;
  private $accountMapper;

  public function __construct(SplitMapper $splitMapper, TransactionMapper $transactionMapper, AccountMapper $accountMapper) {
    $this->splitMapper = $splitMapper;
    $this->transactionMapper = $transactionMapper;
    $this->accountMapper = $accountMapper;
  }

  public function findAll($userId) {
    return $this->splitMapper->findAll($userId);
  }

  private function handleException($e) {
    if ($e instanceof DoesNotExistException ||
        $e instanceof MultipleObjectsReturnedException) {
          throw new NotFoundException($e->getMessage());
    } else {
      throw $e;
    }
  }

  /**
   * Ensures the transaction and destination account referenced by a split
   * belong to the requesting user — a split must never point at another
   * user's rows (cross-user IDOR).
   */
  private function assertOwnership($transactionId, $destAccountId, $userId) {
    $this->transactionMapper->find($transactionId, $userId);
    $this->accountMapper->find($destAccountId, $userId);
  }

  public function find($id, $userId) {
    try {
      return $this->splitMapper->find($id, $userId);
    } catch(Exception $e) {
      $this->handleException($e);
    }
  }

  public function findSplitsOfTransaction($transactionId, $userId) {
    try {
      return $this->splitMapper->findAllSplitsOfTransaction($userId, $transactionId);
    } catch(Exception $e) {
      $this->handleException($e);
    }
  }

  public function create($transactionId, $destAccountId, $value, $convertRate, $description, $userId) {
    try {
      $this->assertOwnership($transactionId, $destAccountId, $userId);
    } catch(Exception $e) {
      $this->handleException($e);
    }

    $split = new Split();
    $split->setUserId($userId);

    $split->setDescription($description);
    $split->setTransactionId($transactionId);
    $split->setDestAccountId($destAccountId);
    $split->setConvertRate($convertRate);
    $split->setValue($value);

    $insertedSplit = $this->splitMapper->insert($split);

    $this->emitCreateAuditEvent($userId, 'split', $insertedSplit);

    return $insertedSplit;
  }

  public function update($id, $transactionId, $destAccountId, $value, $convertRate, $description, $userId) {
    try {
      $split = $this->splitMapper->find($id, $userId);
      $originalSplit = clone $split;

      $this->assertOwnership($transactionId, $destAccountId, $userId);

      $split->setDescription($description);
      $split->setTransactionId($transactionId);
      $split->setDestAccountId($destAccountId);
      $split->setConvertRate($convertRate);
      $split->setValue($value);

      $updatedSplit = $this->splitMapper->update($split);

      $this->emitUpdateAuditEvents($userId, 'split', $updatedSplit, $originalSplit);

      return $updatedSplit;
    } catch(Exception $e) {
      $this->handleException($e);
    }
  }

  public function delete($id, $userId) {
    try {
      $split = $this->splitMapper->find($id, $userId);
      $this->splitMapper->delete($split);

      $this->emitDeleteAuditEvent($userId, 'split', $split);

      return $split;
    } catch(Exception $e) {
      $this->handleException($e);
    }
  }

}

?>
