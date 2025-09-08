document.addEventListener('DOMContentLoaded', function() {
    const form = document.querySelector('form');
    const submitBtn = document.querySelector('button[type="submit"]');
    
    // Add loading state to submit button
    function setLoading(loading) {
        if (loading) {
            submitBtn.disabled = true;
            submitBtn.textContent = 'Logging in...';
        } else {
            submitBtn.disabled = false;
            submitBtn.textContent = 'Login';
        }
    }
    
    // Show message to user
    function showMessage(message, isError = false) {
        // Remove existing message if any
        const existingMessage = document.querySelector('.message');
        if (existingMessage) {
            existingMessage.remove();
        }
        
        // Create new message element
        const messageDiv = document.createElement('div');
        messageDiv.className = `message ${isError ? 'error' : 'success'}`;
        messageDiv.textContent = message;
        messageDiv.style.cssText = `
            padding: 10px;
            margin: 10px 0;
            border-radius: 4px;
            color: white;
            font-weight: bold;
            text-align: center;
        `;
        
        if (isError) {
            messageDiv.style.backgroundColor = '#dc3545';
        } else {
            messageDiv.style.backgroundColor = '#28a745';
        }
        
        // Insert message after the form
        form.parentNode.insertBefore(messageDiv, form.nextSibling);
        
        // Auto-remove success messages after 3 seconds
        if (!isError) {
            setTimeout(() => {
                if (messageDiv.parentNode) {
                    messageDiv.remove();
                }
            }, 3000);
        }
    }
    
    // Client-side validation
    function validateForm() {
        const username = form.querySelector('input[name="username"]').value.trim();
        const password = form.querySelector('input[name="password"]').value.trim();
        
        const errors = [];
        
        if (!username) {
            errors.push('Username is required');
        }
        
        if (!password) {
            errors.push('Password is required');
        }
        
        return errors;
    }
    
    // Handle form submission
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        
        // Validate form
        const errors = validateForm();
        if (errors.length > 0) {
            showMessage(errors.join(', '), true);
            return;
        }
        
        // Set loading state
        setLoading(true);
        
        // Create FormData object
        const formData = new FormData(form);
        
        // Send AJAX request
        fetch('../../php/superadmin/super_admin_login.php', {
            method: 'POST',
            body: formData
        })
        .then(response => {
            console.log('Response status:', response.status);
            
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            
            return response.text(); // Get as text first to debug
        })
        .then(text => {
            console.log('Raw response:', text);
            
            try {
                const data = JSON.parse(text);
                setLoading(false);
                
                if (data.success) {
                    showMessage(data.message, false);
                    
                    // Redirect after successful login
                    setTimeout(() => {
                        if (data.redirect) {
                            window.location.href = data.redirect;
                        } else {
                            // Default redirect to dashboard
                            window.location.href = '../../views/superadmin/dashboard.html';
                        }
                    }, 1500);
                } else {
                    showMessage(data.message, true);
                }
            } catch (parseError) {
                console.error('JSON parse error:', parseError);
                console.error('Response text:', text);
                setLoading(false);
                showMessage('Server returned invalid response. Please check if PHP is running.', true);
            }
        })
        .catch(error => {
            setLoading(false);
            console.error('Fetch error:', error);
            showMessage('Failed to connect to server. Please ensure PHP server is running.', true);
        });
    });
    
    // Real-time validation feedback
    const inputs = form.querySelectorAll('input[required]');
    inputs.forEach(input => {
        input.addEventListener('blur', function() {
            const errors = validateForm();
            if (errors.length > 0) {
                // Remove existing error styling
                input.style.borderColor = '';
                input.style.borderWidth = '';
                
                // Add error styling
                input.style.borderColor = '#dc3545';
                input.style.borderWidth = '2px';
            } else {
                // Remove error styling
                input.style.borderColor = '';
                input.style.borderWidth = '';
            }
        });
    });
    
    // Add Enter key support for better UX
    inputs.forEach(input => {
        input.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                form.dispatchEvent(new Event('submit'));
            }
        });
    });
});
