// ========================================
// MODERN NAVIGATION COMPONENT
// ========================================

(function() {
  'use strict';

  // Navigation configuration
  const NAV_CONFIG = {
    brand: {
      title: 'RFID Access System',
      subtitle: 'Vehicle Management',
      icon: 'directions_car'
    },
    links: [
      { label: 'Dashboard', href: 'index.html', icon: 'dashboard', auth: true },
      { label: 'Vehicles', href: 'vehicles_inside.html', icon: 'directions_car', auth: true },
      { label: 'History', href: 'access_history.html', icon: 'history', auth: true },
      { label: 'Register', href: 'register_rfid.html', icon: 'add_circle', auth: true, admin: true },
      { label: 'Admin', href: 'admin.html', icon: 'settings', auth: true, admin: true },
      { label: 'Audit Log', href: 'admin_audit.php', icon: 'assignment', auth: true, admin: true }
    ]
  };

  // Get current user from auth system
  function getCurrentUser() {
    try {
      if (typeof auth !== 'undefined' && auth.getCurrentUser) {
        return auth.getCurrentUser();
      }
      // Fallback to localStorage
      const session = localStorage.getItem('rfid_session');
      if (session) {
        return JSON.parse(session);
      }
    } catch (e) {
      console.warn('Failed to get current user:', e);
    }
    return null;
  }

  // Check if current page matches link
  function isActivePage(href) {
    const currentPath = window.location.pathname;
    const linkPath = href.split('?')[0];
    return currentPath.endsWith(linkPath);
  }

  // Create navigation HTML
  function createNavigation() {
    const user = getCurrentUser();
    const isAdmin = user && user.role === 'admin';
    const isAuthenticated = !!user;

    // Filter links based on auth state
    const visibleLinks = NAV_CONFIG.links.filter(link => {
      if (link.auth && !isAuthenticated) return false;
      if (link.admin && !isAdmin) return false;
      return true;
    });

    const navHTML = `
      <nav class="modern-nav">
        <div class="nav-container">
          <!-- Brand -->
          <a href="index.html" class="nav-brand">
            <div class="nav-logo"><span class="material-icons">${NAV_CONFIG.brand.icon}</span></div>
            <div>
              <div class="nav-title">${NAV_CONFIG.brand.title}</div>
              <div class="nav-subtitle">${NAV_CONFIG.brand.subtitle}</div>
            </div>
          </a>

          <!-- Mobile Toggle -->
          <button class="nav-toggle" id="navToggle" aria-label="Toggle navigation">
            <span></span>
            <span></span>
            <span></span>
          </button>

          <!-- Navigation Links -->
          <ul class="nav-links" id="navLinks">
            ${visibleLinks.map(link => `
              <li>
                <a href="${link.href}" class="nav-link ${isActivePage(link.href) ? 'active' : ''}">
                  <span class="material-icons">${link.icon}</span>
                  <span>${link.label}</span>
                </a>
              </li>
            `).join('')}

            <!-- User Actions -->
            ${isAuthenticated ? `
              <li>
                <div class="nav-user">
                  <div class="nav-avatar">${user.username.charAt(0).toUpperCase()}</div>
                  <span class="nav-username">${user.username}</span>
                </div>
              </li>
              <li>
                <button class="nav-button secondary" id="navLogout">
                  <span class="material-icons">logout</span>
                  <span>Logout</span>
                </button>
              </li>
            ` : `
              <li>
                <a href="login.html" class="nav-button primary">
                  <span class="material-icons">login</span>
                  <span>Login</span>
                </a>
              </li>
            `}
          </ul>
        </div>
      </nav>
    `;

    return navHTML;
  }

  // Initialize navigation
  function initNavigation() {
    // Check if navigation should be inserted
    const navPlaceholder = document.getElementById('modernNav');
    if (navPlaceholder) {
      navPlaceholder.innerHTML = createNavigation();
    } else {
      // Insert at the beginning of body if no placeholder
      const nav = document.createElement('div');
      nav.innerHTML = createNavigation();
      document.body.insertBefore(nav.firstElementChild, document.body.firstChild);
    }

    // Setup event listeners
    setupEventListeners();
  }

  // Setup event listeners
  function setupEventListeners() {
    // Mobile menu toggle
    const navToggle = document.getElementById('navToggle');
    const navLinks = document.getElementById('navLinks');

    if (navToggle && navLinks) {
      navToggle.addEventListener('click', () => {
        navLinks.classList.toggle('active');
        navToggle.classList.toggle('active');
      });

      // Close menu when clicking outside
      document.addEventListener('click', (e) => {
        if (!navToggle.contains(e.target) && !navLinks.contains(e.target)) {
          navLinks.classList.remove('active');
          navToggle.classList.remove('active');
        }
      });

      // Close menu when clicking a link
      const links = navLinks.querySelectorAll('.nav-link');
      links.forEach(link => {
        link.addEventListener('click', () => {
          navLinks.classList.remove('active');
          navToggle.classList.remove('active');
        });
      });
    }

    // Logout button
    const logoutBtn = document.getElementById('navLogout');
    if (logoutBtn) {
      logoutBtn.addEventListener('click', () => {
        if (typeof auth !== 'undefined' && auth.logout) {
          auth.logout();
        } else {
          // Fallback logout
          localStorage.removeItem('rfid_session');
        }
        window.location.href = 'login.html';
      });
    }
  }

  // Initialize when DOM is ready
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initNavigation);
  } else {
    initNavigation();
  }

  // Export for manual initialization if needed
  window.modernNav = {
    init: initNavigation,
    refresh: initNavigation
  };
})();
