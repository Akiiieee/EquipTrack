document.addEventListener('DOMContentLoaded', function() {
    const form = document.querySelector('form');
    const submitBtn = document.querySelector('button[type="submit"]');
    
    // Add loading state to submit button
    function setLoading(loading) {
        if (loading) {
            submitBtn.disabled = true;
            submitBtn.textContent = 'Adding...';
        } else {
            submitBtn.disabled = false;
            submitBtn.textContent = 'Add';
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
        
        // Auto-remove success messages after 5 seconds
        if (!isError) {
            setTimeout(() => {
                if (messageDiv.parentNode) {
                    messageDiv.remove();
                }
            }, 5000);
        }
    }
    
    // Client-side validation
    function validateForm() {
        const username = form.querySelector('input[name="username"]').value.trim();
        const password = form.querySelector('input[name="password"]').value.trim();
        const email = form.querySelector('input[name="email"]').value.trim();
        
        const errors = [];
        
        if (!username) {
            errors.push('Username is required');
        }
        
        if (!password) {
            errors.push('Password is required');
        } else if (password.length < 6) {
            errors.push('Password must be at least 6 characters long');
        }
        
        if (!email) {
            errors.push('Email is required');
        } else if (!isValidEmail(email)) {
            errors.push('Invalid email format');
        }
        
        return errors;
    }
    
    // Email validation helper
    function isValidEmail(email) {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return emailRegex.test(email);
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
        fetch('../../php/superadmin/add_super_admin.php', {
            method: 'POST',
            body: formData
        })
        .then(response => {
            console.log('Response status:', response.status);
            console.log('Response headers:', response.headers);
            
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
                    form.reset(); // Clear form on success
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
});
