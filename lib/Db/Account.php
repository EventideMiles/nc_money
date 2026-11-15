<?php

namespace OCA\Money\Db;

use JsonSerializable;

use OCP\AppFramework\Db\Entity;

/**
 * @method string getName()
 * @method void setName(string $name)
 * @method int getType()
 * @method void setType(int $type)
 * @method string getCurrency()
 * @method void setCurrency(string $currency)
 * @method string getDescription()
 * @method void setDescription(string $description)
 * @method bool getHidden()
 * @method void setHidden(bool $hidden)
 * @method bool getArchived()
 * @method void setArchived(bool $archived)
 * @method string getIcon()
 * @method void setIcon(string $icon)
 * @method string getUserId()
 * @method void setUserId(string $userId)
 * @method int getBookId()
 * @method void setBookId(int $bookId)
 * @method string getExtraData()
 * @method void setExtraData(string $extraData)
 */
class Account extends Entity implements JsonSerializable {

  protected $name;
  protected $type;
  protected $currency;
  protected $description;
  protected $hidden;
  protected $archived;
  protected $icon;
  protected $userId;
  protected $bookId;
  protected $extraData;

  public function __construct() {
    $this->addType('type', 'integer');
  }

  public function jsonSerialize(): array {
    return [
      'id' => $this->id,
      'name' => $this->name,
      'type' => $this->type,
      'currency' => $this->currency,
      'description' => $this->description,
      'hidden' => $this->hidden,
      'archived' => $this->archived,
      'icon' => $this->icon,
      'extraData' => $this->extraData,
      'bookId' => $this->bookId
    ];
  }

}

?>
