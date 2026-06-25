<div class="glass-card w-full max-w-md p-8 rounded-xl shadow-lg m-4">
    <div class="text-center mb-8">
        <h1 class="text-3xl font-bold text-primary mb-2">Agri-Advisory</h1>
        <p class="text-on-surface-variant">Staff Login</p>
    </div>

    <form action="/login" method="POST" class="space-y-6">
        <div>
            <label class="block text-sm font-bold text-on-surface-variant mb-2">Barua Pepe (Email)</label>
            <input type="email" name="email" class="w-full bg-surface-container-low border border-outline rounded-lg px-4 py-3 focus:ring-2 focus:ring-primary focus:border-primary" placeholder="officer@agriadvisory.go.tz" required />
        </div>

        <div>
            <label class="block text-sm font-medium text-on-surface-variant mb-2">Password</label>
            <input type="password" name="password" class="w-full bg-surface-container-low border border-outline rounded-lg px-4 py-3 focus:ring-2 focus:ring-primary focus:border-primary" required />
        </div>
        
        <?php if (isset($error)): ?>
            <script>document.addEventListener('DOMContentLoaded',function(){showToast(<?php echo json_encode($error); ?>,'error');});</script>
        <?php elseif (isset($_GET['error'])): ?>
            <script>document.addEventListener('DOMContentLoaded',function(){showToast('Invalid credentials.','error');});</script>
        <?php endif; ?>

        <button type="submit" class="mt-4 w-full bg-primary text-white py-3 rounded-xl font-bold hover:bg-primary-container transition-colors">Secure Login</button>
    </form>
    
    <div class="mt-6 text-center text-xs text-outline">
        <p><a href="/farmer/login" class="text-primary hover:underline">Farmer Login</a></p>
    </div>
</div>
