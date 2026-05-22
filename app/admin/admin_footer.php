<?php
// admin/admin_footer.php
 $notify_text    = $notify_text ?? '';
 $notify_variant = $notify_variant ?? 'success';
?>
<script>
function triggerTestFire() {
  const btn       = document.getElementById('testFireBtn');
  const select    = document.getElementById('testPatientSelect');
  const resultBox = document.getElementById('testFireResult');
  const patientId = select.value;

  if (!patientId) {
    resultBox.className = 'mt-3 rounded-lg px-4 py-3 text-sm font-medium bg-warning/10 text-warning';
    resultBox.textContent = '⚠ Please select a patient first.';
    resultBox.classList.remove('hidden');
    return;
  }

  // Loading state
  btn.disabled = true;
  btn.innerHTML = '<i class="fa fa-spinner fa-spin text-sm"></i> Firing...';
  resultBox.classList.add('hidden');

  fetch('/MDR/app/cron/test_fire.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
    body: 'patient_id=' + encodeURIComponent(patientId)
  })
  .then(r => r.json())
  .then(data => {
    if (data.success) {
      resultBox.className = 'mt-3 rounded-lg px-4 py-3 text-sm font-medium bg-success/10 text-success';
      resultBox.innerHTML = `✔ ${data.message}`;
      // Reload table after 2.5s to show new log entry
      setTimeout(() => location.reload(), 2500);
    } else {
      resultBox.className = 'mt-3 rounded-lg px-4 py-3 text-sm font-medium bg-error/10 text-error';
      resultBox.innerHTML = `✘ ${data.message}`;
    }
    resultBox.classList.remove('hidden');
  })
  .catch(() => {
    resultBox.className = 'mt-3 rounded-lg px-4 py-3 text-sm font-medium bg-error/10 text-error';
    resultBox.textContent = '✘ Network error — could not reach server.';
    resultBox.classList.remove('hidden');
  })
  .finally(() => {
    btn.disabled = false;
    btn.innerHTML = '<i class="fa fa-bolt text-sm"></i> Test Fire SMS';
  });
}
</script>
<script>
function closeQuickSend() {
  document.getElementById('quickSendModal').classList.add('hidden');
  document.getElementById('qs_result').classList.add('hidden');
  document.getElementById('qs_phone').value   = '';
  document.getElementById('qs_message').value = '';
  document.getElementById('qs_charcount').textContent = '0';
}

function setTemplate(text) {
  const ta = document.getElementById('qs_message');
  ta.value = text;
  document.getElementById('qs_charcount').textContent = text.length;
}

function sendQuickSMS() {
  const btn     = document.getElementById('qs_sendBtn');
  const phone   = document.getElementById('qs_phone').value.trim();
  const message = document.getElementById('qs_message').value.trim();
  const result  = document.getElementById('qs_result');

  // Basic validation
  if (!phone) {
    showQsResult('error', '⚠ Please enter a phone number.');
    return;
  }
  if (!message) {
    showQsResult('error', '⚠ Please enter a message.');
    return;
  }

  btn.disabled = true;
  btn.innerHTML = '<i class="fa fa-spinner fa-spin text-sm"></i> Sending...';
  result.classList.add('hidden');

  fetch('/MDR/app/cron/test_send.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
    body: 'phone=' + encodeURIComponent(phone) + '&message=' + encodeURIComponent(message)
  })
  .then(r => r.json())
  .then(data => {
    if (data.success) {
      showQsResult('success',
        `✔ ${data.message}<br>
         <span class="font-normal opacity-75">
           ID: ${data.message_id ?? 'N/A'} &nbsp;|&nbsp; Cost: ${data.cost ?? 'N/A'}
         </span>`
      );
      // Reload page after 3s to show new log row
      setTimeout(() => { closeQuickSend(); location.reload(); }, 3000);
    } else {
      showQsResult('error', '✘ ' + data.message);
    }
  })
  .catch(() => showQsResult('error', '✘ Network error — could not reach server.'))
  .finally(() => {
    btn.disabled = false;
    btn.innerHTML = '<i class="fa fa-paper-plane text-sm"></i> Send SMS';
  });
}

function showQsResult(type, html) {
  const el = document.getElementById('qs_result');
  el.className = type === 'success'
    ? 'mb-4 rounded-lg px-4 py-3 text-sm font-medium bg-success/10 text-success'
    : 'mb-4 rounded-lg px-4 py-3 text-sm font-medium bg-error/10 text-error';
  el.innerHTML = html;
  el.classList.remove('hidden');
}
</script>
<script>
function closeModal(id) {
  document.getElementById(id).classList.add('hidden');
  // Clear results
  ['facilityResult','drugResult'].forEach(r => {
    const el = document.getElementById(r);
    if (el) { el.classList.add('hidden'); el.textContent = ''; }
  });
}

// Close on Escape
document.addEventListener('keydown', e => {
  if (e.key === 'Escape') {
    ['addFacilityModal','addDrugModal'].forEach(id => {
      document.getElementById(id)?.classList.add('hidden');
    });
  }
});

function showResult(resultId, success, message) {
  const el = document.getElementById(resultId);
  el.className = 'rounded-lg px-4 py-3 text-sm font-medium ' +
    (success ? 'bg-success/10 text-success' : 'bg-error/10 text-error');
  el.innerHTML = (success ? '✔ ' : '✘ ') + message;
  el.classList.remove('hidden');
}

function submitFacility() {
  const btn  = document.getElementById('facilitySubmitBtn');
  const form = document.getElementById('facilityForm');
  const data = new FormData(form);
  data.append('action', 'add_facility');

  btn.disabled = true;
  btn.innerHTML = '<i class="fa fa-spinner fa-spin text-sm"></i> Saving...';

  fetch('../admin/ajax/ajax_add.php', { method: 'POST', body: data })
    .then(r => r.json())
    .then(res => {
      showResult('facilityResult', res.success, res.message);
      if (res.success) {
        form.reset();
        setTimeout(() => closeModal('addFacilityModal'), 2000);
      }
    })
    .catch(() => showResult('facilityResult', false, 'Network error — could not reach server.'))
    .finally(() => {
      btn.disabled = false;
      btn.innerHTML = '<i class="fa fa-hospital text-sm"></i> Save Facility';
    });
}

function submitDrug() {
  const btn  = document.getElementById('drugSubmitBtn');
  const form = document.getElementById('drugForm');
  const data = new FormData(form);
  data.append('action', 'add_drug');

  btn.disabled = true;
  btn.innerHTML = '<i class="fa fa-spinner fa-spin text-sm"></i> Adding...';

  fetch('../admin/ajax/ajax_add.php', { method: 'POST', body: data })
    .then(r => r.json())
    .then(res => {
      showResult('drugResult', res.success, res.message);
      if (res.success) {
        form.reset();
        setTimeout(() => closeModal('addDrugModal'), 2000);
      }
    })
    .catch(() => showResult('drugResult', false, 'Network error — could not reach server.'))
    .finally(() => {
      btn.disabled = false;
      btn.innerHTML = '<i class="fa fa-pills text-sm"></i> Add Drug';
    });
}
</script>
      </main>
    </div>
    <div id="x-teleport-target"></div>
    <script>
      window.addEventListener("DOMContentLoaded", () => Alpine.start());
    </script>
    <?php if ($notify_text): ?>
    <div x-data
         x-init="
            const params = new URLSearchParams(window.location.search);
            if(params.get('status') === '<?= $notify_text ?>') {
                $notification({text:'<?= addslashes($notify_text) ?>', variant:'<?= $notify_variant ?>', position:'right-top'});
                const url = new URL(window.location);
                url.searchParams.delete('status');
                window.history.replaceState({}, document.title, url.pathname);
            }
         "></div>
    <?php endif; ?>

</body>
</html>