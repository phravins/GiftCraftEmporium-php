// Small progressive enhancements. Every page still works without JavaScript.
document.addEventListener('DOMContentLoaded', () => {
    const $$ = (selector, root = document) => Array.from(root.querySelectorAll(selector));

    // Show/hide a panel: <button data-toggle="#mobile-menu">
    $$('[data-toggle]').forEach((button) => {
        const target = document.querySelector(button.dataset.toggle);
        if (!target) return;
        button.addEventListener('click', () => {
            const hidden = target.classList.toggle('hidden');
            button.setAttribute('aria-expanded', String(!hidden));
        });
    });

    // Close dropdown menus when clicking elsewhere or pressing Escape.
    const dropdowns = $$('details[data-dropdown]');
    document.addEventListener('click', (event) => {
        dropdowns.forEach((menu) => {
            if (menu.open && !menu.contains(event.target)) menu.open = false;
        });
    });
    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') dropdowns.forEach((menu) => { menu.open = false; });
    });

    // Filter panels start collapsed on small screens.
    if (window.innerWidth < 1024) {
        $$('details[data-open-desktop]').forEach((panel) => panel.removeAttribute('open'));
    }

    // Dismissible flash messages.
    $$('[data-dismiss]').forEach((button) => {
        button.addEventListener('click', () => button.closest('[data-flash]')?.remove());
    });

    // Submit a form as soon as a select changes (sort order, cart quantity).
    $$('[data-autosubmit]').forEach((field) => {
        field.addEventListener('change', () => field.form?.submit());
    });

    // Ask before destructive actions, and stop accidental double submits.
    $$('form').forEach((form) => {
        form.addEventListener('submit', (event) => {
            if (form.dataset.confirm && !window.confirm(form.dataset.confirm)) {
                event.preventDefault();
                return;
            }
            const buttons = $$('button[type="submit"], button:not([type])', form);
            window.setTimeout(() => buttons.forEach((button) => { button.disabled = true; }), 0);
        });
    });

    // Quantity steppers.
    $$('[data-stepper]').forEach((stepper) => {
        const input = stepper.querySelector('input');
        $$('[data-step]', stepper).forEach((button) => {
            button.addEventListener('click', () => {
                const min = Number(input.min || 1);
                const max = Number(input.max || 99);
                const next = Number(input.value || min) + Number(button.dataset.step);
                input.value = String(Math.min(max, Math.max(min, next)));
            });
        });
    });

    // Product image gallery.
    const mainImage = document.querySelector('[data-gallery-main]');
    $$('[data-gallery-thumb]').forEach((thumb, _, thumbs) => {
        thumb.addEventListener('click', () => {
            if (mainImage) mainImage.src = thumb.dataset.src;
            thumbs.forEach((other) => {
                other.classList.toggle('ring-brand-600', other === thumb);
                other.classList.toggle('ring-transparent', other !== thumb);
            });
        });
    });

    // Registration: the shop name is only needed for sellers.
    const sellerOnly = document.querySelector('[data-seller-only]');
    $$('[data-role-choice]').forEach((radio) => {
        radio.addEventListener('change', () => {
            if (!sellerOnly) return;
            const isSeller = radio.value === 'seller' && radio.checked;
            sellerOnly.classList.toggle('hidden', !isSeller);
            sellerOnly.querySelector('input').required = isSeller;
        });
    });

    // Checkout: show the new-address form only when it is selected.
    const newAddress = document.querySelector('[data-new-address]');
    $$('[data-address-choice]').forEach((radio) => {
        radio.addEventListener('change', () => {
            if (!newAddress) return;
            const useNew = radio.value === 'new' && radio.checked;
            newAddress.classList.toggle('hidden', !useNew);
            newAddress.disabled = !useNew;
        });
    });

    // Preview images before uploading.
    $$('[data-image-input]').forEach((input) => {
        const preview = document.querySelector(input.dataset.imageInput);
        input.addEventListener('change', () => {
            if (!preview) return;
            preview.innerHTML = '';
            Array.from(input.files).forEach((file) => {
                if (!file.type.startsWith('image/')) return;
                const img = document.createElement('img');
                img.src = URL.createObjectURL(file);
                img.alt = file.name;
                img.className = 'aspect-square w-full rounded-xl object-cover ring-1 ring-stone-200';
                preview.appendChild(img);
            });
        });
    });

    // Clickable table rows.
    $$('tr[data-href]').forEach((row) => {
        row.addEventListener('click', (event) => {
            if (!event.target.closest('a, button, input, select, textarea')) window.location = row.dataset.href;
        });
    });

    // Seller Hub sidebar on small screens.
    const sidebar = document.getElementById('seller-sidebar');
    const backdrop = document.querySelector('[data-sidebar-backdrop]');
    const setSidebar = (open) => {
        sidebar?.classList.toggle('-translate-x-full', !open);
        backdrop?.classList.toggle('hidden', !open);
    };
    document.querySelector('[data-sidebar-open]')?.addEventListener('click', () => setSidebar(true));
    backdrop?.addEventListener('click', () => setSidebar(false));
});
