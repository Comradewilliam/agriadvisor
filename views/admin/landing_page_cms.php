<div class="mb-6">
    <h1 class="text-2xl font-bold text-on-surface">Landing Page Content Management</h1>
    <p class="text-on-surface-variant">Update the text and images displayed on the public landing page.</p>
</div>

<?php if (isset($_GET['success'])): ?>
    <div class="bg-green-100 text-green-800 p-4 rounded-xl mb-6 flex items-center gap-2">
        <span class="material-symbols-outlined">check_circle</span>
        Content updated successfully!
    </div>
<?php endif; ?>

<?php if (isset($_GET['error'])): ?>
    <div class="bg-red-100 text-red-800 p-4 rounded-xl mb-6 flex items-center gap-2">
        <span class="material-symbols-outlined">error</span>
        Failed to update content. Ensure the file is a valid image.
    </div>
<?php endif; ?>

<div class="grid grid-cols-1 gap-8">
    <?php 
    $sections = [
        'hero' => 'Hero Section',
        'features' => 'Features Section',
        'impact' => 'Impact Section',
        'cta' => 'Call to Action Section',
        'footer' => 'Footer & Navigation',
        'farmer_library' => 'Farmer Library (Maktaba ya Kilimo)',
    ];
    
    foreach ($sections as $sectionKey => $sectionTitle): 
    ?>
    <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100">
        <h2 class="text-xl font-bold text-on-surface mb-4 border-b pb-2"><?= $sectionTitle ?></h2>
        
        <div class="space-y-6">
            <?php foreach ($cms as $key => $item): 
                if ($item['section'] !== $sectionKey) continue;
            ?>
            
                <?php if ($item['content_type'] === 'text' || $item['content_type'] === 'html'): ?>
                    <form action="/admin/landing-page/update" method="POST" class="flex flex-col md:flex-row gap-4 items-start md:items-end">
                        <input type="hidden" name="key_name" value="<?= htmlspecialchars($item['key_name']) ?>">
                        <div class="flex-1 w-full">
                            <label class="block text-sm font-bold text-gray-700 mb-1"><?= ucwords(str_replace('_', ' ', $item['key_name'])) ?></label>
                            <?php if ($item['content_type'] === 'html' || strlen($item['content_value']) > 60): ?>
                                <textarea name="content_value" rows="4" class="w-full border border-gray-300 rounded-xl px-4 py-2 focus:ring-2 focus:ring-primary focus:outline-none font-mono text-sm"><?= htmlspecialchars($item['content_value']) ?></textarea>
                            <?php else: ?>
                                <input type="text" name="content_value" value="<?= htmlspecialchars($item['content_value']) ?>" class="w-full border border-gray-300 rounded-xl px-4 py-2 focus:ring-2 focus:ring-primary focus:outline-none">
                            <?php endif; ?>
                        </div>
                        <button type="submit" class="bg-primary text-white px-6 py-2 rounded-xl hover:bg-primary-container font-bold transition-colors whitespace-nowrap">Save</button>
                    </form>
                
                <?php elseif ($item['content_type'] === 'image'): ?>
                    <form action="/admin/landing-page/upload" method="POST" enctype="multipart/form-data" class="flex flex-col md:flex-row gap-4 items-start md:items-end p-4 bg-gray-50 rounded-xl border border-dashed border-gray-300">
                        <input type="hidden" name="key_name" value="<?= htmlspecialchars($item['key_name']) ?>">
                        <div class="flex-1 w-full">
                            <label class="block text-sm font-bold text-gray-700 mb-1"><?= ucwords(str_replace('_', ' ', $item['key_name'])) ?></label>
                            <div class="flex items-center gap-4">
                                <img src="<?= htmlspecialchars($item['content_value']) ?>" alt="Current" class="w-16 h-16 object-cover rounded-lg border">
                                <input type="file" name="image" accept="image/*" class="w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-primary-fixed file:text-primary hover:file:bg-primary hover:file:text-white transition-colors" required>
                            </div>
                        </div>
                        <button type="submit" class="bg-tertiary text-white px-6 py-2 rounded-xl hover:bg-tertiary/90 font-bold transition-colors whitespace-nowrap">Upload Image</button>
                    </form>
                <?php endif; ?>
                
            <?php endforeach; ?>
        </div>
    </div>
    <?php endforeach; ?>
</div>
