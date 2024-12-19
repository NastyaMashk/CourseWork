<?php

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "coursach_db";

$backupDir = 'backups/';
$backupFile = $backupDir . $dbname . '_' . date('Y-m-d_H-i-s') . '.sql';

if (!file_exists($backupDir)) {
    mkdir($backupDir, 0777, true);
}

try {
    $command = "mysqldump --user=$username --password=$password --host=$servername $dbname > $backupFile";
    exec($command, $output, $returnVar);
    if ($returnVar !== 0) {
        throw new Exception("Ошибка при создании бэкапа базы данных.");
    }
    echo "Резервное копирование базы данных успешно завершено. Файл сохранен как: $backupFile";

} catch (Exception $e) {
    error_log($e->getMessage(), 3, 'db_errors.log');
    die("Произошла ошибка при создании резервной копии базы данных.");
}

?>
