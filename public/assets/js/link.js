// Link Activation JavaScript

document.addEventListener('DOMContentLoaded', function() {
    console.log('Link.js loaded');
    const activateForm = document.getElementById('activateForm');
    console.log('Form found:', activateForm);
    
    if (activateForm) {
        activateForm.addEventListener('submit', async function(e) {
            e.preventDefault();
            console.log('Form submitted');
            
            const linkNumber = parseInt(document.getElementById('link_number').value);
            const activationCode = document.getElementById('activation_code').value.trim();
            const messageEl = document.getElementById('message');

            console.log('Form data:', { linkNumber, activationCode });

            if (!linkNumber || !activationCode) {
                console.log('Validation failed: missing fields');
                if (messageEl) {
                    showMessage(messageEl, 'נא למלא את כל השדות', 'error');
                }
                return;
            }

            if (messageEl) {
                showMessage(messageEl, 'מעבד...', 'success');
            }

            try {
                console.log('Calling API.post...');
                const result = await API.post('/api/link/activate', {
                    link_number: linkNumber,
                    activation_code: activationCode
                });
                
                console.log('API result:', result);
                
                if (result.ok && result.redirect_url) {
                    showMessage(messageEl, 'הקישור הופעל בהצלחה!', 'success');
                    setTimeout(() => {
                        window.location.href = result.redirect_url;
                    }, 500);
                } else {
                    const errorMsg = result.message_he || result.message || 'שגיאה בהפעלה';
                    showMessage(messageEl, errorMsg, 'error');
                }
            } catch (error) {
                console.error('Activation error:', error);
                if (messageEl) {
                    showMessage(messageEl, error.message || 'שגיאה בהפעלה. נא לנסות שוב.', 'error');
                }
            }
        });
    } else {
        console.error('activateForm not found!');
    }
});

