/**
 * script.js
 * Lógica principal del Frontend (SPA) y consumo de API.
 * [Versión Optimizada por QA]
 */

const state = {
    user: null,
    currentDate: new Date().toISOString().split('T')[0],
    currentMonth: new Date(),
    habits: []
};

const API_URL = 'api.php?action=';

document.addEventListener('DOMContentLoaded', () => {
    checkAuthStatus();
    initEventListeners();
});

function showView(viewId) {
    document.getElementById('login-view').classList.add('hidden');
    document.getElementById('app-view').classList.add('hidden');
    document.getElementById(viewId).classList.remove('hidden');
}

function showSection(sectionId) {
    document.querySelectorAll('.app-section').forEach(s => s.classList.add('hidden'));
    document.getElementById(`section-${sectionId}`).classList.remove('hidden');

    document.querySelectorAll('.nav-item').forEach(n => n.classList.remove('active'));
    document.querySelector(`[data-target="${sectionId}"]`).classList.add('active');

    if (sectionId === 'home') loadHomeData();
    if (sectionId === 'stats') loadStatsData();
    if (sectionId === 'calendar') renderCalendar();
    if (sectionId === 'achievements') loadAchievements();
}

function initEventListeners() {
    document.querySelectorAll('.nav-item').forEach(item => {
        item.addEventListener('click', (e) => {
            e.preventDefault();
            showSection(e.currentTarget.dataset.target);
        });
    });

    document.getElementById('auth-form').addEventListener('submit', handleAuthSubmit);
    
    const dateInput = document.getElementById('home-date');
    dateInput.value = state.currentDate;
    dateInput.addEventListener('change', (e) => {
        state.currentDate = e.target.value;
        loadHomeData();
    });

    document.getElementById('add-habit-form').addEventListener('submit', handleAddHabit);
}

let isLoginMode = true;
function toggleAuthMode(e) {
    e.preventDefault();
    isLoginMode = !isLoginMode;
    document.getElementById('auth-title').innerText = isLoginMode ? 'Iniciar sesión' : 'Registrarse';
    document.getElementById('auth-submit').innerText = isLoginMode ? 'Log In' : 'Sign Up';
    document.getElementById('auth-toggle-text').innerHTML = isLoginMode 
        ? 'No account yet? <a href="#" onclick="toggleAuthMode(event)">Sign Up</a>'
        : 'Already have an account? <a href="#" onclick="toggleAuthMode(event)">Log In</a>';
}

function togglePassword() {
    const pwd = document.getElementById('password');
    pwd.type = pwd.type === 'password' ? 'text' : 'password';
}

async function checkAuthStatus() {
    try {
        const res = await fetch(API_URL + 'me');
        const data = await res.json();
        if (data.success) {
            state.user = data.user;
            document.getElementById('user-display').innerText = state.user.email.split('@')[0];
            showView('app-view');
            showSection('home');
        } else {
            showView('login-view');
        }
    } catch (e) {
        showView('login-view');
    }
}

async function handleAuthSubmit(e) {
    e.preventDefault();
    const email = document.getElementById('email').value;
    const password = document.getElementById('password').value;
    const action = isLoginMode ? 'login' : 'register';

    try {
        const res = await fetch(API_URL + action, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ email, password })
        });
        const data = await res.json();
        
        if (data.success) {
            checkAuthStatus();
        } else {
            alert(data.error || 'Error en autenticación');
        }
    } catch (e) {
        alert('Error de conexión');
    }
}

async function logout() {
    await fetch(API_URL + 'logout');
    state.user = null;
    showView('login-view');
}

async function loadHomeData() {
    try {
        const res = await fetch(`${API_URL}logs&date=${state.currentDate}`);
        const data = await res.json();
        if (data.success) renderHabitsList(data.data);
    } catch (e) { console.error(e); }
}

function renderHabitsList(habits) {
    const container = document.getElementById('home-habit-list');
    container.innerHTML = '';
    let completedCount = 0;

    habits.forEach(h => {
        const isCompleted = parseInt(h.completed) === 1;
        if (isCompleted) completedCount++;

        const div = document.createElement('div');
        div.className = 'habit-item';
        div.innerHTML = `
            <div class="habit-icon">${h.icon || '💧'}</div>
            <div class="habit-name">${h.name}</div>
            <label class="toggle-switch">
                <input type="checkbox" onchange="toggleHabit(${h.id}, this.checked)" ${isCompleted ? 'checked' : ''}>
                <span class="slider"></span>
            </label>
        `;
        container.appendChild(div);
    });

    updateProgress(completedCount, habits.length, 'home-progress-circle', 'home-progress-text', 'home-habits-completed-text');
}

async function toggleHabit(habitId, isCompleted) {
    try {
        await fetch(API_URL + 'logs', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ habit_id: habitId, date: state.currentDate, completed: isCompleted ? 1 : 0 })
        });
        loadHomeData();
    } catch (e) { console.error(e); }
}

function openAddHabitModal() { document.getElementById('add-habit-modal').classList.remove('hidden'); }
function closeAddHabitModal() { document.getElementById('add-habit-modal').classList.add('hidden'); }

async function handleAddHabit(e) {
    e.preventDefault();
    const name = document.getElementById('new-habit-name').value;
    const icon = document.getElementById('new-habit-icon').value || '💧';
    
    try {
        const res = await fetch(API_URL + 'habits', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ name, icon })
        });
        const data = await res.json();
        if (data.success) {
            closeAddHabitModal();
            document.getElementById('add-habit-form').reset();
            loadHomeData();
        } else {
            alert(data.error);
        }
    } catch (e) { console.error(e); }
}

async function loadStatsData() {
    try {
        const res = await fetch(API_URL + 'stats');
        const data = await res.json();
        if (data.success) {
            document.getElementById('kpi-efectividad').innerText = data.kpis.efectividad + '%';
            document.getElementById('kpi-racha').innerText = data.kpis.racha;
            document.getElementById('kpi-completados').innerText = data.kpis.completados;
            renderBarChart(data.daily_stats);
            renderStatsTable(data.habit_stats);
        }
    } catch (e) { console.error(e); }
}

function renderBarChart(dailyStats) {
    const chart = document.getElementById('daily-bar-chart');
    chart.innerHTML = '';
    let max = 1;
    dailyStats.forEach(d => { if(parseInt(d.completados) > max) max = parseInt(d.completados); });

    dailyStats.forEach(d => {
        const h = (parseInt(d.completados) / max) * 100;
        const bar = document.createElement('div');
        bar.className = 'bar';
        bar.style.height = `${h}%`;
        bar.title = `${d.date}: ${d.completados} completados`;
        chart.appendChild(bar);
    });
}

function renderStatsTable(habitStats) {
    const container = document.getElementById('stats-habit-list');
    container.innerHTML = '';
    habitStats.forEach(h => {
        const row = document.createElement('div');
        row.className = 'stat-row';
        row.innerHTML = `
            <div class="col-habit">${h.name}</div>
            <div class="col-promedio">
                <div class="progress-bar-container">
                    <span class="pct">${h.promedio}%</span>
                    <div class="progress-bar"><div class="fill" style="width: ${h.promedio}%;"></div></div>
                </div>
            </div>
        `;
        container.appendChild(row);
    });
}

async function renderCalendar() {
    const grid = document.getElementById('calendar-grid');
    grid.innerHTML = '';
    const monthNames = ["Enero", "Febrero", "Marzo", "Abril", "Mayo", "Junio", "Julio", "Agosto", "Septiembre", "Octubre", "Noviembre", "Diciembre"];
    const year = state.currentMonth.getFullYear();
    const month = state.currentMonth.getMonth();
    
    document.getElementById('current-month').innerText = `${monthNames[month]} ${year}`;
    
    ['Dom', 'Lun', 'Mar', 'Mie', 'Jue', 'Vie', 'Sab'].forEach(d => {
        const h = document.createElement('div');
        h.className = 'calendar-day-header';
        h.innerText = d;
        grid.appendChild(h);
    });

    const firstDay = new Date(year, month, 1).getDay();
    const daysInMonth = new Date(year, month + 1, 0).getDate();

    for(let i = 0; i < firstDay; i++) grid.appendChild(document.createElement('div'));

    // Fetch month logs for real dots
    let monthData = {};
    try {
        const res = await fetch(`${API_URL}month_logs&year=${year}&month=${month+1}`);
        const data = await res.json();
        if(data.success) monthData = data.data;
    } catch(e) { console.error(e); }

    for(let i = 1; i <= daysInMonth; i++) {
        const dayCell = document.createElement('div');
        dayCell.className = 'calendar-day';
        dayCell.innerHTML = `<span class="day-num">${i}</span>`;
        
        const dStr = `${year}-${String(month+1).padStart(2,'0')}-${String(i).padStart(2,'0')}`;
        const status = monthData[dStr] || 'none';
        
        if(status === 'full') dayCell.innerHTML += `<div class="day-indicator full"></div>`;
        else if (status === 'partial') dayCell.innerHTML += `<div class="day-indicator partial"></div>`;

        dayCell.onclick = () => loadCalendarDayDetails(dStr, dayCell);
        grid.appendChild(dayCell);
    }

    if(month === new Date().getMonth() && year === new Date().getFullYear()) {
        loadCalendarDayDetails(state.currentDate, null);
    }
}

document.querySelector('.month-nav button:first-child').onclick = () => {
    state.currentMonth.setMonth(state.currentMonth.getMonth() - 1);
    renderCalendar();
};
document.querySelector('.month-nav button:last-child').onclick = () => {
    state.currentMonth.setMonth(state.currentMonth.getMonth() + 1);
    renderCalendar();
};

async function loadCalendarDayDetails(dateStr, cellElement) {
    document.querySelectorAll('.calendar-day').forEach(c => c.classList.remove('active'));
    if(cellElement) cellElement.classList.add('active');

    document.getElementById('calendar-selected-date').innerText = `📅 ${dateStr.split('-').reverse().join('/')}`;
    
    try {
        const res = await fetch(`${API_URL}logs&date=${dateStr}`);
        const data = await res.json();
        if (data.success) {
            const compList = document.getElementById('calendar-completed-list');
            const pendList = document.getElementById('calendar-pending-list');
            compList.innerHTML = ''; pendList.innerHTML = '';
            
            let completed = 0;
            data.data.forEach(h => {
                const li = document.createElement('li');
                li.innerText = h.name;
                if(parseInt(h.completed) === 1) {
                    compList.appendChild(li);
                    completed++;
                } else {
                    pendList.appendChild(li);
                }
            });

            const total = data.data.length;
            updateProgress(completed, total, 'calendar-progress-circle', null, null);
            document.querySelector('#calendar-progress-circle span').innerText = total > 0 ? Math.round((completed/total)*100)+'%' : '0%';
            document.querySelector('.calendar-sidebar .progress-text-right strong').innerText = `${completed} DE ${total}`;
        }
    } catch (e) { console.error(e); }
}

async function loadAchievements() {
    try {
        const res = await fetch(API_URL + 'achievements');
        const data = await res.json();
        
        if (data.success) {
            const grid = document.getElementById('achievements-grid');
            grid.innerHTML = '';
            
            let unlockedCount = 0;
            const total = data.data.length;

            data.data.forEach(ach => {
                if(ach.unlocked) unlockedCount++;
                const isUn = ach.unlocked;
                
                const card = document.createElement('div');
                card.className = `achievement-card ${isUn ? 'unlocked' : 'locked'}`;
                card.innerHTML = `
                    <div class="ach-icon">${ach.icon}</div>
                    <h4>${ach.title}</h4>
                    <p>${ach.desc}</p>
                    <div class="status-badge">${isUn ? 'DESBLOQUEADO' : 'BLOQUEADO'}</div>
                `;
                grid.appendChild(card);
            });

            const pct = Math.round((unlockedCount / total) * 100);
            document.querySelector('.achievements-banner .unlocked-count').innerText = `${unlockedCount} / ${total}`;
            document.querySelector('.achievements-banner .pct').innerText = `${pct}%`;
            document.querySelector('.achievements-banner .fill').style.width = `${pct}%`;
        }
    } catch (e) { console.error(e); }
}

function updateProgress(completed, total, circleId, textId, secondaryTextId) {
    const pct = total > 0 ? Math.round((completed / total) * 100) : 0;
    const circle = document.getElementById(circleId);
    circle.style.background = `conic-gradient(var(--primary) ${pct}%, var(--bg-main) 0%)`;
    if(textId) document.getElementById(textId).innerText = pct + '%';
    if(secondaryTextId) document.getElementById(secondaryTextId).innerText = `${completed} DE ${total}`;
}
