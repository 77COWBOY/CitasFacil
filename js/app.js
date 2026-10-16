'use strict';

// Mejora progresiva: los formularios y filtros también funcionan sin JavaScript.
const scheduleForm = document.querySelector('[data-schedule-form]');
if (scheduleForm) {
  const start = scheduleForm.elements.time;
  const end = scheduleForm.elements.endtime;
  const duration = scheduleForm.querySelector('select[name="duration"]');
  const preview = scheduleForm.querySelector('[data-range-preview]');
  const minutes = value => value ? Number(value.slice(0, 2)) * 60 + Number(value.slice(3, 5)) : null;
  const update = () => {
    const from = minutes(start.value);
    const to = minutes(end.value);
    end.setCustomValidity(from !== null && to !== null && to <= from ? 'La salida debe ser posterior al inicio, dentro del mismo día.' : '');
    if (from === null || to === null) preview.textContent = 'Selecciona inicio y salida para ver la duración.';
    else if (to <= from) preview.textContent = 'Revisa el rango: la salida debe ser posterior al inicio.';
    else {
      const windowMinutes = to - from;
      const consultationMinutes = Number(duration.value);
      end.setCustomValidity(windowMinutes < consultationMinutes ? 'El rango debe permitir una consulta completa.' : '');
      preview.textContent = `${start.value} – ${end.value} · Consultas de ${consultationMinutes} minutos.`;
    }
  };
  scheduleForm.addEventListener('input', update);
  update();
}

if (typeof HTMLDialogElement !== 'undefined') {
  const dialog = document.createElement('dialog');
  dialog.className = 'confirmation';
  dialog.setAttribute('aria-labelledby', 'confirmation-title');
  dialog.setAttribute('aria-describedby', 'confirmation-copy');
  dialog.innerHTML = '<form method="dialog"><h2 id="confirmation-title">Revisa los detalles</h2><p id="confirmation-copy"></p><div class="form-actions"><button class="secondary" value="back" autofocus>Volver</button><button value="confirm">Confirmar</button></div></form>';
  document.body.append(dialog);
  let pending = null;
  let trigger = null;
  let triggerLabel = '';
  let submitted = false;
  document.addEventListener('submit', event => {
    const form = event.target;
    if (event.defaultPrevented || !form.matches('form[data-confirm]') || submitted) return;
    event.preventDefault();
    pending = form;
    trigger = event.submitter;
    triggerLabel = trigger?.textContent || '';
    const slot = form.querySelector('[name="slot"]:checked');
    if (slot && !form.querySelector('[data-booking-selection]')?.textContent.includes('·')) form.dataset.confirm = `Tu consulta: ${slot.value.split('|')[1]} – ${slot.dataset.end?.slice(11,16) || ''}.`;
    dialog.querySelector('#confirmation-copy').textContent = form.dataset.confirm;
    dialog.querySelector('[value="confirm"]').textContent = form.dataset.confirmLabel || 'Confirmar';
    dialog.returnValue = '';
    dialog.showModal();
  });
  dialog.addEventListener('close', () => {
    if (dialog.returnValue === 'confirm' && pending) {
      submitted = true;
      if (trigger) { trigger.disabled = true; trigger.textContent = 'Guardando…'; }
      HTMLFormElement.prototype.submit.call(pending);
    } else trigger?.focus();
    pending = null;
  });
  window.addEventListener('pageshow', () => {
    submitted = false;
    if (trigger) { trigger.disabled = false; trigger.textContent = triggerLabel; }
  });
}
const publish = document.getElementById('publish');
const revealPublish = () => { if (publish && location.hash === '#publish') publish.open = true; };
window.addEventListener('hashchange', revealPublish);
revealPublish();
