const menuToggle = document.querySelector('.menu-toggle');
const mainNav = document.querySelector('.main-nav');

menuToggle?.addEventListener('click', () => {
  const isOpen = mainNav.classList.toggle('open');
  menuToggle.setAttribute('aria-expanded', String(isOpen));
});

mainNav?.querySelectorAll('a').forEach((link) => {
  link.addEventListener('click', () => {
    mainNav.classList.remove('open');
    menuToggle?.setAttribute('aria-expanded', 'false');
  });
});

const menuTabs = document.querySelectorAll('[data-menu-tab]');
const menuPanels = document.querySelectorAll('.menu-panel');

menuTabs.forEach((tab) => {
  tab.addEventListener('click', () => {
    menuTabs.forEach((item) => {
      const isActive = item === tab;
      item.classList.toggle('is-active', isActive);
      item.setAttribute('aria-selected', String(isActive));
    });

    menuPanels.forEach((panel) => {
      panel.hidden = panel.id !== tab.dataset.menuTab;
    });
  });
});

const bookingForm = document.querySelector('#booking-form');
const bookingDate = document.querySelector('#booking-date');
const formStatus = document.querySelector('#form-status');

if (bookingDate) {
  const today = new Date();
  bookingDate.min = new Date(today.getTime() - today.getTimezoneOffset() * 60000)
    .toISOString()
    .split('T')[0];
}

bookingForm?.addEventListener('submit', async (event) => {
  event.preventDefault();
  if (!bookingForm.checkValidity()) {
    bookingForm.reportValidity();
    return;
  }

  const submitButton = bookingForm.querySelector('.booking-submit');
  submitButton.disabled = true;
  submitButton.textContent = 'Se trimite…';
  formStatus.className = 'form-status';
  formStatus.textContent = '';

  try {
    const response = await fetch(bookingForm.action, {
      method: 'POST',
      headers: { Accept: 'application/json' },
      body: new FormData(bookingForm),
    });
    const result = await response.json().catch(() => ({}));
    if (!response.ok || !result.success) {
      throw new Error(result.message || 'Submission failed');
    }
    bookingForm.reset();
    formStatus.className = 'form-status success';
    formStatus.textContent = 'Mulțumim! Cererea ta de rezervare a fost trimisă cu succes.\n\nAceasta este o cerere de rezervare și nu reprezintă o confirmare finală. Vom verifica disponibilitatea și te vom contacta pentru a confirma rezervarea.';
  } catch (error) {
    formStatus.className = 'form-status error';
    formStatus.textContent = 'Cererea nu a putut fi trimisă. Te rugăm să încerci din nou. Dacă problema persistă, ne poți scrie la mihai.tomoiaga@grillwine.ro.';
  } finally {
    submitButton.disabled = false;
    submitButton.textContent = 'Trimite cererea';
  }
});
