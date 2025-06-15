// MD5 hashing with input validation
function md5(string) {
    if (!string || typeof string !== 'string') return '';
    return CryptoJS.MD5(string).toString();
}

// Login verification
async function checkLogin() {
    try {
        // Expected credentials
        const storedUsername = 'admin';
        const correctPassword = 'password'; // The actual password, not the hash
        
        // Get user inputs
        const inputUsername = document.getElementById('username').value.trim();
        const inputPassword = document.getElementById('password').value;
        
        console.log('Raw inputs:', {
            username: inputUsername,
            password: inputPassword,
            passwordLength: inputPassword.length
        });

        // Compare directly (not recommended for production)
        const isAuthenticated = (inputUsername === storedUsername) && 
                              (inputPassword === correctPassword);
        
        // Or compare hashes (better)
        // const isAuthenticated = (inputUsername === storedUsername) && 
        //                       (md5(inputPassword) === '5f4dcc3b5aa765d61d8327deb882cf99');
        
        console.log('Authentication result:', isAuthenticated);
        return isAuthenticated;

    } catch (error) {
        console.error('Login check failed:', error);
        return false;
    }
}

// Form submission handler
document.getElementById('loginForm')?.addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const errorElement = document.getElementById('errorMessage');
    
    if (await checkLogin()) {
        localStorage.setItem('adminAuthenticated', 'true');
        window.location.href = 'dashboard.html';
    } else {
        errorElement.textContent = 'Invalid username or password';
        errorElement.style.display = 'block';
        document.getElementById('password').value = '';
    }
});

// Session management
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
