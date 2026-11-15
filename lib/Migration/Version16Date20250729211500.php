<?php

declare(strict_types=1);

namespace OCA\Money\Migration;

use Closure;
use OCP\DB\ISchemaWrapper;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;

/**
 * Auto-generated migration step: Please modify to your needs!
 */
class Version16Date20250729211500 extends SimpleMigrationStep {

  /**
   * @param IOutput $output
   * @param Closure $schemaClosure The `\Closure` returns a `ISchemaWrapper`
   * @param array $options
   * @return null|ISchemaWrapper
   */
  public function changeSchema(IOutput $output, Closure $schemaClosure, array $options): ?ISchemaWrapper {
    $schema = $schemaClosure();

    $transactionsTable = $schema->getTable('money_transactions');

    // The column was already created as a string type in Version02Date20220821220000,
    // so only the length/default/notnull options need adjusting here. Typed setters are
    // used instead of a changeColumn() options array with a Doctrine DBAL type instance,
    // which is no longer part of the public API in Nextcloud 35's DBAL wrapper.
    $transactionsDescriptionColumn = $transactionsTable->getColumn('description');
    $transactionsDescriptionColumn->setLength(1024);
    $transactionsDescriptionColumn->setDefault('');
    $transactionsDescriptionColumn->setNotnull(false);

    $splitsTable = $schema->getTable('money_splits');

    $splitsDescriptionColumn = $splitsTable->getColumn('description');
    $splitsDescriptionColumn->setLength(1024);
    $splitsDescriptionColumn->setDefault('');
    $splitsDescriptionColumn->setNotnull(false);

    return $schema;
  }

}
