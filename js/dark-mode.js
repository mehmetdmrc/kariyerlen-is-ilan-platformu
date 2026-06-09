document.addEventListener('DOMContentLoaded', () => {
    // Theme is applied immediately via inline script in header to prevent FOUC
    const isDark = document.body.classList.contains('dark-mode');
    updateToggleIcons(isDark);
});

// Function to attach to the toggle button onclick
window.toggleTheme = function() {
    const isDark = document.body.classList.toggle('dark-mode');
    localStorage.setItem('theme', isDark ? 'dark' : 'light');
    updateToggleIcons(isDark);
};

function updateToggleIcons(isDark) {
    const sunIcon = document.getElementById('theme-icon-sun');
    const moonIcon = document.getElementById('theme-icon-moon');
    const btn = document.getElementById('theme-toggle');
    
    if (sunIcon && moonIcon && btn) {
        if (isDark) {
            sunIcon.style.display = 'block';
            moonIcon.style.display = 'none';
        } else {
            sunIcon.style.display = 'none';
            moonIcon.style.display = 'block';
        }
    }
}
