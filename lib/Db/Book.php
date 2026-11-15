<?php

namespace OCA\Money\Db;

use JsonSerializable;

use OCP\AppFramework\Db\Entity;

/**
 * @method string getName()
 * @method void setName(string $name)
 * @method string getDescription()
 * @method void setDescription(string $description)
 * @method string getUserId()
 * @method void setUserId(string $userId)
 */
class Book extends Entity implements JsonSerializable {

  protected $name;
  protected $description;
  protected $userId;

  public function __construct() {}

  public function jsonSerialize(): array {
    return [
      'id' => $this->id,
      'name' => $this->name,
      'description' => $this->description,
    ];
  }

}

?>
