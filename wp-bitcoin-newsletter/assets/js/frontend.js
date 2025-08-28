(function(){
  function handleSubmit(event) {
    var form = event.target;
    if (!form.classList.contains('wpbn-form')) return;
    event.preventDefault();

    var action = form.getAttribute('action');
    var formData = new FormData(form);

    fetch(action, { method: 'POST', body: formData, credentials: 'same-origin' })
      .then(function(res){ return res.json(); })
      .then(function(json){
        if (!json || !json.success) {
          var msg = (json && json.data && json.data.message) ? json.data.message : 'Submission failed';
          alert(msg);
          return;
        }
        if (json.data && json.data.payment_url) {
          window.location.href = json.data.payment_url;
        } else {
          alert('Payment initiation failed.');
        }
      })
      .catch(function(){ alert('Network error. Please try again.'); });
  }

  document.addEventListener('submit', handleSubmit, false);
})();
