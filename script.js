

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

            // If client-side checks fail, stop here.
            if (!valid) {
                return;
            }

            // Send to the PHP backend, which validates again and saves to MySQL.
            const submitBtn = signupForm.querySelector('button[type="submit"]');
            const originalBtnText = submitBtn.textContent;
            submitBtn.disabled = true;
            submitBtn.textContent = 'Creating…';

            fetch('backend/signup.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ name: name, email: email, phone: phone, gender: gender })
            })
            .then(function(response) {
                return response.json().then(function(data) {
                    return { status: response.status, data: data };
                });
            })
            .then(function(result) {
                const data = result.data;

                // Server-side validation errors → show them per field.
                if (result.status === 400 && data.errors) {
                    Object.keys(data.errors).forEach(function(field) {
                        showError('signup-' + field, data.errors[field]);
                    });
                    return;
                }

                // Duplicate email or other handled error.
                if (!data.ok) {
                    showError('signup-email', data.message || 'Sign-up failed. Please try again.');
                    return;
                }

                // Success — account saved in the database.
                const successMsg = document.getElementById('signup-success');
                successMsg.innerHTML = '🌸 Welcome <strong>' + name + '</strong>! Your account has been created successfully. Check ' + email + ' for confirmation.';
                successMsg.classList.add('show');
                signupForm.reset();

                setTimeout(function() {
                    closeModal('signup-modal');
                    successMsg.classList.remove('show');
                }, 3000);
            })
            .catch(function(err) {
                console.error('Sign-up request failed:', err);
                showError('signup-email', 'Could not reach the server. Is the backend running?');
            })
            .finally(function() {
                submitBtn.disabled = false;
                submitBtn.textContent = originalBtnText;
            });
        });
    }
});

// ═══════════════════════════════════════════
//   SHOPPING CART & CHECKOUT
// ═══════════════════════════════════════════

var CART_KEY = 'bloom_cart';
var DELIVERY_FEE = 300;            // mirrors backend/order.php (display only)
var FREE_DELIVERY_THRESHOLD = 3000;

// Load / save the cart (array of {id, name, price, qty}) from localStorage.
function loadCart() {
    try {
        return JSON.parse(localStorage.getItem(CART_KEY)) || [];
    } catch (e) {
        return [];
    }
}

function saveCart(cart) {
    localStorage.setItem(CART_KEY, JSON.stringify(cart));
    updateCartCount();
}

function cartItemCount() {
    return loadCart().reduce(function(sum, item) { return sum + item.qty; }, 0);
}

function updateCartCount() {
    var badge = document.getElementById('cart-count');
    if (badge) badge.textContent = cartItemCount();
}

function formatKES(amount) {
    return 'KES ' + Number(amount).toLocaleString('en-KE');
}

// Add a product to the cart (or bump its quantity).
function addToCart(id, name, price) {
    var cart = loadCart();
    var existing = cart.find(function(item) { return item.id === id; });
    if (existing) {
        existing.qty += 1;
    } else {
        cart.push({ id: id, name: name, price: price, qty: 1 });
    }
    saveCart(cart);
}

function setQty(id, qty) {
    var cart = loadCart();
    var item = cart.find(function(i) { return i.id === id; });
    if (!item) return;
    item.qty = qty;
    if (item.qty <= 0) {
        cart = cart.filter(function(i) { return i.id !== id; });
    }
    saveCart(cart);
    renderCart();
}

function removeFromCart(id) {
    saveCart(loadCart().filter(function(i) { return i.id !== id; }));
    renderCart();
}

// Build the cart modal contents and totals (display estimate; the server
// recomputes the authoritative total on checkout).
function renderCart() {
    var cart = loadCart();
    var itemsBox = document.getElementById('cart-items');
    var emptyBox = document.getElementById('cart-empty');
    var summary  = document.getElementById('cart-summary');

    if (cart.length === 0) {
        itemsBox.innerHTML = '';
        emptyBox.style.display = 'block';
        summary.style.display = 'none';
        return;
    }

    emptyBox.style.display = 'none';
    summary.style.display = 'block';

    var html = '';
    var subtotal = 0;
    cart.forEach(function(item) {
        var lineTotal = item.price * item.qty;
        subtotal += lineTotal;
        html +=
            '<div class="cart-item">' +
                '<div class="cart-item-info">' +
                    '<strong>' + item.name + '</strong>' +
                    '<span>' + formatKES(item.price) + ' each</span>' +
                '</div>' +
                '<div class="cart-item-qty">' +
                    '<button onclick="setQty(' + item.id + ',' + (item.qty - 1) + ')">−</button>' +
                    '<span>' + item.qty + '</span>' +
                    '<button onclick="setQty(' + item.id + ',' + (item.qty + 1) + ')">+</button>' +
                '</div>' +
                '<div class="cart-item-total">' + formatKES(lineTotal) + '</div>' +
                '<button class="cart-item-remove" onclick="removeFromCart(' + item.id + ')" title="Remove">🗑</button>' +
            '</div>';
    });
    itemsBox.innerHTML = html;

    var delivery = subtotal >= FREE_DELIVERY_THRESHOLD ? 0 : DELIVERY_FEE;
    document.getElementById('cart-subtotal').textContent = formatKES(subtotal);
    document.getElementById('cart-delivery').textContent = delivery === 0 ? 'FREE' : formatKES(delivery);
    document.getElementById('cart-total').textContent = formatKES(subtotal + delivery);
}

function openCart() {
    renderCart();
    openModal('cart-modal');
}

// Move from cart → checkout.
function goToCheckout() {
    if (loadCart().length === 0) return;
    closeModal('cart-modal');
    openModal('checkout-modal');
}

// Wire up the "Add 🛒" buttons and the checkout form once the page loads.
document.addEventListener('DOMContentLoaded', function() {
    updateCartCount();

    document.querySelectorAll('.add-cart').forEach(function(btn) {
        btn.addEventListener('click', function() {
            var id = parseInt(btn.getAttribute('data-id'), 10);
            var name = btn.getAttribute('data-name');
            var price = parseFloat(btn.getAttribute('data-price'));
            addToCart(id, name, price);

            // Quick visual confirmation.
            var original = btn.textContent;
            btn.textContent = 'Added ✓';
            btn.disabled = true;
            setTimeout(function() {
                btn.textContent = original;
                btn.disabled = false;
            }, 900);
        });
    });

    var checkoutForm = document.getElementById('checkout-form');
    if (checkoutForm) {
        checkoutForm.addEventListener('submit', function(e) {
            e.preventDefault();

            var name    = document.getElementById('checkout-name').value.trim();
            var email   = document.getElementById('checkout-email').value.trim();
            var phone   = document.getElementById('checkout-phone').value.trim();
            var address = document.getElementById('checkout-address').value.trim();
            var time    = document.getElementById('checkout-time').value;
            var promo   = document.getElementById('cart-promo-code').value.trim();

            // Client-side checks (server validates again).
            var valid = true;
            if (!isValidName(name))   { showError('checkout-name', 'Please enter a valid name.'); valid = false; } else { clearError('checkout-name'); }
            if (!isValidEmail(email)) { showError('checkout-email', 'Please enter a valid email.'); valid = false; } else { clearError('checkout-email'); }
            if (!isValidPhone(phone)) { showError('checkout-phone', 'Please enter a valid phone number.'); valid = false; } else { clearError('checkout-phone'); }
            if (address.length < 6)   { showError('checkout-address', 'Please enter your delivery address.'); valid = false; } else { clearError('checkout-address'); }
            if (!valid) return;

            var cart = loadCart();
            if (cart.length === 0) return;

            var orderError = document.getElementById('checkout-order-error');
            orderError.style.display = 'none';

            var submitBtn = checkoutForm.querySelector('button[type="submit"]');
            var originalBtnText = submitBtn.textContent;
            submitBtn.disabled = true;
            submitBtn.textContent = 'Placing order…';

            fetch('backend/order.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    customer: { name: name, email: email, phone: phone, address: address, delivery_time: time },
                    items: cart.map(function(i) { return { id: i.id, quantity: i.qty }; }),
                    promo_code: promo
                })
            })
            .then(function(response) {
                return response.json().then(function(data) { return { status: response.status, data: data }; });
            })
            .then(function(result) {
                var data = result.data;

                if (result.status === 400 && data.errors) {
                    Object.keys(data.errors).forEach(function(field) {
                        if (field === 'promo_code' || field === 'items') {
                            orderError.textContent = data.errors[field];
                            orderError.style.display = 'block';
                        } else {
                            showError('checkout-' + field, data.errors[field]);
                        }
                    });
                    return;
                }

                if (!data.ok) {
                    orderError.textContent = data.message || 'Could not place order. Please try again.';
                    orderError.style.display = 'block';
                    return;
                }

                // Success — order saved. Show confirmation and clear the cart.
                var successMsg = document.getElementById('checkout-success');
                successMsg.innerHTML =
                    '🌸 Thank you, <strong>' + name + '</strong>! Order <strong>#' + data.order_id + '</strong> placed.<br>' +
                    'Total: <strong>' + formatKES(data.total) + '</strong>' +
                    (data.discount > 0 ? ' (incl. ' + formatKES(data.discount) + ' discount)' : '') +
                    '. We\'ll deliver to your address soon!';
                successMsg.classList.add('show');

                saveCart([]);
                checkoutForm.reset();
                document.getElementById('cart-promo-code').value = '';

                setTimeout(function() {
                    closeModal('checkout-modal');
                    successMsg.classList.remove('show');
                }, 5000);
            })
            .catch(function(err) {
                console.error('Order request failed:', err);
                orderError.textContent = 'Could not reach the server. Is the backend running?';
                orderError.style.display = 'block';
            })
            .finally(function() {
                submitBtn.disabled = false;
                submitBtn.textContent = originalBtnText;
            });
        });
    }
});
