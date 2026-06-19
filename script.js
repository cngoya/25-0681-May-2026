

// ─── Show / hide modals ───
function openModal(id) {
    document.getElementById(id).classList.add('active');
}

function closeModal(id) {
    document.getElementById(id).classList.remove('active');
}

// ─── Promo chime (gentle two-note sound, generated in-browser) ───
function playPromoSound() {
    try {
        const AudioCtx = window.AudioContext || window.webkitAudioContext;
        if (!AudioCtx) return;
        const ctx = new AudioCtx();

        // Browsers suspend audio until a user gesture; try to resume.
        if (ctx.state === 'suspended') ctx.resume();

        // Two soft notes: a pleasant "ding-dong" chime.
        const notes = [
            { freq: 880, start: 0,    duration: 0.18 }, // A5
            { freq: 1175, start: 0.16, duration: 0.30 } // D6
        ];

        notes.forEach(function(note) {
            const osc  = ctx.createOscillator();
            const gain = ctx.createGain();
            osc.type = 'sine';
            osc.frequency.value = note.freq;

            const t = ctx.currentTime + note.start;
            // Quick fade-in then gentle fade-out so it doesn't click.
            gain.gain.setValueAtTime(0.0001, t);
            gain.gain.exponentialRampToValueAtTime(0.25, t + 0.02);
            gain.gain.exponentialRampToValueAtTime(0.0001, t + note.duration);

            osc.connect(gain);
            gain.connect(ctx.destination);
            osc.start(t);
            osc.stop(t + note.duration + 0.02);
        });
    } catch (err) {
        // Audio not supported / blocked — fail silently, popup still shows.
        console.warn('Promo sound could not play:', err);
    }
}

// ─── Show the promo popup with a chime ───
function showPromo() {
    openModal('promo-modal');
    playPromoSound();
}

// ─── Welcome popup (auto-shows on page load) ───
window.addEventListener('load', function() {
    // Show welcome popup after 1.5 seconds
    setTimeout(function() {
        openModal('welcome-modal');
    }, 1500);

    // Show promo popup after 8 seconds
    setTimeout(function() {
        // Only show if welcome is already closed
        if (!document.getElementById('welcome-modal').classList.contains('active')) {
            showPromo();
        } else {
            // Wait for welcome to close, then show promo
            setTimeout(function() {
                showPromo();
            }, 3000);
        }
    }, 8000);
});

// ─── Close modal when clicking on the dark overlay ───
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.modal-overlay').forEach(function(modal) {
        modal.addEventListener('click', function(e) {
            if (e.target === modal) {
                modal.classList.remove('active');
            }
        });
    });
});

// ─── Close modal with Escape key ───
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        document.querySelectorAll('.modal-overlay.active').forEach(function(modal) {
            modal.classList.remove('active');
        });
    }
});

// ═══════════════════════════════════════════
//   SIGN-UP FORM VALIDATION
// ═══════════════════════════════════════════

// Validate email like example@gmail.com
function isValidEmail(email) {
    // Standard email regex: text@text.text
    const emailPattern = /^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/;
    return emailPattern.test(email);
}

// Validate name (letters and spaces only, at least 2 characters)
function isValidName(name) {
    const namePattern = /^[a-zA-Z\s]{2,}$/;
    return namePattern.test(name.trim());
}

// Validate phone (digits only, 10-13 characters allowing +)
function isValidPhone(phone) {
    const phonePattern = /^\+?[0-9]{10,13}$/;
    return phonePattern.test(phone.replace(/\s/g, ''));
}

// Show / hide error message for a field
function showError(fieldId, message) {
    const field = document.getElementById(fieldId);
    const errorEl = document.getElementById(fieldId + '-error');
    field.classList.add('invalid');
    errorEl.textContent = message;
    errorEl.classList.add('show');
}

function clearError(fieldId) {
    const field = document.getElementById(fieldId);
    const errorEl = document.getElementById(fieldId + '-error');
    field.classList.remove('invalid');
    errorEl.classList.remove('show');
}

// Live validation as user types
document.addEventListener('DOMContentLoaded', function() {

    // Email live validation
    const emailInput = document.getElementById('signup-email');
    if (emailInput) {
        emailInput.addEventListener('blur', function() {
            const email = emailInput.value.trim();
            if (email === '') {
                clearError('signup-email');
            } else if (!isValidEmail(email)) {
                showError('signup-email', 'Please enter a valid email (e.g. cleon@gmail.com)');
            } else {
                clearError('signup-email');
            }
        });
    }

    // Form submission
    const signupForm = document.getElementById('signup-form');
    if (signupForm) {
        signupForm.addEventListener('submit', function(e) {
            e.preventDefault();

            const name   = document.getElementById('signup-name').value.trim();
            const email  = document.getElementById('signup-email').value.trim();
            const phone  = document.getElementById('signup-phone').value.trim();
            const gender = document.getElementById('signup-gender').value;

            let valid = true;

            // Validate Name
            if (!isValidName(name)) {
                showError('signup-name', 'Please enter a valid name (letters only, at least 2 characters)');
                valid = false;
            } else {
                clearError('signup-name');
            }

            // Validate Email (e.g. cleon@gmail.com)
            if (!isValidEmail(email)) {
                showError('signup-email', 'Please enter a valid email (e.g. cleon@gmail.com)');
                valid = false;
            } else {
                clearError('signup-email');
            }

            // Validate Phone
            if (!isValidPhone(phone)) {
                showError('signup-phone', 'Please enter a valid phone number (10-13 digits)');
                valid = false;
            } else {
                clearError('signup-phone');
            }

            // Validate Gender
            if (gender === '') {
                showError('signup-gender', 'Please select your gender');
                valid = false;
            } else {
                clearError('signup-gender');
            }

            // If everything is valid, show success
            if (valid) {
                const successMsg = document.getElementById('signup-success');
                successMsg.innerHTML = '🌸 Welcome <strong>' + name + '</strong>! Your account has been created successfully. Check ' + email + ' for confirmation.';
                successMsg.classList.add('show');

                // Reset form
                signupForm.reset();

                // Close modal after 3 seconds
                setTimeout(function() {
                    closeModal('signup-modal');
                    successMsg.classList.remove('show');
                }, 3000);
            }
        });
    }
});
