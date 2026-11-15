<?php

namespace OCA\Money\Db;

use JsonSerializable;

use OCP\AppFramework\Db\Entity;

/**
 * @method int getTransactionId()
 * @method void setTransactionId(int $transactionId)
 * @method int getDestAccountId()
 * @method void setDestAccountId(int $destAccountId)
 * @method float getValue()
 * @method void setValue(float $value)
 * @method float getConvertRate()
 * @method void setConvertRate(float $convertRate)
 * @method string getDescription()
 * @method void setDescription(string $description)
 * @method string getUserId()
 * @method void setUserId(string $userId)
 */
class Split extends Entity implements JsonSerializable {

  protected $transactionId;
  protected $destAccountId;
  protected $value;
  protected $convertRate;
  protected $description;
  protected $userId;

  public function __construct() {
    $this->addType('transactionId', 'integer');
    $this->addType('destAccountId', 'integer');
    $this->addType('value', 'float');
    $this->addType('convertRate', 'float');
  }

  public function jsonSerialize(): array {
    return [
      'id' => $this->id,
      'transactionId' => $this->transactionId,
      'destAccountId' => $this->destAccountId,
      'value' => $this->value,
      'convertRate' => $this->convertRate,
      'description' => $this->description
    ];
  }

}

?>
