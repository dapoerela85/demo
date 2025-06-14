// Fungsi untuk menghasilkan hash MD5 (sederhana, tidak untuk produksi)
function md5(string) {
    return CryptoJS.MD5(string).toString();
}

// Fungsi untuk memeriksa login
async function checkLogin() {
    try {
        const response = await fetch('https://dapoerela85.github.io/demo/_data/admin-credentials.md');
        if (!response.ok) throw new Error('Failed to fetch credentials');
        
        const text = await response.text();
        console.log('Raw file content:', text);  // Debugging output

        // Clean the text and parse credentials
        const cleanText = text.trim();
        
        // Alternative parsing methods:

        // METHOD 1: Simple line parsing
        const lines = cleanText.split('\n');
        const credentials = {};
        lines.forEach(line => {
            const [key, value] = line.split(':').map(part => part.trim());
            if (key && value) credentials[key] = value;
        });

        // METHOD 2: YAML front matter parsing (if using --- delimiters)
        // const yamlContent = cleanText.split('---')[1]; // For Jekyll-style files
        // ...parse yamlContent...

        if (!credentials.username || !credentials.password) {
            console.error('Invalid credentials format');
            return false;
        }

        const inputUsername = document.getElementById('username').value;
        const inputPassword = md5(document.getElementById('password').value);
        
        return inputUsername === credentials.username && 
               inputPassword === credentials.password;

    } catch (error) {
        console.error('Authentication error:', error);
        return false;
    }
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
