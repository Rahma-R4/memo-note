// Global variables
let currentMemoId = null;
let memos = [];
let isEditing = false;

// DOM elements
const sidebar = document.getElementById('sidebar');
const sidebarToggle = document.getElementById('sidebar-toggle');
const memoList = document.getElementById('memo-list');
const searchInput = document.getElementById('search-input');
const welcomeScreen = document.getElementById('welcome-screen');
const memoView = document.getElementById('memo-view');
const memoEdit = document.getElementById('memo-edit');
const backToTop = document.getElementById('back-to-top');
const deleteModal = document.getElementById('delete-modal');

// Initialize app
document.addEventListener('DOMContentLoaded', function() {
    initializeApp();
    setupEventListeners();
    loadMemos();
});

function initializeApp() {
    // Check if sidebar should be collapsed on mobile
    if (window.innerWidth <= 768) {
        sidebar.classList.add('collapsed');
    }
    
    // Setup dropdown functionality
    setupDropdowns();
}

function setupEventListeners() {
    // Sidebar toggle
    sidebarToggle.addEventListener('click', toggleSidebar);
    
    // Search functionality
    searchInput.addEventListener('input', debounce(handleSearch, 300));
    
    // New memo buttons
    document.getElementById('new-memo-btn').addEventListener('click', createNewMemo);
    document.getElementById('create-first-memo').addEventListener('click', createNewMemo);
    
    // Memo actions
    document.getElementById('edit-memo-btn').addEventListener('click', editCurrentMemo);
    document.getElementById('delete-memo-btn').addEventListener('click', showDeleteModal);
    document.getElementById('save-memo-btn').addEventListener('click', saveMemo);
    document.getElementById('cancel-edit-btn').addEventListener('click', cancelEdit);
    
    // Delete modal
    document.getElementById('confirm-delete').addEventListener('click', deleteMemo);
    document.getElementById('cancel-delete').addEventListener('click', hideDeleteModal);
    document.querySelector('.modal-close').addEventListener('click', hideDeleteModal);
    
    // Back to top button
    backToTop.addEventListener('click', scrollToTop);
    window.addEventListener('scroll', handleScroll);
    
    // Close modal on outside click
    deleteModal.addEventListener('click', function(e) {
        if (e.target === deleteModal) {
            hideDeleteModal();
        }
    });
    
    // Handle window resize
    window.addEventListener('resize', handleResize);
    
    // Auto-save functionality
    document.getElementById('edit-content').addEventListener('input', debounce(autoSave, 2000));
    document.getElementById('edit-title').addEventListener('input', debounce(autoSave, 2000));
}

function setupDropdowns() {
    const dropdowns = document.querySelectorAll('.dropdown');
    
    dropdowns.forEach(dropdown => {
        const toggle = dropdown.querySelector('.dropdown-toggle');
        
        toggle.addEventListener('click', function(e) {
            e.stopPropagation();
            
            // Close other dropdowns
            dropdowns.forEach(d => {
                if (d !== dropdown) {
                    d.classList.remove('active');
                }
            });
            
            dropdown.classList.toggle('active');
        });
    });
    
    // Close dropdowns when clicking outside
    document.addEventListener('click', function() {
        dropdowns.forEach(dropdown => {
            dropdown.classList.remove('active');
        });
    });
}

function toggleSidebar() {
    if (window.innerWidth <= 768) {
        // On mobile, use 'active' class instead of 'collapsed'
        sidebar.classList.toggle('active');
    } else {
        // On desktop, use 'collapsed' class
        sidebar.classList.toggle('collapsed');
        // Save preference only on desktop
        localStorage.setItem('sidebarCollapsed', sidebar.classList.contains('collapsed'));
    }
}

function handleSearch() {
    const query = searchInput.value.trim();
    
    if (query === '') {
        loadMemos();
    } else {
        searchMemos(query);
    }
}

function handleScroll() {
    if (window.pageYOffset > 300) {
        backToTop.classList.add('visible');
    } else {
        backToTop.classList.remove('visible');
    }
}

function handleResize() {
    if (window.innerWidth <= 768) {
        // On mobile, remove collapsed class and ensure sidebar is hidden by default
        sidebar.classList.remove('collapsed');
        sidebar.classList.remove('active');
    } else {
        // On desktop, restore collapsed state from localStorage
        sidebar.classList.remove('active');
        const wasCollapsed = localStorage.getItem('sidebarCollapsed') === 'true';
        if (wasCollapsed) {
            sidebar.classList.add('collapsed');
        } else {
            sidebar.classList.remove('collapsed');
        }
    }
}

function scrollToTop() {
    window.scrollTo({
        top: 0,
        behavior: 'smooth'
    });
}

// API functions
async function apiRequest(url, options = {}) {
    try {
        const response = await fetch(url, {
            headers: {
                'Content-Type': 'application/json',
                ...options.headers
            },
            ...options
        });
        
        const data = await response.json();
        
        if (!response.ok) {
            throw new Error(data.error || 'Request failed');
        }
        
        return data;
    } catch (error) {
        console.error('API Error:', error);
        showToast(error.message, 'error');
        throw error;
    }
}

async function loadMemos() {
    try {
        const data = await apiRequest('/api/api.php?path=memos');
        memos = data.memos;
        renderMemoList(memos);
        
        if (memos.length === 0) {
            showWelcomeScreen();
        } else if (currentMemoId) {
            // Reload current memo if it exists
            const currentMemo = memos.find(m => m.id == currentMemoId);
            if (currentMemo) {
                showMemo(currentMemo);
            } else {
                showWelcomeScreen();
            }
        }
    } catch (error) {
        console.error('Failed to load memos:', error);
    }
}

async function searchMemos(query) {
    try {
        const data = await apiRequest(`/api/api.php?path=search&q=${encodeURIComponent(query)}`);
        renderMemoList(data.memos);
    } catch (error) {
        console.error('Search failed:', error);
    }
}

async function saveMemo() {
    const title = document.getElementById('edit-title').value.trim();
    const content = document.getElementById('edit-content').value.trim();
    
    if (!title) {
        showToast('Title is required', 'error');
        return;
    }
    
    if (!content) {
        showToast('Content is required', 'error');
        return;
    }
    
    try {
        if (currentMemoId) {
            // Update existing memo
            await apiRequest(`/api/api.php?path=memo/${currentMemoId}`, {
                method: 'PUT',
                body: JSON.stringify({ title, content })
            });
            showToast('Memo updated successfully', 'success');
        } else {
            // Create new memo
            const data = await apiRequest('/api/api.php?path=memo', {
                method: 'POST',
                body: JSON.stringify({ title, content })
            });
            currentMemoId = data.id;
            showToast('Memo created successfully', 'success');
        }
        
        isEditing = false;
        await loadMemos();
        
        // Show the saved memo
        const memo = memos.find(m => m.id == currentMemoId);
        if (memo) {
            showMemo(memo);
        }
        
    } catch (error) {
        console.error('Save failed:', error);
    }
}

async function deleteMemo() {
    if (!currentMemoId) return;
    
    try {
        await apiRequest(`/api/api.php?path=memo/${currentMemoId}`, {
            method: 'DELETE'
        });
        
        showToast('Memo deleted successfully', 'success');
        hideDeleteModal();
        
        currentMemoId = null;
        await loadMemos();
        showWelcomeScreen();
        
    } catch (error) {
        console.error('Delete failed:', error);
    }
}

// UI functions
function renderMemoList(memos) {
    memoList.innerHTML = '';
    
    if (memos.length === 0) {
        memoList.innerHTML = '<div class="no-memos">No memos found</div>';
        return;
    }
    
    memos.forEach(memo => {
        const memoItem = createMemoItem(memo);
        memoList.appendChild(memoItem);
    });
}

function createMemoItem(memo) {
    const item = document.createElement('div');
    item.className = 'memo-item';
    item.dataset.memoId = memo.id;
    
    if (memo.id == currentMemoId) {
        item.classList.add('active');
    }
    
    const createdDate = formatDateForSidebar(memo.created_at);
    const updatedTime = formatTimeAgo(memo.updated_at);
    
    item.innerHTML = `
        <div class="memo-item-title">${escapeHtml(memo.title)}</div>
        <div class="memo-item-created">Created: ${createdDate}</div>
        <div class="memo-item-updated">Updated: ${updatedTime}</div>
    `;
    
    item.addEventListener('click', () => selectMemo(memo));
    
    return item;
}

function selectMemo(memo) {
    currentMemoId = memo.id;
    
    // Update active state in sidebar
    document.querySelectorAll('.memo-item').forEach(item => {
        item.classList.remove('active');
    });
    document.querySelector(`[data-memo-id="${memo.id}"]`).classList.add('active');
    
    showMemo(memo);
    
    // Close sidebar on mobile
    if (window.innerWidth <= 768) {
        sidebar.classList.remove('active');
    }
}

function showMemo(memo) {
    hideAllScreens();
    memoView.style.display = 'block';
    
    document.getElementById('memo-title').textContent = memo.title;
    document.getElementById('memo-content').textContent = memo.content;
    document.getElementById('memo-created-date').textContent = formatDate(memo.created_at);
    document.getElementById('memo-updated-date').textContent = formatDate(memo.updated_at);
}

function showWelcomeScreen() {
    hideAllScreens();
    welcomeScreen.style.display = 'flex';
    currentMemoId = null;
}

function createNewMemo() {
    currentMemoId = null;
    isEditing = true;
    
    hideAllScreens();
    memoEdit.style.display = 'block';
    
    document.getElementById('edit-title').value = '';
    document.getElementById('edit-content').value = '';
    document.getElementById('edit-title').focus();
    
    // Clear active state in sidebar
    document.querySelectorAll('.memo-item').forEach(item => {
        item.classList.remove('active');
    });
}

function editCurrentMemo() {
    if (!currentMemoId) return;
    
    const memo = memos.find(m => m.id == currentMemoId);
    if (!memo) return;
    
    isEditing = true;
    
    hideAllScreens();
    memoEdit.style.display = 'block';
    
    document.getElementById('edit-title').value = memo.title;
    document.getElementById('edit-content').value = memo.content;
    document.getElementById('edit-title').focus();
}

function cancelEdit() {
    isEditing = false;
    
    if (currentMemoId) {
        const memo = memos.find(m => m.id == currentMemoId);
        if (memo) {
            showMemo(memo);
        } else {
            showWelcomeScreen();
        }
    } else {
        showWelcomeScreen();
    }
}

function hideAllScreens() {
    welcomeScreen.style.display = 'none';
    memoView.style.display = 'none';
    memoEdit.style.display = 'none';
}

function showDeleteModal() {
    deleteModal.classList.add('active');
}

function hideDeleteModal() {
    deleteModal.classList.remove('active');
}

function showToast(message, type = 'info') {
    const container = document.getElementById('toast-container');
    const toast = document.createElement('div');
    toast.className = `toast ${type}`;
    
    const icon = getToastIcon(type);
    toast.innerHTML = `
        <i class="${icon}"></i>
        <span>${escapeHtml(message)}</span>
    `;
    
    container.appendChild(toast);
    
    // Trigger animation
    setTimeout(() => toast.classList.add('show'), 100);
    
    // Remove toast after 4 seconds
    setTimeout(() => {
        toast.classList.remove('show');
        setTimeout(() => {
            if (toast.parentNode) {
                toast.parentNode.removeChild(toast);
            }
        }, 300);
    }, 4000);
}

function getToastIcon(type) {
    switch (type) {
        case 'success': return 'fas fa-check-circle';
        case 'error': return 'fas fa-exclamation-circle';
        case 'warning': return 'fas fa-exclamation-triangle';
        default: return 'fas fa-info-circle';
    }
}

// Auto-save functionality
let autoSaveTimeout;
async function autoSave() {
    if (!isEditing || !currentMemoId) return;
    
    const title = document.getElementById('edit-title').value.trim();
    const content = document.getElementById('edit-content').value.trim();
    
    if (!title || !content) return;
    
    try {
        await apiRequest(`/api/api.php?path=memo/${currentMemoId}`, {
            method: 'PUT',
            body: JSON.stringify({ title, content })
        });
        
        // Update the memo in local array
        const memoIndex = memos.findIndex(m => m.id == currentMemoId);
        if (memoIndex !== -1) {
            memos[memoIndex].title = title;
            memos[memoIndex].content = content;
            memos[memoIndex].updated_at = new Date().toISOString();
        }
        
        // Update sidebar
        renderMemoList(memos);
        
        showToast('Auto-saved', 'success');
    } catch (error) {
        console.error('Auto-save failed:', error);
    }
}

// Utility functions
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

function escapeHtml(text) {
    const map = {
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#039;'
    };
    return text.replace(/[&<>"']/g, m => map[m]);
}

function formatDate(dateString) {
    // Database sudah menyimpan waktu sesuai timezone user, jadi tidak perlu konversi
    const date = new Date(dateString);
    const now = new Date();
    
    const diff = now - date;
    const days = Math.floor(diff / (1000 * 60 * 60 * 24));
    
    if (days === 0) {
        return 'Today at ' + date.toLocaleTimeString('en-US', { 
            hour: '2-digit', 
            minute: '2-digit'
        });
    } else if (days === 1) {
        return 'Yesterday at ' + date.toLocaleTimeString('en-US', { 
            hour: '2-digit', 
            minute: '2-digit'
        });
    } else if (days < 7) {
        return `${days} days ago`;
    } else {
        return date.toLocaleDateString('en-US', {
            year: 'numeric',
            month: 'short',
            day: 'numeric'
        });
    }
}

function formatDateForSidebar(dateString) {
    // Database sudah menyimpan waktu sesuai timezone user, jadi tidak perlu konversi
    const date = new Date(dateString);
    
    const options = {
        day: 'numeric',
        month: 'short',
        year: 'numeric'
    };
    
    return date.toLocaleDateString('en-US', options);
}

function formatTimeAgo(dateString) {
    // Database sudah menyimpan waktu sesuai timezone user, jadi tidak perlu konversi
    const date = new Date(dateString);
    const now = new Date();
    
    const diff = now - date;
    
    const minutes = Math.floor(diff / (1000 * 60));
    const hours = Math.floor(diff / (1000 * 60 * 60));
    const days = Math.floor(diff / (1000 * 60 * 60 * 24));
    
    if (minutes < 1) {
        return 'Just now';
    } else if (minutes < 60) {
        return `${minutes} minute${minutes > 1 ? 's' : ''} ago`;
    } else if (hours < 24) {
        return `${hours} hour${hours > 1 ? 's' : ''} ago`;
    } else if (days < 7) {
        return `${days} day${days > 1 ? 's' : ''} ago`;
    } else {
        return formatDateForSidebar(dateString);
    }
}

// Handle unsaved changes
window.addEventListener('beforeunload', function(e) {
    if (isEditing) {
        const confirmationMessage = 'You have unsaved changes. Are you sure you want to leave?';
        e.returnValue = confirmationMessage;
        return confirmationMessage;
    }
});

// Keyboard shortcuts
document.addEventListener('keydown', function(e) {
    // Ctrl/Cmd + S to save
    if ((e.ctrlKey || e.metaKey) && e.key === 's') {
        e.preventDefault();
        if (isEditing) {
            saveMemo();
        }
    }
    
    // Ctrl/Cmd + N for new memo
    if ((e.ctrlKey || e.metaKey) && e.key === 'n') {
        e.preventDefault();
        createNewMemo();
    }
    
    // Escape to cancel edit
    if (e.key === 'Escape') {
        if (isEditing) {
            cancelEdit();
        } else if (deleteModal.classList.contains('active')) {
            hideDeleteModal();
        }
    }
});
