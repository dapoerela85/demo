// Fungsi untuk menghasilkan hash MD5 (sederhana, tidak untuk produksi)
function md5(string) {
    return CryptoJS.MD5(string).toString();
}

// Fungsi untuk memeriksa login
async function checkLogin() {
    // Ambil kredensial dari file markdown
    const response = await fetch('../_data/admin-credentials.md');
    const text = await response.text();
    const credentials = text.match(/username: (.*)\npassword: (.*)/);
    
    if (!credentials) {
        console.error('File kredensial tidak valid');
        return false;
    }
    
    const storedUsername = credentials[1].trim();
    const storedPassword = credentials[2].trim();
    
    const inputUsername = document.getElementById('username').value;
    const inputPassword = md5(document.getElementById('password').value);
    
    return inputUsername === storedUsername && inputPassword === storedPassword;
}

// Handle form login
document.getElementById('loginForm')?.addEventListener('submit', async function(e) {
    e.preventDefault();
    
    if (await checkLogin()) {
        localStorage.setItem('adminAuthenticated', 'true');
        window.location.href = 'dashboard.html';
    } else {
        document.getElementById('errorMessage').textContent = 'Username atau password salah';
    }
});

// Verifikasi sesi untuk halaman admin
if (window.location.pathname.includes('/admin/') && 
    !window.location.pathname.includes('login.html') &&
    localStorage.getItem('adminAuthenticated') !== 'true') {
    window.location.href = 'login.html';
}

// Logout
if (window.location.pathname.includes('logout.html')) {
    localStorage.removeItem('adminAuthenticated');
    window.location.href = 'login.html';
}