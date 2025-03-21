<?php

require '../db.php';

/**
 * Executa um comando no PowerShell e retorna a saída.
 *
 * @param string $command Comando a ser executado no PowerShell.
 * @return string|null Retorna a saída do comando ou null em caso de falha.
 */
function runPowerShellCommand(string $command): ?string {
    $escapedCommand = escapeshellarg($command);
    $output = shell_exec("powershell -Command $escapedCommand");
    return $output ?: null;
}

/**
 * Busca o ID de um servidor no banco de dados pelo IP fornecido.
 *
 * @param PDO $pdo Conexão com o banco de dados.
 * @param string $ip_address Endereço IP do servidor a ser buscado.
 * @return string|null Retorna o ID do servidor ou null se não encontrado.
 */
function getServerIdFromIp(PDO $pdo, string $ip_address): ?string {
    $stmt = $pdo->prepare("SELECT id FROM servers WHERE ip_address = ?");
    $stmt->execute([$ip_address]);
    $server = $stmt->fetch(PDO::FETCH_ASSOC);
    
    return $server['id'] ?? null;
}

/**
 * Obtém o uso de CPU e memória do próprio servidor via PowerShell.
 *
 * @return array Retorna um array associativo com as chaves "cpu" e "memory".
 */
function getCpuAndMemoryUsage(): array {
    $cpuCommand = "Get-WmiObject -Class Win32_Processor | Select-Object LoadPercentage | ConvertTo-Json";
    $memoryCommand = "Get-WmiObject -Class Win32_OperatingSystem | Select-Object TotalVisibleMemorySize, FreePhysicalMemory | ConvertTo-Json";

    $cpuUsage = runPowerShellCommand($cpuCommand);
    $memoryUsage = runPowerShellCommand($memoryCommand);

    $cpuUsage = json_decode($cpuUsage, true);
    $memoryUsage = json_decode($memoryUsage, true);

    if (!$cpuUsage || !$memoryUsage) {
        return ["cpu" => null, "memory" => null];
    }

    $totalMemory = round($memoryUsage['TotalVisibleMemorySize'] / 1024, 2);
    $freeMemory = round($memoryUsage['FreePhysicalMemory'] / 1024, 2);

    return [
        "cpu" => $cpuUsage["LoadPercentage"] ?? 0,
        "memory" => $totalMemory - $freeMemory
    ];
}

/**
 * Verifica se o servidor web local está rodando na porta 80.
 *
 * @return int Retorna 1 se o servidor web estiver rodando, 0 caso contrário.
 */
function isWebServerRunning(): int {
    $connection = @fsockopen('127.0.0.1', 80, $errno, $errstr, 2);
    if ($connection) {
        fclose($connection);
        return 1;
    }
    return 0;
}

/**
 * Atualiza o status de um servidor no banco de dados com base no ping e retorna o status.
 *
 * @param PDO $pdo Conexão com o banco de dados.
 * @param string $ip_address Endereço IP do servidor a ser verificado.
 * @return string Retorna 'active' se o servidor responder ao ping, ou 'inactive' caso contrário.
 */
function updateServerStatus(PDO $pdo, string $ip_address): string {
    $server_id = getServerIdFromIp($pdo, $ip_address);
    
    $status = (exec("ping -c 1 $ip_address")) ? 'active' : 'inactive';

    if ($server_id) {
        $pdo->prepare("UPDATE servers SET status = ? WHERE id = ?")
            ->execute([$status, $server_id]);
    }

    return $status;
}

/**
 * Insere um registro de monitoramento no banco de dados.
 *
 * @param PDO $pdo Conexão com o banco de dados.
 * @param int $server_id ID do servidor monitorado.
 * @param int $is_up Indica se o servidor está ativo (1) ou inativo (0).
 * @param int|null $cpu_usage Porcentagem de uso da CPU.
 * @param float|null $memory_usage Quantidade de memória usada em MB.
 * @param int $web_server_running Indica se o servidor web está rodando (1) ou não (0).
 * @return void
 */
function insertMonitoringLog(PDO $pdo, int $server_id, int $is_up, ?int $cpu_usage, ?float $memory_usage, int $web_server_running): void {
    $stmt = $pdo->prepare("INSERT INTO monitoring_logs (server_id, is_up, cpu_usage, memory_usage, web_server_running) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([$server_id, $is_up, $cpu_usage, $memory_usage, $web_server_running]);
}

$ipThisServer = "192.168.0.1";

$server_id = getServerIdFromIp($pdo, $ipThisServer);

$status = updateServerStatus($pdo, $ipThisServer);
$enumStatus = ($status == 'active') ? 1 : 0;

$webServerProcesses = isWebServerRunning();
$usage = getCpuAndMemoryUsage();
$cpu_usage = $usage['cpu'];
$memory_usage = $usage['memory'];

insertMonitoringLog($pdo, $server_id, $enumStatus, $cpu_usage, $memory_usage, $webServerProcesses);

echo "Monitoramento concluído!\n";
