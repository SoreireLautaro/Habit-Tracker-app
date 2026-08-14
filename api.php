<?php
/**
 * api.php
 * API RESTful interna para manejar autenticación y lógica de la aplicación.
 * [Versión Optimizada por QA]
 */
session_start();
require_once 'db.php';

header('Content-Type: application/json');

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

// Leer JSON payload
$input = json_decode(file_get_contents('php://input'), true);
$response = ['success' => false];

try {
    switch ($action) {
        // --- AUTENTICACIÓN ---
        case 'register':
            if ($method !== 'POST') throw new Exception("Método no permitido");
            $email = trim($input['email'] ?? '');
            $password = $input['password'] ?? '';
            
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) throw new Exception("Formato de email inválido");
            if (strlen($password) < 6) throw new Exception("La contraseña debe tener al menos 6 caracteres");
            
            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->execute([$email]);
            if ($stmt->fetch()) throw new Exception("El email ya está registrado");

            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO users (email, password_hash) VALUES (?, ?)");
            $stmt->execute([$email, $hash]);
            
            $_SESSION['user_id'] = $pdo->lastInsertId();
            $_SESSION['email'] = $email;
            $response = ['success' => true, 'message' => 'Usuario registrado'];
            break;

        case 'login':
            if ($method !== 'POST') throw new Exception("Método no permitido");
            $email = trim($input['email'] ?? '');
            $password = $input['password'] ?? '';
            
            $stmt = $pdo->prepare("SELECT id, email, password_hash FROM users WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch();
            
            if ($user && password_verify($password, $user['password_hash'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['email'] = $user['email'];
                $response = ['success' => true, 'message' => 'Login exitoso'];
            } else {
                throw new Exception("Credenciales incorrectas");
            }
            break;

        case 'logout':
            session_unset();
            session_destroy();
            $response = ['success' => true];
            break;

        case 'me':
            if (isset($_SESSION['user_id'])) {
                $response = ['success' => true, 'user' => ['id' => $_SESSION['user_id'], 'email' => $_SESSION['email']]];
            } else {
                $response = ['success' => false, 'message' => 'No autenticado'];
            }
            break;

        // --- RUTAS PROTEGIDAS ---
        default:
            if (!isset($_SESSION['user_id']) && $action !== '') {
                throw new Exception("No autorizado", 401);
            }
            $user_id = $_SESSION['user_id'] ?? null;

            switch ($action) {
                case 'habits':
                    if ($method === 'GET') {
                        $stmt = $pdo->prepare("SELECT * FROM habits WHERE user_id = ? ORDER BY id DESC");
                        $stmt->execute([$user_id]);
                        $response = ['success' => true, 'data' => $stmt->fetchAll()];
                    } elseif ($method === 'POST') {
                        $name = trim($input['name'] ?? '');
                        $icon = $input['icon'] ?? '💧';
                        if (empty($name)) throw new Exception("El nombre del hábito no puede estar vacío");
                        
                        $stmt = $pdo->prepare("INSERT INTO habits (user_id, name, icon) VALUES (?, ?, ?)");
                        $stmt->execute([$user_id, $name, $icon]);
                        $response = ['success' => true, 'id' => $pdo->lastInsertId()];
                    }
                    break;

                case 'logs':
                    if ($method === 'GET') {
                        $date = $_GET['date'] ?? date('Y-m-d');
                        $sql = "SELECT h.id, h.name, h.icon, IFNULL(l.completed, 0) as completed
                                FROM habits h
                                LEFT JOIN habit_logs l ON h.id = l.habit_id AND l.date = ?
                                WHERE h.user_id = ?";
                        $stmt = $pdo->prepare($sql);
                        $stmt->execute([$date, $user_id]);
                        $response = ['success' => true, 'data' => $stmt->fetchAll(), 'date' => $date];
                    } elseif ($method === 'POST') {
                        $habit_id = $input['habit_id'] ?? null;
                        $date = $input['date'] ?? date('Y-m-d');
                        $completed = $input['completed'] ?? 1;

                        if (!$habit_id) throw new Exception("habit_id requerido");
                        
                        $stmt = $pdo->prepare("
                            INSERT INTO habit_logs (habit_id, user_id, date, completed) 
                            VALUES (?, ?, ?, ?)
                            ON DUPLICATE KEY UPDATE completed = ?
                        ");
                        $stmt->execute([$habit_id, $user_id, $date, $completed, $completed]);
                        $response = ['success' => true];
                    }
                    break;

                case 'month_logs':
                    // Para poblar el calendario con datos reales
                    if ($method === 'GET') {
                        $year = $_GET['year'] ?? date('Y');
                        $month = str_pad($_GET['month'] ?? date('m'), 2, '0', STR_PAD_LEFT);
                        
                        // Total de hábitos por día
                        $stmtHabits = $pdo->prepare("SELECT COUNT(*) as total FROM habits WHERE user_id = ?");
                        $stmtHabits->execute([$user_id]);
                        $totalHabits = $stmtHabits->fetch()['total'] ?: 1;

                        // Hábitos completados por día en el mes
                        $stmt = $pdo->prepare("
                            SELECT date, SUM(completed) as completados 
                            FROM habit_logs 
                            WHERE user_id = ? AND date LIKE ?
                            GROUP BY date
                        ");
                        $stmt->execute([$user_id, "$year-$month-%"]);
                        $logs = $stmt->fetchAll();

                        $monthData = [];
                        foreach ($logs as $log) {
                            $pct = ($log['completados'] / $totalHabits);
                            $status = 'none';
                            if ($pct >= 1) $status = 'full';
                            elseif ($pct > 0) $status = 'partial';
                            $monthData[$log['date']] = $status;
                        }

                        $response = ['success' => true, 'data' => $monthData];
                    }
                    break;

                case 'stats':
                    // 1. Tasa de completación últimos 7 días
                    $stmt = $pdo->prepare("SELECT COUNT(*) as total_logs, SUM(completed) as total_completed 
                                           FROM habit_logs 
                                           WHERE user_id = ? AND date >= DATE_SUB(CURRENT_DATE, INTERVAL 7 DAY)");
                    $stmt->execute([$user_id]);
                    $week_stats = $stmt->fetch();
                    $tasa_efectividad = $week_stats['total_logs'] > 0 
                        ? round(($week_stats['total_completed'] / $week_stats['total_logs']) * 100) : 0;

                    $habitos_completados = (int) $week_stats['total_completed'];

                    // 2. Cálculo real de racha actual
                    // Buscamos todos los días distintos donde se completó al menos un hábito, ordenados desc
                    $stmt = $pdo->prepare("SELECT DISTINCT date FROM habit_logs WHERE user_id = ? AND completed = 1 ORDER BY date DESC");
                    $stmt->execute([$user_id]);
                    $fechas_completadas = $stmt->fetchAll(PDO::FETCH_COLUMN);

                    $racha = 0;
                    $hoy = new DateTime();
                    $hoy->setTime(0,0,0);
                    
                    $ayer = clone $hoy;
                    $ayer->modify('-1 day');
                    
                    $fechaEsperada = null;

                    if (!empty($fechas_completadas)) {
                        $primeraF = new DateTime($fechas_completadas[0]);
                        // La racha sigue viva si el último log es hoy o ayer
                        if ($primeraF == $hoy || $primeraF == $ayer) {
                            $racha = 1;
                            $fechaEsperada = clone $primeraF;
                            $fechaEsperada->modify('-1 day');
                            
                            for ($i = 1; $i < count($fechas_completadas); $i++) {
                                $f = new DateTime($fechas_completadas[$i]);
                                if ($f == $fechaEsperada) {
                                    $racha++;
                                    $fechaEsperada->modify('-1 day');
                                } else {
                                    break;
                                }
                            }
                        }
                    }

                    // 3. Gráfico de barras (últimos 7 días)
                    $stmt = $pdo->prepare("SELECT date, SUM(completed) as completados 
                                           FROM habit_logs 
                                           WHERE user_id = ? AND date >= DATE_SUB(CURRENT_DATE, INTERVAL 6 DAY)
                                           GROUP BY date ORDER BY date ASC");
                    $stmt->execute([$user_id]);
                    $daily_stats = $stmt->fetchAll();

                    // 4. Promedios por hábito
                    $stmt = $pdo->prepare("SELECT h.name, COUNT(l.id) as days_logged, SUM(l.completed) as days_completed 
                                           FROM habits h LEFT JOIN habit_logs l ON h.id = l.habit_id
                                           WHERE h.user_id = ? GROUP BY h.id");
                    $stmt->execute([$user_id]);
                    $habit_stats_raw = $stmt->fetchAll();
                    $habit_stats = [];
                    foreach ($habit_stats_raw as $hs) {
                        $pct = $hs['days_logged'] > 0 ? round(($hs['days_completed'] / $hs['days_logged']) * 100) : 0;
                        $habit_stats[] = ['name' => $hs['name'], 'promedio' => $pct];
                    }

                    $response = [
                        'success' => true,
                        'kpis' => ['efectividad' => $tasa_efectividad, 'racha' => $racha, 'completados' => $habitos_completados],
                        'daily_stats' => $daily_stats,
                        'habit_stats' => $habit_stats
                    ];
                    break;
                    
                case 'achievements':
                    // Cálculo real de logros
                    $stmtCount = $pdo->prepare("SELECT SUM(completed) as total FROM habit_logs WHERE user_id = ?");
                    $stmtCount->execute([$user_id]);
                    $total_completed = (int)$stmtCount->fetch()['total'];

                    // Reutilizar lógica de racha rápida
                    $stmtRacha = $pdo->prepare("SELECT DISTINCT date FROM habit_logs WHERE user_id = ? AND completed = 1 ORDER BY date DESC");
                    $stmtRacha->execute([$user_id]);
                    $fechas = $stmtRacha->fetchAll(PDO::FETCH_COLUMN);
                    $max_racha = 0; $racha_tmp = 0; $fechAnt = null;
                    foreach($fechas as $f) {
                        $fObj = new DateTime($f);
                        if ($fechAnt) {
                            $diff = $fechAnt->diff($fObj)->days;
                            if ($diff == 1) $racha_tmp++; else $racha_tmp = 1;
                        } else {
                            $racha_tmp = 1;
                        }
                        $fechAnt = $fObj;
                        if ($racha_tmp > $max_racha) $max_racha = $racha_tmp;
                    }

                    $response = ['success' => true, 'data' => [
                        ['title' => 'Racha de 7 días', 'desc' => 'Mantén una racha de 7 días', 'unlocked' => ($max_racha >= 7), 'icon' => '⚡'],
                        ['title' => '10 hábitos completados', 'desc' => 'Completa 10 hábitos en total', 'unlocked' => ($total_completed >= 10), 'icon' => '✓'],
                        ['title' => 'Racha de 30 días', 'desc' => 'Mantén una racha de 30 días', 'unlocked' => ($max_racha >= 30), 'icon' => '🔥'],
                        ['title' => '50 hábitos completados', 'desc' => 'Completa 50 hábitos en total', 'unlocked' => ($total_completed >= 50), 'icon' => '⭐'],
                        ['title' => 'Centurión', 'desc' => 'Completa 100 hábitos', 'unlocked' => ($total_completed >= 100), 'icon' => '💯']
                    ]];
                    break;

                default:
                    if ($action !== '') throw new Exception("Acción no encontrada", 404);
                    break;
            }
            break;
    }
} catch (Exception $e) {
    http_response_code($e->getCode() ?: 400);
    $response = ['success' => false, 'error' => $e->getMessage()];
}

echo json_encode($response);
?>
