// Fungsi untuk menampilkan notifikasi
function showNotification(message, type = 'info') {
    const notification = document.createElement('div');
    notification.className = `notification ${type}`;
    notification.innerHTML = `
        <span class="notification-message">${message}</span>
        <button class="notification-close">&times;</button>
    `;
    
    document.body.appendChild(notification);
    
    // Animasi masuk
    setTimeout(() => notification.classList.add('show'), 100);
    
    // Hapus notifikasi setelah 5 detik
    setTimeout(() => {
        notification.classList.remove('show');
        setTimeout(() => notification.remove(), 300);
    }, 5000);
    
    // Tombol close
    notification.querySelector('.notification-close').addEventListener('click', () => {
        notification.classList.remove('show');
        setTimeout(() => notification.remove(), 300);
    });
}

// Fungsi untuk konfirmasi aksi
function confirmAction(message) {
    return confirm(message);
}

// Format input rupiah
function formatRupiahInput(input) {
    input.addEventListener('input', function(e) {
        let value = this.value.replace(/\D/g, '');
        this.value = new Intl.NumberFormat('id-ID').format(value);
    });
}

// Auto refresh untuk halaman tertentu
function autoRefresh(interval = 30000) {
    if (window.location.pathname.includes('transaksi.php')) {
        setTimeout(() => {
            location.reload();
        }, interval);
    }
}

// Initialize ketika dokumen siap
document.addEventListener('DOMContentLoaded', function() {
    // Auto refresh
    autoRefresh();
    
    // Format semua input rupiah
    document.querySelectorAll('.rupiah-input').forEach(formatRupiahInput);
    
    // Validasi form
    document.querySelectorAll('form').forEach(form => {
        form.addEventListener('submit', function(e) {
            const requiredFields = this.querySelectorAll('[required]');
            let valid = true;
            
            requiredFields.forEach(field => {
                if (!field.value.trim()) {
                    valid = false;
                    field.style.borderColor = '#F44336';
                } else {
                    field.style.borderColor = '#e0e0e0';
                }
            });
            
            if (!valid) {
                e.preventDefault();
                showNotification('Harap isi semua bidang yang wajib diisi!', 'error');
            }
        });
    });
});

// CSS untuk notifikasi
const notificationStyle = document.createElement('style');
notificationStyle.textContent = `
    .notification {
        position: fixed;
        top: 20px;
        right: 20px;
        padding: 15px 20px;
        border-radius: 5px;
        color: white;
        display: flex;
        align-items: center;
        justify-content: space-between;
        min-width: 300px;
        max-width: 500px;
        transform: translateX(400px);
        transition: transform 0.3s ease;
        z-index: 9999;
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    }
    
    .notification.show {
        transform: translateX(0);
    }
    
    .notification.info {
        background: linear-gradient(135deg, #2196F3 0%, #1976D2 100%);
    }
    
    .notification.success {
        background: linear-gradient(135deg, #4CAF50 0%, #2E7D32 100%);
    }
    
    .notification.warning {
        background: linear-gradient(135deg, #FF9800 0%, #F57C00 100%);
    }
    
    .notification.error {
        background: linear-gradient(135deg, #F44336 0%, #D32F2F 100%);
    }
    
    .notification-message {
        flex: 1;
        margin-right: 10px;
    }
    
    .notification-close {
        background: none;
        border: none;
        color: white;
        font-size: 20px;
        cursor: pointer;
        padding: 0;
        width: 24px;
        height: 24px;
        display: flex;
        align-items: center;
        justify-content: center;
    }
`;
document.head.appendChild(notificationStyle);