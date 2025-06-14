// MD5 hashing using CryptoJS
function md5(string) {
    return CryptoJS.MD5(string).toString();
}

// Login verification function
async function checkLogin() {
    try {
        // Temporary hardcoded credentials
        const storedUsername = 'admin';
        const storedPassword = '5f4dcc3b5aa765d61d8327deb882cf99'; // MD5 of 'password'
        
        const inputUsername = document.getElementById('username').value.trim();
        const inputPassword = md5(document.getElementById('password').value);
        
        console.log('Input hash:', inputPassword); // Debugging
        
        return inputUsername === storedUsername && 
               inputPassword === storedPassword;
    } catch (error) {
        console.error('Login error:', error);
        return false;
    }
}

// Form submission handler
document.getElementById('loginForm')?.addEventListener('submit', async function(e) {
    e.preventDefault();
    
    if (await checkLogin()) {
        localStorage.setItem('adminAuthenticated', 'true');
        window.location.href = 'dashboard.html';
    } else {
        document.getElementById('errorMessage').textContent = 'Username atau password salah';
    }
});

// Session verification for admin pages
if (window.location.pathname.includes('/admin/') && 
    !window.location.pathname.includes('login.html') &&
    localStorage.getItem('adminAuthenticated') !== 'true') {
    window.location.href = 'login.html';
}

// Logout handler
if (window.location.pathname.includes('logout.html')) {
    localStorage.removeItem('adminAuthenticated');
    window.location.href = 'login.html';
}
