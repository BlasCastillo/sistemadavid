// Detección automática y configuración inmediata para evitar parpadeos (FOUC)
const htmlElement = document.documentElement;

// Función global (para poder re-usarla)
window.aplicarTemaReditus = function(tema, themeIcon = null) {
    if (tema === 'dark') {
        htmlElement.setAttribute('data-theme', 'dark');
        if (themeIcon) {
            themeIcon.classList.remove('fa-sun');
            themeIcon.classList.add('fa-moon'); // Luna para modo oscuro
        }
    } else {
        htmlElement.removeAttribute('data-theme');
        if (themeIcon) {
            themeIcon.classList.remove('fa-moon');
            themeIcon.classList.add('fa-sun'); // Sol para modo claro
        }
    }
};

// 1. Detección y aplicación inmediata del tema guardado
const savedTheme = localStorage.getItem('reditus_theme');
if (savedTheme) {
    window.aplicarTemaReditus(savedTheme);
} else {
    const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
    window.aplicarTemaReditus(prefersDark ? 'dark' : 'light');
}

// 2. Event Listeners al cargar el DOM
document.addEventListener('DOMContentLoaded', () => {
    const btnThemeToggle = document.getElementById('btnThemeToggle');
    const themeIcon = document.getElementById('themeIcon');
    
    // Aseguramos que el ícono coincida con el tema actual al cargar
    const currentTheme = htmlElement.getAttribute('data-theme') || 'light';
    window.aplicarTemaReditus(currentTheme, themeIcon);

    if (btnThemeToggle) {
        btnThemeToggle.addEventListener('click', () => {
            const activeTheme = htmlElement.getAttribute('data-theme') === 'dark' ? 'dark' : 'light';
            const newTheme = activeTheme === 'dark' ? 'light' : 'dark';
            
            window.aplicarTemaReditus(newTheme, themeIcon);
            localStorage.setItem('reditus_theme', newTheme);
        });
    }
});

// Opcional: Escuchar cambios en la preferencia del sistema operativo (si no hay elección manual guardada)
window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', (e) => {
    if (!localStorage.getItem('reditus_theme')) {
        const themeIcon = document.getElementById('themeIcon');
        window.aplicarTemaReditus(e.matches ? 'dark' : 'light', themeIcon);
    }
});
