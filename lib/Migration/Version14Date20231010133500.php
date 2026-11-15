<?php

declare(strict_types=1);

namespace OCA\Money\Migration;

use Closure;
use OCP\DB\ISchemaWrapper;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;
use OCP\DB\Types;

/**
 * Auto-generated migration step: Please modify to your needs!
 */
class Version14Date20231010133500 extends SimpleMigrationStep {

  /**
   * @param IOutput $output
   * @param Closure $schemaClosure The `\Closure` returns a `ISchemaWrapper`
   * @param array $options
   * @return null|ISchemaWrapper
   */
  public function changeSchema(IOutput $output, Closure $schemaClosure, array $options): ?ISchemaWrapper {
    $schema = $schemaClosure();

    $accountsTable = $schema->getTable('money_accounts');

    if(!$accountsTable->hasColumn('extra_data')) {
      $accountsTable->addColumn('extra_data', Types::TEXT, [
        'default' => '',
        'notnull' => false
      ]);
    }

    // The column was already created as a string type in Version02Date20220821220000,
    // so only the length/default/notnull options need adjusting here. Typed setters are
    // used instead of a changeColumn() options array with a Doctrine DBAL type instance,
    // which is no longer part of the public API in Nextcloud 35's DBAL wrapper.
    $descriptionColumn = $accountsTable->getColumn('description');
    $descriptionColumn->setLength(1024);
    $descriptionColumn->setDefault('');
    $descriptionColumn->setNotnull(false);

    return $schema;
  }

}
