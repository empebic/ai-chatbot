(function(){
  function ajax(url, opts){
    return fetch(url, opts).then(function(r){ return r.json(); });
  }

  function pollStatus(invoice, onPaid){
    var tries = 0;
    var maxTries = 60; // ~60s
    function step(){
      tries++;
      ajax((window.WPBN && WPBN.restUrl ? WPBN.restUrl : (window.wpbnRestUrl || '/wp-json/wpbn/v1/')) + 'status/' + encodeURIComponent(invoice), { credentials: 'same-origin' })
        .then(function(json){
          if (json && json.paid) { onPaid(json.redirect || '/'); return; }
          if (tries < maxTries) setTimeout(step, 1000);
        })
        .catch(function(){ if (tries < maxTries) setTimeout(step, 1500); });
    }
    step();
  }

  function createModal(){
    var backdrop = document.createElement('div');
    backdrop.className = 'wpbn-modal-backdrop';
    var modal = document.createElement('div');
    modal.className = 'wpbn-modal';
    modal.innerHTML = '<h3>Scan to Pay</h3><div class="wpbn-qr"><iframe class="wpbn-qr-frame" style="width:320px;height:380px;border:none"></iframe></div><div class="wpbn-actions"><button class="wpbn-close">Close</button></div>';
    backdrop.appendChild(modal);
    document.body.appendChild(backdrop);
    backdrop.addEventListener('click', function(e){ if(e.target===backdrop) backdrop.style.display='none'; });
    modal.querySelector('.wpbn-close').addEventListener('click', function(){ backdrop.style.display='none'; });
    return { backdrop: backdrop, frame: modal.querySelector('.wpbn-qr-frame') };
  }

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
        var invoice = json.data.invoice_id;
        var payUrl = json.data.payment_url;
        // Try inline modal for Coinsnap-compatible checkout link; otherwise redirect
        try {
          var modal = createModal();
          modal.frame.src = payUrl;
          modal.backdrop.style.display = 'flex';
          pollStatus(invoice, function(redirect){ window.location.href = redirect; });
        } catch (e) {
          window.location.href = payUrl;
        }
      })
      .catch(function(){ alert('Network error. Please try again.'); });
  }

  document.addEventListener('submit', handleSubmit, false);
})();
