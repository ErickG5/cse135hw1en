<?php
declare(strict_types=1);

function db(): PDO {
  $host = "127.0.0.1";
  $db   = "analytics";
  $user = "YOUR_MYSQL_USER";
  $pass = "YOUR_MYSQL_PASS";
  $charset = "utf8mb4";

  $dsn = "mysql:host=$host;dbname=$db;charset=$charset";
  $opt = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
  ];

  return new PDO($dsn, $user, $pass, $opt);
}
