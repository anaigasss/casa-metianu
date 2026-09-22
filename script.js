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
    const response = await fetch('https://formsubmit.co/ajax/mihai.tomoiaga@grillwine.ro', {
      method: 'POST',
      headers: { Accept: 'application/json' },
      body: new FormData(bookingForm),
    });
    if (!response.ok) throw new Error('Submission failed');
    bookingForm.reset();
    formStatus.className = 'form-status success';
    formStatus.textContent = 'Cererea a fost trimisă. Echipa Casa Mețianu te va contacta pentru confirmare.';
  } catch (error) {
    formStatus.className = 'form-status error';
    formStatus.innerHTML = 'Cererea nu a putut fi trimisă. Te rugăm să ne scrii la <a href="mailto:mihai.tomoiaga@grillwine.ro">mihai.tomoiaga@grillwine.ro</a>.';
  } finally {
    submitButton.disabled = false;
    submitButton.textContent = 'Trimite cererea';
  }
});
