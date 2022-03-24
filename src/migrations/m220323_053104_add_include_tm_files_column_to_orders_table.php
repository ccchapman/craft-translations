<?php

namespace acclaro\translations\migrations;

use acclaro\translations\Constants;
use craft\db\Migration;

/**
 * m220323_053104_add_include_tm_files_column_to_orders_table migration.
 */
class m220323_053104_add_include_tm_files_column_to_orders_table extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        echo "Adding translations_orders includeTmFiles column...\n";
		$this->addColumn(Constants::TABLE_ORDERS, 'includeTmFiles', $this->integer()->defaultValue(0));
		$this->addColumn(Constants::TABLE_FILES, 'dateTmSync', $this->dateTime());
		echo "Done adding translations_files includeTmFiles column...\n";
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        echo "m220323_053104_add_include_tm_files_column_to_orders_table cannot be reverted.\n";
        return false;
    }
}
