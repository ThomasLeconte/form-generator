<?php

require_once join(DIRECTORY_SEPARATOR, [__DIR__, 'vendor', 'autoload.php']);

use Doctrine\ORM\Tools\Setup;
use Doctrine\ORM\EntityManager;

$entitiesPath = [
  join(DIRECTORY_SEPARATOR, [__DIR__, "src", "Entity"])
];

$isDevMode = true;
$proxyDir = null;
$cache = null;
$useSimpleAnnotationReader = false;

// Connexion à la base de données
$dbParams = [
  'driver'   => 'pdo_mysql',
  'host'     => 'localhost',
  'charset'  => 'utf8',
  'user'     => 'root',
  'password' => '',
  'dbname'   => 'zend',
];

$config = Setup::createAnnotationMetadataConfiguration(
  $entitiesPath,
  $isDevMode,
  $proxyDir,
  $cache,
  $useSimpleAnnotationReader
);
return EntityManager::create($dbParams, $config);