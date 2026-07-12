/* Buscardini — Coming Soon
   Endpoints (backend: PHP + PHPMailer):
   POST /process/contact   { name, email, message }  → 2xx on success
   POST /process/subscribe { email }                 → 2xx on success
*/

(function () {
  'use strict';

  var CONTACT_URL = '/process/contact';
  var SUBSCRIBE_URL = '/process/subscribe';

  function post(url, data) {
    return fetch(url, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(data)
    }).then(function (res) {
      if (!res.ok) throw new Error('HTTP ' + res.status);
      return res;
    });
  }

  function showError(el, msg) {
    el.textContent = msg;
    el.hidden = false;
  }

  function hideError(el) {
    el.textContent = '';
    el.hidden = true;
  }

  /* ---------- Modal ---------- */

  var modal = document.getElementById('contact-modal');
  var contactForm = document.getElementById('contact-form');
  var contactSuccess = document.getElementById('contact-success');
  var contactError = document.getElementById('contact-error');
  var contactSubmit = document.getElementById('contact-submit');
  var sending = false;

  function openModal() {
    contactForm.hidden = false;
    contactSuccess.hidden = true;
    hideError(contactError);
    modal.hidden = false;
    var first = contactForm.querySelector('input');
    if (first) first.focus();
  }

  function closeModal() {
    modal.hidden = true;
  }

  document.querySelectorAll('[data-open-modal]').forEach(function (el) {
    el.addEventListener('click', openModal);
  });

  document.querySelectorAll('[data-close-modal]').forEach(function (el) {
    el.addEventListener('click', closeModal);
  });

  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape' && !modal.hidden) closeModal();
  });

  /* ---------- Contact form ---------- */

  contactForm.addEventListener('submit', function (e) {
    e.preventDefault();
    if (sending) return;

    var name = contactForm.elements.name.value.trim();
    var email = contactForm.elements.email.value.trim();
    var message = contactForm.elements.message.value.trim();

    if (!name || !email || !message) {
      showError(contactError, 'Preencha todos os campos.');
      return;
    }

    sending = true;
    hideError(contactError);
    contactSubmit.textContent = 'A enviar…';

    post(CONTACT_URL, { name: name, email: email, message: message })
      .then(function () {
        contactForm.reset();
        contactForm.hidden = true;
        contactSuccess.hidden = false;
      })
      .catch(function () {
        showError(contactError, 'Não foi possível enviar. Tente novamente.');
      })
      .finally(function () {
        sending = false;
        contactSubmit.textContent = 'Enviar mensagem';
      });
  });

  /* ---------- Newsletter ---------- */

  var newsletterForm = document.getElementById('newsletter-form');
  var newsletterError = document.getElementById('newsletter-error');
  var newsletterDone = document.getElementById('newsletter-done');
  var subscribing = false;

  newsletterForm.addEventListener('submit', function (e) {
    e.preventDefault();
    if (subscribing) return;

    var email = newsletterForm.elements.email.value.trim();

    if (!email || email.indexOf('@') === -1) {
      showError(newsletterError, 'Introduza um email válido.');
      return;
    }

    subscribing = true;
    hideError(newsletterError);

    post(SUBSCRIBE_URL, { email: email })
      .then(function () {
        newsletterForm.hidden = true;
        newsletterDone.hidden = false;
      })
      .catch(function () {
        showError(newsletterError, 'Não foi possível subscrever. Tente novamente.');
      })
      .finally(function () {
        subscribing = false;
      });
  });

})();
