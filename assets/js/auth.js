async function postJSON(url, payload) {
  const r = await fetch(url, {
    method: 'POST',
    headers: {'Content-Type': 'application/json'},
    body: JSON.stringify(payload),
    credentials: 'include'
  });
  return r.json();
}

document.addEventListener('DOMContentLoaded', () => {
  // Password visibility toggler
  const passwordToggles = document.querySelectorAll('.password-toggle');
  passwordToggles.forEach(toggle => {
    const input = toggle.querySelector('input[type="password"], input[type="text"]');
    const button = toggle.querySelector('.password-toggle-btn');
    
    if (input && button) {
      button.addEventListener('click', () => {
        const isPassword = input.type === 'password';
        input.type = isPassword ? 'text' : 'password';
        button.innerHTML = isPassword ? 
          '<i class="bi bi-eye-slash"></i>' : 
          '<i class="bi bi-eye"></i>';
      });
    }
  });

  const loginForm = document.getElementById('loginForm');
  if (loginForm) {
    loginForm.addEventListener('submit', async (e) => {
      e.preventDefault();
      const form = new FormData(loginForm);
      const data = Object.fromEntries(form.entries());
      const res = await postJSON('/lazio_site/auth/login.php', data);
      const alert = document.getElementById('loginAlert');
      if (res.ok) {
        window.location.href = res.redirect || '/lazio_site/pages/welcome.php';
      } else {
        alert.classList.remove('d-none'); alert.textContent = res.message || 'Login failed';
      }
    });
  }

  const signupForm = document.getElementById('signupForm');
  if (signupForm) {
    const submitBtn = document.getElementById('signupSubmit');
    const alertBox = document.getElementById('signupAlert');
    const inputs = signupForm.querySelectorAll('input, select');
    const phoneInput = signupForm.phone;
    const phoneFeedback = document.getElementById('phoneFeedback');

    phoneInput.addEventListener('input', (e) => {
      const original = e.target.value;
      const filtered = original.replace(/\D/g, '');
      e.target.value = filtered;

      // Show feedback if non-numeric was entered or length is not 10
      if (original !== filtered) {
        phoneFeedback.textContent = 'Please enter numbers only (0-9).';
        phoneInput.classList.add('is-invalid');
      } else if (filtered.length > 0 && filtered.length !== 10) {
        phoneFeedback.textContent = 'Phone must be exactly 10 digits.';
        phoneInput.classList.add('is-invalid');
      } else {
        phoneFeedback.textContent = '';
        phoneInput.classList.remove('is-invalid');
      }
    });

    function validateSignup() {
      let valid = true;
      let messages = [];

      inputs.forEach(i => i.classList.remove('is-invalid'));

      ['first_name','last_name','address','phone','gender','username','password'].forEach(name => {
        const input = signupForm[name];
        if (!input.value.trim()) {
          valid = false;
          // Only highlight if field has been blurred
        }
      });

      // Phone validation
      const phoneVal = phoneInput.value.trim();
      if (phoneVal && (!/^\d{10}$/.test(phoneVal))) {
        valid = false;
      }

      submitBtn.disabled = !valid;
      return valid;
    }

    // Show red only after blur
    inputs.forEach(i => {
      i.addEventListener('blur', () => {
        if (!i.value.trim()) {
          i.classList.add('is-invalid');
        } else {
          i.classList.remove('is-invalid');
        }
        // For phone, check length
        if (i.name === 'phone' && i.value.trim() && i.value.trim().length !== 10) {
          i.classList.add('is-invalid');
        }
      });
      i.addEventListener('input', () => {
        i.classList.remove('is-invalid');
        submitBtn.disabled = false;
        alertBox.classList.add('d-none');
      });
      i.addEventListener('change', validateSignup);
    });
    validateSignup();

    signupForm.addEventListener('submit', async (e) => {
      if (!validateSignup()) {
        e.preventDefault();
        // Show error only on submit
        let messages = [];
        ['first_name','last_name','address','phone','gender','username','password'].forEach(name => {
          const input = signupForm[name];
          let labelText = (input.labels && input.labels[0]) 
            ? input.labels[0].textContent 
            : (input.previousElementSibling && input.previousElementSibling.classList.contains('form-label'))
              ? input.previousElementSibling.textContent
              : input.name;
          if (!input.value.trim()) {
            messages.push(`${labelText} is required.`);
            input.classList.add('is-invalid');
          }
        });
        const phoneVal = phoneInput.value.trim();
        if (phoneVal && (!/^\d{10}$/.test(phoneVal))) {
          messages.push('Phone must be exactly 10 digits (0-9).');
          phoneInput.classList.add('is-invalid');
        }
        alertBox.classList.remove('d-none');
        alertBox.textContent = messages.join(' ');
        return false;
      }
      submitBtn.disabled = true;
      const form = new FormData(signupForm);
      const data = Object.fromEntries(form.entries());
      const res = await postJSON('/lazio_site/auth/signup.php', data);
      const alert = document.getElementById('signupAlert');
      if (res.ok) {
        try {
          const message = 'Account created successfully. You will be redirected to the login page in a few seconds.';
          if ('speechSynthesis' in window) {
            const utter = new SpeechSynthesisUtterance(message);
            utter.rate = 1;
            utter.pitch = 1;
            speechSynthesis.cancel();
            speechSynthesis.speak(utter);
          }
        } catch (err) {}
        alert.classList.remove('d-none');
        alert.classList.remove('alert-danger');
        alert.classList.add('alert-success');
        alert.textContent = 'Account created successfully. Redirecting to login...';
        setTimeout(() => {
          window.location.href = '/lazio_site/auth/login.php?msg=signup_success';
        }, 2500);
      } else {
        alert.classList.remove('d-none'); alert.textContent = res.message || 'Sign up failed';
      }
    });
  }

  // Client / Intern tabs on the sign-up page
  const roleTabs = document.getElementById('roleTabs');
  const internSignupForm = document.getElementById('internSignupForm');
  if (roleTabs && signupForm && internSignupForm) {
    const intro = document.getElementById('signupIntro');
    const alertBox = document.getElementById('signupAlert');
    const showRole = (role) => {
      roleTabs.querySelectorAll('[data-role]').forEach(b => b.classList.toggle('active', b.dataset.role === role));
      signupForm.classList.toggle('d-none', role !== 'client');
      internSignupForm.classList.toggle('d-none', role !== 'intern');
      if (intro) intro.textContent = intro.dataset[role];
      alertBox.classList.add('d-none');
    };
    roleTabs.querySelectorAll('[data-role]').forEach(b => b.addEventListener('click', () => showRole(b.dataset.role)));
    if (new URLSearchParams(window.location.search).get('role') === 'intern') showRole('intern');

    const phoneInput = internSignupForm.phone;
    phoneInput.addEventListener('input', () => {
      phoneInput.value = phoneInput.value.replace(/\D/g, '').slice(0, 10);
    });

    internSignupForm.addEventListener('submit', async (e) => {
      e.preventDefault();
      const submit = document.getElementById('internSignupSubmit');
      const f = internSignupForm;
      const fail = (msg) => { alertBox.classList.remove('d-none', 'alert-success'); alertBox.classList.add('alert-danger'); alertBox.textContent = msg; };
      alertBox.classList.add('d-none');

      const data = Object.fromEntries(new FormData(f).entries());
      for (const k of ['full_name', 'reg_number', 'institution', 'course']) data[k] = (data[k] || '').trim();
      if (!data.full_name || !data.reg_number || !data.institution || !data.course) return fail('Please fill in all the fields.');
      if (!/^\d{10}$/.test(data.phone || '')) return fail('Phone must be exactly 10 digits (0-9).');
      if ((data.password || '').length < 6) return fail('Password must be at least 6 characters.');
      if (data.password !== data.confirm_password) return fail('Passwords do not match.');

      submit.disabled = true;
      try {
        const res = await postJSON('/lazio_site/auth/signup.php', {...data, role: 'intern'});
        if (res.ok) {
          alertBox.classList.remove('d-none', 'alert-danger');
          alertBox.classList.add('alert-success');
          alertBox.textContent = 'You are registered. Taking you to your intern portal...';
          setTimeout(() => { window.location.href = res.redirect || '/lazio_site/intern/index.php'; }, 1200);
        } else {
          fail(res.message || 'Registration failed.');
          submit.disabled = false;
        }
      } catch (err) {
        fail('Network error. Please try again.');
        submit.disabled = false;
      }
    });
  }

  // Forgot Password toggle
  const forgotBtn = document.getElementById('forgotPasswordBtn');
  const resetForm = document.getElementById('resetForm');
  const backBtn = document.getElementById('backToLoginBtn');

  if (forgotBtn && resetForm && loginForm && backBtn) {
    forgotBtn.addEventListener('click', () => {
      console.log('Forgot Password button clicked');
      loginForm.classList.add('d-none');
      resetForm.classList.remove('d-none');
    });
    backBtn.addEventListener('click', () => {
      resetForm.classList.add('d-none');
      loginForm.classList.remove('d-none');
    });

    resetForm.addEventListener('submit', async (e) => {
      e.preventDefault();
      const username = resetForm.reset_username.value.trim();
      const password = resetForm.reset_password.value;
      const confirm = resetForm.reset_confirm.value;
      const alert = document.getElementById('loginAlert');
      if (!username || !password || !confirm) {
        alert.classList.remove('d-none');
        alert.textContent = 'All fields are required.';
        return;
      }
      if (password !== confirm) {
        alert.classList.remove('d-none');
        alert.textContent = 'Passwords do not match.';
        return;
      }
      const res = await postJSON('/lazio_site/auth/login.php', {
        action: 'reset_password',
        username,
        password,
        phone: resetForm.reset_phone ? resetForm.reset_phone.value.trim() : ''
      });
      if (res.ok) {
        alert.classList.remove('d-none');
        alert.classList.remove('alert-danger');
        alert.classList.add('alert-success');
        alert.textContent = 'Password updated successfully. You can now login.';
        setTimeout(() => {
          resetForm.classList.add('d-none');
          loginForm.classList.remove('d-none');
          alert.classList.add('d-none');
        }, 2000);
      } else {
        alert.classList.remove('d-none');
        alert.textContent = res.message || 'Password update failed.';
      }
    });
  }
});
