<?php

namespace OCA\Money\Db;

use JsonSerializable;

use OCP\AppFramework\Db\Entity;

/**
 * @method string getDescription()
 * @method void setDescription(string $description)
 * @method string getDate()
 * @method void setDate(string $date)
 * @method int getTimestampAdded()
 * @method void setTimestampAdded(int $timestampAdded)
 * @method string getUserId()
 * @method void setUserId(string $userId)
 */
class Transaction extends Entity implements JsonSerializable {

  protected $description;
  protected $date;
  protected $timestampAdded;
  protected $userId;

  public function __construct() {

  }

  public function jsonSerialize(): array {
    return [
      'id' => $this->id,
      'description' => $this->description,
      'date' => $this->date,
      'timestampAdded' => $this->timestampAdded,
    ];
  }

}

?>
