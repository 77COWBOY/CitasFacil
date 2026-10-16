'use strict';

const motionButton = document.querySelector('.motion-toggle');
const landing = document.querySelector('.landing');
if (motionButton && landing && document.querySelector('.hero-visual') && location.protocol !== 'file:') {
  const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)');
  const updateControl = () => { motionButton.hidden = reducedMotion.matches; };
  updateControl();
  reducedMotion.addEventListener('change', updateControl);
  motionButton.addEventListener('click', () => {
    const paused = landing.classList.toggle('motion-paused');
    motionButton.setAttribute('aria-pressed', String(paused));
    motionButton.textContent = paused ? 'Reanudar animaciones' : 'Pausar animaciones';
  });
}
