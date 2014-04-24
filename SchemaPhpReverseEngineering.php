<?php
/**
 * User  : Nikita.Makarov
 * Date  : 4/24/14
 * Time  : 3:10 PM
 * E-Mail: nikita.makarov@effective-soft.com
 */

/**
 * Class SchemaPhpReverseEngineering
 */
class SchemaPhpReverseEngineering
{
    /**
     * @var \Doctrine\DBAL\Schema\Schema
     */
    private $schema;

    /**
     * @var string
     */
    private $name = 'schema';

    /**
     * @var array
     */
    private $tables = array();
    /**
     * @var array
     */
    private $indices = array();
    /**
     * @var array
     */
    private $sequences = array();
    /**
     * @var array
     */
    private $columns = array();

    /**
     * @param \Doctrine\DBAL\Schema\Schema $schema
     *
     * @param string                       $name
     *
     * @throws Exception
     */
    public function __construct($schema, $name = 'schema')
    {
        if (!($schema instanceof \Doctrine\DBAL\Schema\Schema)) {
            throw new \Exception('Kill me twice, but you\'ve passed not Schema');
        }

        $this->schema = $schema;

        //TODO Add validation for name
        $this->name = $name;
    }

    protected function _tables()
    {
        foreach ($this->schema->getTables() as $table) {
            $this->tables[$table->getName()] = '$' . $table->getName() . ' = $' . $this->name . '->createTable("' . $table->getName() . '");';
            if ($table->getForeignKeys()) {
                throw new \Exception('ForeignKeys are not implemented');
            }
            if ($table->getOptions()) {
                throw new \Exception('Options are not implemented');
            }
            foreach ($table->getColumns() as $column) {
                $this->_column($column, $table->getName());
            }
            $this->_indices($table);
        }
    }

    /**
     * @param \Doctrine\DBAL\Schema\Column $column
     * @param                              $table
     *
     * @throws Exception
     */
    protected function _column($column, $table)
    {
        $this->columns[$table][$column->getName()] = '$' . $table . '->addColumn("' . $column->getName() . '", "' . $column->getType()->getName() . '", array(';

        if ($column->getUnsigned()) {
            $this->columns[$table][$column->getName()] .= '"unsigned"=>true,';
        }

        if ($column->getAutoincrement()) {
            $this->columns[$table][$column->getName()] .= '"autoincrement"=>true,';
        }

        if (!is_null($column->getDefault())) {
            $this->columns[$table][$column->getName()] .= '"default"=>"' . (is_null($column->getDefault()) ? 'null' : $column->getDefault()) . '",';
        }

        if ($column->getComment()) {
            $this->columns[$table][$column->getName()] .= '"comment"=>"' . $column->getComment() . '",';
        }

        if (is_numeric($column->getLength())) {
            $this->columns[$table][$column->getName()] .= '"length"=>"' . $column->getLength() . '",';
        }

        if ($column->getNotnull()) {
            $this->columns[$table][$column->getName()] .= '"notnull"=>true,';
        } else {
            $this->columns[$table][$column->getName()] .= '"notnull"=>false,';
        }

        if ($column->getFixed()) {
            $this->columns[$table][$column->getName()] .= '"fixed"=>"' . $column->getFixed() . '",';
        }

        switch ($column->getType()->getName()) {
            case 'float':
            case 'decimal':
                if ($column->getPrecision()) {
                    $this->columns[$table][$column->getName()] .= '"precision"=>"' . $column->getPrecision() . '",';
                }

                if ($column->getScale()) {
                    $this->columns[$table][$column->getName()] .= '"scale"=>"' . $column->getScale() . '",';
                }
                break;
            case 'boolean':
            case 'datetime':
            case 'integer':
            case 'smallint':
            case 'bigint':
            case 'string':
            case 'text':
            default:
                break;
        }

        //TODO Add handler for _platformOptions if not null
        if (count($column->getPlatformOptions())) {
            throw new \Exception('Unhandled platform options' . print_r($column->getPlatformOptions(), true));
        }
        //TODO Add handler for _customSchemaOptions if not null
        if (count($column->getCustomSchemaOptions())) {
            throw new \Exception('Unhandled getCustomSchemaOptions' . print_r($column->getCustomSchemaOptions(), true));
        }
        //TODO Add handler for _ColumnDefinition if not null
        if (!is_null($column->getColumnDefinition())) {
            throw new \Exception('Unhandled _ColumnDefinition' . print_r($column->getColumnDefinition(), true));
        }

        //if ($column->getType()->getName() == 'datetime') {
        //  var_dump($column);
        //die();
        //}

        $this->columns[$table][$column->getName()] .= '));';
    }

    /**
     * @param \Doctrine\DBAL\Schema\Table $table
     *
     * TODO add Flags capability
     */
    protected function _indices($table)
    {
        foreach ($table->getIndexes() as $index) {

            if ($index->isPrimary()) {
                if ($index->getName()) {
                    $this->indices[$table->getName()][] = '$' . $table->getName() . '->setPrimaryKey(array("' . implode('","', $index->getColumns()) . '"),"' . $index->getName() . '");';
                } else {
                    $this->indices[$table->getName()][] = '$' . $table->getName() . '->setPrimaryKey(array("' . implode('","', $index->getColumns()) . '"));';
                }
                continue;
            }
            if ($index->isUnique()) {
                if ($index->getName()) {
                    $this->indices[$table->getName()][] = '$' . $table->getName() . '->addUniqueIndex(array("' . implode('","', $index->getColumns()) . '"),"' . $index->getName() . '");';
                } else {
                    $this->indices[$table->getName()][] = '$' . $table->getName() . '->addUniqueIndex(array("' . implode('","', $index->getColumns()) . '"));';
                }
                continue;
            }
            if ($index->getName()) {
                $this->indices[$table->getName()][] = '$' . $table->getName() . '->addIndex(array("' . implode('","', $index->getColumns()) . '"),"' . $index->getName() . '");';
            } else {
                $this->indices[$table->getName()][] = '$' . $table->getName() . '->addIndex(array("' . implode('","', $index->getColumns()) . '"));';
            }

        };
    }

    /**
     * Handles Sequences
     *
     * TODO Add support
     */
    protected function _sequences()
    {
        try {
            $this->schema->getSequences();
        } catch (\Doctrine\DBAL\DBALException $e) {
            //$schema w/o exceptions
        }
    }

    public function __toString()
    {
        $this->_tables();
        $str = "//Schema" . PHP_EOL;
        $str .= '$' . $this->name . ' = new \Doctrine\DBAL\Schema\Schema(' . PHP_EOL;
        $str .= 'array(),' . PHP_EOL;
        $str .= 'array(),' . PHP_EOL;
        $str .= 'new \Doctrine\DBAL\Schema\SchemaConfig()' . PHP_EOL;
        $str .= ');';

        foreach ($this->columns as $k => &$v) {
            $v = implode(PHP_EOL, $v);
        }
        foreach ($this->indices as $k => &$v) {
            $v = implode(PHP_EOL, $v);
        }

        $all = array_merge(
            array($str),
            array(PHP_EOL . '//Schema Sequences'),
            $this->sequences,
            array(PHP_EOL . '//Tables'),
            $this->tables,
            array(PHP_EOL . '//Table Columns'),
            (array)implode(PHP_EOL, $this->columns),
            array(PHP_EOL . '//Table Indices'),
            (array)implode(PHP_EOL, $this->indices)
        );
        return @implode(PHP_EOL, $all);
    }
}