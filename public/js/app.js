// Configuración de debug (solo en desarrollo)
const DEBUG = window.location.hostname === 'localhost' || window.location.hostname === '127.0.0.1';

// Función de log condicional
function debugLog(...args) {
    if (DEBUG) {
        console.log(...args);
    }
}

function debugError(...args) {
    if (DEBUG) {
        console.error(...args);
    }
}

// Funciones de notificación Toast
window.showSuccess = function(message) {
    showToast('success', 'Éxito', message);
};

window.showError = function(message) {
    showToast('error', 'Error', message);
};

window.showInfo = function(message) {
    showToast('info', 'Información', message);
};

window.showWarning = function(message) {
    showToast('warning', 'Advertencia', message);
};

function showToast(type, title, message) {
    debugLog('=== showToast llamado ===', {type, title, message});
    
    // Asegurar que existe el contenedor
    let container = document.querySelector('.toast-container');
    if (!container) {
        debugLog('Creando contenedor de toast');
        container = document.createElement('div');
        container.className = 'toast-container';
        container.setAttribute('role', 'status');
        container.setAttribute('aria-live', 'polite');
        document.body.appendChild(container);
    }
    
    // Crear el toast
    const toast = document.createElement('div');
    toast.className = `toast ${type}`;
    toast.setAttribute('role', 'alert');
    
    // Iconos según el tipo
    const icons = {
        success: '✓',
        error: '✕',
        info: 'ℹ',
        warning: '⚠'
    };
    
    toast.innerHTML = `
        <div class="toast-icon" aria-hidden="true">${icons[type] || '•'}</div>
        <div class="toast-content">
            <div class="toast-title">${title}</div>
            <div class="toast-message">${message}</div>
        </div>
        <button class="toast-close" onclick="this.parentElement.remove()" title="Cerrar" aria-label="Cerrar notificación">×</button>
    `;
    
    container.appendChild(toast);
    
    // Forzar reflow para que la animación funcione
    toast.offsetHeight;
    
    // Auto-eliminar después de 5 segundos
    setTimeout(() => {
        if (toast.parentElement) {
            toast.style.animation = 'slideOutRight 0.3s ease-out';
            setTimeout(() => {
                if (toast.parentElement) {
                    toast.remove();
                }
            }, 300);
        }
    }, 5000);
}

// Función para abrir configuración
window.openSettings = function() {
    debugLog('=== openSettings EJECUTADO ===');
    const modalElement = document.getElementById('settingsModal');
    if (!modalElement) {
        debugError('ERROR: Modal no encontrado');
        showError('Error: No se encontró el modal de configuración');
        return;
    }
    try {
        if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
            const modal = new bootstrap.Modal(modalElement);
            
            // Añadir event listeners para limpiar cuando se cierre el modal
            modalElement.addEventListener('hidden.bs.modal', function() {
                debugLog('Modal cerrado, limpiando backdrop');
                const backdrops = document.querySelectorAll('.modal-backdrop');
                backdrops.forEach(backdrop => backdrop.remove());
                document.body.classList.remove('modal-open');
                document.body.style.overflow = '';
                document.body.style.paddingRight = '';
            });
            
            modal.show();
        } else {
            debugLog('Usando fallback manual');
            modalElement.style.display = 'block';
            modalElement.classList.add('show');
            document.body.classList.add('modal-open');
            const backdrop = document.createElement('div');
            backdrop.className = 'modal-backdrop fade show';
            backdrop.id = 'modalBackdrop';
            document.body.appendChild(backdrop);
            backdrop.addEventListener('click', function() {
                window.closeSettings();
            });
        }
    } catch (error) {
        debugError('ERROR al abrir modal:', error);
        showError('Error al abrir el modal: ' + error.message);
    }
};

// Función para cerrar configuración
window.closeSettings = function() {
    const modalElement = document.getElementById('settingsModal');
    if (modalElement) {
        if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
            const modal = bootstrap.Modal.getInstance(modalElement);
            if (modal) {
                modal.hide();
            } else {
                const newModal = new bootstrap.Modal(modalElement);
                newModal.hide();
            }
        } else {
            modalElement.style.display = 'none';
            modalElement.classList.remove('show');
            document.body.classList.remove('modal-open');
            const backdrop = document.getElementById('modalBackdrop');
            if (backdrop) {
                backdrop.remove();
            }
        }
        
        // Limpiar cualquier backdrop que quede
        const backdrops = document.querySelectorAll('.modal-backdrop');
        backdrops.forEach(backdrop => backdrop.remove());
        document.body.classList.remove('modal-open');
        document.body.style.overflow = '';
        document.body.style.paddingRight = '';
    }
};

// Función mejorada para peticiones AJAX con retry
async function fetchWithRetry(url, options = {}, maxRetries = 3) {
    let lastError;
    
    for (let i = 0; i < maxRetries; i++) {
        try {
            const response = await fetch(url, {
                ...options,
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    ...options.headers
                }
            });
            
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            
            return await response.json();
        } catch (error) {
            lastError = error;
            debugError(`Intento ${i + 1} fallido:`, error);
            
            // No reintentar en el último intento
            if (i < maxRetries - 1) {
                // Esperar antes de reintentar (exponential backoff)
                await new Promise(resolve => setTimeout(resolve, Math.pow(2, i) * 1000));
            }
        }
    }
    
    throw lastError;
}

// Navegación por teclado para tarjetas de juego
function initKeyboardNavigation() {
    // Añadir soporte de teclado a las tarjetas de juego
    const gameCards = document.querySelectorAll('.game-card[role="button"]');
    gameCards.forEach(card => {
        // Enter o Espacio para activar
        card.addEventListener('keydown', function(e) {
            if (e.key === 'Enter' || e.key === ' ') {
                e.preventDefault();
                // Simular doble clic para toggle de favoritos
                const gameId = parseInt(card.getAttribute('data-game-id'));
                const favoriteIcon = card.querySelector('.game-favorite');
                if (gameId && favoriteIcon && typeof window.toggleLike === 'function') {
                    window.toggleLike(gameId, favoriteIcon);
                }
            }
        });
    });
}

// Inicializar cuando el DOM esté listo
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initKeyboardNavigation);
} else {
    initKeyboardNavigation();
}

// ============================================
// UTILIDADES DE RENDIMIENTO
// ============================================

// Debounce: Ejecutar función después de que pase un tiempo sin llamadas
function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

// Throttle: Ejecutar función como máximo una vez por período
function throttle(func, limit) {
    let inThrottle;
    return function(...args) {
        if (!inThrottle) {
            func.apply(this, args);
            inThrottle = true;
            setTimeout(() => inThrottle = false, limit);
        }
    };
}

// Cache de peticiones
const requestCache = new Map();
const CACHE_DURATION = 30000; // 30 segundos

function getCachedRequest(url, options = {}) {
    const cacheKey = `${url}_${JSON.stringify(options)}`;
    const cached = requestCache.get(cacheKey);
    
    if (cached && Date.now() - cached.timestamp < CACHE_DURATION) {
        debugLog('Cache hit:', url);
        return Promise.resolve(cached.data);
    }
    
    return null;
}

function setCachedRequest(url, options = {}, data) {
    const cacheKey = `${url}_${JSON.stringify(options)}`;
    requestCache.set(cacheKey, {
        data: data,
        timestamp: Date.now()
    });
}

// Función fetch mejorada con cache
async function fetchWithCache(url, options = {}, useCache = true) {
    // Verificar cache primero
    if (useCache) {
        const cached = getCachedRequest(url, options);
        if (cached) {
            return cached;
        }
    }
    
    // Si no hay cache, hacer petición
    try {
        const response = await fetch(url, {
            ...options,
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                ...options.headers
            }
        });
        
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        const data = await response.json();
        
        // Guardar en cache si es GET
        if (useCache && (!options.method || options.method === 'GET')) {
            setCachedRequest(url, options, data);
        }
        
        return data;
    } catch (error) {
        debugError('Error en fetchWithCache:', error);
        throw error;
    }
}

// Lazy loading de imágenes
let imageObserver = null;

function initLazyLoading() {
    if ('IntersectionObserver' in window) {
        imageObserver = new IntersectionObserver((entries, observer) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const img = entry.target;
                    if (img.dataset.src) {
                        img.src = img.dataset.src;
                        img.removeAttribute('data-src');
                        img.classList.add('loaded');
                        observer.unobserve(img);
                    }
                }
            });
        }, {
            rootMargin: '50px' // Cargar 50px antes de que sea visible
        });
        
        // Observar todas las imágenes con data-src
        document.querySelectorAll('img[data-src]').forEach(img => {
            // Si la imagen ya está visible, cargarla inmediatamente
            if (img.offsetParent !== null) {
                img.src = img.dataset.src;
                img.removeAttribute('data-src');
                img.classList.add('loaded');
            } else {
                imageObserver.observe(img);
            }
        });
    } else {
        // Fallback para navegadores sin IntersectionObserver
        document.querySelectorAll('img[data-src]').forEach(img => {
            img.src = img.dataset.src;
            img.removeAttribute('data-src');
            img.classList.add('loaded');
        });
    }
}

// Función para reinicializar lazy loading (útil cuando se añaden imágenes dinámicamente)
function refreshLazyLoading() {
    if (imageObserver) {
        document.querySelectorAll('img[data-src]').forEach(img => {
            // Si la imagen ya está visible, cargarla inmediatamente
            if (img.offsetParent !== null) {
                img.src = img.dataset.src;
                img.removeAttribute('data-src');
                img.classList.add('loaded');
            } else {
                imageObserver.observe(img);
            }
        });
    } else {
        initLazyLoading();
    }
}

// Optimizar eventos de scroll y resize con throttle
function initOptimizedEvents() {
    // Throttle para scroll
    const handleScroll = throttle(() => {
        // Reajustar visibilidad de cards si existe la función
        if (typeof window.hideOverflowCards === 'function') {
            window.hideOverflowCards();
        }
    }, 100);
    
    window.addEventListener('scroll', handleScroll, { passive: true });
    
    // Throttle para resize
    const handleResize = throttle(() => {
        if (typeof window.hideOverflowCards === 'function') {
            window.hideOverflowCards();
        }
        if (typeof window.filterGames === 'function') {
            window.filterGames();
        }
    }, 150);
    
    window.addEventListener('resize', handleResize, { passive: true });
}

// Inicializar mejoras de rendimiento
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
        initLazyLoading();
        initOptimizedEvents();
    });
} else {
    initLazyLoading();
    initOptimizedEvents();
}

// Exportar funciones globales
window.debugLog = debugLog;
window.debugError = debugError;
window.fetchWithRetry = fetchWithRetry;
window.fetchWithCache = fetchWithCache;
window.debounce = debounce;
window.throttle = throttle;
window.refreshLazyLoading = refreshLazyLoading;

