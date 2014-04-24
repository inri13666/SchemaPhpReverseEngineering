<?php
/**
 * User  : Nikita.Makarov
 * Date  : 4/24/14
 * Time  : 3:10 PM
 * E-Mail: nikita.makarov@effective-soft.com
 */

require_once 'phar://' . dirname(__FILE__) . DIRECTORY_SEPARATOR . 'doctrine.phar';
require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . 'SchemaPhpReverseEngineering.php';

$source_params = array(
    'driver' => 'pdo_mysql',
    'dbname' => 'mysql',
    'user' => 'root',
    'password' => '',
    'host' => 'localhost'
);

/**
 * Create Connections
 */
$source = Doctrine\DBAL\DriverManager::getConnection($source_params);

/**
 * Create Custom Doctrine Column Types
 */
$source->getDatabasePlatform()->registerDoctrineTypeMapping('enum', 'string');

$sourceSchema = new Doctrine\DBAL\Schema\Schema(
    $source->getSchemaManager()->listTables($source->getDatabase()),
    array(),
    new \Doctrine\DBAL\Schema\SchemaConfig()
);

$gretta = new \Doctrine\DBAL\Schema\Schema();


$destinationSchema = new SchemaPhpReverseEngineering($sourceSchema, 'gretta');

eval($x = $destinationSchema->__toString());

$comparator = new \Doctrine\DBAL\Schema\Comparator();
$schemaDiff = $comparator->compare($gretta, $sourceSchema);

echo '<pre>Schema Diff</pre>';
var_dump($schemaDiff->toSql($source->getDatabasePlatform())); // queries to get from one to another schema.
var_dump($schemaDiff->toSaveSql($source->getDatabasePlatform()));

if (strtolower(php_sapi_name()) == 'cli') {
    file_put_contents('out.php', "<?php" . PHP_EOL . $x);
} else {
    echo 'PHP Definition : <br/><pre>' . ($destinationSchema) . '</pre>';
}