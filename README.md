# Habit Tracker 2.0 🎯

Una aplicación web moderna, rápida y dinámica tipo SPA (Single Page Application) diseñada para ayudarte a construir y mantener buenos hábitos diarios. Cuenta con un sistema de estadísticas en tiempo real, registro de actividad mediante calendario y un sistema de logros desbloqueables.

## 🚀 Características Principales

*   **Autenticación Segura:** Sistema de registro y login con contraseñas encriptadas (Bcrypt/`password_hash`).
*   **Gestión de Hábitos:** Crea hábitos personalizados eligiendo tu propio icono (emojis).
*   **Seguimiento Diario:** Marca tus hábitos como completados cada día de forma rápida e intuitiva.
*   **Estadísticas en Tiempo Real:** 
    *   Tasa de efectividad semanal.
    *   Cálculo automático de tu racha (streak) actual.
    *   Gráficos dinámicos de barras con CSS.
*   **Calendario Interactivo:** Visualiza todo tu historial mensual. Los días se iluminan dinámicamente dependiendo de tu nivel de éxito.
*   **Sistema de Gamificación (Logros):** Desbloquea recompensas visuales al alcanzar hitos como 7 días de racha, 50 hábitos completados, etc.
*   **Arquitectura SPA:** Navegación fluida sin recargar la página utilizando Javascript asíncrono (`fetch` API).

## 🛠️ Stack Tecnológico

*   **Frontend:** HTML5 Semántico, CSS3 Puro (Variables, Flexbox/Grid, Glassmorphism), Vanilla JavaScript.
*   **Backend:** PHP 8+ (API RESTful interna).
*   **Base de Datos:** MySQL (Conexión segura vía PDO y sentencias preparadas).

## ⚙️ Instalación y Uso (Entorno Local)

Para correr este proyecto en tu computadora, necesitarás un entorno de servidor local como **XAMPP**, WAMP o Laragon.

1.  **Clonar el repositorio:**
    Abre tu terminal y clona este repositorio dentro de tu carpeta pública del servidor (ej. `C:\xampp\htdocs\` en XAMPP):
    ```bash
    git clone https://github.com/SoreireLautaro/Habit-Tracker-app.git
    cd Habit-Tracker-app
    ```

2.  **Configurar la Base de Datos:**
    *   Inicia los módulos **Apache** y **MySQL** en tu panel de control de XAMPP.
    *   Abre tu navegador y dirígete a `http://localhost/phpmyadmin/`.
    *   (Opcional) Crea una base de datos vacía llamada `habit_tracker_db`.
    *   Ve a la pestaña **Importar** y selecciona el archivo `schema.sql` que se encuentra en la raíz del proyecto. Este archivo creará las tablas necesarias automáticamente.

3.  **Ejecutar la App:**
    *   Dirígete en tu navegador a: `http://localhost/Habit-Tracker-app/` *(ajusta la ruta si nombraste la carpeta de otra manera)*.
    *   ¡Regístrate con un nuevo usuario y comienza a usar la aplicación!

## 📁 Estructura del Proyecto

```text
/
├── api.php           # Controlador principal del backend (Rutas API REST)
├── db.php            # Configuración y conexión PDO a la base de datos MySQL
├── index.php         # Esqueleto HTML principal (Single Page Application)
├── schema.sql        # Script SQL para estructurar la base de datos
├── script.js         # Lógica frontend, peticiones fetch() y reactividad DOM
└── style.css         # Hoja de estilos (UI/UX)
```

## ✒️ Autor

*   **Lautaro Soreire** - *Trabajo y Desarrollo* - [SoreireLautaro](https://github.com/SoreireLautaro)

## 📄 Licencia

Este proyecto está bajo la Licencia MIT.
