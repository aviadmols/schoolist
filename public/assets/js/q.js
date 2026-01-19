// Q Activation JavaScript

document.addEventListener('DOMContentLoaded', function() {
    const activateForm = document.getElementById('activateForm');
    
    if (activateForm) {
        activateForm.addEventListener('submit', async function(e) {
            e.preventDefault();
            const qNumber = parseInt(document.getElementById('q_number').value);
            const pageUniqueId = parseInt(document.getElementById('page_unique_id').value);
            const messageEl = document.getElementById('message');

            try {
                const result = await API.post('/api/q/activate', {
                    q_number: qNumber,
                    page_unique_id: pageUniqueId
                });
                
                if (result.ok) {
                    showMessage(messageEl, 'הקישור הופעל בהצלחה!', 'success');
                    setTimeout(() => {
                        window.location.href = result.redirect_url;
                    }, 1500);
                }
            } catch (error) {
                showMessage(messageEl, error.message, 'error');
            }
        });
    }
});



