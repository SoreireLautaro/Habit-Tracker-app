<?php
session_start();
// Check session briefly just to inform JS (not strictly necessary but helpful for initial load)
$is_logged_in = isset($_SESSION['user_id']) ? 'true' : 'false';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Habit Tracker</title>
    <!-- Usar una fuente moderna como Inter -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
</head>
<body data-logged-in="<?php echo $is_logged_in; ?>">

    <!-- VISTA LOGIN / REGISTRO -->
    <div id="login-view" class="view hidden">
        <div class="login-container">
            <div class="login-left">
                <!-- Imagen/Gráfico representativo (se simula con CSS o un SVG) -->
                <div class="login-graphic">
                    <img src="https://ui-avatars.com/api/?name=Habit+Tracker&background=7000FF&color=fff&size=512&rounded=true" alt="Habit Tracker" style="display:none;">
                    <div class="runner-silhouette">🏃‍♀️</div>
                </div>
            </div>
            <div class="login-right">
                <div class="logo">
                    <span class="logo-icon">✔️</span> HABIT TRACKER
                </div>
                <h1 id="auth-title">Iniciar sesión</h1>
                <form id="auth-form">
                    <div class="form-group">
                        <label>Email Address</label>
                        <input type="email" id="email" placeholder="Placeholder" required>
                    </div>
                    <div class="form-group">
                        <label>Password</label>
                        <div class="password-wrapper">
                            <input type="password" id="password" placeholder="Placeholder" required>
                            <span class="toggle-password" onclick="togglePassword()">👁️</span>
                        </div>
                        <small>It must be a combination of minimum 8 letters, numbers, and symbols.</small>
                    </div>
                    <div class="form-actions">
                        <label class="remember-me"><input type="checkbox"> Remember me</label>
                        <a href="#" class="forgot-pass">Forgot Password?</a>
                    </div>
                    <button type="submit" class="btn-primary" id="auth-submit">Log In</button>
                    
                    <div class="social-logins">
                        <button type="button" class="btn-social">Google</button>
                        <button type="button" class="btn-social">Apple</button>
                    </div>
                    
                    <div class="toggle-auth">
                        <span id="auth-toggle-text">No account yet? <a href="#" onclick="toggleAuthMode(event)">Sign Up</a></span>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- APP PRINCIPAL -->
    <div id="app-view" class="view hidden">
        <aside class="sidebar">
            <div class="logo">
                <span class="logo-icon">✔️</span> HABIT TRACKER
            </div>
            <nav>
                <a href="#" class="nav-item active" data-target="home">
                    <span class="nav-icon">🏠</span> Inicio
                </a>
                <a href="#" class="nav-item" data-target="stats">
                    <span class="nav-icon">📊</span> Estadísticas
                </a>
                <a href="#" class="nav-item" data-target="calendar">
                    <span class="nav-icon">📅</span> Calendario
                </a>
                <a href="#" class="nav-item" data-target="achievements">
                    <span class="nav-icon">🏆</span> Logros
                </a>
            </nav>
            <div class="user-profile">
                <button class="btn-user" onclick="logout()">
                    <span class="user-icon">👤</span> <span id="user-display">Usuario</span> <span>⌄</span>
                </button>
            </div>
        </aside>

        <main class="main-content">
            <header class="topbar">
                <div class="search-bar">websitename.com</div>
                <div class="notifications">🔔</div>
            </header>

            <!-- SECCIÓN: INICIO -->
            <section id="section-home" class="app-section">
                <div class="greeting-card">
                    <div class="greeting-text">
                        <h2>¡Qué gusto verte!</h2>
                        <p>Seleccioná la fecha para ver tus hábitos del día.</p>
                        <div class="date-selector">
                            <input type="date" id="home-date" value="">
                        </div>
                    </div>
                    <div class="greeting-avatar">
                        <div class="avatar-circle">👤</div>
                    </div>
                </div>

                <div class="daily-progress-section">
                    <div class="progress-header">
                        <h3>PROGRESO DIARIO</h3>
                        <div class="progress-circle" id="home-progress-circle">
                            <span id="home-progress-text">0%</span>
                        </div>
                        <div class="progress-text-right">
                            <strong id="home-habits-completed-text">0 DE 0</strong>
                            <span>hábitos completados</span>
                        </div>
                    </div>

                    <div class="habit-list" id="home-habit-list">
                        <!-- Hábitos inyectados por JS -->
                    </div>

                    <button class="btn-add-habit" onclick="openAddHabitModal()">+ AGREGAR NUEVO HÁBITO</button>
                </div>
            </section>

            <!-- SECCIÓN: ESTADÍSTICAS -->
            <section id="section-stats" class="app-section hidden">
                <div class="section-header">
                    <h2>ESTADISTICAS</h2>
                    <div class="period-selector">
                        Periodo: <select><option>Esta semana</option></select>
                    </div>
                </div>

                <div class="kpi-cards">
                    <div class="kpi-card">
                        <h4>Tasa de efectividad</h4>
                        <div class="kpi-value" id="kpi-efectividad">0%</div>
                        <div class="kpi-desc">Promedio semanal</div>
                    </div>
                    <div class="kpi-card">
                        <h4>Racha actual</h4>
                        <div class="kpi-value" id="kpi-racha">0</div>
                        <div class="kpi-desc">Días</div>
                    </div>
                    <div class="kpi-card">
                        <h4>Hábitos completados</h4>
                        <div class="kpi-value" id="kpi-completados">0</div>
                        <div class="kpi-desc">Esta semana</div>
                    </div>
                </div>

                <div class="chart-card">
                    <h3>EFECTIVIDAD DIARIA</h3>
                    <div class="bar-chart" id="daily-bar-chart">
                        <!-- Barras inyectadas por JS -->
                    </div>
                    <div class="chart-labels">
                        <span>Lun</span><span>Mar</span><span>Mier</span><span>Jue</span><span>Vie</span><span>Sab</span><span>Dom</span>
                    </div>
                </div>

                <div class="stats-table">
                    <div class="table-header">
                        <div class="col-habit">HABITO</div>
                        <div class="col-promedio">PROMEDIO</div>
                    </div>
                    <div id="stats-habit-list">
                        <!-- Lista inyectada por JS -->
                    </div>
                </div>
            </section>

            <!-- SECCIÓN: CALENDARIO -->
            <section id="section-calendar" class="app-section hidden">
                <div class="calendar-layout">
                    <div class="calendar-main">
                        <div class="calendar-header">
                            <h2>CALENDARIO</h2>
                            <div class="month-selector">
                                <span id="current-month">Marzo 2026</span>
                                <div class="month-nav">
                                    <button>&lt;</button>
                                    <button>&gt;</button>
                                </div>
                            </div>
                        </div>
                        <div class="calendar-grid" id="calendar-grid">
                            <!-- Calendario inyectado por JS -->
                        </div>
                    </div>
                    <div class="calendar-sidebar">
                        <h3>DETALLE DEL DÍA</h3>
                        <div class="selected-date" id="calendar-selected-date">
                            📅 11/08/2026
                        </div>
                        <div class="progress-circle large" id="calendar-progress-circle">
                            <span>65%</span>
                        </div>
                        <div class="progress-text-right">
                            <strong>3 DE 5</strong> hábitos completados
                        </div>

                        <div class="habit-status-list">
                            <h4>Completados</h4>
                            <ul id="calendar-completed-list"></ul>
                            <h4>Pendientes</h4>
                            <ul id="calendar-pending-list"></ul>
                        </div>
                    </div>
                </div>
            </section>

            <!-- SECCIÓN: LOGROS -->
            <section id="section-achievements" class="app-section hidden">
                <div class="section-header">
                    <h2>LOGROS</h2>
                </div>
                
                <div class="achievements-banner">
                    <div class="banner-icon">🏆</div>
                    <div class="banner-content">
                        <h4>Logros desbloqueados</h4>
                        <div class="banner-stats">
                            <span class="unlocked-count">3 / 6</span>
                            <div class="progress-bar-container">
                                <span class="pct">50%</span>
                                <div class="progress-bar"><div class="fill" style="width: 50%;"></div></div>
                            </div>
                        </div>
                    </div>
                    <div class="banner-message">
                        <h4>Vas por un gran camino!</h4>
                        <p>Sigue construyendo buenos hábitos día a día</p>
                    </div>
                </div>

                <h3>TUS LOGROS</h3>
                <div class="achievements-grid" id="achievements-grid">
                    <!-- Logros inyectados por JS -->
                </div>
            </section>
        </main>
    </div>

    <!-- Modal Agregar Hábito -->
    <div id="add-habit-modal" class="modal hidden">
        <div class="modal-content">
            <h3>Agregar Nuevo Hábito</h3>
            <form id="add-habit-form">
                <input type="text" id="new-habit-name" placeholder="Nombre del hábito" required>
                <input type="text" id="new-habit-icon" placeholder="Icono (ej: 💧, 📖, 🏃)" required>
                <div class="modal-actions">
                    <button type="button" class="btn-cancel" onclick="closeAddHabitModal()">Cancelar</button>
                    <button type="submit" class="btn-primary">Guardar</button>
                </div>
            </form>
        </div>
    </div>

    <script src="script.js"></script>
</body>
</html>
