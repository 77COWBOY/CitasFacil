'use strict';
(() => {
  const root = document.querySelector('[data-calendar]');
  const configNode = document.getElementById('calendar-config');
  if (!root || !configNode) return;
  const state = JSON.parse(configNode.textContent);
  state.view = 'month';
  const $ = selector => root.querySelector(selector);
  const iso = date => `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, '0')}-${String(date.getDate()).padStart(2, '0')}`;
  const date = value => { const [y, m, d] = value.slice(0, 10).split('-').map(Number); return new Date(y, m - 1, d, 12); };
  const addDays = (value, days) => { const result = date(value); result.setDate(result.getDate() + days); return result; };
  const monday = value => { const result = date(value); result.setDate(result.getDate() - (result.getDay() + 6) % 7); return result; };
  const label = value => date(value).toLocaleDateString('es-NI', {day: 'numeric', month: 'long'});
  const escape = value => String(value ?? '').replace(/[&<>"']/g, c => ({'&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'}[c]));
  const minutes = value => Number(value.slice(0, 2)) * 60 + Number(value.slice(3, 5));
  let controller;
  let generation = 0;
  let lastLoaded = Date.now();
  let validData = true;
  root.classList.add('calendar-enhanced');
  root.querySelectorAll('[data-nojs]').forEach(node => { node.hidden = true; });
  root.querySelectorAll('[data-js-only]').forEach(node => { node.hidden = false; });

  function daysWithHours() {
    return new Set(state.data.schedules.filter(s => s.slots.some(slot => slot.available)).map(s => s.date));
  }
  function renderMonth() {
    const first = monday(state.month);
    const available = daysWithHours();
    const booked = new Set(state.data.appointments.map(a => a.start.slice(0, 10)));
    const cells = [];
    for (let i = 0; i < 42; i++) {
      const current = addDays(iso(first), i);
      const key = iso(current);
      const classes = ['calendar-day', key.slice(0, 7) !== state.month.slice(0, 7) ? 'outside' : '', key === state.data.today ? 'today' : '', key === state.selected ? 'selected' : '', available.has(key) ? 'has-availability' : '', booked.has(key) ? 'has-booking' : ''].join(' ');
      cells.push(`<button type="button" class="${classes}" data-date="${key}" aria-label="${escape(label(key))}${available.has(key) ? ', con horas disponibles' : ''}${booked.has(key) ? ', con citas' : ''}" ${key === state.selected ? 'aria-current="date"' : ''}><span>${current.getDate()}</span><span class="day-dots">${available.has(key) ? '<i></i>' : ''}${booked.has(key) ? '<i class="booked"></i>' : ''}</span></button>`);
    }
    $('[data-calendar-grid]').innerHTML = cells.join('');
  }
  function renderWeek() {
    const first = monday(state.selected);
    const keys = Array.from({length: 7}, (_, i) => iso(addDays(iso(first), i)));
    const sessions = state.data.schedules.filter(s => keys.includes(s.date));
    const appointments = state.data.appointments.filter(a => keys.includes(a.start.slice(0, 10)));
    const fromHour = Math.max(0, Math.min(8, ...sessions.map(s => Math.floor(minutes(s.start) / 60)), ...appointments.map(a => Math.floor(minutes(a.start.slice(11, 16)) / 60))));
    const toHour = Math.min(24, Math.max(18, ...sessions.filter(s => s.end).map(s => Math.ceil(minutes(s.end) / 60)), ...appointments.map(a => Math.ceil(minutes(a.end.slice(11, 16)) / 60))));
    const span = (toHour - fromHour) * 60;
    const minimumDuration = Math.min(30, ...sessions.map(s => s.duration).filter(duration => duration >= 15));
    const canvasHeight = state.role === 'p' ? Math.max(460, span / minimumDuration * 26) : 460;
    const position = (start, end) => `top:${Math.max(0, (minutes(start) - fromHour * 60) / span * 100)}%;height:${Math.max(4, (minutes(end) - minutes(start)) / span * 100)}%`;
    const headers = keys.map(key => `<button type="button" class="week-day-heading ${key === state.selected ? 'selected' : ''}" data-date="${key}"><small>${date(key).toLocaleDateString('es-NI', {weekday: 'short'})}</small><strong>${date(key).getDate()}</strong></button>`).join('');
    const scale = Array.from({length: toHour - fromHour + 1}, (_, i) => `<span style="top:${i / (toHour - fromHour) * 100}%">${String(i + fromHour).padStart(2, '0')}:00</span>`).join('');
    const columns = keys.map(key => {
      const windows = sessions.filter(s => s.date === key);
      const events = appointments.filter(a => a.start.startsWith(key));
      if (!state.doctor) return `<div class="week-column overview">${windows.slice(0, 4).map(s => `<button type="button" class="week-event" data-date="${key}"><strong>${escape(s.start)}</strong><small>${escape(s.doctor)}</small></button>`).join('')}${windows.length > 4 ? `<button type="button" class="week-more" data-date="${key}">+${windows.length - 4} horarios</button>` : ''}${events.length ? `<button type="button" class="week-event booked" data-date="${key}">${events.length} consultas</button>` : ''}</div>`;
      const availability = state.role === 'p' ? windows.flatMap(s => s.slots.filter(slot => slot.available).map(slot => `<button type="button" class="week-event free-hour" data-date="${key}" data-slot="${s.id}|${escape(slot.start)}" style="${position(slot.start.slice(11,16), slot.end.slice(11,16))}" aria-label="${escape(label(key))}, ${escape(slot.label)} disponible"><strong>${escape(slot.label)}</strong></button>`)).join('') : windows.filter(s => s.end).map(s => `<button type="button" class="week-event" data-date="${key}" style="${position(s.start, s.end)}"><strong>${escape(s.start)}–${escape(s.end)}</strong><small>Atención</small></button>`).join('');
      return `<div class="week-column">${availability}${events.map(a => `<button type="button" class="week-event booked" data-date="${key}" style="${position(a.start.slice(11, 16), a.end.slice(11, 16))}"><strong>${a.pending_time ? 'Hora pendiente' : escape(a.start.slice(11, 16))}</strong><small>${escape(a.patient || 'Mi cita')}</small></button>`).join('')}</div>`;
    }).join('');
    $('[data-calendar-week]').innerHTML = `<div class="week-heading"><span></span>${headers}</div><div class="week-canvas" style="height:${canvasHeight}px;--hour-step:${100 / (toHour - fromHour)}%"><div class="week-scale">${scale}</div>${columns}</div>`;
  }
  function renderDay() {
    const panel = $('[data-day-panel]');
    const windows = state.data.schedules.filter(s => s.date === state.selected);
    const heading = `<span class="calendar-kicker">${state.role === 'p' ? 'ELIGE TU HORA' : 'AGENDA DEL DÍA'}</span><h3>${escape(label(state.selected))}</h3>`;
    if (state.role === 'p') {
      if (state.blocked) {
        panel.innerHTML = heading + '<div class="calendar-empty"><p>Puedes cambiar la hora de tu cita desde Mis citas.</p><a href="/appointments.php">Gestionar mi cita →</a></div>';
        return;
      }
      const choices = windows.flatMap(s => s.slots.filter(slot => slot.available).map(slot => ({...slot, id: s.id, duration: s.duration}))).sort((a, b) => a.start.localeCompare(b.start));
      const radios = choices.map(slot => `<label class="time-option"><input type="radio" name="slot" value="${slot.id}|${escape(slot.start)}" data-end="${escape(slot.end)}" data-duration="${slot.duration}" required><span>${escape(slot.label)}</span></label>`).join('');
      panel.innerHTML = heading + `<p class="day-panel-description">${choices.length ? 'Selecciona una hora disponible' : 'Un día para descansar'}</p><form method="post" class="time-booking" data-confirm="Confirma la hora elegida." data-confirm-label="${state.reschedule ? 'Confirmar cambio' : 'Confirmar cita'}"><input type="hidden" name="csrf" value="${escape(state.csrf)}"><input type="hidden" name="action" value="${state.reschedule ? 'reschedule' : 'book'}"><input type="hidden" name="appointment_id" value="${state.reschedule}"><div class="time-options">${radios}</div>${choices.length ? '<p class="booking-selection" data-booking-selection>Elige una hora para continuar.</p>' : '<p class="calendar-empty">No hay horas disponibles. Elige otro día marcado en azul o cambia de médico.</p>'}<button class="booking-submit" ${choices.length ? '' : 'disabled'}>${state.reschedule ? 'Confirmar cambio' : 'Confirmar cita'}</button></form>`;
    } else {
      const events = state.data.appointments.filter(a => a.start.startsWith(state.selected));
      panel.innerHTML = heading + `<div class="day-agenda">${events.length ? events.map(a => `<a class="day-event" href="/appointments.php${a.active ? '' : '?history=1'}#appointment-${a.id}"><strong>${a.pending_time ? 'Hora pendiente' : escape(a.start.slice(11, 16))} · ${escape(a.patient)}</strong><small>${escape(a.doctor)}</small></a>`).join('') : '<p class="calendar-empty">Sin consultas reservadas.</p>'}</div><h4>Disponibilidad</h4>${windows.length ? windows.map(s => `<a class="availability-event" href="/schedule.php?edit=${s.id}#publish"><strong>${escape(s.start)} – ${escape(s.end || 'salida pendiente')}</strong><small>${escape(s.doctor)} · ${s.duration} min</small></a>`).join('') : '<p class="calendar-empty">Sin horarios publicados.</p>'}`;
      const availability = document.createElement('div');
      availability.className = 'day-availability';
      panel.querySelectorAll('.availability-event').forEach(event => availability.append(event));
      panel.append(availability);
    }
  }
  function render() {
    const title = state.view === 'month' ? date(state.month).toLocaleDateString('es-NI', {month: 'long', year: 'numeric'}) : `${label(iso(monday(state.selected)))} – ${label(iso(addDays(iso(monday(state.selected)), 6)))}`;
    $('[data-month-title]').textContent = title.charAt(0).toUpperCase() + title.slice(1);
    $('[data-calendar-grid]').hidden = state.view !== 'month';
    $('.calendar-weekdays').hidden = state.view !== 'month';
    $('[data-calendar-week]').hidden = state.view !== 'week';
    renderMonth();
    if (state.view === 'week') renderWeek();
    renderDay();
    root.querySelectorAll('[data-view]').forEach(button => button.setAttribute('aria-pressed', String(button.dataset.view === state.view)));
    const doctor = state.doctors.find(d => d.id === state.doctor);
    $('[data-doctor-card]').innerHTML = `<span class="avatar">${escape((doctor?.name || 'C').slice(0, 1))}</span><div><strong>${escape(doctor?.name || 'Equipo de la clínica')}</strong><small>${escape(doctor?.specialty || 'Todas las especialidades')}</small></div>`;
    const url = new URL(location.href);
    url.searchParams.set('doctor', state.doctor);
    url.searchParams.set('specialty', state.specialty);
    url.searchParams.set('date', state.selected);
    url.searchParams.set('month', state.month.slice(0, 7));
    history.replaceState(null, '', url);
  }
  async function load(autoPick = false) {
    controller?.abort();
    controller = new AbortController();
    const current = ++generation;
    validData = false;
    root.setAttribute('aria-busy', 'true');
    $('[data-calendar-status]').textContent = 'Actualizando disponibilidad…';
    root.querySelectorAll('.booking-submit').forEach(button => { button.disabled = true; });
    const from = iso(monday(state.month));
    const params = new URLSearchParams({from, to: iso(addDays(from, 42)), doctor: state.doctor, specialty: state.specialty, reschedule: state.reschedule});
    try {
      const response = await fetch(`/calendar-data.php?${params}`, {signal: controller.signal, cache: 'no-store', headers: {'Accept': 'application/json'}});
      if (response.redirected) { location.assign('/login.php'); return; }
      const data = await response.json();
      if (!response.ok) throw new Error(data.error || 'No se pudo cargar la agenda.');
      if (current !== generation) return;
      state.data = data;
      state.blocked = data.blocked;
      validData = true;
      const activeBanner = document.querySelector('.active-booking');
      if (activeBanner) activeBanner.hidden = !state.blocked;
      if (autoPick) {
        const days = [...daysWithHours()].filter(day => day.slice(0, 7) === state.month.slice(0, 7)).sort();
        if (!days.includes(state.selected) && days.length) state.selected = days[0];
      }
      render();
      lastLoaded = Date.now();
      $('[data-calendar-status]').textContent = '';
    } catch (error) {
      if (error.name === 'AbortError') return;
      $('[data-calendar-status]').textContent = error.message + ' ';
      const retry = document.createElement('button');
      retry.type = 'button'; retry.className = 'calendar-retry'; retry.textContent = 'Reintentar'; retry.addEventListener('click', () => load(autoPick));
      $('[data-calendar-status]').append(retry);
    } finally { if (current === generation) root.removeAttribute('aria-busy'); }
  }
  function navigate(direction) {
    if (state.view === 'week') state.selected = iso(addDays(state.selected, direction * 7));
    const next = date(state.view === 'week' ? state.selected : state.month);
    if (state.view === 'month') next.setMonth(next.getMonth() + direction);
    next.setDate(1);
    state.month = iso(next);
    if (state.view === 'month') state.selected = state.month;
    load(state.view === 'month');
  }
  root.addEventListener('click', event => {
    const day = event.target.closest('[data-date]');
    if (day) {
      if (!validData) { event.preventDefault(); return; }
      event.preventDefault(); state.selected = day.dataset.date;
      if (day.dataset.slot) {
        const selectedSlot = day.dataset.slot;
        render();
        const radio = [...root.querySelectorAll('[name="slot"]')].find(input => input.value === selectedSlot);
        if (radio) { radio.checked = true; radio.dispatchEvent(new Event('change', {bubbles: true})); radio.focus(); }
        return;
      }
      if (state.selected.slice(0, 7) !== state.month.slice(0, 7)) { state.month = state.selected.slice(0, 7) + '-01'; load(); }
      else render();
      return;
    }
    const view = event.target.closest('[data-view]');
    if (view) { if (validData) { state.view = view.dataset.view; render(); } return; }
    if (event.target.closest('[data-prev]')) { event.preventDefault(); navigate(-1); }
    if (event.target.closest('[data-next]')) { event.preventDefault(); navigate(1); }
    if (event.target.closest('[data-today]')) { event.preventDefault(); state.selected = state.data.today; state.month = state.selected.slice(0, 7) + '-01'; load(); }
  });
  root.addEventListener('change', event => {
    if (event.target.id === 'calendar-specialty') {
      state.specialty = Number(event.target.value);
      const doctors = state.doctors.filter(d => !state.specialty || d.specialty_id === state.specialty);
      state.doctor = state.role === 'a' ? 0 : (doctors[0]?.id || 0);
      $('#calendar-doctor').innerHTML = (state.role === 'a' ? '<option value="0">Todo el equipo</option>' : '') + doctors.map(d => `<option value="${d.id}">${escape(d.name)}</option>`).join('');
      load(true);
    } else if (event.target.id === 'calendar-doctor') {
      state.doctor = Number(event.target.value); load(true);
    } else if (event.target.matches('[name="slot"]')) {
      const input = event.target;
      const start = input.value.split('|')[1];
      const doctor = state.doctors.find(d => d.id === state.doctor);
      const summary = `${doctor?.name || ''} · ${label(start)} · ${start.slice(11, 16)} – ${input.dataset.end.slice(11, 16)}`;
      input.form.dataset.confirm = summary;
      input.form.querySelector('[data-booking-selection]').textContent = summary;
    }
  });
  $('.calendar-filters').addEventListener('submit', event => event.preventDefault());
  root.addEventListener('submit', event => {
    if (event.target.matches('.time-booking') && !validData) event.preventDefault();
  });
  window.addEventListener('pageshow', event => { if (event.persisted) load(); });
  window.addEventListener('focus', () => { if (Date.now() - lastLoaded > 15000) load(); });
  render();
})();
