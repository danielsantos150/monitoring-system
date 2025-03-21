<?php
require '../db.php';

$stmt = $pdo->query("SELECT * FROM servers");
$servers = $stmt->fetchAll(PDO::FETCH_ASSOC);

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["hostname"], $_POST["ip_address"])) {
    $stmt = $pdo->prepare("INSERT INTO servers (hostname, ip_address, status) VALUES (?, ?, 'inactive')");
    $stmt->execute([$_POST["hostname"], $_POST["ip_address"]]);
    header("Location: " . $_SERVER["PHP_SELF"]); // Recarrega a página
    exit;
}

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["server_id"], $_POST["name"], $_POST["description"], $_POST["status"])) {
    $stmt = $pdo->prepare("INSERT INTO systems (server_id, name, description, status) VALUES (?, ?, ?, ?)");
    $stmt->execute([$_POST["server_id"], $_POST["name"], $_POST["description"], $_POST["status"]]);
    header("Location: " . $_SERVER["PHP_SELF"]); // Recarrega a página
    exit;
}

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["delete_system_id"])) {
    $stmt = $pdo->prepare("DELETE FROM systems WHERE id = ?");
    $stmt->execute([$_POST["delete_system_id"]]);
    header("Location: " . $_SERVER["PHP_SELF"]); // Recarrega a página
    exit;
}
?>

<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Monitoramento de Servidores</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .status-circle {
            width: 30px;
            height: 30px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
        }

        .status-active {
            background-color: green;
        }

        .status-inactive {
            background-color: red;
        }

        .status-circle .x {
            font-size: 18px;
        }
    </style>
</head>
<body>
<div class="container mt-4">
    <h1 class="mb-4 text-center">Monitoramento de Servidores</h1>

    <!-- Formulário para adicionar servidor -->
    <div class="card mb-4">
        <div class="card-header">Adicionar Servidor</div>
        <div class="card-body">
            <form method="POST">
                <div class="mb-3">
                    <label class="form-label">Nome do Servidor</label>
                    <input type="text" name="hostname" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Endereço IP</label>
                    <input type="text" name="ip_address" class="form-control" required>
                </div>
                <button type="submit" class="btn btn-primary">Adicionar</button>
            </form>
        </div>
    </div>

    <!-- Tabela de servidores -->
    <div class="table-responsive">
        <table class="table table-bordered table-striped text-center">
            <thead class="table-dark">
                <tr>
                    <th>ID</th>
                    <th>Nome</th>
                    <th>IP</th>
                    <th>Status</th>
                    <th>CPU (%)</th>
                    <th>Memória (MB)</th>
                    <th>Servidor Web</th>
                    <th>Sistemas</th>
                    <th>Adicionar Sistema</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($servers as $server): ?>
                <?php
                // Buscar o último log de monitoramento
                $stmt = $pdo->prepare("SELECT * FROM monitoring_logs WHERE server_id = ? ORDER BY timestamp DESC LIMIT 1");
                $stmt->execute([$server['id']]);
                $log = $stmt->fetch(PDO::FETCH_ASSOC);

                $cpu_usage = $log ? $log['cpu_usage'] : 'N/A';
                $memory_usage = $log ? $log['memory_usage'] : 'N/A';
                
                // Buscar sistemas vinculados
                $stmt = $pdo->prepare("SELECT * FROM systems WHERE server_id = ?");
                $stmt->execute([$server['id']]);
                $systems = $stmt->fetchAll(PDO::FETCH_ASSOC);
                ?>
                <tr>
                    <td><?= $server['id'] ?></td>
                    <td><?= $server['hostname'] ?></td>
                    <td><?= $server['ip_address'] ?></td>
                    <td>
                        <span class="badge bg-<?= $server['status'] === 'active' ? 'success' : ($server['status'] === 'inactive' ? 'danger' : 'warning') ?>">
                            <?= ucfirst($server['status']) ?>
                        </span>
                    </td>
                    <td><?= $cpu_usage ?>%</td>
                    <td><?= round($memory_usage, 2) ?? 'N/A' ?> MB</td>
                    <td>
                        <span class="badge bg-<?= $log && $log['web_server_running'] ? 'success' : 'danger' ?>">
                            <?= $log && $log['web_server_running'] ? 'up' : 'down' ?>
                        </span>
                    </td>
                    <td>
                        <?php if (!empty($systems)): ?>
                            <ul class="list-unstyled">
                                <?php foreach ($systems as $system): ?>
                                    <li class="py-1">
                                        <strong><?= $system['name'] ?></strong>
                                        <span class="badge bg-<?= $system['status'] === 'running' ? 'success' : ($system['status'] === 'stopped' ? 'danger' : 'warning') ?>">
                                            <?= ucfirst($system['status']) ?>
                                        </span>
                                        <!-- Botão para excluir sistema -->
                                        <form method="POST" class="d-inline-block">
                                        <input type="hidden" name="delete_system_id" value="<?= $system['id'] ?>">
                                        <button type="submit" class="btn btn-outline-danger btn-sm">Excluir</button>
                                        </form>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php else: ?>
                            <span class="text-muted">Nenhum sistema vinculado</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <form method="POST">
                            <input type="hidden" name="server_id" value="<?= $server['id'] ?>">
                            <div class="mb-2">
                                <input type="text" name="name" class="form-control mb-1" placeholder="Nome do Sistema" required>
                                <input type="text" name="description" class="form-control mb-1" placeholder="Descrição" required>
                                <select name="status" class="form-select mb-1" required>
                                    <option value="running">Running</option>
                                    <option value="stopped">Stopped</option>
                                    <option value="maintenance">Maintenance</option>
                                </select>
                            </div>
                            <button type="submit" class="btn btn-sm btn-primary">Adicionar Sistema</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
