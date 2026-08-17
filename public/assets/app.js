let deferredInstallPrompt = null;

const isStandaloneMode = () => window.matchMedia('(display-mode: standalone)').matches
  || window.navigator.standalone === true;

const showInstallButtons = (visible) => {
  document.querySelectorAll('[data-pwa-install]').forEach((button) => {
    button.classList.toggle('d-none', !visible || isStandaloneMode());
  });
};

window.addEventListener('beforeinstallprompt', (event) => {
  event.preventDefault();
  deferredInstallPrompt = event;
  showInstallButtons(true);
});

window.addEventListener('appinstalled', () => {
  deferredInstallPrompt = null;
  showInstallButtons(false);
});

document.addEventListener('DOMContentLoaded', () => {
  document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach((element) => {
    if (window.bootstrap) new window.bootstrap.Tooltip(element);
  });

  document.querySelectorAll('[data-password-toggle]').forEach((button) => {
    button.addEventListener('click', () => {
      const input = document.querySelector(button.dataset.passwordToggle);
      if (!(input instanceof HTMLInputElement)) return;
      const visible = input.type === 'text';
      input.type = visible ? 'password' : 'text';
      const icon = button.querySelector('i');
      if (icon) icon.className = visible ? 'bi bi-eye' : 'bi bi-eye-slash';
      button.setAttribute('aria-label', visible ? 'Mostrar contraseña' : 'Ocultar contraseña');
    });
  });

  document.querySelectorAll('form[data-confirm]').forEach((form) => {
    form.addEventListener('submit', (event) => {
      if (!window.confirm(form.dataset.confirm || '¿Confirmas esta acción?')) event.preventDefault();
    });
  });

  document.querySelectorAll('.record-form').forEach((form) => {
    const calculate = () => {
      const value = (name) => Number.parseFloat(form.querySelector(`[name="${name}"]`)?.value || '0') || 0;
      const writeIfEmpty = (name, result) => {
        const field = form.querySelector(`[name="${name}"]`);
        if (field instanceof HTMLInputElement && (!field.value || Number(field.value) === 0)) field.value = result.toFixed(2);
      };
      if (form.querySelector('[name="net_amount"]')) {
        const net = value('net_amount');
        writeIfEmpty('tax_amount', net * .19);
        writeIfEmpty('total', net + value('tax_amount'));
      }
      if (form.querySelector('[name="liters"]')) writeIfEmpty('total_cost', value('liters') * value('unit_cost'));
    };
    form.querySelectorAll('input[type="number"]').forEach((input) => input.addEventListener('blur', calculate));
  });

  document.addEventListener('submit', (event) => {
    const form = event.target;
    if (!(form instanceof HTMLFormElement) || event.defaultPrevented) return;
    const button = form.querySelector('button[type="submit"]');
    if (!(button instanceof HTMLButtonElement)) return;
    button.disabled = true;
    const text = button.querySelector('span');
    if (text) text.textContent = 'Procesando…';
  });

  const isIos = /iphone|ipad|ipod/i.test(window.navigator.userAgent)
    || (/macintosh/i.test(window.navigator.userAgent) && window.navigator.maxTouchPoints > 1);
  if (isIos && !isStandaloneMode()) showInstallButtons(true);

  document.querySelectorAll('[data-pwa-install]').forEach((button) => {
    button.addEventListener('click', async () => {
      if (deferredInstallPrompt) {
        deferredInstallPrompt.prompt();
        await deferredInstallPrompt.userChoice;
        deferredInstallPrompt = null;
        showInstallButtons(false);
        return;
      }

      const instructions = isIos
        ? 'En Safari, toca Compartir y luego “Agregar a pantalla de inicio”.'
        : 'Abre el menú del navegador y selecciona “Instalar aplicación” o “Agregar a pantalla de inicio”.';
      window.alert(instructions);
    });
  });

  if ('serviceWorker' in navigator) {
    const configuredBase = document.documentElement.dataset.basePath || '';
    const scope = `${configuredBase.replace(/\/$/, '')}/` || '/';
    navigator.serviceWorker.register(`${scope}sw.js`, { scope }).catch(() => {
      // La aplicación continúa funcionando normalmente si el navegador bloquea PWA.
    });
  }
});
