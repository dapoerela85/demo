// MD5 hashing with input validation
function md5(string) {
    if (!string || typeof string !== 'string') {
        console.error('Invalid input for MD5 function');
        return '';
    }
    return CryptoJS.MD5(string).toString();
}

// Login verification with debug logging
async function checkLogin() {
    try {
        const storedUsername = 'admin';
        const storedPasswordHash = '5f4dcc3b5aa765d61d8327deb882cf99'; // MD5 of "password"
        
        const usernameInput = document.getElementById('username');
        const passwordInput = document.getElementById('password');
        
        if (!usernameInput || !passwordInput) {
            console.error('Could not find username/password inputs');
            return false;
        }

        const inputUsername = usernameInput.value.trim();
        const inputPassword = passwordInput.value;
        
        console.log('Input values:', {
            username: inputUsername,
            password: inputPassword,
            passwordLength: inputPassword.length
        });

        const inputPasswordHash = md5(inputPassword);
        console.log('Generated hash:', inputPasswordHash);

        const isAuthenticated = (inputUsername === storedUsername) && 
                              (inputPasswordHash === storedPasswordHash);
        
        console.log('Authentication result:', isAuthenticated);
        return isAuthenticated;

    } catch (error) {
        console.error('Login check failed:', error);
        return false;
    }
}

// Form submission handler with better error messages
document.getElementById('loginForm')?.addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const errorElement = document.getElementById('errorMessage');
    
    if (await checkLogin()) {
        localStorage.setItem('adminAuthenticated', 'true');
        window.location.href = 'dashboard.html';
    } else {
        errorElement.textContent = 'Invalid username or password';
        errorElement.style.display = 'block';
        
        // Clear password field
        document.getElementById('password').value = '';
    }
});

// Session management (unchanged)
if (window.location.pathname.includes('/admin/') && 
    !window.location.pathname.includes('login.html') &&
    localStorage.getItem('adminAuthenticated') !== 'true') {
    window.location.href = 'login.html';
}

// Logout handler (unchanged)
if (window.location.pathname.includes('logout.html')) {
    localStorage.removeItem('adminAuthenticated');
    window.location.href = 'login.html';
}
