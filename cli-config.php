<?php

use Doctrine\ORM\Tools\Console\ConsoleRunner;

// replace with file to your own project bootstrap
$entityManager = require_once 'bootstrap.php';

// replace with mechanism to retrieve EntityManager in your app

return ConsoleRunner::createHelperSet($entityManager);
