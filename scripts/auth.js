// Ensure CryptoJS is loaded - add this to your HTML head:
// <script src="https://cdnjs.cloudflare.com/ajax/libs/crypto-js/4.1.1/crypto-js.min.js"></script>

// MD5 hashing function
function md5(string) {
    if (typeof CryptoJS === 'undefined') {
        console.error('CryptoJS not loaded!');
        return null;
    }
    return CryptoJS.MD5(string).toString();
}

// Improved checkLogin function
async function checkLogin() {
    try {
        const usernameInput = document.getElementById('username');
        const passwordInput = document.getElementById('password');
        
        if (!usernameInput || !passwordInput) {
            console.error('Username or password input not found');
            return false;
        }

        // Hardcoded credentials (for demo only)
        const storedUsername = 'admin';
        const storedPasswordHash = '5f4dcc3b5aa765d61d8327deb882cf99'; // 'password'
        
        const inputUsername = usernameInput.value.trim();
        const inputPasswordHash = md5(passwordInput.value);
        
        if (!inputPasswordHash) {
            console.error('Hashing failed');
            return false;
        }

        console.log('Comparing:', {
            inputUsername,
            storedUsername,
            inputPasswordHash,
            storedPasswordHash
        });

        return inputUsername === storedUsername && 
               inputPasswordHash === storedPasswordHash;
    } catch (error) {
        console.error('Login check failed:', error);
        return false;
    }
}

// Proper form event handling
function initializeLoginForm() {
    const loginForm = document.getElementById('loginForm');
    
    if (!loginForm) {
        console.error('Login form not found');
        return;
    }
    
    loginForm.addEventListener('submit', async function(e) {
        e.preventDefault();
        
        const errorElement = document.getElementById('errorMessage') || 
                           document.createElement('div');
        
        try {
            const isAuthenticated = await checkLogin();
            
            if (isAuthenticated) {
                localStorage.setItem('adminAuthenticated', 'true');
                window.location.href = 'dashboard.html';
            } else {
                errorElement.textContent = 'Username atau password salah';
                errorElement.style.color = 'red';
                loginForm.appendChild(errorElement);
            }
        } catch (error) {
            console.error('Login error:', error);
            errorElement.textContent = 'Terjadi kesalahan saat login';
            loginForm.appendChild(errorElement);
        }
    });
}

// Initialize when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    initializeLoginForm();
    
    // Session verification for admin pages
    if (window.location.pathname.includes('/admin/') && 
        !window.location.pathname.includes('login.html') &&
        localStorage.getItem('adminAuthenticated') !== 'true') {
        window.location.href = 'login.html';
    }
});

// Logout handler
if (window.location.pathname.includes('logout.html')) {
    localStorage.removeItem('adminAuthenticated');
    window.location.href = 'login.html';
}
