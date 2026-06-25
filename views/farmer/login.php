<div class="glass-card w-full max-w-md p-8 rounded-xl shadow-lg m-4">
    <div class="text-center mb-8">
        <h1 class="text-3xl font-bold text-primary mb-2">Agri-Advisory</h1>
        <p class="text-on-surface-variant">Farmer Web Portal</p>
    </div>

    <form id="otpForm" class="space-y-6">
        <div id="phoneStep">
            <label class="block text-sm font-medium text-on-surface-variant mb-2">Phone Number</label>
            <input type="tel" id="phone" name="phone" class="w-full bg-surface-container-low border border-outline rounded-lg px-4 py-3 focus:ring-2 focus:ring-primary focus:border-primary" placeholder="07XX XXX XXX" required />
            <button type="button" id="btnRequestOtp" class="mt-4 w-full bg-primary text-white py-3 rounded-xl font-bold hover:bg-primary-container transition-colors">Request OTP</button>
        </div>

        <div id="tokenStep" style="display:none;">
            <label class="block text-sm font-medium text-on-surface-variant mb-2">Enter 6-Digit OTP</label>
            <input type="text" id="token" name="token" class="w-full bg-surface-container-low border border-outline rounded-lg px-4 py-3 text-center text-2xl tracking-widest focus:ring-2 focus:ring-primary focus:border-primary" maxlength="6" inputmode="numeric" />
            <button type="button" id="btnVerifyOtp" class="mt-4 w-full bg-primary text-white py-3 rounded-xl font-bold hover:bg-primary-container transition-colors">Login</button>
        </div>

        <p id="msg" class="text-center text-sm font-medium mt-4 text-primary hidden" aria-hidden="true"></p>
    </form>

    <div class="mt-6 text-center text-xs text-outline">
        <p>An Agri-Advisory Initiative. <br><a href="/login" class="text-primary hover:underline">Staff Login</a></p>
    </div>
</div>

<script>
    const btnRequest = document.getElementById('btnRequestOtp');
    const btnVerify = document.getElementById('btnVerifyOtp');
    const phoneInput = document.getElementById('phone');
    const tokenInput = document.getElementById('token');

    function notify(msg, type) {
        if (typeof window.showToast === 'function') {
            window.showToast(msg, type || 'error');
        }
    }

    btnRequest.addEventListener('click', async () => {
        if (!phoneInput.value) return;

        btnRequest.disabled = true;
        btnRequest.innerText = 'Sending...';

        const formData = new FormData();
        formData.append('phone', phoneInput.value);

        try {
            const res = await fetch('/api/auth/request-otp', { method: 'POST', body: formData });
            const data = await res.json();

            if (data.ok) {
                notify(data.msg, 'success');
                document.getElementById('phoneStep').style.display = 'none';
                document.getElementById('tokenStep').style.display = 'block';
            } else {
                notify(data.msg || 'Request failed.', 'error');
                btnRequest.disabled = false;
                btnRequest.innerText = 'Request OTP';
            }
        } catch (e) {
            notify('Network error. Try again.', 'error');
            btnRequest.disabled = false;
            btnRequest.innerText = 'Request OTP';
        }
    });

    btnVerify.addEventListener('click', async () => {
        if (!tokenInput.value) return;

        btnVerify.disabled = true;
        btnVerify.innerText = 'Verifying...';

        const formData = new FormData();
        formData.append('phone', phoneInput.value);
        formData.append('token', tokenInput.value);

        try {
            const res = await fetch('/api/auth/verify-otp', { method: 'POST', body: formData });
            const data = await res.json();

            if (data.ok) {
                window.location.href = data.redirect;
            } else {
                notify(data.msg || 'Invalid or expired OTP. Please try again.', 'error');
                btnVerify.disabled = false;
                btnVerify.innerText = 'Login';
            }
        } catch (e) {
            notify('Network error. Try again.', 'error');
            btnVerify.disabled = false;
            btnVerify.innerText = 'Login';
        }
    });
</script>
