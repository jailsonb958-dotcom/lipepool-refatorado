<?php

declare(strict_types=1);

use LipePool\Database\ConnectionFactory;
use LipePool\Support\Input;
use LipePool\Support\Config;

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

$root = dirname(__DIR__);
require $root . '/vendor/autoload.php';
Config::load($root);

fwrite(STDOUT, "Nome do administrador: ");
$name = trim((string) fgets(STDIN));
fwrite(STDOUT, "E-mail do administrador: ");
$email = strtolower(trim((string) fgets(STDIN)));
fwrite(STDOUT, "Telefone com DDD: ");
$phone = trim((string) fgets(STDIN));
fwrite(STDOUT, "Senha (mínimo 12 caracteres): ");
if (strncasecmp(PHP_OS, 'WIN', 3) !== 0) {
    system('stty -echo');
}
$password = rtrim((string) fgets(STDIN), "\r\n");
if (strncasecmp(PHP_OS, 'WIN', 3) !== 0) {
    system('stty echo');
}
fwrite(STDOUT, "\n");

try {
    $name = Input::string($name, 120, 'Nome');
    $email = Input::email($email);
    $phone = Input::phone($phone);
} catch (InvalidArgumentException $error) {
    fwrite(STDERR, $error->getMessage() . "\n");
    exit(1);
}
if (mb_strlen($password, 'UTF-8') < 12 || strlen($password) > 72) {
    fwrite(STDERR, "A senha deve ter pelo menos 12 caracteres e até 72 bytes UTF-8.\n");
    exit(1);
}

$pdo = ConnectionFactory::mysql();
$statement = $pdo->prepare("INSERT INTO users (name, email, phone, password_hash, role) VALUES (:name, :email, :phone, :hash, 'admin')");
try {
    $statement->execute(['name' => $name, 'email' => $email, 'phone' => $phone, 'hash' => password_hash($password, PASSWORD_DEFAULT)]);
    fwrite(STDOUT, "Administrador criado com sucesso.\n");
} catch (PDOException $error) {
    if ((string) $error->getCode() === '23000') {
        fwrite(STDERR, "Já existe uma conta com esse e-mail.\n");
        exit(1);
    }
    throw $error;
}
